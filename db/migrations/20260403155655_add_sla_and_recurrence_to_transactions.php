<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSlaAndRecurrenceToTransactions extends AbstractMigration
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
        $tables = ['assets', 'bills'];

        foreach ($tables as $tableName) {
            $table = $this->table($tableName);
            
            $table->addColumn('due_date', 'date', ['null' => true])
                  ->addColumn('status', 'enum', ['values' => ['PENDING', 'PAID'], 'default' => 'PAID'])
                  ->addColumn('priority', 'enum', ['values' => ['LOW', 'NORMAL', 'HIGH'], 'default' => 'NORMAL'])
                  ->addColumn('recurrence_type', 'enum', ['values' => ['NONE', 'MONTHLY', 'YEARLY'], 'default' => 'NONE'])
                  ->update();
        }
    }
}
