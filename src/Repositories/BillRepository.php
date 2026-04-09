<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Contracts\ITransactionRepository;
use PDO;

class BillRepository implements ITransactionRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function findAllByWorkspaceId(int $workspaceId): array {
        // Bills schema has `Dates` column typo instead of `Date`
        $stmt = $this->db->prepare("
            SELECT b.BillsId, b.WorkspaceId, b.Title, b.Dates, b.CategoryId, b.AccountId, b.Amount, b.Description, b.ParentTransactionId,
                   b.due_date, b.status, b.priority, b.recurrence_type,
                   c.CategoryName, acc.AccountName
            FROM bills b
            LEFT JOIN category c ON b.CategoryId = c.CategoryId
            LEFT JOIN account acc ON b.AccountId = acc.AccountId
            WHERE b.WorkspaceId = :userId
        ");
        $stmt->execute(['userId' => $workspaceId]);
        
        $bills = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $t = new Transaction(
                $row['WorkspaceId'], 'bill', $row['Title'], $row['Dates'], 
                $row['CategoryId'], $row['AccountId'], (float)$row['Amount'], $row['Description'], $row['BillsId'],
                $row['ParentTransactionId'], $row['due_date'], $row['status'], $row['priority'], $row['recurrence_type']
            );
            if ($row['CategoryName']) $t->setCategoryName($row['CategoryName']);
            if ($row['AccountName']) $t->setAccountName($row['AccountName']);
            $bills[] = $t;
        }
        return $bills;
    }

    public function findByIdAndWorkspaceId(int $id, int $workspaceId): ?Transaction {
        $stmt = $this->db->prepare("SELECT * FROM bills WHERE BillsId = :id AND WorkspaceId = :userId LIMIT 1");
        $stmt->execute(['id' => $id, 'userId' => $workspaceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;
        return new Transaction(
            $row['WorkspaceId'], 'bill', $row['Title'], $row['Dates'], 
            $row['CategoryId'], $row['AccountId'], (float)$row['Amount'], $row['Description'], $row['BillsId'],
            $row['ParentTransactionId'], $row['due_date'], $row['status'], $row['priority'], $row['recurrence_type']
        );
    }

    public function findByTitleAndWorkspaceId(string $title, int $workspaceId): ?Transaction {
        $stmt = $this->db->prepare("SELECT * FROM bills WHERE Title = :title AND WorkspaceId = :userId LIMIT 1");
        $stmt->execute(['title' => $title, 'userId' => $workspaceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;
        return new Transaction(
            $row['WorkspaceId'], 'bill', $row['Title'], $row['Dates'], 
            $row['CategoryId'], $row['AccountId'], (float)$row['Amount'], $row['Description'], $row['BillsId'],
            $row['ParentTransactionId'], $row['due_date'], $row['status'], $row['priority'], $row['recurrence_type']
        );
    }

    public function save(Transaction $t): Transaction {
        if ($t->getId()) {
            $stmt = $this->db->prepare("
                UPDATE bills 
                SET Title = :title, Dates = :date, CategoryId = :catId, AccountId = :accId, Amount = :amt, Description = :desc, ParentTransactionId = :parentId,
                    due_date = :due_date, status = :status, priority = :priority, recurrence_type = :recurrence_type
                WHERE BillsId = :id AND WorkspaceId = :userId
            ");
            $stmt->execute([
                'title' => $t->getTitle(), 'date' => $t->getDate(), 'catId' => $t->getCategoryId(),
                'accId' => $t->getAccountId(), 'amt' => (string)$t->getAmount(), 'desc' => $t->getDescription(),
                'parentId' => $t->getParentTransactionId(),
                'due_date' => $t->getDueDate(), 'status' => $t->getStatus(), 
                'priority' => $t->getPriority(), 'recurrence_type' => $t->getRecurrenceType(),
                'id' => $t->getId(), 'userId' => $t->getUserId()
            ]);
            return $t;
        }

        $stmt = $this->db->prepare("
            INSERT INTO bills (WorkspaceId, Title, Dates, CategoryId, AccountId, Amount, Description, ParentTransactionId, due_date, status, priority, recurrence_type) 
            VALUES (:userId, :title, :date, :catId, :accId, :amt, :desc, :parentId, :due_date, :status, :priority, :recurrence_type)
        ");
        $stmt->execute([
            'userId' => $t->getUserId(), 'title' => $t->getTitle(), 'date' => $t->getDate(),
            'catId' => $t->getCategoryId(), 'accId' => $t->getAccountId(), 'amt' => (string)$t->getAmount(),
            'desc' => $t->getDescription(), 'parentId' => $t->getParentTransactionId(),
            'due_date' => $t->getDueDate(), 'status' => $t->getStatus(),
            'priority' => $t->getPriority(), 'recurrence_type' => $t->getRecurrenceType()
        ]);
        
        return new Transaction(
            $t->getUserId(), 'bill', $t->getTitle(), $t->getDate(), 
            $t->getCategoryId(), $t->getAccountId(), $t->getAmount(), $t->getDescription(), (int)$this->db->lastInsertId(),
            $t->getParentTransactionId(), $t->getDueDate(), $t->getStatus(), $t->getPriority(), $t->getRecurrenceType()
        );
    }

    public function delete(int $id, int $workspaceId): bool {
        $stmt = $this->db->prepare("DELETE FROM bills WHERE BillsId = :id AND WorkspaceId = :userId");
        $stmt->execute(['id' => $id, 'userId' => $workspaceId]);
        return $stmt->rowCount() > 0;
    }
}
