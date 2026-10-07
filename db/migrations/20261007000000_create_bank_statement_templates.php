<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Templates de layout de extrato aprendidos pelo HybridAIEngine.
 * A tabela só existia em um .sql solto (nunca aplicado pelo Phinx), então a importação de extratos
 * de layout desconhecido falhava com "table not found". hasTable() mantém instalações onde ela foi criada à mão.
 */
final class CreateBankStatementTemplates extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('bank_statement_templates')) return;

        $this->table('bank_statement_templates')
            ->addColumn('bank_name', 'string', ['limit' => 100])
            ->addColumn('detection_pattern', 'text')
            ->addColumn('row_pattern', 'text')
            ->addColumn('date_format', 'string', ['limit' => 50, 'default' => 'd/m/Y'])
            ->addColumn('column_map', 'json')
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->create();
    }

    public function down(): void
    {
        $this->table('bank_statement_templates')->drop()->save();
    }
}
