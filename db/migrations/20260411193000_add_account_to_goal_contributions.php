<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddAccountToGoalContributions extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('goal_contributions');
        $table->addColumn('AccountId', 'integer', ['signed' => false, 'null' => true, 'after' => 'GoalId'])
              ->addForeignKey('AccountId', 'bank_accounts', 'AccountId', ['delete'=> 'SET_NULL', 'update'=> 'CASCADE'])
              ->update();
    }
}
