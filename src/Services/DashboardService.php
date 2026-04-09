<?php

namespace App\Services;

use PDO;
use Exception;

class DashboardService {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getAnalytics(int $workspaceId, bool $is360 = false, ?int $userId = null): array {
        
        if ($is360 && $userId) {
            // Get all workspaces the user has access to
            $stmtWs = $this->db->prepare("SELECT WorkspaceId FROM workspace_users WHERE UserId = ?");
            $stmtWs->execute([$userId]);
            $wsIds = $stmtWs->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($wsIds)) $wsIds = [$workspaceId];
            $inQuery = implode(',', array_map('intval', $wsIds));
            
            $stmtIn = $this->db->query("SELECT SUM(Amount) FROM assets WHERE WorkspaceId IN ($inQuery)");
            $totalIncome = (float) $stmtIn->fetchColumn();

            $stmtOut = $this->db->query("SELECT SUM(Amount) FROM bills WHERE WorkspaceId IN ($inQuery)");
            $totalExpense = (float) $stmtOut->fetchColumn();

            $stmtAccounts = $this->db->query("
                SELECT CONCAT(w.WorkspaceName, ' - ', a.AccountName) as name, COALESCE(t.Totals, 0) as balance 
                FROM account a
                INNER JOIN workspaces w ON a.WorkspaceId = w.WorkspaceId
                LEFT JOIN totals t ON a.AccountId = t.AccountId AND t.WorkspaceId = a.WorkspaceId
                WHERE a.WorkspaceId IN ($inQuery)
            ");
            $accounts = $stmtAccounts->fetchAll(PDO::FETCH_ASSOC);
            
        } else {
            // Query Total Incomes
            $stmtIn = $this->db->prepare("SELECT SUM(Amount) FROM assets WHERE WorkspaceId = :uid");
            $stmtIn->execute(['uid' => $workspaceId]);
            $totalIncome = (float) $stmtIn->fetchColumn();

            // Query Total Expenses
            $stmtOut = $this->db->prepare("SELECT SUM(Amount) FROM bills WHERE WorkspaceId = :uid");
            $stmtOut->execute(['uid' => $workspaceId]);
            $totalExpense = (float) $stmtOut->fetchColumn();

            // Query Balance per Account
            $stmtAccounts = $this->db->prepare("
                SELECT a.AccountName as name, COALESCE(t.Totals, 0) as balance 
                FROM account a
                LEFT JOIN totals t ON a.AccountId = t.AccountId AND t.WorkspaceId = :u1
                WHERE a.WorkspaceId = :u2
            ");
            $stmtAccounts->execute(['u1' => $workspaceId, 'u2' => $workspaceId]);
            $accounts = $stmtAccounts->fetchAll(PDO::FETCH_ASSOC);
        }
        
        $balance = $totalIncome - $totalExpense;
        
        foreach ($accounts as &$acc) {
            $acc['balance'] = (float) $acc['balance'];
        }

        return [
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'balance' => $balance,
            'accounts' => $accounts
        ];
    }
}
