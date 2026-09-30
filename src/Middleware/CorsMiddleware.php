<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * CORS configurável via CORS_ALLOWED_ORIGINS (lista separada por vírgula).
 * Sem a variável mantém o comportamento anterior ("*"); em produção prefira definir
 * explicitamente as origens do frontend (OWASP A05 - Security Misconfiguration).
 */
class CorsMiddleware
{
    /** @var string[] */
    private array $allowedOrigins;

    public function __construct(?string $allowedOrigins = null)
    {
        $raw = $allowedOrigins ?? (getenv('CORS_ALLOWED_ORIGINS') ?: '*');
        $this->allowedOrigins = array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);
        $origin = $request->getHeaderLine('Origin');

        if (in_array('*', $this->allowedOrigins, true)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        } elseif ($origin !== '' && in_array($origin, $this->allowedOrigins, true)) {
            $response = $response
                ->withHeader('Access-Control-Allow-Origin', $origin)
                ->withAddedHeader('Vary', 'Origin');
        } else {
            return $response;
        }

        return $response
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, X-Workspace-Id')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    }
}
