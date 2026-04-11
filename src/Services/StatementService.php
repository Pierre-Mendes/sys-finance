<?php

namespace App\Services;

use PDO;

class StatementService {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getAccountStatement(int $accountId, int $workspaceId): array {
        // 1. Fetch all transactions (Assets and Bills) for this account
        // We order by Date ASC to calculate running balance
        $stmt = $this->db->prepare("
            SELECT t.* FROM (
                SELECT AssetsId as id, 'asset' as type, Title as title, Amount as amount, Date as date, CategoryId as categoryId, status 
                FROM assets 
                WHERE AccountId = ? AND WorkspaceId = ?
                UNION ALL
                SELECT BillsId as id, 'bill' as type, Title as title, Amount as amount, Dates as date, CategoryId as categoryId, status 
                FROM bills 
                WHERE AccountId = ? AND WorkspaceId = ?
            ) t
            ORDER BY t.date ASC, t.id ASC
        ");
        $stmt->execute([$accountId, $workspaceId, $accountId, $workspaceId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Resolve Category Names
        $stmtCat = $this->db->prepare("SELECT CategoryId, CategoryName FROM category WHERE WorkspaceId = ?");
        $stmtCat->execute([$workspaceId]);
        $categories = $stmtCat->fetchAll(PDO::FETCH_KEY_PAIR);

        $runningBalance = 0.0;
        $statement = [];

        foreach ($rows as $row) {
            $amount = (float) $row['amount'];
            
            // Only affect balance if PAID
            if ($row['status'] === 'PAID') {
                if ($row['type'] === 'asset') {
                    $runningBalance += $amount;
                } else {
                    $runningBalance -= $amount;
                }
            }

            $statement[] = [
                'id' => $row['id'],
                'type' => $row['type'],
                'title' => $row['title'],
                'amount' => $amount,
                'date' => $row['date'],
                'status' => $row['status'],
                'categoryName' => $categories[$row['categoryId']] ?? 'Sem Categoria',
                'runningBalance' => $runningBalance
            ];
        }

        // Return reversed (newest first) but with the correct running balances calculated
        return array_reverse($statement);
    }
}
