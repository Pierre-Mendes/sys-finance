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

    public function __construct(ICreditCardRepository $cardRepo, ICreditCardTransactionRepository $txRepo, BillRepository $billRepo, CategoryRepository $categoryRepo) {
        $this->cardRepo = $cardRepo;
        $this->txRepo = $txRepo;
        $this->billRepo = $billRepo;
        $this->categoryRepo = $categoryRepo;
    }

    public function getAllCards(int $workspaceId): array {
        $cards = $this->cardRepo->findAllByWorkspaceId($workspaceId);
        $result = [];
        foreach ($cards as $card) {
            $txs = $this->txRepo->findAllByCardId($card->getId(), $workspaceId);
            $usedAmount = 0;
            foreach ($txs as $tx) {
                // For now, let's sum ALL transactions on the card.
                // In a more complex system, we'd only sum unbilled ones.
                $usedAmount += $tx->getAmount();
            }
            $result[] = [
                'card' => $card,
                'usedAmount' => $usedAmount
            ];
        }
        return $result;
    }

    public function createCard(int $workspaceId, CreditCardDTO $dto): CreditCard {
        $card = new CreditCard(
            $workspaceId, $dto->accountId, $dto->name, $dto->limitAmount, 
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

        $updated = new CreditCard(
            $workspaceId,
            (int) ($data['accountId'] ?? $card->getAccountId()),
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

    public function generateInvoice(int $cardId, int $workspaceId, int $month, int $year): ?Transaction {
        $card = $this->cardRepo->findByIdAndWorkspaceId($cardId, $workspaceId);
        if (!$card) throw new Exception("Credit card not found.");

        $allTxs = $this->txRepo->findAllByCardId($cardId, $workspaceId);
        $invoiceTotal = 0;

        foreach ($allTxs as $tx) {
            $dt = new \DateTime($tx->getDate());
            $txMonth = (int) $dt->format('n');
            $txYear = (int) $dt->format('Y');

            if ($txMonth === $month && $txYear === $year) {
                $invoiceTotal += $tx->getAmount();
            }
        }

        if ($invoiceTotal <= 0) return null;

        $dueObj = new \DateTime();
        $dueObj->setDate($year, $month, $card->getDueDay());
        $dueStr = $dueObj->format('Y-m-d');

        $title = "Fatura " . $card->getName() . " ({$month}/{$year})";
        $existing = $this->billRepo->findByTitleAndWorkspaceId($title, $workspaceId);
        if ($existing) {
            throw new Exception("A fatura deste cartão para {$month}/{$year} já foi gerada.");
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
            date('Y-m-d'),
            $defaultCategoryId,
            $card->getAccountId(),
            $invoiceTotal,
            "Fatura gerada automaticamente pelo consolidado de compras do mês.",
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

        $updated = new CreditCardTransaction(
            $tx->getCardId(),
            $workspaceId,
            (int) ($data['categoryId'] ?? $tx->getCategoryId()),
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
