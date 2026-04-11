<?php

namespace App\Repositories;

use App\Models\GoalContribution;
use App\Database;
use PDO;

class GoalContributionRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function findAllByGoalId(int $goalId, int $workspaceId): array {
        $stmt = $this->db->prepare("SELECT * FROM goal_contributions WHERE GoalId = ? AND WorkspaceId = ? ORDER BY Date DESC, Id DESC");
        $stmt->execute([$goalId, $workspaceId]);
        $rows = $stmt->fetchAll();
        return array_map([$this, 'mapRowToContribution'], $rows);
    }

    public function save(GoalContribution $contribution): GoalContribution {
        $stmt = $this->db->prepare("INSERT INTO goal_contributions (GoalId, AccountId, WorkspaceId, Amount, Date, Description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $contribution->getGoalId(),
            $contribution->getAccountId(),
            $contribution->getWorkspaceId(),
            $contribution->getAmount(),
            $contribution->getDate(),
            $contribution->getDescription()
        ]);
        
        // Also update the accumulated amount in the goals table
        $stmtUpdate = $this->db->prepare("UPDATE goals SET AccumulatedAmount = AccumulatedAmount + ? WHERE GoalId = ?");
        $stmtUpdate->execute([$contribution->getAmount(), $contribution->getGoalId()]);

        $id = (int) $this->db->lastInsertId();
        return new GoalContribution(
            $contribution->getGoalId(),
            $contribution->getWorkspaceId(),
            $contribution->getAmount(),
            $contribution->getDate(),
            $contribution->getDescription(),
            $id,
            $contribution->getAccountId()
        );
    }

    private function mapRowToContribution(array $row): GoalContribution {
        return new GoalContribution(
            (int) $row['GoalId'],
            (int) $row['WorkspaceId'],
            (float) $row['Amount'],
            $row['Date'],
            $row['Description'],
            (int) $row['Id'],
            $row['AccountId'] ? (int) $row['AccountId'] : null
        );
    }
}
