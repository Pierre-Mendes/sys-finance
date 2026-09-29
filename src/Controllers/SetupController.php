<?php

namespace App\Controllers;

use App\Contracts\ICreditCardRepository;
use App\Repositories\AccountRepository;
use App\Repositories\CategoryRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Informa o que o workspace já tem cadastrado, para o frontend guiar o usuário
 * (checklist de primeiros passos / criação inline) em vez de exigir o fluxo
 * rígido conta -> cartão -> transação.
 */
class SetupController {
    private AccountRepository $accountRepo;
    private CategoryRepository $categoryRepo;
    private ICreditCardRepository $cardRepo;

    public function __construct(AccountRepository $accountRepo, CategoryRepository $categoryRepo, ICreditCardRepository $cardRepo) {
        $this->accountRepo = $accountRepo;
        $this->categoryRepo = $categoryRepo;
        $this->cardRepo = $cardRepo;
    }

    public function status(Request $request, Response $response): Response {
        $workspaceId = (int) $request->getAttribute('workspaceId');

        $accounts = $this->accountRepo->countByWorkspaceId($workspaceId);
        $incomeCategories = $this->categoryRepo->countByWorkspaceIdAndLevel($workspaceId, 1);
        $expenseCategories = $this->categoryRepo->countByWorkspaceIdAndLevel($workspaceId, 2);
        $creditCards = count($this->cardRepo->findAllByWorkspaceId($workspaceId));

        $response->getBody()->write(json_encode([
            "success" => true,
            "data" => [
                "accounts" => $accounts,
                "creditCards" => $creditCards,
                "incomeCategories" => $incomeCategories,
                "expenseCategories" => $expenseCategories,
                "hasAccount" => $accounts > 0,
                "hasCreditCard" => $creditCards > 0,
                "hasCategories" => ($incomeCategories + $expenseCategories) > 0,
            ]
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
