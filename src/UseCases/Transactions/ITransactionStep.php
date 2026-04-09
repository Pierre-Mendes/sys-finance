<?php

namespace App\UseCases\Transactions;

interface ITransactionStep {
    public function handle(TransactionContext $context): void;
}
