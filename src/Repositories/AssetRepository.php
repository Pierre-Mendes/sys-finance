<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Contracts\ITransactionRepository;
use PDO;

class AssetRepository implements ITransactionRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function findAllByWorkspaceId(int $workspaceId): array {
        // We join Category and Account schemas cleanly for the aggregated lists
        $stmt = $this->db->prepare("
            SELECT a.AssetsId, a.WorkspaceId, a.Title, a.Date, a.CategoryId, a.AccountId, a.Amount, a.Description,
                   a.due_date, a.status, a.priority, a.recurrence_type,
                   c.CategoryName, acc.AccountName
            FROM assets a
            LEFT JOIN category c ON a.CategoryId = c.CategoryId
            LEFT JOIN account acc ON a.AccountId = acc.AccountId
            WHERE a.WorkspaceId = :userId
        ");
        $stmt->execute(['userId' => $workspaceId]);
        
        $assets = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $t = new Transaction(
                $row['WorkspaceId'], 'asset', $row['Title'], $row['Date'], 
                $row['CategoryId'], $row['AccountId'], (float)$row['Amount'], $row['Description'], $row['AssetsId'],
                null, $row['due_date'], $row['status'], $row['priority'], $row['recurrence_type']
            );
            if ($row['CategoryName']) $t->setCategoryName($row['CategoryName']);
            if ($row['AccountName']) $t->setAccountName($row['AccountName']);
            $assets[] = $t;
        }
        return $assets;
    }

    public function findByIdAndWorkspaceId(int $id, int $workspaceId): ?Transaction {
        $stmt = $this->db->prepare("SELECT * FROM assets WHERE AssetsId = :id AND WorkspaceId = :userId LIMIT 1");
        $stmt->execute(['id' => $id, 'userId' => $workspaceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;
        return new Transaction(
            $row['WorkspaceId'], 'asset', $row['Title'], $row['Date'], 
            $row['CategoryId'], $row['AccountId'], (float)$row['Amount'], $row['Description'], $row['AssetsId'],
            null, $row['due_date'], $row['status'], $row['priority'], $row['recurrence_type']
        );
    }

    public function save(Transaction $t): Transaction {
        if ($t->getId()) {
            $stmt = $this->db->prepare("
                UPDATE assets 
                SET Title = :title, Date = :date, CategoryId = :catId, AccountId = :accId, Amount = :amt, Description = :desc,
                    due_date = :due_date, status = :status, priority = :priority, recurrence_type = :recurrence_type
                WHERE AssetsId = :id AND WorkspaceId = :userId
            ");
            $stmt->execute([
                'title' => $t->getTitle(), 'date' => $t->getDate(), 'catId' => $t->getCategoryId(),
                'accId' => $t->getAccountId(), 'amt' => (string)$t->getAmount(), 'desc' => $t->getDescription(),
                'due_date' => $t->getDueDate(), 'status' => $t->getStatus(), 
                'priority' => $t->getPriority(), 'recurrence_type' => $t->getRecurrenceType(),
                'id' => $t->getId(), 'userId' => $t->getUserId()
            ]);
            return $t;
        }

        $stmt = $this->db->prepare("
            INSERT INTO assets (WorkspaceId, Title, Date, CategoryId, AccountId, Amount, Description, due_date, status, priority, recurrence_type) 
            VALUES (:userId, :title, :date, :catId, :accId, :amt, :desc, :due_date, :status, :priority, :recurrence_type)
        ");
        $stmt->execute([
            'userId' => $t->getUserId(), 'title' => $t->getTitle(), 'date' => $t->getDate(),
            'catId' => $t->getCategoryId(), 'accId' => $t->getAccountId(), 'amt' => (string)$t->getAmount(),
            'desc' => $t->getDescription(), 'due_date' => $t->getDueDate(), 'status' => $t->getStatus(),
            'priority' => $t->getPriority(), 'recurrence_type' => $t->getRecurrenceType()
        ]);
        
        return new Transaction(
            $t->getUserId(), 'asset', $t->getTitle(), $t->getDate(), 
            $t->getCategoryId(), $t->getAccountId(), $t->getAmount(), $t->getDescription(), (int)$this->db->lastInsertId(),
            null, $t->getDueDate(), $t->getStatus(), $t->getPriority(), $t->getRecurrenceType()
        );
    }

    public function delete(int $id, int $workspaceId): bool {
        $stmt = $this->db->prepare("DELETE FROM assets WHERE AssetsId = :id AND WorkspaceId = :userId");
        $stmt->execute(['id' => $id, 'userId' => $workspaceId]);
        return $stmt->rowCount() > 0;
    }
}
