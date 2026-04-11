<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddAccountAndFavoriteToGoals extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('goals');
        $table->addColumn('AccountId', 'integer', ['signed' => false, 'null' => true, 'after' => 'SharedWithWorkspaceId'])
              ->addColumn('IsFavorite', 'boolean', ['default' => false, 'after' => 'AccountId'])
              ->addForeignKey('AccountId', 'bank_accounts', 'AccountId', ['delete'=> 'SET_NULL', 'update'=> 'CASCADE'])
              ->update();
    }
}
