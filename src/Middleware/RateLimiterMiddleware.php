<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use App\Security\ClientIp;
use PDO;

class RateLimiterMiddleware
{
    private PDO $db;
    private int $maxAttempts;
    private int $decayMinutes;

    public function __construct(PDO $db, int $maxAttempts = 5, int $decayMinutes = 15)
    {
        $this->db = $db;
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // Atrás de proxy (tailscale serve, Caddy...) o REMOTE_ADDR é o do proxy para todos: ver TRUSTED_PROXIES
        $ip = ClientIp::from($request);
        $endpoint = $request->getUri()->getPath();

        // Check current attempts
        $stmt = $this->db->prepare("SELECT id, attempts, last_attempt FROM rate_limits WHERE ip_address = :ip AND endpoint = :endpoint");
        $stmt->execute(['ip' => $ip, 'endpoint' => $endpoint]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        $now = new \DateTime();

        if ($record) {
            $lastAttempt = new \DateTime($record['last_attempt']);
            $diff = $now->diff($lastAttempt);
            $minutesPassed = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;

            if ($minutesPassed > $this->decayMinutes) {
                // Reset limit after decay period
                $update = $this->db->prepare("UPDATE rate_limits SET attempts = 1, last_attempt = :now WHERE id = :id");
                $update->execute(['now' => $now->format('Y-m-d H:i:s'), 'id' => $record['id']]);
            } else {
                if ($record['attempts'] >= $this->maxAttempts) {
                    $response = new Response();
                    $response->getBody()->write(json_encode([
                        'error' => 'Too Many Requests',
                        'message' => 'Você excedeu o limite de tentativas. Tente novamente mais tarde.'
                    ]));
                    return $response->withStatus(429)->withHeader('Content-Type', 'application/json');
                }

                $update = $this->db->prepare("UPDATE rate_limits SET attempts = attempts + 1, last_attempt = :now WHERE id = :id");
                $update->execute(['now' => $now->format('Y-m-d H:i:s'), 'id' => $record['id']]);
            }
        } else {
            // First attempt
            $insert = $this->db->prepare("INSERT INTO rate_limits (ip_address, endpoint, attempts, last_attempt) VALUES (:ip, :endpoint, 1, :now)");
            $insert->execute(['ip' => $ip, 'endpoint' => $endpoint, 'now' => $now->format('Y-m-d H:i:s')]);
        }

        return $handler->handle($request);
    }
}
