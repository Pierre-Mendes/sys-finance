<?php

namespace App\Repositories;

use PDO;

class BudgetRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getBudgetsByWorkspaceId(int $workspaceId): array {
        // Fetch budgets mapped to their category, alongside how much was actually spent in that category this month
        // This is a complex analytical query joining budget -> category -> bills
        $stmt = $this->db->prepare("
            SELECT 
                b.BudgetId as id, 
                b.CategoryId as categoryId, 
                c.CategoryName as categoryName, 
                b.Amount as amount,
                b.Dates as date,
                COALESCE((
                    SELECT SUM(Amount) FROM bills 
                    WHERE CategoryId = b.CategoryId AND WorkspaceId = :uid AND MONTH(Dates) = MONTH(b.Dates) AND YEAR(Dates) = YEAR(b.Dates)
                ), 0) as spent
            FROM budget b
            JOIN category c ON b.CategoryId = c.CategoryId
            WHERE b.WorkspaceId = :uid2
            ORDER BY b.Dates DESC
        ");
        $stmt->execute(['uid' => $workspaceId, 'uid2' => $workspaceId]);
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($res as &$row) {
            $row['amount'] = (float) $row['amount'];
            $row['spent'] = (float) $row['spent'];
        }
        return $res;
    }

    public function createBudget(int $workspaceId, int $categoryId, float $amount): bool {
        // Checking if already exists for this month/category
        $check = $this->db->prepare("SELECT BudgetId FROM budget WHERE WorkspaceId = :uid AND CategoryId = :cid AND MONTH(Dates) = MONTH(CURRENT_DATE()) AND YEAR(Dates) = YEAR(CURRENT_DATE())");
        $check->execute(['uid' => $workspaceId, 'cid' => $categoryId]);
        if ($check->fetchColumn()) {
            throw new \Exception("Já existe um orçamento definido para esta categoria no mês atual.");
        }

        $date = date("Y-m-d");
        $stmt = $this->db->prepare("INSERT INTO budget (WorkspaceId, CategoryId, Dates, Amount) VALUES (:uid, :cid, :date, :amount)");
        return $stmt->execute([
            'uid' => $workspaceId,
            'cid' => $categoryId,
            'date' => $date,
            'amount' => $amount
        ]);
    }

    public function updateBudget(int $workspaceId, int $id, float $amount): bool {
        // verify ownership explicitly via user verification not implemented deeply but implicitly on delete
        $stmt = $this->db->prepare("UPDATE budget SET Amount = :amount WHERE BudgetId = :id AND WorkspaceId = :uid");
        return $stmt->execute([
            'amount' => $amount,
            'id' => $id,
            'uid' => $workspaceId
        ]);
    }

    public function deleteBudget(int $workspaceId, int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM budget WHERE BudgetId = :id AND WorkspaceId = :uid");
        return $stmt->execute([
            'id' => $id,
            'uid' => $workspaceId
        ]);
    }
}
