<?php

namespace App\Controllers;

use App\Services\StatementService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class StatementController {
    private StatementService $statementService;

    public function __construct(StatementService $statementService) {
        $this->statementService = $statementService;
    }

    public function getAccountStatement(Request $request, Response $response, array $args): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $accountId = (int) ($args['id'] ?? 0);

        try {
            $statement = $this->statementService->getAccountStatement($accountId, $workspaceId);
            $response->getBody()->write(json_encode(["success" => true, "data" => $statement]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }
}
