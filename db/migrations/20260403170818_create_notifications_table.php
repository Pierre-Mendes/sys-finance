<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateNotificationsTable extends AbstractMigration
{
    public function change(): void
    {
        $isSqlite = $this->getAdapter()->getAdapterType() === 'sqlite';
        $enumType = $isSqlite ? 'string' : 'enum';

        $table = $this->table('notifications', ['id' => false, 'primary_key' => ['id']]);
        
        $table->addColumn('id', 'integer', ['identity' => true])
              ->addColumn('user_id', 'integer', ['signed' => false])
              ->addColumn('title', 'string', ['limit' => 255])
              ->addColumn('message', 'text')
              ->addColumn('type', $enumType, ['values' => ['SLA_WARNING', 'SYSTEM', 'INVITE'], 'default' => 'SYSTEM'])
              ->addColumn('related_id', 'integer', ['null' => true])
              ->addColumn('read_at', 'timestamp', ['null' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('user_id', 'user', 'UserId', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->create();
    }
}
