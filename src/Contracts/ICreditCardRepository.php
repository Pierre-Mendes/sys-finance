<?php

namespace App\Contracts;

use App\Models\CreditCard;

interface ICreditCardRepository {
    /**
     * @param int $workspaceId
     * @return CreditCard[]
     */
    public function findAllByWorkspaceId(int $workspaceId): array;

    public function findByIdAndWorkspaceId(int $id, int $workspaceId): ?CreditCard;

    public function save(CreditCard $card): CreditCard;

    public function delete(int $id, int $workspaceId): bool;
}
