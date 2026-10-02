<?php

namespace App\Security;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Sessão em cookie (OWASP ASVS V3): o JWT fica num cookie HttpOnly, fora do alcance de JavaScript,
 * então um XSS não consegue roubar a sessão.
 *
 * - sf_session: JWT, HttpOnly, SameSite, Path=/api, Secure quando a requisição é HTTPS.
 * - sf_csrf: token aleatório legível pelo JS (não HttpOnly). Requisições de escrita autenticadas pelo cookie
 *   precisam repetir esse valor no header X-CSRF-Token (double-submit cookie). Outro site não consegue ler
 *   o cookie, então não consegue montar o header.
 *
 * Variáveis: COOKIE_SECURE=auto|true|false (padrão auto) e COOKIE_SAMESITE=Strict|Lax|None (padrão Strict;
 * None só faz sentido com o frontend em outro domínio e exige HTTPS).
 */
final class SessionCookie {
    public const SESSION = 'sf_session';
    public const CSRF = 'sf_csrf';
    public const CSRF_HEADER = 'X-CSRF-Token';

    public static function attach(Response $response, Request $request, string $jwt, int $ttlSeconds): Response {
        $csrf = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        return $response
            ->withAddedHeader('Set-Cookie', self::build(self::SESSION, $jwt, $ttlSeconds, '/api', true, $request))
            ->withAddedHeader('Set-Cookie', self::build(self::CSRF, $csrf, $ttlSeconds, '/', false, $request));
    }

    public static function clear(Response $response, Request $request): Response {
        return $response
            ->withAddedHeader('Set-Cookie', self::build(self::SESSION, '', 0, '/api', true, $request))
            ->withAddedHeader('Set-Cookie', self::build(self::CSRF, '', 0, '/', false, $request));
    }

    /** Token da sessão: cookie primeiro; o header Bearer continua aceito para clientes de API. */
    public static function token(Request $request): array {
        $cookie = $request->getCookieParams()[self::SESSION] ?? '';
        if (is_string($cookie) && $cookie !== '') return [$cookie, 'cookie'];

        if (preg_match('/^Bearer\s+(\S+)$/i', $request->getHeaderLine('Authorization'), $m)) return [$m[1], 'bearer'];
        return [null, null];
    }

    /** Escrita autenticada por cookie exige o header CSRF igual ao cookie. */
    public static function csrfValid(Request $request): bool {
        if (in_array(strtoupper($request->getMethod()), ['GET', 'HEAD', 'OPTIONS'], true)) return true;
        $cookie = (string) ($request->getCookieParams()[self::CSRF] ?? '');
        $header = $request->getHeaderLine(self::CSRF_HEADER);
        return $cookie !== '' && $header !== '' && hash_equals($cookie, $header);
    }

    private static function build(string $name, string $value, int $maxAge, string $path, bool $httpOnly, Request $request): string {
        $sameSite = ucfirst(strtolower(getenv('COOKIE_SAMESITE') ?: 'Strict'));
        if (!in_array($sameSite, ['Strict', 'Lax', 'None'], true)) $sameSite = 'Strict';
        $secure = self::secure($request) || $sameSite === 'None';

        $parts = [
            $name . '=' . rawurlencode($value),
            'Path=' . $path,
            'Max-Age=' . max($maxAge, 0),
            'SameSite=' . $sameSite,
        ];
        if ($maxAge <= 0) $parts[] = 'Expires=Thu, 01 Jan 1970 00:00:00 GMT';
        if ($httpOnly) $parts[] = 'HttpOnly';
        if ($secure) $parts[] = 'Secure';
        return implode('; ', $parts);
    }

    private static function secure(Request $request): bool {
        $mode = strtolower(getenv('COOKIE_SECURE') ?: 'auto');
        if ($mode === 'true') return true;
        if ($mode === 'false') return false;
        return $request->getUri()->getScheme() === 'https'
            || strtolower($request->getHeaderLine('X-Forwarded-Proto')) === 'https'
            || (($request->getServerParams()['HTTPS'] ?? '') !== '' && ($request->getServerParams()['HTTPS'] ?? '') !== 'off');
    }
}
