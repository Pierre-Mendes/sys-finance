<?php

namespace App\Repositories;

use App\Contracts\ICreditCardTransactionRepository;
use App\Models\CreditCardTransaction;
use PDO;

class CreditCardTransactionRepository implements ICreditCardTransactionRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function findAllByWorkspaceId(int $workspaceId): array {
        $stmt = $this->db->prepare("SELECT * FROM credit_card_transactions WHERE WorkspaceId = :userId ORDER BY Date DESC");
        $stmt->execute(['userId' => $workspaceId]);
        
        $txs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $txs[] = new CreditCardTransaction(
                $row['CardId'], $row['WorkspaceId'], $row['CategoryId'], $row['Title'], 
                (float)$row['Amount'], $row['Date'], $row['Installments'], $row['CurrentInstallment'],
                $row['Description'], $row['id']
            );
        }
        return $txs;
    }

    public function findAllByCardId(int $cardId, int $workspaceId): array {
        $stmt = $this->db->prepare("SELECT * FROM credit_card_transactions WHERE CardId = :cardId AND WorkspaceId = :userId ORDER BY Date DESC");
        $stmt->execute(['cardId' => $cardId, 'userId' => $workspaceId]);
        
        $txs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $txs[] = new CreditCardTransaction(
                $row['CardId'], $row['WorkspaceId'], $row['CategoryId'], $row['Title'], 
                (float)$row['Amount'], $row['Date'], $row['Installments'], $row['CurrentInstallment'],
                $row['Description'], $row['id']
            );
        }
        return $txs;
    }

    public function findByIdAndWorkspaceId(int $id, int $workspaceId): ?CreditCardTransaction {
        $stmt = $this->db->prepare("SELECT * FROM credit_card_transactions WHERE Id = :id AND WorkspaceId = :userId LIMIT 1");
        $stmt->execute(['id' => $id, 'userId' => $workspaceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;
        return new CreditCardTransaction(
            $row['CardId'], $row['WorkspaceId'], $row['CategoryId'], $row['Title'], 
            (float)$row['Amount'], $row['Date'], $row['Installments'], $row['CurrentInstallment'],
            $row['Description'], $row['id']
        );
    }

    public function save(CreditCardTransaction $tx): CreditCardTransaction {
        if ($tx->getId()) {
            $stmt = $this->db->prepare("
                UPDATE credit_card_transactions 
                SET CategoryId = :catId, Title = :title, Description = :desc, Amount = :amt, 
                    Date = :date, Installments = :inst, CurrentInstallment = :currInst
                WHERE Id = :id AND WorkspaceId = :userId
            ");
            $stmt->execute([
                'catId' => $tx->getCategoryId(), 'title' => $tx->getTitle(), 'desc' => $tx->getDescription(),
                'amt' => $tx->getAmount(), 'date' => $tx->getDate(), 'inst' => $tx->getInstallments(),
                'currInst' => $tx->getCurrentInstallment(), 'id' => $tx->getId(), 'userId' => $tx->getWorkspaceId()
            ]);
            return $tx;
        }

        $stmt = $this->db->prepare("
            INSERT INTO credit_card_transactions (CardId, WorkspaceId, CategoryId, Title, Description, Amount, Date, Installments, CurrentInstallment)
            VALUES (:cardId, :userId, :catId, :title, :desc, :amt, :date, :inst, :currInst)
        ");
        $stmt->execute([
            'cardId' => $tx->getCardId(), 'userId' => $tx->getWorkspaceId(), 'catId' => $tx->getCategoryId(),
            'title' => $tx->getTitle(), 'desc' => $tx->getDescription(), 'amt' => $tx->getAmount(),
            'date' => $tx->getDate(), 'inst' => $tx->getInstallments(), 'currInst' => $tx->getCurrentInstallment()
        ]);
        
        return new CreditCardTransaction(
            $tx->getCardId(), $tx->getWorkspaceId(), $tx->getCategoryId(), $tx->getTitle(), 
            $tx->getAmount(), $tx->getDate(), $tx->getInstallments(), $tx->getCurrentInstallment(),
            $tx->getDescription(), (int)$this->db->lastInsertId()
        );
    }

    public function delete(int $id, int $workspaceId): bool {
        $stmt = $this->db->prepare("DELETE FROM credit_card_transactions WHERE Id = :id AND WorkspaceId = :userId");
        $stmt->execute(['id' => $id, 'userId' => $workspaceId]);
        return $stmt->rowCount() > 0;
    }
}
