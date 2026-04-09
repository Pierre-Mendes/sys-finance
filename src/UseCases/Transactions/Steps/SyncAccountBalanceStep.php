<?php

namespace App\UseCases\Transactions\Steps;

use App\UseCases\Transactions\ITransactionStep;
use App\UseCases\Transactions\TransactionContext;
use PDO;

class SyncAccountBalanceStep implements ITransactionStep {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function handle(TransactionContext $context): void {
        if (!$context->transaction) {
            return;
        }
        
        $accountId = $context->transaction->getAccountId();
        // Here we could extract logic to a BalanceService, but keeping it inline to match original
        $stmtIn = $this->db->prepare("SELECT SUM(Amount) FROM assets WHERE AccountId = :acc AND WorkspaceId = :userId");
        $stmtIn->execute(['acc' => $accountId, 'userId' => $context->workspaceId]);
        $incomes = (float) $stmtIn->fetchColumn();

        $stmtOut = $this->db->prepare("SELECT SUM(Amount) FROM bills WHERE AccountId = :acc AND WorkspaceId = :userId");
        $stmtOut->execute(['acc' => $accountId, 'userId' => $context->workspaceId]);
        $expenses = (float) $stmtOut->fetchColumn();

        $total = $incomes - $expenses;

        $stmtCheck = $this->db->prepare("SELECT TotalsId FROM totals WHERE AccountId = :acc AND WorkspaceId = :userId");
        $stmtCheck->execute(['acc' => $accountId, 'userId' => $context->workspaceId]);
        $exists = $stmtCheck->fetchColumn();

        if ($exists) {
            $stmtUp = $this->db->prepare("UPDATE totals SET Totals = :tot WHERE AccountId = :acc AND WorkspaceId = :userId");
            $stmtUp->execute(['tot' => $total, 'acc' => $accountId, 'userId' => $context->workspaceId]);
        } else {
            $stmtIns = $this->db->prepare("INSERT INTO totals (WorkspaceId, AccountId, Totals) VALUES (:userId, :acc, :tot)");
            $stmtIns->execute(['userId' => $context->workspaceId, 'acc' => $accountId, 'tot' => $total]);
        }
    }
}
