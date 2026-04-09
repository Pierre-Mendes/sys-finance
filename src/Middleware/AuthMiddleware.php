<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware {
    public function __invoke(Request $request, RequestHandler $handler): Response {
        $header = $request->getHeaderLine('Authorization');
        
        if (empty($header) || !preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode(["success" => false, "error" => "Token is missing."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $token = $matches[1];
        $decoded = base64_decode($token);
        
        if (!$decoded || !str_contains($decoded, ':')) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode(["success" => false, "error" => "Invalid token format."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        list($userId, $email) = explode(':', $decoded);

        // Attach userId to the request for the controllers
        $request = $request->withAttribute('userId', (int) $userId);

        return $handler->handle($request);
    }
}
