<?php

namespace App\Repositories;

use App\Contracts\IGoalRepository;
use App\Models\Goal;
use App\Database;
use PDO;

class GoalRepository implements IGoalRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function findByIdAndWorkspaceId(int $id, int $workspaceId): ?Goal {
        $stmt = $this->db->prepare("SELECT * FROM goals WHERE GoalId = ? AND (WorkspaceId = ? OR SharedWithWorkspaceId = ?)");
        $stmt->execute([$id, $workspaceId, $workspaceId]);
        $row = $stmt->fetch();
        if (!$row) return null;
        return $this->mapRowToGoal($row);
    }

    public function findAllByWorkspaceId(int $workspaceId): array {
        $stmt = $this->db->prepare("SELECT * FROM goals WHERE WorkspaceId = ? OR SharedWithWorkspaceId = ? ORDER BY GoalId DESC");
        $stmt->execute([$workspaceId, $workspaceId]);
        $rows = $stmt->fetchAll();
        return array_map([$this, 'mapRowToGoal'], $rows);
    }

    public function save(Goal $goal): Goal {
        if ($goal->getId()) {
            $stmt = $this->db->prepare("UPDATE goals SET Title = ?, TargetAmount = ?, AccumulatedAmount = ?, TargetDate = ?, SharedWithWorkspaceId = ?, AccountId = ?, IsFavorite = ? WHERE GoalId = ? AND WorkspaceId = ?");
            $stmt->execute([
                $goal->getTitle(),
                $goal->getTargetAmount(),
                $goal->getAccumulatedAmount(),
                $goal->getTargetDate(),
                $goal->getSharedWithWorkspaceId(),
                $goal->getAccountId(),
                $goal->isFavorite() ? 1 : 0,
                $goal->getId(),
                $goal->getWorkspaceId()
            ]);
            return $goal;
        } else {
            $stmt = $this->db->prepare("INSERT INTO goals (WorkspaceId, Title, TargetAmount, AccumulatedAmount, TargetDate, SharedWithWorkspaceId, AccountId, IsFavorite) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $goal->getWorkspaceId(),
                $goal->getTitle(),
                $goal->getTargetAmount(),
                $goal->getAccumulatedAmount(),
                $goal->getTargetDate(),
                $goal->getSharedWithWorkspaceId(),
                $goal->getAccountId(),
                $goal->isFavorite() ? 1 : 0
            ]);
            $id = (int) $this->db->lastInsertId();
            return new Goal(
                $goal->getWorkspaceId(),
                $goal->getTitle(),
                $goal->getTargetAmount(),
                $goal->getAccumulatedAmount(),
                $goal->getTargetDate(),
                $goal->getSharedWithWorkspaceId(),
                $id,
                $goal->getAccountId(),
                $goal->isFavorite()
            );
        }
    }

    public function delete(int $id, int $workspaceId): bool {
        $stmt = $this->db->prepare("DELETE FROM goals WHERE GoalId = ? AND WorkspaceId = ?");
        return $stmt->execute([$id, $workspaceId]);
    }

    private function mapRowToGoal(array $row): Goal {
        return new Goal(
            (int) $row['WorkspaceId'],
            $row['Title'],
            (float) $row['TargetAmount'],
            (float) $row['AccumulatedAmount'],
            $row['TargetDate'],
            $row['SharedWithWorkspaceId'] ? (int) $row['SharedWithWorkspaceId'] : null,
            (int) $row['GoalId'],
            $row['AccountId'] ? (int) $row['AccountId'] : null,
            (bool) $row['IsFavorite']
        );
    }
}
