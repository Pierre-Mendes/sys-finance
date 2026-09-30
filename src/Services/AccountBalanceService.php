<?php

namespace App\Services;

use PDO;

/**
 * Recalcula o saldo cacheado de uma conta (tabela totals).
 *
 * Fonte única da regra: só lançamentos com status PAID contam. Pendentes (contas a vencer,
 * próximas parcelas de recorrência, faturas em aberto) só entram no saldo quando forem pagos.
 */
class AccountBalanceService {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function sync(int $accountId, int $workspaceId): float {
        $params = ['acc' => $accountId, 'ws' => $workspaceId];

        $stmtIn = $this->db->prepare("SELECT SUM(Amount) FROM assets WHERE AccountId = :acc AND WorkspaceId = :ws AND status = 'PAID'");
        $stmtIn->execute($params);
        $stmtOut = $this->db->prepare("SELECT SUM(Amount) FROM bills WHERE AccountId = :acc AND WorkspaceId = :ws AND status = 'PAID'");
        $stmtOut->execute($params);

        $total = (float) $stmtIn->fetchColumn() - (float) $stmtOut->fetchColumn();

        $stmtCheck = $this->db->prepare("SELECT TotalsId FROM totals WHERE AccountId = :acc AND WorkspaceId = :ws");
        $stmtCheck->execute($params);

        if ($stmtCheck->fetchColumn()) {
            $stmt = $this->db->prepare("UPDATE totals SET Totals = :tot WHERE AccountId = :acc AND WorkspaceId = :ws");
        } else {
            $stmt = $this->db->prepare("INSERT INTO totals (WorkspaceId, AccountId, Totals) VALUES (:ws, :acc, :tot)");
        }
        $stmt->execute($params + ['tot' => $total]);

        return $total;
    }
}
