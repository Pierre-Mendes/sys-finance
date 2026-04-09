<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddInvestmentsTables extends AbstractMigration
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
        $investments = $this->table('investments');
        $investments->addColumn('workspaceId', 'integer', ['signed' => false])
                    ->addColumn('type', 'enum', ['values' => ['ACAO', 'FII', 'CDB', 'TESOURO', 'OUTRO']])
                    ->addColumn('ticker', 'string', ['limit' => 20, 'null' => true])
                    ->addColumn('name', 'string', ['limit' => 100])
                    ->addColumn('quantity', 'decimal', ['precision' => 15, 'scale' => 4, 'default' => 0.0000])
                    ->addColumn('average_price', 'decimal', ['precision' => 15, 'scale' => 4, 'default' => 0.0000])
                    ->addColumn('current_price', 'decimal', ['precision' => 15, 'scale' => 4, 'default' => 0.0000])
                    ->addColumn('currency', 'string', ['limit' => 3, 'default' => 'BRL'])
                    ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                    ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                    ->addForeignKey('workspaceId', 'workspaces', 'WorkspaceId', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                    ->create();
                    
        $transactions = $this->table('investment_transactions');
        $transactions->addColumn('investmentId', 'integer', ['signed' => false])
                     ->addColumn('action', 'enum', ['values' => ['BUY', 'SELL', 'INCOME']])
                     ->addColumn('quantity', 'decimal', ['precision' => 15, 'scale' => 4])
                     ->addColumn('price', 'decimal', ['precision' => 15, 'scale' => 4])
                     ->addColumn('date', 'date')
                     ->addColumn('description', 'string', ['limit' => 255, 'null' => true])
                     ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                     ->addForeignKey('investmentId', 'investments', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                     ->create();
    }
}
