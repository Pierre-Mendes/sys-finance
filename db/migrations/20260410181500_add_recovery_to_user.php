<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddRecoveryToUser extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('user');
        $table->addColumn('SecurityQuestion', 'string', ['null' => true, 'after' => 'Password'])
              ->addColumn('SecurityAnswer', 'string', ['null' => true, 'after' => 'SecurityQuestion'])
              ->update();
    }
}
