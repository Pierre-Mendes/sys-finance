<?php

namespace App\Contracts;

use App\Models\CreditCardTransaction;

interface ICreditCardTransactionRepository {
    /**
     * @param int $workspaceId
     * @return CreditCardTransaction[]
     */
    public function findAllByWorkspaceId(int $workspaceId): array;

    /**
     * @param int $cardId
     * @param int $workspaceId
     * @return CreditCardTransaction[]
     */
    public function findAllByCardId(int $cardId, int $workspaceId): array;

    public function findByIdAndWorkspaceId(int $id, int $workspaceId): ?CreditCardTransaction;

    public function save(CreditCardTransaction $tx): CreditCardTransaction;

    public function delete(int $id, int $workspaceId): bool;
}
