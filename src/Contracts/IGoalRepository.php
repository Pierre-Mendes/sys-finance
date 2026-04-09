<?php

namespace App\Contracts;

use App\Models\Goal;

interface IGoalRepository {
    public function findByIdAndWorkspaceId(int $id, int $workspaceId): ?Goal;
    public function findAllByWorkspaceId(int $workspaceId): array;
    public function save(Goal $goal): Goal;
    public function delete(int $id, int $workspaceId): bool;
}
