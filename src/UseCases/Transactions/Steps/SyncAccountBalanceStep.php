<?php

namespace App\UseCases\Transactions\Steps;

use App\Services\AccountBalanceService;
use App\UseCases\Transactions\ITransactionStep;
use App\UseCases\Transactions\TransactionContext;

class SyncAccountBalanceStep implements ITransactionStep {
    private AccountBalanceService $balances;

    public function __construct(AccountBalanceService $balances) {
        $this->balances = $balances;
    }

    public function handle(TransactionContext $context): void {
        if (!$context->transaction) {
            return;
        }

        $this->balances->sync($context->transaction->getAccountId(), $context->workspaceId);
    }
}
