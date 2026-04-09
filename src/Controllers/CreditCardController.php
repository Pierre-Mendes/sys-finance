<?php

namespace App\Controllers;

use App\Services\CreditCardService;
use App\DTO\CreditCardDTO;
use App\DTO\CreditCardTransactionDTO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

class CreditCardController {
    private CreditCardService $cardService;

    public function __construct(CreditCardService $cardService) {
        $this->cardService = $cardService;
    }

    public function index(Request $request, Response $response): Response {
        $workspaceId = $request->getAttribute('workspaceId');

        try {
            $cardsData = $this->cardService->getAllCards($workspaceId);
            $data = array_map(fn($item) => [
                "id" => $item['card']->getId(),
                "name" => $item['card']->getName(),
                "accountId" => $item['card']->getAccountId(),
                "brand" => $item['card']->getBrand(),
                "limitAmount" => $item['card']->getLimitAmount(),
                "closingDay" => $item['card']->getClosingDay(),
                "dueDay" => $item['card']->getDueDay(),
                "color" => $item['card']->getColor(),
                "usedAmount" => $item['usedAmount']
            ], $cardsData);

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
        $dto = new CreditCardDTO($input);

        if (!$dto->isValid()) {
            $response->getBody()->write(json_encode(["success" => false, "error" => "Dados obrigatórios inválidos."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $c = $this->cardService->createCard($workspaceId, $dto);
            $response->getBody()->write(json_encode([
                "success" => true, "message" => "Card created.", "data" => ["id" => $c->getId()]
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $id = (int) ($args['id'] ?? 0);

        try {
            $this->cardService->deleteCard($id, $workspaceId);
            $response->getBody()->write(json_encode(["success" => true, "message" => "Card deleted."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function update(Request $request, Response $response, array $args): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $id = (int) ($args['id'] ?? 0);
        $input = (array) $request->getParsedBody();

        try {
            $card = $this->cardService->updateCard($id, $workspaceId, $input);
            $response->getBody()->write(json_encode(["success" => true, "message" => "Card updated.", "data" => ["id" => $card->getId()]]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function addTransaction(Request $request, Response $response, array $args): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $id = (int) ($args['id'] ?? 0);
        $input = (array) $request->getParsedBody();
        $input['cardId'] = $id;
        $dto = new CreditCardTransactionDTO($input);

        if (!$dto->isValid()) {
            $response->getBody()->write(json_encode(["success" => false, "error" => "Dados de transação de cartão inválidos."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // Emulates installments if present
            $txs = $this->cardService->addTransaction($workspaceId, $dto);
            $response->getBody()->write(json_encode([
                "success" => true, "message" => "Transaction added to card.", "count" => count($txs)
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function generateInvoice(Request $request, Response $response, array $args): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $id = (int) ($args['id'] ?? 0);
        $input = (array) $request->getParsedBody();
        $month = (int) ($input['month'] ?? date('n'));
        $year = (int) ($input['year'] ?? date('Y'));

        try {
            $bill = $this->cardService->generateInvoice($id, $workspaceId, $month, $year);
            $response->getBody()->write(json_encode([
                "success" => true, 
                "message" => $bill ? "Invoice generated successfully." : "No transactions found for this period.", 
                "data" => ["billId" => $bill ? $bill->getId() : null]
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function transactions(Request $request, Response $response, array $args): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $id = (int) ($args['id'] ?? 0);

        try {
            $txs = $this->cardService->getTransactionsByCard($id, $workspaceId);
            $data = array_map(fn($t) => [
                "id" => $t->getId(),
                "cardId" => $t->getCardId(),
                "categoryId" => $t->getCategoryId(),
                "title" => $t->getTitle(),
                "amount" => $t->getAmount(),
                "date" => $t->getDate(),
                "installments" => $t->getInstallments(),
                "currentInstallment" => $t->getCurrentInstallment(),
                "description" => $t->getDescription()
            ], $txs);

            $response->getBody()->write(json_encode(["success" => true, "data" => $data]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function deleteTransaction(Request $request, Response $response, array $args): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $id = (int) ($args['id'] ?? 0);

        try {
            $this->cardService->deleteTransaction($id, $workspaceId);
            $response->getBody()->write(json_encode(["success" => true, "message" => "Purchase deleted."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function updateTransaction(Request $request, Response $response, array $args): Response {
        $workspaceId = $request->getAttribute('workspaceId');
        $id = (int) ($args['id'] ?? 0);
        $input = (array) $request->getParsedBody();

        try {
            $tx = $this->cardService->updateTransaction($id, $workspaceId, $input);
            $response->getBody()->write(json_encode([
                "success" => true, 
                "message" => "Purchase updated.", 
                "data" => ["id" => $tx->getId()]
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["success" => false, "error" => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }
}
