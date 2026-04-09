<?php

use Phinx\Migration\AbstractMigration;

class CreateRateLimitsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('rate_limits', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'integer', ['identity' => true])
              ->addColumn('ip_address', 'string', ['limit' => 45])
              ->addColumn('endpoint', 'string', ['limit' => 100])
              ->addColumn('attempts', 'integer', ['default' => 1])
              ->addColumn('last_attempt', 'datetime')
              ->addIndex(['ip_address', 'endpoint'])
              ->create();
    }
}
