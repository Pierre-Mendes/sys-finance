<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCreditCardsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('credit_cards', ['id' => 'CardId']);
        $table->addColumn('WorkspaceId', 'integer', ['signed' => false])
              ->addColumn('AccountId', 'integer', ['signed' => false])
              ->addColumn('Name', 'string', ['limit' => 255])
              ->addColumn('Brand', 'string', ['limit' => 50, 'null' => true])
              ->addColumn('LimitAmount', 'decimal', ['precision' => 10, 'scale' => 2])
              ->addColumn('ClosingDay', 'integer')
              ->addColumn('DueDay', 'integer')
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('WorkspaceId', 'workspaces', 'WorkspaceId', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
              ->addForeignKey('AccountId', 'account', 'AccountId', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
              ->create();
    }
}
