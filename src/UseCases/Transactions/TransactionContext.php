<?php

namespace App\UseCases\Transactions;

use App\DTO\TransactionDTO;
use App\Models\Transaction;

class TransactionContext {
    public int $workspaceId;
    public TransactionDTO $dto;
    public ?Transaction $transaction = null;
    public ?int $parentId = null;
    public ?int $userId = null;

    public function __construct(int $workspaceId, TransactionDTO $dto, ?int $parentId = null, ?int $userId = null) {
        $this->workspaceId = $workspaceId;
        $this->dto = $dto;
        $this->parentId = $parentId;
        $this->userId = $userId;
    }
}
