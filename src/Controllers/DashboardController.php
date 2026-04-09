<?php

namespace App\Controllers;

use App\Services\DashboardService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

class DashboardController {
    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService) {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request, Response $response): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $userId = $request->getAttribute('userId');
        $queryParams = $request->getQueryParams();
        $is360 = filter_var($queryParams['view360'] ?? false, FILTER_VALIDATE_BOOLEAN);

        try {
            $data = $this->dashboardService->getAnalytics($workspaceId, $is360, $userId);
            $response->getBody()->write(json_encode(["success" => true, "data" => $data]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }
}
