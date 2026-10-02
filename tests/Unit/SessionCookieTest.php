<?php

namespace Tests\Unit;

use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;
use App\Models\User;
use App\Security\SessionCookie;
use App\Security\TokenService;
use App\Services\AuthService;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

class SessionCookieTest extends TestCase
{
    private TokenService $tokens;

    protected function setUp(): void
    {
        putenv('COOKIE_SECURE');
        putenv('COOKIE_SAMESITE');
        $this->tokens = new TokenService(str_repeat('k', 48));
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    private function request(string $method, string $uri = 'http://localhost/api/accounts'): ServerRequestInterface
    {
        return (new ServerRequestFactory())->createServerRequest($method, $uri);
    }

    /** Handler que devolve 200 e o userId recebido. */
    private function handler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface {
                $r = new Response();
                $r->getBody()->write((string) $request->getAttribute('userId'));
                return $r;
            }
        };
    }

    public function test_login_puts_the_token_only_in_an_httponly_cookie(): void
    {
        $auth = Mockery::mock(AuthService::class);
        $auth->shouldReceive('login')->andReturn(new User('Ana', 'R', 'ana@x.com', 'h', 'BRL', 42, 'CODE'));
        $controller = new AuthController($auth, $this->tokens);

        $request = $this->request('POST', 'https://app.example.com/api/auth/login')->withParsedBody(['email' => 'ana@x.com', 'password' => 'Senha@123']);
        $response = $controller->login($request, new Response());

        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayNotHasKey('token', $body, 'O JWT não pode chegar ao JavaScript');

        [$session, $csrf] = $response->getHeader('Set-Cookie');
        $this->assertStringStartsWith('sf_session=', $session);
        foreach (['HttpOnly', 'Secure', 'SameSite=Strict', 'Path=/api'] as $flag) {
            $this->assertStringContainsString($flag, $session);
        }
        $this->assertStringStartsWith('sf_csrf=', $csrf);
        $this->assertStringNotContainsString('HttpOnly', $csrf, 'O JS precisa ler o token CSRF para mandar no header');
    }

    public function test_secure_flag_follows_https_unless_forced(): void
    {
        $plain = SessionCookie::attach(new Response(), $this->request('POST'), 'jwt', 60)->getHeaderLine('Set-Cookie');
        $this->assertStringNotContainsString('Secure', $plain, 'Em HTTP o navegador recusaria um cookie Secure');

        $proxied = SessionCookie::attach(new Response(), $this->request('POST')->withHeader('X-Forwarded-Proto', 'https'), 'jwt', 60)->getHeaderLine('Set-Cookie');
        $this->assertStringContainsString('Secure', $proxied);

        putenv('COOKIE_SECURE=true');
        $this->assertStringContainsString('Secure', SessionCookie::attach(new Response(), $this->request('POST'), 'jwt', 60)->getHeaderLine('Set-Cookie'));
    }

    public function test_cookie_session_reads_freely_but_writes_need_matching_csrf_header(): void
    {
        $mw = new AuthMiddleware($this->tokens);
        $jwt = $this->tokens->issue(7, 'a@x.com');
        $cookies = [SessionCookie::SESSION => $jwt, SessionCookie::CSRF => 'abc123'];

        $get = $mw($this->request('GET')->withCookieParams($cookies), $this->handler());
        $this->assertSame(200, $get->getStatusCode());
        $this->assertSame('7', (string) $get->getBody());

        $this->assertSame(403, $mw($this->request('POST')->withCookieParams($cookies), $this->handler())->getStatusCode(), 'Sem header = CSRF');
        $this->assertSame(403, $mw($this->request('DELETE')->withCookieParams($cookies)->withHeader('X-CSRF-Token', 'outro'), $this->handler())->getStatusCode());
        $this->assertSame(200, $mw($this->request('POST')->withCookieParams($cookies)->withHeader('X-CSRF-Token', 'abc123'), $this->handler())->getStatusCode());
    }

    public function test_bearer_clients_still_work_and_bad_tokens_are_rejected(): void
    {
        $mw = new AuthMiddleware($this->tokens);
        $jwt = $this->tokens->issue(9, 'b@x.com');

        $this->assertSame(200, $mw($this->request('POST')->withHeader('Authorization', "Bearer {$jwt}"), $this->handler())->getStatusCode(), 'Bearer não é enviado sozinho pelo navegador: sem CSRF');
        $this->assertSame(401, $mw($this->request('GET'), $this->handler())->getStatusCode());
        $this->assertSame(401, $mw($this->request('GET')->withCookieParams([SessionCookie::SESSION => 'forjado']), $this->handler())->getStatusCode());
    }

    public function test_logout_expires_both_cookies(): void
    {
        $controller = new AuthController(Mockery::mock(AuthService::class), $this->tokens);
        $cookies = $controller->logout($this->request('POST'), new Response())->getHeader('Set-Cookie');
        $this->assertCount(2, $cookies);
        foreach ($cookies as $c) {
            $this->assertStringContainsString('Max-Age=0', $c);
        }
    }
}
