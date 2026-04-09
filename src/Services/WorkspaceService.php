<?php

namespace App\Services;

use PDO;
use Exception;

class WorkspaceService {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Creates a default workspace for a user.
     * 
     * @param int $userId
     * @param string $userName
     * @return int The ID of the created workspace
     */
    public function createDefaultWorkspace(int $userId, string $userName): int {
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
            }

            $name = "Meu Workspace (" . $userName . ")";
            
            $ins = $this->db->prepare("INSERT INTO workspaces (WorkspaceName, OwnerId) VALUES (?, ?)");
            $ins->execute([$name, $userId]);
            $workspaceId = (int) $this->db->lastInsertId();

            $insUser = $this->db->prepare("INSERT INTO workspace_users (WorkspaceId, UserId, Role) VALUES (?, ?, 'owner')");
            $insUser->execute([$workspaceId, $userId]);

            if ($this->db->inTransaction()) {
                $this->db->commit();
            }

            return $workspaceId;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Checks if a user has any workspace, and if not, creates a default one.
     * This is useful for retroactive fixes during login.
     * 
     * @param int $userId
     * @param string $userName
     */
    public function ensureHasWorkspace(int $userId, string $userName): void {
        $stmt = $this->db->prepare("SELECT WorkspaceId FROM workspace_users WHERE UserId = ? LIMIT 1");
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            $this->createDefaultWorkspace($userId, $userName);
        }
    }
}
