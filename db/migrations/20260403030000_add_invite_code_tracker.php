<?php

use Phinx\Migration\AbstractMigration;

class AddInviteCodeTracker extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('workspace_users');
        $table->addColumn('UsedInviteCode', 'string', ['limit' => 50, 'null' => true, 'after' => 'Role'])
              ->update();
    }
}
