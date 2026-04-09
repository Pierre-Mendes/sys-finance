<?php

namespace App\Contracts;

use App\Models\Transaction;

interface ITransactionRepository extends IRepository {
    public function findAllByWorkspaceId(int $workspaceId): array;
    public function findByIdAndWorkspaceId(int $id, int $workspaceId): ?Transaction;
    public function save(Transaction $t): Transaction;
}
