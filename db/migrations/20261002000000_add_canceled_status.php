<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Status CANCELED ("desconsiderada"): conta pendente que o usuário decidiu não pagar.
 * Fica no histórico, mas não conta no saldo, na previsão nem nos lembretes.
 * No SQLite (testes) a coluna já é string; só o enum do MySQL precisa mudar.
 */
final class AddCanceledStatus extends AbstractMigration
{
    public function up(): void
    {
        if ($this->getAdapter()->getAdapterType() === 'sqlite') return;
        foreach (['assets', 'bills'] as $table) {
            $this->table($table)
                ->changeColumn('status', 'enum', ['values' => ['PENDING', 'PAID', 'CANCELED'], 'default' => 'PAID', 'null' => false])
                ->update();
        }
    }

    public function down(): void
    {
        if ($this->getAdapter()->getAdapterType() === 'sqlite') return;
        foreach (['assets', 'bills'] as $table) {
            $this->execute("UPDATE {$table} SET status = 'PENDING' WHERE status = 'CANCELED'");
            $this->table($table)
                ->changeColumn('status', 'enum', ['values' => ['PENDING', 'PAID'], 'default' => 'PAID', 'null' => false])
                ->update();
        }
    }
}
