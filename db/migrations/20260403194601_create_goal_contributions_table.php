<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateGoalContributionsTable extends AbstractMigration
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
    public function change(): void
    {
        $table = $this->table('goal_contributions', ['id' => 'Id']);
        $table->addColumn('GoalId', 'integer', ['signed' => false]) // Unsigned to match goals.GoalId
              ->addColumn('WorkspaceId', 'integer', ['signed' => false]) // Unsigned to match workspaces.WorkspaceId
              ->addColumn('Amount', 'decimal', ['precision' => 10, 'scale' => 2])
              ->addColumn('Date', 'date')
              ->addColumn('Description', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('GoalId', 'goals', 'GoalId', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
              ->addForeignKey('WorkspaceId', 'workspaces', 'WorkspaceId', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
              ->create();
    }
}
