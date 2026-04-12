<?php
namespace App\Controllers;

use PDO;

class HealthController {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function metrics($request, $response) {
        $start = microtime(true);
        $dbStatus = 'unhealthy';
        $dbLatency = 0;

        try {
            $this->db->query('SELECT 1');
            $dbStatus = 'healthy';
            $dbLatency = round((microtime(true) - $start) * 1000, 2);
        } catch (\Throwable $e) {
            $dbStatus = 'down';
        }

        $memoryUsed = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);

        $payload = [
            'status' => $dbStatus === 'healthy' ? 'operational' : 'degraded',
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'metrics' => [
                'database' => [
                    'status' => $dbStatus,
                    'latency_ms' => $dbLatency
                ],
                'memory' => [
                    'current_mb' => round($memoryUsed / 1024 / 1024, 2),
                    'peak_mb' => round($memoryPeak / 1024 / 1024, 2)
                ],
                'environment' => getenv('APP_ENV') ?: 'production',
                'php_version' => PHP_VERSION
            ]
        ];

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')
                        ->withStatus($dbStatus === 'healthy' ? 200 : 503);
    }
}
