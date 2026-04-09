<?php

namespace App\UseCases\Transactions;

use App\DTO\TransactionDTO;
use App\Models\Transaction;

class CreateTransactionUseCase {
    /** @var ITransactionStep[] */
    private array $steps;

    public function __construct(array $steps = []) {
        $this->steps = $steps;
    }

    public function addStep(ITransactionStep $step): void {
        $this->steps[] = $step;
    }

    public function execute(int $workspaceId, TransactionDTO $dto, ?int $parentId = null): Transaction {
        $context = new TransactionContext($workspaceId, $dto, $parentId);

        foreach ($this->steps as $step) {
            $step->handle($context);
        }

        return $context->transaction;
    }
}
