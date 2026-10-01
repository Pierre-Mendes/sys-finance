<?php

namespace App\Services;

use PDO;

class DashboardService {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getAnalytics(int $workspaceId, bool $is360 = false, ?int $userId = null): array {
        
        $wsIds = [$workspaceId];
        
        if ($is360 && $userId) {
            $stmtWs = $this->db->prepare("SELECT WorkspaceId FROM workspace_users WHERE UserId = ?");
            $stmtWs->execute([$userId]);
            $memberOf = $stmtWs->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($memberOf)) {
                $wsIds = array_map('intval', $memberOf);
            }
        }

        // Totals: só o que já foi pago/recebido. Pendentes (inclusive as próximas parcelas geradas
        // pela recorrência e faturas em aberto) entram apenas na projeção.
        ['income' => $totalIncome, 'expense' => $totalExpense] = $this->paidTotals($wsIds);

        $balance = $totalIncome - $totalExpense;

        // Breakdown (only for 360 mode)
        $breakdown = [];
        if ($is360 && $userId) {
            $stmtBreakdown = $this->select($wsIds, "
                SELECT w.WorkspaceId as id, w.WorkspaceName as name, w.Type as type,
                       (COALESCE((SELECT SUM(Amount) FROM assets WHERE WorkspaceId = w.WorkspaceId AND status = 'PAID'), 0) - 
                        COALESCE((SELECT SUM(Amount) FROM bills WHERE WorkspaceId = w.WorkspaceId AND status = 'PAID'), 0)) as balance
                FROM workspaces w
                WHERE w.WorkspaceId IN ({ws})
                ORDER BY balance DESC
            ");
            $breakdown = $stmtBreakdown->fetchAll(PDO::FETCH_ASSOC);
            foreach ($breakdown as &$b) {
                $b['balance'] = (float) $b['balance'];
            }
        }

        // Accounts list
        $accountsQuery = $is360 
            ? "SELECT CONCAT(w.WorkspaceName, ' - ', a.AccountName) as name, COALESCE(t.Totals, 0) as balance 
               FROM account a
               INNER JOIN workspaces w ON a.WorkspaceId = w.WorkspaceId
               LEFT JOIN totals t ON a.AccountId = t.AccountId AND t.WorkspaceId = a.WorkspaceId
               WHERE a.WorkspaceId IN ({ws})"
            : "SELECT a.AccountName as name, COALESCE(t.Totals, 0) as balance 
               FROM account a
               LEFT JOIN totals t ON a.AccountId = t.AccountId AND t.WorkspaceId = a.WorkspaceId
               WHERE a.WorkspaceId IN ({ws})";
               
        $stmtAccounts = $this->select($wsIds, $accountsQuery);
        $accounts = $stmtAccounts->fetchAll(PDO::FETCH_ASSOC);
        foreach ($accounts as &$acc) {
            $acc['balance'] = (float) $acc['balance'];
        }

        // Evolution: Last 6 Months
        $stmtEvoMonthly = $this->select($wsIds, "
            SELECT DATE_FORMAT(Date, '%Y-%m') as period, 
                   SUM(CASE WHEN type = 'asset' THEN Amount ELSE 0 END) as income,
                   SUM(CASE WHEN type = 'bill' THEN Amount ELSE 0 END) as expense
            FROM (
                SELECT Date, Amount, 'asset' as type FROM assets WHERE WorkspaceId IN ({ws}) AND Date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                UNION ALL
                SELECT Dates as Date, Amount, 'bill' as type FROM bills WHERE WorkspaceId IN ({ws}) AND Dates >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            ) as t
            GROUP BY period ORDER BY period ASC
        ");
        $evolutionMonthly = $stmtEvoMonthly->fetchAll(PDO::FETCH_ASSOC);

        // Evolution: Last 30 Days
        $stmtEvoDaily = $this->select($wsIds, "
            SELECT DATE_FORMAT(Date, '%Y-%m-%d') as period, 
                   SUM(CASE WHEN type = 'asset' THEN Amount ELSE 0 END) as income,
                   SUM(CASE WHEN type = 'bill' THEN Amount ELSE 0 END) as expense
            FROM (
                SELECT Date, Amount, 'asset' as type FROM assets WHERE WorkspaceId IN ({ws}) AND Date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                UNION ALL
                SELECT Dates as Date, Amount, 'bill' as type FROM bills WHERE WorkspaceId IN ({ws}) AND Dates >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ) as t
            GROUP BY period ORDER BY period ASC
        ");
        $evolutionDaily = $stmtEvoDaily->fetchAll(PDO::FETCH_ASSOC);

        // Category Distribution (General Expenses)
        $stmtCatGen = $this->select($wsIds, "
            SELECT c.CategoryName as name, SUM(b.Amount) as total
            FROM bills b
            JOIN category c ON b.CategoryId = c.CategoryId
            WHERE b.WorkspaceId IN ({ws})
            GROUP BY b.CategoryId
            ORDER BY total DESC
        ");
        $categoryDistribution = $stmtCatGen->fetchAll(PDO::FETCH_ASSOC);
        foreach ($categoryDistribution as &$cat) {
            $cat['total'] = (float) $cat['total'];
        }

        // Category Distribution (Credit Card only)
        $stmtCatCard = $this->select($wsIds, "
            SELECT c.CategoryName as name, SUM(ct.Amount) as total
            FROM credit_card_transactions ct
            JOIN category c ON ct.CategoryId = c.CategoryId
            WHERE ct.WorkspaceId IN ({ws})
            GROUP BY ct.CategoryId
            ORDER BY total DESC
        ");
        $creditCardCategoryDistribution = $stmtCatCard->fetchAll(PDO::FETCH_ASSOC);
        foreach ($creditCardCategoryDistribution as &$cat) {
            $cat['total'] = (float) $cat['total'];
        }


        // MoM Comparison (Previous Month)
        $stmtPrevIn = $this->select($wsIds, "SELECT SUM(Amount) FROM assets WHERE WorkspaceId IN ({ws}) AND Date >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH) AND Date < DATE_FORMAT(CURDATE(), '%Y-%m-01')");
        $prevIncome = (float) $stmtPrevIn->fetchColumn();

        $stmtPrevOut = $this->select($wsIds, "SELECT SUM(Amount) FROM bills WHERE WorkspaceId IN ({ws}) AND Dates >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH) AND Dates < DATE_FORMAT(CURDATE(), '%Y-%m-01')");
        $prevExpense = (float) $stmtPrevOut->fetchColumn();

        // Projections (Pending for current month)
        $stmtPendingIn = $this->select($wsIds, "SELECT SUM(Amount) FROM assets WHERE WorkspaceId IN ({ws}) AND status = 'PENDING' AND DATE_FORMAT(Date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')");
        $pendingIncome = (float) $stmtPendingIn->fetchColumn();

        $stmtPendingOut = $this->select($wsIds, "SELECT SUM(Amount) FROM bills WHERE WorkspaceId IN ({ws}) AND status = 'PENDING' AND DATE_FORMAT(Dates, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')");
        $pendingExpense = (float) $stmtPendingOut->fetchColumn();

        $projectedBalance = $balance + $pendingIncome - $pendingExpense;

        // Top Category (Villain) for current month
        $stmtTopCat = $this->select($wsIds, "
            SELECT c.CategoryName as name, SUM(b.Amount) as total
            FROM bills b
            JOIN category c ON b.CategoryId = c.CategoryId
            WHERE b.WorkspaceId IN ({ws}) AND DATE_FORMAT(b.Dates, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
            GROUP BY b.CategoryId
            ORDER BY total DESC LIMIT 1
        ");
        $villain = $stmtTopCat->fetch(PDO::FETCH_ASSOC);

        // Goal Progress with Favorites Logic
        $stmtFav = $this->select($wsIds, "SELECT GoalId, Title, TargetAmount as target, AccumulatedAmount as current FROM goals WHERE WorkspaceId IN ({ws}) AND IsFavorite = 1");
        $favorites = $stmtFav->fetchAll(PDO::FETCH_ASSOC);
        
        $label = "Dinheiro Guardado";
        $target = 0;
        $current = 0;

        if (count($favorites) === 1) {
            $label = $favorites[0]['Title'];
            $target = (float) $favorites[0]['target'];
            $current = (float) $favorites[0]['current'];
        } elseif (count($favorites) > 1) {
            $label = "Metas Favoritas";
            foreach ($favorites as $f) {
                $target += (float) $f['target'];
                $current += (float) $f['current'];
            }
        } else {
            // Fallback: Total sum of all goals
            $stmtAll = $this->select($wsIds, "SELECT SUM(TargetAmount) as target, SUM(AccumulatedAmount) as current FROM goals WHERE WorkspaceId IN ({ws})");
            $all = $stmtAll->fetch(PDO::FETCH_ASSOC);
            $target = (float) ($all['target'] ?? 0);
            $current = (float) ($all['current'] ?? 0);
        }

        $goalsProgress = [
            'label' => $label,
            'target' => $target,
            'current' => $current,
            'percent' => ($target > 0) ? round(($current / $target) * 100, 1) : 0
        ];

        return [
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'balance' => $balance,
            'prevMonth' => [
                'income' => $prevIncome,
                'expense' => $prevExpense
            ],
            'projections' => [
                'pendingIncome' => $pendingIncome,
                'pendingExpense' => $pendingExpense,
                'projectedBalance' => $projectedBalance
            ],
            'villain' => $villain,
            'goals' => $goalsProgress,
            'breakdown' => $breakdown,
            'accounts' => $accounts,
            'charts' => [
                'evolution' => [
                    'monthly' => $evolutionMonthly,
                    'daily' => $evolutionDaily
                ],
                'categories' => [
                    'general' => $categoryDistribution,
                    'creditCard' => $creditCardCategoryDistribution
                ]
            ]
        ];
    }

    /**
     * Receitas e despesas efetivamente pagas: é o mesmo critério do saldo das contas (tabela totals).
     *
     * @return array{income: float, expense: float}
     */
    public function paidTotals(array $wsIds): array {
        $income = (float) $this->select($wsIds, "SELECT SUM(Amount) FROM assets WHERE WorkspaceId IN ({ws}) AND status = 'PAID'")->fetchColumn();
        $expense = (float) $this->select($wsIds, "SELECT SUM(Amount) FROM bills WHERE WorkspaceId IN ({ws}) AND status = 'PAID'")->fetchColumn();

        return ['income' => $income, 'expense' => $expense];
    }

    /**
     * Executa a consulta trocando cada marcador {ws} por placeholders "?" ligados aos IDs de workspace.
     * Nada de concatenar valores no SQL (OWASP A03 - Injection).
     */
    private function select(array $wsIds, string $sql): \PDOStatement {
        $params = [];
        $sql = preg_replace_callback('/\{ws\}/', function () use (&$params, $wsIds) {
            array_push($params, ...$wsIds);
            return implode(',', array_fill(0, count($wsIds), '?'));
        }, $sql);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
