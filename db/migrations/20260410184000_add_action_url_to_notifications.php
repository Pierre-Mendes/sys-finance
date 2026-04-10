<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddActionUrlToNotifications extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('notifications');
        $table->addColumn('action_url', 'string', ['limit' => 255, 'null' => true, 'after' => 'related_id'])
              ->update();
    }
}
