<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Lembretes de contas a vencer: preferências por usuário, dispositivos com Web Push
 * e o registro do que já foi enviado (para não repetir o mesmo aviso).
 */
final class CreateBillRemindersTables extends AbstractMigration
{
    public function change(): void
    {
        $this->table('notification_settings', ['id' => false, 'primary_key' => ['user_id']])
            ->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('remind_days', 'string', ['limit' => 20, 'null' => false, 'default' => '3,1,0'])
            ->addColumn('notify_overdue', 'boolean', ['null' => false, 'default' => true])
            ->addColumn('push_enabled', 'boolean', ['null' => false, 'default' => true])
            ->addColumn('reminder_hour', 'integer', ['null' => false, 'default' => 8])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->create();

        // O endpoint pode passar de 500 caracteres: a unicidade fica no hash SHA-256.
        $this->table('push_subscriptions')
            ->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('endpoint', 'text', ['null' => false])
            ->addColumn('endpoint_hash', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('p256dh', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('auth', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('user_agent', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addIndex(['endpoint_hash'], ['unique' => true])
            ->addIndex(['user_id'])
            ->create();

        $this->table('bill_reminders_sent')
            ->addColumn('bill_id', 'integer', ['null' => false])
            ->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('kind', 'string', ['limit' => 10, 'null' => false])
            ->addColumn('due_date', 'date', ['null' => false])
            ->addColumn('sent_at', 'datetime', ['null' => false])
            ->addIndex(['bill_id', 'user_id', 'kind', 'due_date'], ['unique' => true, 'name' => 'uniq_bill_reminder'])
            ->create();
    }
}
