<?php

namespace App\Services;

use App\Contracts\ICreditCardRepository;
use App\Contracts\ICreditCardTransactionRepository;
use App\Repositories\BillRepository;
use App\Repositories\CategoryRepository;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\CreditCardTransaction;
use App\Models\Transaction;
use App\DTO\CreditCardDTO;
use App\DTO\CreditCardTransactionDTO;
use Exception;

class CreditCardService {
    private ICreditCardRepository $cardRepo;
    private ICreditCardTransactionRepository $txRepo;
    private BillRepository $billRepo;
    private CategoryRepository $categoryRepo;
    private ReferenceResolver $resolver;

    public function __construct(ICreditCardRepository $cardRepo, ICreditCardTransactionRepository $txRepo, BillRepository $billRepo, CategoryRepository $categoryRepo, ReferenceResolver $resolver) {
        $this->cardRepo = $cardRepo;
        $this->txRepo = $txRepo;
        $this->billRepo = $billRepo;
        $this->categoryRepo = $categoryRepo;
        $this->resolver = $resolver;
    }

    /**
     * Cartões com limite usado e a fatura aberta.
     * Limite usado = compras cujas faturas ainda não foram pagas (pagar a fatura libera o limite).
     */
    public function getAllCards(int $workspaceId, ?\DateTimeImmutable $today = null): array {
        $today ??= new \DateTimeImmutable('today');
        $cards = $this->cardRepo->findAllByWorkspaceId($workspaceId);
        $result = [];
        foreach ($cards as $card) {
            $cycle = new CardBillingCycle($card->getClosingDay(), $card->getDueDay());
            $open = $cycle->openInvoice($today);
            $paidCache = [];
            $usedAmount = 0.0;
            $openTotal = 0.0;
            foreach ($this->txRepo->findAllByCardId($card->getId(), $workspaceId) as $tx) {
                $inv = $cycle->invoiceFor(new \DateTimeImmutable($tx->getDate()));
                $title = CardBillingCycle::title($card->getName(), $inv['month'], $inv['year']);
                if (!array_key_exists($title, $paidCache)) {
                    $bill = $this->billRepo->findByTitleAndWorkspaceId($title, $workspaceId);
                    $paidCache[$title] = $bill !== null && $bill->getStatus() === 'PAID';
                }
                if (!$paidCache[$title]) $usedAmount += $tx->getAmount();
                if ($inv['month'] === $open['month'] && $inv['year'] === $open['year']) $openTotal += $tx->getAmount();
            }
            $result[] = [
                'card' => $card,
                'usedAmount' => round($usedAmount, 2),
                'bestPurchaseDay' => $cycle->bestPurchaseDay(),
                'openInvoice' => [
                    'closingDate' => $open['closing']->format('Y-m-d'),
                    'dueDate' => $open['due']->format('Y-m-d'),
                    'total' => round($openTotal, 2),
                ],
            ];
        }
        return $result;
    }

    public function createCard(int $workspaceId, CreditCardDTO $dto): CreditCard {
        // Vincula a uma conta existente ou cria a conta na hora (ex.: "Nubank").
        $account = $this->resolver->resolveAccount($workspaceId, $dto->accountId, $dto->accountName);

        $card = new CreditCard(
            $workspaceId, (int) $account->getId(), $dto->name, $dto->limitAmount, 
            $dto->closingDay, $dto->dueDay, $dto->brand, $dto->color
        );
        return $this->cardRepo->save($card);
    }

    public function deleteCard(int $id, int $workspaceId): void {
        $success = $this->cardRepo->delete($id, $workspaceId);
        if (!$success) throw new Exception("Credit card not found or could not be deleted.");
    }

    public function updateCard(int $id, int $workspaceId, array $data): CreditCard {
        $card = $this->cardRepo->findByIdAndWorkspaceId($id, $workspaceId);
        if (!$card) throw new Exception("Credit card not found.");

        $account = $this->resolver->resolveAccount(
            $workspaceId,
            (int) ($data['accountId'] ?? 0) ?: (empty($data['accountName']) ? $card->getAccountId() : 0),
            $data['accountName'] ?? null
        );

        $updated = new CreditCard(
            $workspaceId,
            (int) $account->getId(),
            $data['name'] ?? $card->getName(),
            (float) ($data['limitAmount'] ?? $card->getLimitAmount()),
            (int) ($data['closingDay'] ?? $card->getClosingDay()),
            (int) ($data['dueDay'] ?? $card->getDueDay()),
            $data['brand'] ?? $card->getBrand(),
            $data['color'] ?? $card->getColor(),
            $id
        );
        return $this->cardRepo->save($updated);
    }

    public function addTransaction(int $workspaceId, CreditCardTransactionDTO $dto): array {
        $card = $this->cardRepo->findByIdAndWorkspaceId($dto->cardId, $workspaceId);
        if (!$card) {
            throw new Exception("Credit card not found.");
        }

        $category = $this->resolver->resolveCategory($workspaceId, $dto->categoryId, $dto->categoryName, 2);
        $dto->categoryId = (int) $category->getId();

        $dateObj = new \DateTime($dto->date);
        $amountPerInstallment = $dto->amount / $dto->installments;
        $createdTransactions = [];

        for ($i = 1; $i <= $dto->installments; $i++) {
            $tx = new CreditCardTransaction(
                $dto->cardId,
                $workspaceId,
                $dto->categoryId,
                $dto->title . ($dto->installments > 1 ? " ($i/{$dto->installments})" : ""),
                $amountPerInstallment,
                $dateObj->format('Y-m-d'),
                $dto->installments,
                $i,
                $dto->description
            );
            $createdTransactions[] = $this->txRepo->save($tx);

            if ($dto->installments > 1) {
                $dateObj->modify('+1 month');
            }
        }
        
        return $createdTransactions;
    }

    /**
     * Gera a conta a pagar da fatura que VENCE em $month/$year (ciclo por dia de fechamento).
     * Sem mês informado, gera a última fatura já fechada.
     */
    public function generateInvoice(int $cardId, int $workspaceId, ?int $month = null, ?int $year = null, ?\DateTimeImmutable $today = null): ?Transaction {
        $card = $this->cardRepo->findByIdAndWorkspaceId($cardId, $workspaceId);
        if (!$card) throw new Exception("Credit card not found.");

        $cycle = new CardBillingCycle($card->getClosingDay(), $card->getDueDay());
        $invoice = ($month && $year) ? $cycle->invoiceDueIn($year, $month) : $cycle->lastClosedInvoice($today ?? new \DateTimeImmutable('today'));
        $month = $invoice['month'];
        $year = $invoice['year'];

        $invoiceTotal = 0;
        foreach ($this->txRepo->findAllByCardId($cardId, $workspaceId) as $tx) {
            $inv = $cycle->invoiceFor(new \DateTimeImmutable($tx->getDate()));
            if ($inv['month'] === $month && $inv['year'] === $year) {
                $invoiceTotal += $tx->getAmount();
            }
        }

        if ($invoiceTotal <= 0) return null;

        $dueStr = $invoice['due']->format('Y-m-d');

        $title = CardBillingCycle::title($card->getName(), $month, $year);
        $existing = $this->billRepo->findByTitleAndWorkspaceId($title, $workspaceId);
        if ($existing) {
            throw new Exception("A fatura deste cartão com vencimento em {$month}/{$year} já foi gerada.");
        }

        // Find or create "Cartão de Crédito" category
        $ccCategoryName = "Cartão de Crédito";
        $workspaceCategories = $this->categoryRepo->findAllByWorkspaceIdAndLevel($workspaceId, 2);
        $foundCat = null;
        foreach ($workspaceCategories as $cat) {
            if (strcasecmp($cat->getCategoryName(), $ccCategoryName) === 0) {
                $foundCat = $cat;
                break;
            }
        }
        
        if (!$foundCat) {
            $newCat = new Category($workspaceId, $ccCategoryName, 2);
            $foundCat = $this->categoryRepo->save($newCat);
        }
        $defaultCategoryId = $foundCat->getId();

        $bill = new Transaction(
            $workspaceId,
            'bill',
            $title,
            $invoice['closing']->format('Y-m-d'),
            $defaultCategoryId,
            $card->getAccountId(),
            $invoiceTotal,
            "Fatura gerada pelas compras do ciclo que fechou em " . $invoice['closing']->format('d/m/Y') . ".",
            null,
            null,
            $dueStr,
            'PENDING',
            'HIGH',
            'NONE'
        );

        return $this->billRepo->save($bill);
    }

    /**
     * @return CreditCardTransaction[]
     */
    public function getTransactionsByCard(int $cardId, int $workspaceId): array {
        return $this->txRepo->findAllByCardId($cardId, $workspaceId);
    }

    public function deleteTransaction(int $txId, int $workspaceId): void {
        $tx = $this->txRepo->findByIdAndWorkspaceId($txId, $workspaceId);
        if (!$tx) throw new Exception("Transaction not found.");
        
        $success = $this->txRepo->delete($txId, $workspaceId);
        if (!$success) throw new Exception("Could not delete credit card transaction.");
    }

    public function updateTransaction(int $txId, int $workspaceId, array $data): CreditCardTransaction {
        $tx = $this->txRepo->findByIdAndWorkspaceId($txId, $workspaceId);
        if (!$tx) throw new Exception("Transaction not found.");

        $category = $this->resolver->resolveCategory(
            $workspaceId,
            (int) ($data['categoryId'] ?? 0) ?: (empty($data['categoryName']) ? $tx->getCategoryId() : 0),
            $data['categoryName'] ?? null,
            2
        );

        $updated = new CreditCardTransaction(
            $tx->getCardId(),
            $workspaceId,
            (int) $category->getId(),
            $data['title'] ?? $tx->getTitle(),
            (float) ($data['amount'] ?? $tx->getAmount()),
            $data['date'] ?? $tx->getDate(),
            (int) ($data['installments'] ?? $tx->getInstallments()),
            (int) ($data['currentInstallment'] ?? $tx->getCurrentInstallment()),
            $data['description'] ?? $tx->getDescription(),
            $txId
        );

        return $this->txRepo->save($updated);
    }
}
