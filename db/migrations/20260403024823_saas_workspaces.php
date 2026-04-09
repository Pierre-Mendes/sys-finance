<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SaasWorkspaces extends AbstractMigration
{
    public function up(): void
    {
        // 1. Create workspaces table
        $workspaces = $this->table('workspaces', ['id' => 'WorkspaceId']);
        $workspaces->addColumn('WorkspaceName', 'string', ['limit' => 255])
                   ->addColumn('OwnerId', 'integer', ['signed' => false])
                   ->addColumn('CreatedAt', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                   ->addForeignKey('OwnerId', 'user', 'UserId', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                   ->create();

        // 2. Create workspace_users table (pivot)
        $workspaceUsers = $this->table('workspace_users', ['id' => false]);
        $workspaceUsers->addColumn('WorkspaceId', 'integer', ['signed' => false, 'null' => false])
                       ->addColumn('UserId', 'integer', ['signed' => false, 'null' => false])
                       ->addColumn('Role', 'string', ['limit' => 50, 'default' => 'viewer'])
                       ->addColumn('JoinedAt', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                       ->addIndex(['WorkspaceId', 'UserId'], ['unique' => true])
                       ->addForeignKey('WorkspaceId', 'workspaces', 'WorkspaceId', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                       ->addForeignKey('UserId', 'user', 'UserId', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                       ->create();

        // 3. Create workspace_invites table
        $workspaceInvites = $this->table('workspace_invites', ['id' => 'InviteId']);
        $workspaceInvites->addColumn('WorkspaceId', 'integer', ['signed' => false])
                         ->addColumn('InviteCode', 'string', ['limit' => 50])
                         ->addColumn('Passcode', 'string', ['limit' => 255])
                         ->addColumn('ExpiresAt', 'datetime', ['null' => true])
                         ->addColumn('CreatedBy', 'integer', ['signed' => false])
                         ->addColumn('CreatedAt', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                         ->addIndex(['InviteCode'], ['unique' => true])
                         ->addForeignKey('WorkspaceId', 'workspaces', 'WorkspaceId', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                         ->addForeignKey('CreatedBy', 'user', 'UserId', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                         ->create();

        // 4. Data Migration: Create default workspace for existing users
        $users = $this->fetchAll('SELECT UserId, FirstName FROM user');
        
        // Prepare mapping UserId -> WorkspaceId
        $userWorkspaceMap = [];

        foreach ($users as $user) {
            $workspaceName = "Espaço de " . $user['FirstName'];
            
            // Insert workspace
            $this->execute(sprintf(
                "INSERT INTO workspaces (WorkspaceName, OwnerId) VALUES ('%s', %d)",
                $workspaceName,
                intval($user['UserId'])
            ));

            $workspaceId = $this->fetchRow('SELECT LAST_INSERT_ID() as id')['id'];
            $userWorkspaceMap[$user['UserId']] = $workspaceId;

            // Insert into workspace_users
            $this->execute(sprintf(
                "INSERT INTO workspace_users (WorkspaceId, UserId, Role) VALUES (%d, %d, 'owner')",
                intval($workspaceId),
                intval($user['UserId'])
            ));
        }

        // 5. Migrate Entities: Add WorkspaceId, Update Rows, Drop UserId
        $tablesToMigrate = ['account', 'category', 'assets', 'bills', 'budget', 'totals'];

        foreach ($tablesToMigrate as $tableName) {
            $table = $this->table($tableName);
            
            // Add column
            $table->addColumn('WorkspaceId', 'integer', ['signed' => false, 'null' => true])
                  ->update();

            // Port data
            foreach ($userWorkspaceMap as $uid => $wid) {
                $this->execute(sprintf(
                    "UPDATE %s SET WorkspaceId = %d WHERE UserId = %d",
                    $tableName,
                    intval($wid),
                    intval($uid)
                ));
            }

            // Make it non-null
            $table->changeColumn('WorkspaceId', 'integer', ['signed' => false, 'null' => false])
                  ->update();

            // Link FK and drop old FK / Column
            $table->dropForeignKey('UserId')
                  ->removeColumn('UserId')
                  ->addForeignKey('WorkspaceId', 'workspaces', 'WorkspaceId', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                  ->update();
        }
    }

    public function down(): void
    {
        $tablesToMigrate = ['account', 'category', 'assets', 'bills', 'budget', 'totals'];

        // Reverse for entities: Add UserId, Update Rows based on workspaces.OwnerId, Drop WorkspaceId
        foreach ($tablesToMigrate as $tableName) {
            $table = $this->table($tableName);
            $table->addColumn('UserId', 'integer', ['signed' => false, 'null' => true])
                  ->update();

            // Map back based on workspace owner
            $this->execute(sprintf("
                UPDATE %s entity
                INNER JOIN workspaces w ON w.WorkspaceId = entity.WorkspaceId
                SET entity.UserId = w.OwnerId
            ", $tableName));

            $table->changeColumn('UserId', 'integer', ['signed' => false, 'null' => false])
                  ->dropForeignKey('WorkspaceId')
                  ->removeColumn('WorkspaceId')
                  ->addForeignKey('UserId', 'user', 'UserId', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                  ->update();
        }

        $this->table('workspace_invites')->drop()->update();
        $this->table('workspace_users')->drop()->update();
        $this->table('workspaces')->drop()->update();
    }
}
