<?php

namespace App\Middleware;

use App\Security\SessionCookie;
use App\Security\TokenService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware {
    private TokenService $tokenService;

    public function __construct(?TokenService $tokenService = null) {
        $this->tokenService = $tokenService ?? new TokenService();
    }

    public function __invoke(Request $request, RequestHandler $handler): Response {
        // Sessão no cookie HttpOnly (navegador) ou Bearer (clientes de API).
        [$token, $source] = SessionCookie::token($request);
        if ($token === null) {
            return $this->unauthorized("Token is missing.");
        }

        $claims = $this->tokenService->verify($token);
        if ($claims === null) {
            return $this->unauthorized("Invalid or expired token.");
        }

        // Cookie é enviado automaticamente pelo navegador: escrita só com o header CSRF (double-submit).
        if ($source === 'cookie' && !SessionCookie::csrfValid($request)) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode(["success" => false, "error" => "CSRF token ausente ou inválido."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        // Attach userId to the request for the controllers
        $request = $request->withAttribute('userId', (int) $claims['sub']);

        return $handler->handle($request);
    }

    private function unauthorized(string $message): Response {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode(["success" => false, "error" => $message]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }
}
