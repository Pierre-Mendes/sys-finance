<?php

namespace App\Controllers;

use App\Services\TransactionService;
use App\DTO\TransactionDTO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

class TransactionController {
    private TransactionService $txService;

    public function __construct(TransactionService $txService) {
        $this->txService = $txService;
    }

    public function index(Request $request, Response $response): Response {
        $workspaceId = $request->getAttribute('workspaceId');

        try {
            $transactions = $this->txService->getAllForUser($workspaceId);
            $data = array_map(function($t) {
                return [
                    "id" => $t->getId(),
                    "type" => $t->getType(),
                    "title" => $t->getTitle(),
                    "date" => $t->getDate(),
                    "categoryId" => $t->getCategoryId(),
                    "categoryName" => $t->getCategoryName(),
                    "accountId" => $t->getAccountId(),
                    "accountName" => $t->getAccountName(),
                    "amount" => $t->getAmount(),
                    "description" => $t->getDescription(),
                    "dueDate" => $t->getDueDate(),
                    "status" => $t->getStatus(),
                    "priority" => $t->getPriority(),
                    "recurrenceType" => $t->getRecurrenceType()
                ];
            }, $transactions);

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
        $dto = new TransactionDTO($input);

        if (!$dto->isValid()) {
            $response->getBody()->write(json_encode(["success" => false, "error" => "Dados obrigatórios inválidos."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $t = $this->txService->create($workspaceId, $dto, (int) $request->getAttribute('userId'));
            $response->getBody()->write(json_encode([
                "success" => true, "message" => "Transaction created.", "data" => ["id" => $t->getId()]
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
        $dto = new TransactionDTO($input);

        if (!$dto->isValid()) {
            $response->getBody()->write(json_encode(["success" => false, "error" => "Dados obrigatórios inválidos."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $t = $this->txService->update($id, $workspaceId, $dto);
            $response->getBody()->write(json_encode([
                "success" => true, "message" => "Transaction updated.", "data" => ["id" => $t->getId()]
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
        $params = $request->getQueryParams();
        $type = $params['type'] ?? '';

        try {
            if (empty($type)) throw new Exception("Type is required for deletion");
            $this->txService->delete($id, $workspaceId, $type);
            $response->getBody()->write(json_encode(["success" => true, "message" => "Transaction deleted."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function pay(Request $request, Response $response, array $args): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $id = (int) ($args['id'] ?? 0);
        $input = (array) $request->getParsedBody();
        $type = $input['type'] ?? '';

        try {
            if (empty($type)) throw new Exception("Type is required to pay a transaction");
            $t = $this->txService->pay($id, $workspaceId, $type);
            $response->getBody()->write(json_encode([
                "success" => true, "message" => "Transaction marked as paid.", "data" => ["id" => $t->getId()]
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }
}
