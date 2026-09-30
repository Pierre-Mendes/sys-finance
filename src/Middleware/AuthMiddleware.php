<?php

namespace App\Middleware;

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
        $header = $request->getHeaderLine('Authorization');
        
        if (empty($header) || !preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $this->unauthorized("Token is missing.");
        }

        $claims = $this->tokenService->verify(trim($matches[1]));
        if ($claims === null) {
            return $this->unauthorized("Invalid or expired token.");
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
