<?php

use Phinx\Migration\AbstractMigration;

class AddPhaseEightSaasFeatures extends AbstractMigration
{
    public function change()
    {
        // 1. Add UserCode to user
        $user = $this->table('user');
        if (!$user->hasColumn('UserCode')) {
            $user->addColumn('UserCode', 'string', ['limit' => 20, 'null' => true])
                 ->addIndex(['UserCode'], ['unique' => true])
                 ->update();

            $dbUsers = $this->fetchAll('SELECT UserId FROM user WHERE UserCode IS NULL');
            foreach ($dbUsers as $u) {
                 $code = 'U-' . strtoupper(substr(base_convert(hash('crc32', uniqid() . $u['UserId']), 16, 36), 0, 6));
                 $this->execute("UPDATE user SET UserCode = '{$code}' WHERE UserId = {$u['UserId']}");
            }
        }

        // 2. Add Affinity to workspace_users
        $workspaceUsers = $this->table('workspace_users');
        if (!$workspaceUsers->hasColumn('Affinity')) {
            $workspaceUsers->addColumn('Affinity', 'string', ['limit' => 50, 'null' => true, 'default' => 'Amigo'])
                           ->update();
        }

        // 3. System Notifications Table (In-App Invites)
        if (!$this->hasTable('system_invitations')) {
            $invites = $this->table('system_invitations', ['id' => 'InvitationId']);
            $invites->addColumn('SenderId', 'integer', ['signed' => false])
                    ->addColumn('TargetUserId', 'integer', ['signed' => false])
                    ->addColumn('WorkspaceId', 'integer', ['signed' => false])
                    ->addColumn('Status', 'string', ['limit' => 20, 'default' => 'pending'])
                    ->addColumn('CreatedAt', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                    ->addForeignKey('SenderId', 'user', 'UserId', ['delete' => 'CASCADE'])
                    ->addForeignKey('TargetUserId', 'user', 'UserId', ['delete' => 'CASCADE'])
                    ->addForeignKey('WorkspaceId', 'workspaces', 'WorkspaceId', ['delete' => 'CASCADE'])
                    ->create();
        }

        // 4. Rateio Tracking (Transactions Split)
        $bills = $this->table('bills');
        if (!$bills->hasColumn('ParentTransactionId')) {
            $bills->addColumn('ParentTransactionId', 'integer', ['signed' => false, 'null' => true])
                  ->addForeignKey('ParentTransactionId', 'bills', 'BillsId', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                  ->update();
        }
    }
}
