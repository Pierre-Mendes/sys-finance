<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * As rotas de escrita de investimentos passaram a exigir o módulo "investments".
 * Membros existentes recebem nele a mesma permissão que já têm em "transactions",
 * para ninguém perder (nem ganhar) acesso com a mudança.
 */
final class AddInvestmentsPermission extends AbstractMigration
{
    public function up(): void
    {
        $rows = $this->fetchAll("SELECT WorkspaceId, UserId, Permissions FROM workspace_users");
        $pdo = $this->getAdapter()->getConnection();
        $update = $pdo->prepare("UPDATE workspace_users SET Permissions = ? WHERE WorkspaceId = ? AND UserId = ?");

        foreach ($rows as $row) {
            $permissions = json_decode((string) ($row['Permissions'] ?? ''), true);
            if (!is_array($permissions) || isset($permissions['investments'])) continue;
            $permissions['investments'] = ($permissions['transactions'] ?? 'viewer') === 'editor' ? 'editor' : 'viewer';
            $update->execute([json_encode($permissions), $row['WorkspaceId'], $row['UserId']]);
        }
    }

    public function down(): void
    {
    }
}
