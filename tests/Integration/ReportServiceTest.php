<?php

namespace Tests\Integration;

use App\Controllers\ReportController;
use App\DTO\TransactionDTO;
use App\Repositories\AccountRepository;
use App\Repositories\AssetRepository;
use App\Repositories\BillRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ReportRepository;
use App\Services\ReferenceResolver;
use App\Services\ReportService;
use App\Services\TransactionService;
use App\Services\WorkspaceService;
use DateTimeImmutable;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    private TransactionService $tx;
    private ReportService $reports;
    private int $ws;

    protected function setUp(): void
    {
        parent::setUp();
        // O banco temporário é compartilhado pelos testes da classe.
        foreach ([
            'DELETE FROM bills', 'DELETE FROM assets', 'DELETE FROM totals', 'DELETE FROM account', 'DELETE FROM category',
            'DELETE FROM credit_card_transactions', 'DELETE FROM credit_cards', 'DELETE FROM workspace_users', 'DELETE FROM workspaces',
        ] as $sql) {
            $this->db->exec($sql);
        }
        $workspaces = new WorkspaceService($this->db);
        $this->tx = new TransactionService(
            new AssetRepository($this->db), new BillRepository($this->db), $this->db,
            new ReferenceResolver(new AccountRepository($this->db), new CategoryRepository($this->db)), $workspaces
        );
        $this->reports = new ReportService(new ReportRepository($this->db));
        $this->ws = $workspaces->createDefaultWorkspace(1, 'Ana');
    }

    private function add(string $type, string $title, float $amount, string $date, string $category, string $status = 'PAID', array $extra = []): void
    {
        $this->tx->create($this->ws, new TransactionDTO(array_merge([
            'type' => $type, 'title' => $title, 'amount' => $amount, 'date' => $date,
            'accountName' => 'Banco', 'categoryName' => $category, 'status' => $status,
        ], $extra)), 1);
    }

    public function test_monthly_counts_only_paid_and_compares_with_previous_month(): void
    {
        $this->add('asset', 'Salário', 5000, '2025-12-05', 'Salário');           // histórico antigo
        $this->add('asset', 'Salário', 4000, '2026-08-05', 'Salário');
        $this->add('bill', 'Mercado', 1000, '2026-08-10', 'Alimentação');
        $this->add('asset', 'Salário', 5000, '2026-09-05', 'Salário');           // sexta-feira
        $this->add('bill', 'Aluguel', 1500, '2026-09-06', 'Moradia');            // domingo
        $this->add('bill', 'Mercado', 500, '2026-09-20', 'Alimentação');         // domingo
        $this->add('bill', 'Luz', 300, '2026-09-25', 'Moradia', 'PENDING');      // pendente: fora

        $r = $this->reports->monthly($this->ws, '2026-09');

        $this->assertSame(5000.0, $r['kpis']['income']);
        $this->assertSame(2000.0, $r['kpis']['expense']);
        $this->assertSame(3000.0, $r['kpis']['balance']);
        $this->assertSame(60.0, $r['kpis']['savingsRate']);
        $this->assertSame(25.0, $r['kpis']['incomeChange']);
        $this->assertSame(100.0, $r['kpis']['expenseChange']);

        // 12 meses terminando no mês escolhido, com o saldo acumulado real (inclui dez/2025).
        $this->assertCount(12, $r['timeline']);
        $this->assertSame('2025-10', $r['timeline'][0]['period']);
        $this->assertSame('2026-09', $r['timeline'][11]['period']);
        $this->assertSame('set/26', $r['timeline'][11]['label']);
        $this->assertSame(5000.0, $r['timeline'][2]['balance']);
        $this->assertSame(11000.0, $r['timeline'][11]['balance']);

        $moradia = array_values(array_filter($r['categories'], fn ($c) => $c['name'] === 'Moradia'))[0];
        $this->assertSame(1500.0, $moradia['expense']);
        $this->assertSame(75.0, $moradia['expenseShare']);

        $this->assertSame(2000.0, $r['byWeekday'][0], 'Domingo');
        $this->assertCount(30, $r['byMonthDay']);
        $this->assertSame(1500.0, $r['byMonthDay'][5]['expense']);

        $dominant = array_values(array_filter($r['insights'], fn ($i) => $i['title'] === 'Categoria dominante'))[0];
        $this->assertSame('Moradia concentra 75% das despesas.', $dominant['message']);
        $this->assertStringContainsString('guardou 60%', $r['insights'][0]['message']);
    }

    public function test_percentages_without_base_are_null_instead_of_nan(): void
    {
        $this->add('asset', 'Salário', 7000, '2026-09-05', 'Salário');

        $r = $this->reports->monthly($this->ws, '2026-09');

        $this->assertNull($r['kpis']['incomeChange'], 'Sem mês anterior não há variação (o Recta mostrava 100%)');
        $this->assertNull($r['history']['incomeVsAvg'], 'Sem histórico não há comparação (o Recta mostrava NaN%)');
        $this->assertSame([], $r['pies']['expense'], 'Sem despesas, nada de "Salário R$ 0,00" na pizza de despesas');
        $json = json_encode($r);
        $this->assertNotFalse($json);
        $this->assertStringNotContainsString('NaN', $json);
        $this->assertNull(ReportService::change(10, 0));
    }

    public function test_annual_accumulates_balance_and_averages_over_elapsed_months(): void
    {
        $this->add('asset', 'Salário', 1000, '2025-06-01', 'Salário');
        $this->add('asset', 'Salário', 3000, '2026-01-10', 'Salário');
        $this->add('bill', 'Mercado', 600, '2026-02-10', 'Alimentação');

        $r = $this->reports->annual($this->ws, 2026, new DateTimeImmutable('2026-03-15'));

        $this->assertSame(['jan', 'fev', 'mar'], array_column(array_slice($r['timeline'], 0, 3), 'label'));
        $this->assertSame(4000.0, $r['timeline'][0]['balance'], 'Acumulado parte do saldo anterior ao ano');
        $this->assertSame(3400.0, $r['timeline'][11]['balance'], 'Não zera nos meses sem movimento');
        $this->assertSame(800.0, $r['kpis']['monthlyAverage'], '2400 em 3 meses vividos, não em 12');
        $this->assertContains(2025, $r['availableYears']);
    }

    public function test_forecast_includes_overdue_recurrences_and_card_purchases_once(): void
    {
        $this->add('asset', 'Salário', 2000, '2026-09-01', 'Salário');
        $this->add('bill', 'Condomínio', 300, '2026-09-25', 'Moradia', 'PENDING', ['due_date' => '2026-09-28']);          // atrasada
        $this->add('bill', 'Aluguel', 1500, '2026-10-01', 'Moradia', 'PENDING', ['due_date' => '2026-10-10', 'recurrence_type' => 'MONTHLY']);
        $this->add('asset', 'Freela', 500, '2026-10-20', 'Freela', 'PENDING');

        $this->db->exec("INSERT INTO credit_cards (CardId, WorkspaceId, AccountId, Name, LimitAmount, ClosingDay, DueDay) VALUES (7, {$this->ws}, 1, 'Roxinho', 5000, 5, 15)");
        $this->db->exec("INSERT INTO credit_card_transactions (CardId, WorkspaceId, CategoryId, Title, Amount, Date) VALUES (7, {$this->ws}, 1, 'Notebook 1/2', 400, '2026-10-03'), (7, {$this->ws}, 1, 'Notebook 2/2', 400, '2026-11-03')");
        // Fatura de outubro já gerada: vira conta pendente e não pode ser contada de novo pelas compras.
        $this->add('bill', 'Fatura Roxinho (10/2026)', 400, '2026-10-05', 'Cartão de Crédito', 'PENDING', ['due_date' => '2026-10-15']);

        $f = $this->reports->forecast($this->ws, new DateTimeImmutable('2026-10-01'), 60);

        $this->assertSame(2000.0, $f['startBalance']);
        $this->assertCount(61, $f['points']);
        $this->assertSame(1700.0, $f['points'][0]['balance'], 'Conta atrasada entra hoje');
        $byTitle = array_count_values(array_column($f['events'], 'title'));
        $this->assertSame(2, $byTitle['Aluguel'], '10/10 e a recorrência de 10/11');
        $this->assertSame(1, $byTitle['Fatura Roxinho (10/2026)']);
        $this->assertSame(1, $byTitle['Fatura Roxinho (11/2026)'], 'Compra de novembro ainda sem fatura entra no vencimento');
        // 2000 - 300 - 1500 - 400 + 500 - 1500 - 400 = -1600
        $this->assertSame(-1600.0, $f['endBalance']);
        $this->assertSame('2026-10-15', $f['firstNegativeDate'], '200 - fatura de 400');
        $this->assertSame(-1600.0, $f['min']['balance']);
    }

    public function test_period_validation_rejects_malformed_input(): void
    {
        $this->assertSame(['type' => 'monthly', 'month' => '2026-09'], ReportController::period(['type' => 'monthly', 'month' => '2026-09']));
        $this->assertSame(['type' => 'annual', 'year' => 2026], ReportController::period(['type' => 'annual', 'year' => '2026']));
        foreach ([['month' => '2026-13'], ['month' => "2026-09' OR 1=1"], ['type' => 'annual', 'year' => '20x6'], ['type' => 'weekly']] as $q) {
            $this->assertNull(ReportController::period($q), json_encode($q));
        }
    }

    public function test_summary_csv_quotes_amounts_and_neutralizes_formulas(): void
    {
        $this->add('asset', 'Salário', 7000, '2026-09-05', 'Salário');
        $this->add('bill', 'Hack', 10, '2026-09-06', '=HYPERLINK("x")');

        $controller = new ReportController($this->tx, $this->reports);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/reports/summary/csv')
            ->withQueryParams(['type' => 'monthly', 'month' => '2026-09'])
            ->withAttribute('workspaceId', $this->ws);
        $csv = (string) $controller->summaryCsv($request, new Response())->getBody();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString("Receitas;7000,00\n", $csv, 'Separador ; e vírgula decimal não desalinham colunas');
        $this->assertStringContainsString("\"'=HYPERLINK(\"\"x\"\")\"", $csv);
        foreach (array_filter(explode("\n", trim(substr($csv, 3)))) as $line) {
            $this->assertLessThanOrEqual(6, count(str_getcsv($line, ';', '"', '')), $line);
        }
    }

    public function test_forecast_leaves_out_bills_overdue_for_over_30_days_and_reports_them(): void
    {
        $this->add('asset', 'Salário', 1000, '2026-09-01', 'Salário');
        $this->add('bill', 'IPTU esquecido', 900, '2026-06-01', 'Impostos', 'PENDING', ['due_date' => '2026-06-10']);
        $this->add('bill', 'Luz', 100, '2026-09-25', 'Moradia', 'PENDING', ['due_date' => '2026-09-28']);

        $f = $this->reports->forecast($this->ws, new DateTimeImmutable('2026-10-01'), 30);

        $this->assertSame(900.0, $f['endBalance'], 'Só a Luz (atrasada há 3 dias) entra');
        $this->assertSame(['count' => 1, 'amount' => -900.0, 'olderThanDays' => 30], $f['stale']);
        $this->assertNotContains('IPTU esquecido', array_column($f['events'], 'title'));
    }
}
