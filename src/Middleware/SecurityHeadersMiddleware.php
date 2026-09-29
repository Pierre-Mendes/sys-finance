<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

/**
 * Cabeçalhos de segurança (OWASP Secure Headers) para as respostas da API.
 * Os arquivos estáticos da SPA recebem cabeçalhos equivalentes via public/.htaccess.
 */
class SecurityHeadersMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);

        return $response
            ->withHeader('X-Frame-Options', 'DENY') // Prevent clickjacking
            ->withHeader('X-Content-Type-Options', 'nosniff') // Prevent MIME sniffing
            ->withHeader('X-XSS-Protection', '0') // Filtro legado desativado (recomendação OWASP); a proteção vem do CSP
            ->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains') // HSTS
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->withHeader('Cross-Origin-Resource-Policy', 'same-site')
            // A API só devolve JSON/arquivos: nada deve ser executado ou embutido a partir dela.
            ->withHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'none'")
            // Dados financeiros não devem ficar em cache de navegador/proxy.
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('Pragma', 'no-cache');
    }
}
