<?php

namespace App\Repositories;

use App\Models\Account;
use PDO;

class AccountRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function findAllByWorkspaceId(int $workspaceId): array {
        $stmt = $this->db->prepare("SELECT AccountId, WorkspaceId, AccountName FROM account WHERE WorkspaceId = :userId ORDER BY AccountName ASC");
        $stmt->execute(['userId' => $workspaceId]);
        
        $accounts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $accounts[] = new Account($row['WorkspaceId'], $row['AccountName'], $row['AccountId']);
        }
        return $accounts;
    }

    public function findByIdAndWorkspaceId(int $id, int $workspaceId): ?Account {
        $stmt = $this->db->prepare("SELECT * FROM account WHERE AccountId = :id AND WorkspaceId = :userId LIMIT 1");
        $stmt->execute(['id' => $id, 'userId' => $workspaceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;
        return new Account($row['WorkspaceId'], $row['AccountName'], $row['AccountId']);
    }

    public function findByNameAndWorkspaceId(string $name, int $workspaceId): ?Account {
        $stmt = $this->db->prepare("SELECT * FROM account WHERE WorkspaceId = :userId AND LOWER(AccountName) = LOWER(:name) LIMIT 1");
        $stmt->execute(['userId' => $workspaceId, 'name' => trim($name)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;
        return new Account($row['WorkspaceId'], $row['AccountName'], $row['AccountId']);
    }

    public function countByWorkspaceId(int $workspaceId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM account WHERE WorkspaceId = :userId");
        $stmt->execute(['userId' => $workspaceId]);
        return (int) $stmt->fetchColumn();
    }

    public function save(Account $account): Account {
        if ($account->getId()) {
            $stmt = $this->db->prepare("UPDATE account SET AccountName = :name WHERE AccountId = :id AND WorkspaceId = :userId");
            $stmt->execute([
                'name' => $account->getAccountName(),
                'id' => $account->getId(),
                'userId' => $account->getUserId()
            ]);
            return $account;
        }

        $stmt = $this->db->prepare("INSERT INTO account (WorkspaceId, AccountName) VALUES (:userId, :name)");
        $stmt->execute([
            'userId' => $account->getUserId(),
            'name' => $account->getAccountName()
        ]);
        
        return new Account($account->getUserId(), $account->getAccountName(), (int) $this->db->lastInsertId());
    }

    public function delete(int $id, int $workspaceId): bool {
        $stmt = $this->db->prepare("DELETE FROM account WHERE AccountId = :id AND WorkspaceId = :userId");
        $stmt->execute(['id' => $id, 'userId' => $workspaceId]);
        return $stmt->rowCount() > 0;
    }
}
