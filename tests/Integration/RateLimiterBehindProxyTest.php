<?php

namespace Tests\Integration;

use App\Middleware\RateLimiterMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Tests\TestCase;

/**
 * Atrás do tailscale serve/Docker todos chegam com o mesmo REMOTE_ADDR (o proxy). Sem considerar o
 * X-Forwarded-For de um proxy confiável, 5 logins de qualquer pessoa bloqueavam o login de todo mundo.
 */
class RateLimiterBehindProxyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->db->exec('DELETE FROM rate_limits'); // o banco de teste é compartilhado entre os testes da classe
    }

    protected function tearDown(): void
    {
        putenv('TRUSTED_PROXIES');
        parent::tearDown();
    }

    private function login(RateLimiterMiddleware $limiter, string $clientIp): int
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/auth/login', ['REMOTE_ADDR' => '172.18.0.1'])
            ->withHeader('X-Forwarded-For', $clientIp);
        $ok = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };
        return $limiter($request, $ok)->getStatusCode();
    }

    public function test_one_client_hitting_the_limit_does_not_block_others_behind_the_same_proxy(): void
    {
        putenv('TRUSTED_PROXIES=172.16.0.0/12');
        $limiter = new RateLimiterMiddleware($this->db, 5, 10);

        for ($i = 0; $i < 5; $i++) {
            $this->assertSame(200, $this->login($limiter, '100.64.0.10'));
        }
        $this->assertSame(429, $this->login($limiter, '100.64.0.10'));

        $this->assertSame(200, $this->login($limiter, '100.64.0.20'));
    }

    public function test_without_trusted_proxies_the_proxy_ip_is_the_key(): void
    {
        $limiter = new RateLimiterMiddleware($this->db, 2, 10);

        $this->assertSame(200, $this->login($limiter, '100.64.0.10'));
        $this->assertSame(200, $this->login($limiter, '100.64.0.20'));
        $this->assertSame(429, $this->login($limiter, '100.64.0.30'));
    }
}
