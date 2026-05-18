<?php

namespace App\Services\Hydration;

use App\Database;
use App\Domain\DTO\IAContext;
use PDO;

class ContextHydrator {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function hydrateForWorkspace(int $workspaceId): IAContext {
        return new IAContext(
            $this->getFrequentCategories($workspaceId),
            $this->getActiveGoals($workspaceId),
            $this->getBankTemplates(),
            $this->getRecentTransactions($workspaceId)
        );
    }

    private function getFrequentCategories(int $workspaceId): array {
        // Query to find common descriptions and their most frequent category
        $query = "
            SELECT Title as description, CategoryId, COUNT(*) as frequency
            FROM (
                SELECT Title, CategoryId FROM assets WHERE WorkspaceId = :ws1
                UNION ALL
                SELECT Title, CategoryId FROM bills WHERE WorkspaceId = :ws2
            ) as combined
            GROUP BY description, CategoryId
            HAVING frequency > 1
            ORDER BY frequency DESC
            LIMIT 50
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute(['ws1' => $workspaceId, 'ws2' => $workspaceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getActiveGoals(int $workspaceId): array {
        $stmt = $this->db->prepare("SELECT GoalId, Title FROM goals WHERE WorkspaceId = ?");
        $stmt->execute([$workspaceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getBankTemplates(): array {
        return $this->db->query("SELECT * FROM bank_statement_templates")->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getRecentTransactions(int $workspaceId): array {
        $query = "
            SELECT Title, CategoryId, Date as dt FROM assets WHERE WorkspaceId = :ws1
            UNION ALL
            SELECT Title, CategoryId, Dates as dt FROM bills WHERE WorkspaceId = :ws2
            ORDER BY dt DESC
            LIMIT 20
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['ws1' => $workspaceId, 'ws2' => $workspaceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
