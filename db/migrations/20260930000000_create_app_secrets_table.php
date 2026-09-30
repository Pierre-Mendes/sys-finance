<?php

use Phinx\Migration\AbstractMigration;

/**
 * Segredos gerados pela própria aplicação (ex.: chave de assinatura do JWT),
 * para não exigir configuração manual a cada deploy.
 */
class CreateAppSecretsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('app_secrets', ['id' => false, 'primary_key' => ['name']]);
        $table->addColumn('name', 'string', ['limit' => 64])
              ->addColumn('value', 'string', ['limit' => 255])
              ->addColumn('created_at', 'datetime')
              ->create();
    }
}
