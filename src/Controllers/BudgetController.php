<?php

namespace App\Controllers;

use App\Repositories\BudgetRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class BudgetController {
    private BudgetRepository $budgetRepo;

    public function __construct(BudgetRepository $budgetRepo) {
        $this->budgetRepo = $budgetRepo;
    }

    public function index(Request $request, Response $response): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        try {
            $budgets = $this->budgetRepo->getBudgetsByWorkspaceId($workspaceId);
            $response->getBody()->write(json_encode(["success" => true, "data" => $budgets]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => \App\Security\PublicError::message($e)]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function create(Request $request, Response $response): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $data = json_decode($request->getBody()->getContents(), true);

        if (!isset($data['categoryId']) || !isset($data['amount'])) {
            $response->getBody()->write(json_encode(["success" => false, "error" => "Campos obrigatórios ausentes."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $this->budgetRepo->createBudget($workspaceId, (int)$data['categoryId'], (float)$data['amount']);
            $response->getBody()->write(json_encode(["success" => true]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => \App\Security\PublicError::message($e)]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function update(Request $request, Response $response, array $args): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $id = (int)$args['id'];
        $data = json_decode($request->getBody()->getContents(), true);

        try {
            $this->budgetRepo->updateBudget($workspaceId, $id, (float)$data['amount']);
            $response->getBody()->write(json_encode(["success" => true]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => \App\Security\PublicError::message($e)]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $id = (int)$args['id'];

        try {
            $this->budgetRepo->deleteBudget($workspaceId, $id);
            $response->getBody()->write(json_encode(["success" => true]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => \App\Security\PublicError::message($e)]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }
}
