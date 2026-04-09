<?php

namespace App\Controllers;

use App\Services\AccountService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

class AccountController {
    private AccountService $accountService;

    public function __construct(AccountService $accountService) {
        $this->accountService = $accountService;
    }

    public function index(Request $request, Response $response): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        try {
            $accounts = $this->accountService->getAllForUser($workspaceId);
            $data = array_map(function($acc) {
                return [
                    "id" => $acc->getId(),
                    "name" => $acc->getAccountName()
                ];
            }, $accounts);

            $response->getBody()->write(json_encode(["success" => true, "data" => $data]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function create(Request $request, Response $response): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $input = (array) $request->getParsedBody();
        $name = $input['name'] ?? '';

        try {
            $account = $this->accountService->create($workspaceId, $name);
            $response->getBody()->write(json_encode([
                "success" => true, 
                "message" => "Account created successfully.",
                "data" => ["id" => $account->getId(), "name" => $account->getAccountName()]
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function update(Request $request, Response $response, array $args): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $id = (int) ($args['id'] ?? 0);
        $input = (array) $request->getParsedBody();
        $name = $input['name'] ?? '';

        try {
            $account = $this->accountService->update($id, $workspaceId, $name);
            $response->getBody()->write(json_encode([
                "success" => true, 
                "message" => "Account updated successfully.",
                "data" => ["id" => $account->getId(), "name" => $account->getAccountName()]
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $id = (int) ($args['id'] ?? 0);

        try {
            $this->accountService->delete($id, $workspaceId);
            $response->getBody()->write(json_encode(["success" => true, "message" => "Account deleted."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }
}
