<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddAccountToGoalContributions extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('goal_contributions');
        if (!$table->hasColumn('AccountId')) {
            $table->addColumn('AccountId', 'integer', ['signed' => false, 'null' => true, 'after' => 'GoalId'])
                  ->addForeignKey('AccountId', 'account', 'AccountId', ['delete'=> 'SET_NULL', 'update'=> 'CASCADE']);
        }
        $table->update();
    }
}
