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
        // 'null' => false é obrigatório: o Phinx cria colunas NULL por padrão e o MySQL
        // recusa chave primária anulável (erro 1171).
        $table->addColumn('name', 'string', ['limit' => 64, 'null' => false])
              ->addColumn('value', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('created_at', 'datetime', ['null' => false])
              ->create();
    }
}
