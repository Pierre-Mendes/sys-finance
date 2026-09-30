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

    public function execute(int $workspaceId, TransactionDTO $dto, ?int $parentId = null, ?int $userId = null): Transaction {
        $context = new TransactionContext($workspaceId, $dto, $parentId, $userId);

        foreach ($this->steps as $step) {
            $step->handle($context);
        }

        return $context->transaction;
    }
}
