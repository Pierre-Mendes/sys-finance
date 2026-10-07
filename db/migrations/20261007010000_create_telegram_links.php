<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Vínculo entre um usuário do sistema e o chat dele com o bot do Telegram.
 * O vínculo nasce de um código de uso único gerado em Configurações (válido por 15 min) e enviado ao bot.
 * workspace_id é o espaço em que o bot lança/consulta; last_tx_* permite o /desfazer.
 */
final class CreateTelegramLinks extends AbstractMigration
{
    public function change(): void
    {
        $this->table('telegram_links')
            ->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('workspace_id', 'integer', ['null' => false])
            ->addColumn('chat_id', 'biginteger', ['null' => true])
            ->addColumn('telegram_username', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('link_code', 'string', ['limit' => 16, 'null' => true])
            ->addColumn('link_code_expires_at', 'datetime', ['null' => true])
            ->addColumn('last_tx_type', 'string', ['limit' => 10, 'null' => true])
            ->addColumn('last_tx_id', 'integer', ['null' => true])
            ->addColumn('last_tx_at', 'datetime', ['null' => true])
            ->addColumn('linked_at', 'datetime', ['null' => true])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addIndex(['user_id'], ['unique' => true])
            ->addIndex(['chat_id'], ['unique' => true])
            ->addIndex(['link_code'], ['unique' => true])
            ->create();
    }
}
