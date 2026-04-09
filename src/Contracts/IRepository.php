<?php

namespace App\Contracts;

interface IRepository {
    public function delete(int $id, int $workspaceId): bool;
}
