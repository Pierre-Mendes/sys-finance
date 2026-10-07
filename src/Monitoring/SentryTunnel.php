<?php

namespace App\Monitoring;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

/**
 * Túnel do Sentry para o frontend (opção `tunnel` do @sentry/vue).
 *
 * O navegador envia os eventos para a própria API (mesma origem: sem CSP extra, sem bloqueio de adblock)
 * e a API repassa ao Sentry/GlitchTip, que pode ficar só na rede interna do Docker.
 * Só aceita envelopes do DSN configurado em SENTRY_FRONTEND_DSN e o destino sai da configuração,
 * nunca da requisição (não vira proxy aberto / SSRF).
 */
final class SentryTunnel {
    public const MAX_BYTES = 1024 * 1024;

    private ?array $dsn;
    private ClientInterface $http;

    public function __construct(?string $frontendDsn, ?ClientInterface $http = null) {
        $this->dsn = self::parseDsn((string) $frontendDsn);
        $this->http = $http ?? new Client(['timeout' => 5, 'connect_timeout' => 2, 'http_errors' => false]);
    }

    public function isEnabled(): bool {
        return $this->dsn !== null;
    }

    /**
     * @return int status HTTP a devolver ao navegador
     */
    public function forward(string $envelope): int {
        if ($this->dsn === null) return 404;
        if ($envelope === '' || strlen($envelope) > self::MAX_BYTES) return 413;

        $headerLine = strtok($envelope, "\n");
        $header = json_decode((string) $headerLine, true);
        $sent = is_array($header) ? self::parseDsn((string) ($header['dsn'] ?? '')) : null;
        if ($sent === null || $sent['key'] !== $this->dsn['key'] || $sent['project'] !== $this->dsn['project']) {
            return 400;
        }

        try {
            $response = $this->http->request('POST', $this->dsn['envelopeUrl'], [
                'headers' => ['Content-Type' => 'application/x-sentry-envelope'],
                'body' => $envelope,
            ]);
            return $response->getStatusCode() < 300 ? 200 : 502;
        } catch (\Throwable $e) {
            // Monitoramento fora do ar não pode afetar o app: só registra no log do PHP.
            error_log('Sentry tunnel: ' . $e->getMessage());
            return 502;
        }
    }

    /** @return array{key: string, project: string, envelopeUrl: string}|null */
    public static function parseDsn(string $dsn): ?array {
        $parts = parse_url(trim($dsn));
        if (!$parts || empty($parts['scheme']) || empty($parts['host']) || empty($parts['user']) || empty($parts['path'])) {
            return null;
        }
        if (!in_array($parts['scheme'], ['http', 'https'], true)) return null;

        $path = trim($parts['path'], '/');
        $segments = explode('/', $path);
        $project = (string) array_pop($segments);
        if (!ctype_digit($project)) return null;
        $prefix = $segments ? '/' . implode('/', $segments) : '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return [
            'key' => $parts['user'],
            'project' => $project,
            'envelopeUrl' => "{$parts['scheme']}://{$parts['host']}{$port}{$prefix}/api/{$project}/envelope/",
        ];
    }
}
