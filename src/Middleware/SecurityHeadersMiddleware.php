<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class SecurityHeadersMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);

        return $response
            ->withHeader('X-Frame-Options', 'DENY') // Prevent clickjacking
            ->withHeader('X-XSS-Protection', '1; mode=block') // Prevent XSS
            ->withHeader('X-Content-Type-Options', 'nosniff') // Prevent MIME sniffing
            ->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains') // HSTS
            ->withHeader('Content-Security-Policy', "default-src 'self' 'unsafe-inline' 'unsafe-eval' data: blob: https://www.gravatar.com https://brapi.dev"); // CSP
    }
}
