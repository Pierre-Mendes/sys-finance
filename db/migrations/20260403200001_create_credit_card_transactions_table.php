<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCreditCardTransactionsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('credit_card_transactions');
        $table->addColumn('CardId', 'integer', ['signed' => false])
              ->addColumn('WorkspaceId', 'integer', ['signed' => false])
              ->addColumn('CategoryId', 'integer', ['signed' => false])
              ->addColumn('Title', 'string', ['limit' => 255])
              ->addColumn('Description', 'text', ['null' => true])
              ->addColumn('Amount', 'decimal', ['precision' => 10, 'scale' => 2])
              ->addColumn('Installments', 'integer', ['default' => 1])
              ->addColumn('CurrentInstallment', 'integer', ['default' => 1])
              ->addColumn('Date', 'date')
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('CardId', 'credit_cards', 'CardId', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
              ->addForeignKey('WorkspaceId', 'workspaces', 'WorkspaceId', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
              ->addForeignKey('CategoryId', 'category', 'CategoryId', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
              ->create();
    }
}
