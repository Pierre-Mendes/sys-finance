<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPermissionsAndWorkspaceType extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up(): void
    {
        $workspaces = $this->table('workspaces');
        $workspaces->addColumn('Type', 'string', ['limit' => 20, 'default' => 'shared'])
                   ->update();
        
        $workspaceUsers = $this->table('workspace_users');
        $workspaceUsers->addColumn('Permissions', 'json', ['null' => true])
                       ->update();
                       
        // Migrate data: if workspace has only 1 user, it's personal
        $this->execute("UPDATE workspaces w SET Type = 'personal' WHERE (SELECT COUNT(*) FROM workspace_users wu WHERE wu.WorkspaceId = w.WorkspaceId) = 1");
        
        // Let's set default valid permissions depending on role
        $this->execute("
            UPDATE workspace_users SET Permissions = '{\"accounts\":\"editor\",\"categories\":\"editor\",\"transactions\":\"editor\",\"budgets\":\"editor\",\"goals\":\"editor\",\"credit_cards\":\"editor\",\"reports\":\"viewer\"}' WHERE Role IN ('owner', 'editor');
        ");
        $this->execute("
            UPDATE workspace_users SET Permissions = '{\"accounts\":\"viewer\",\"categories\":\"viewer\",\"transactions\":\"viewer\",\"budgets\":\"viewer\",\"goals\":\"viewer\",\"credit_cards\":\"viewer\",\"reports\":\"viewer\"}' WHERE Role = 'viewer';
        ");
    }

    public function down(): void
    {
        $workspaceUsers = $this->table('workspace_users');
        $workspaceUsers->removeColumn('Permissions')->update();
        
        $workspaces = $this->table('workspaces');
        $workspaces->removeColumn('Type')->update();
    }
}
