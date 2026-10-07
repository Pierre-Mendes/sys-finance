<?php

namespace App\Controllers;

use App\Monitoring\SentryTunnel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MonitoringController {
    private SentryTunnel $tunnel;

    public function __construct(SentryTunnel $tunnel) {
        $this->tunnel = $tunnel;
    }

    public function sentryTunnel(Request $request, Response $response): Response {
        $status = $this->tunnel->forward((string) $request->getBody());
        return $response->withStatus($status);
    }
}
