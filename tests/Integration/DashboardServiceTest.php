<?php

namespace Tests\Integration;

use App\Services\DashboardService;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    public function test_workspace_filter_uses_bound_parameters(): void
    {
        // O SQL do Dashboard é específico de MySQL; aqui validamos só o helper de placeholders.
        $service = new DashboardService($this->db);
        $select = (new \ReflectionClass($service))->getMethod('select');
        $select->setAccessible(true);

        $this->db->exec("INSERT INTO account (WorkspaceId, AccountName) VALUES (1, 'A'), (2, 'B'), (3, 'C')");

        $stmt = $select->invoke($service, [1, 3], "SELECT AccountName FROM account WHERE WorkspaceId IN ({ws}) OR WorkspaceId IN ({ws}) ORDER BY AccountName");
        $this->assertSame(['A', 'C'], $stmt->fetchAll(\PDO::FETCH_COLUMN));

        $stmt = $select->invoke($service, ["1) OR (1=1"], "SELECT COUNT(*) FROM account WHERE WorkspaceId IN ({ws})");
        $this->assertSame(0, (int) $stmt->fetchColumn(), 'Valor malicioso deve ser tratado como dado, não como SQL');
    }

    public function test_paid_totals_ignore_pending_transactions(): void
    {
        $this->db->exec("INSERT INTO assets (WorkspaceId, Title, Date, CategoryId, AccountId, Amount, status) VALUES (1, 'Salário', '2026-09-01', 1, 1, 5000, 'PAID')");
        $this->db->exec("INSERT INTO bills (WorkspaceId, Title, Dates, CategoryId, AccountId, Amount, status) VALUES
            (1, 'Aluguel pago', '2026-09-05', 1, 1, 1500, 'PAID'),
            (1, 'Aluguel do mês que vem', '2026-10-05', 1, 1, 1500, 'PENDING')");

        $totals = (new DashboardService($this->db))->paidTotals([1]);

        $this->assertSame(5000.0, $totals['income']);
        $this->assertSame(1500.0, $totals['expense'], 'Despesa pendente não pode reduzir o saldo atual');
    }
}
