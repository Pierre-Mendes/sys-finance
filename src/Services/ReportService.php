<?php

namespace App\Services;

use App\Repositories\ReportRepository;
use DateTimeImmutable;

/**
 * Relatórios mensal/anual e previsão de saldo.
 *
 * Regras:
 * - Receitas, despesas e saldo contam só lançamentos PAID (mesma regra do saldo das contas).
 *   Compras no cartão entram quando a fatura é paga (a fatura é uma despesa).
 * - Percentuais sem base (divisão por zero) voltam null, nunca NaN/Infinity.
 * - "Saldo acumulado" é o saldo real no fim de cada mês (tudo que foi pago até ali).
 */
class ReportService {
    public const FORECAST_HORIZONS = [30, 60, 90];
    private const MONTHS = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
    private const PIE_SLICES = 7;

    public function __construct(private ReportRepository $repo) {}

    public function monthly(int $workspaceId, string $month): array {
        $start = new DateTimeImmutable($month . '-01');
        $end = $start->modify('last day of this month');
        $prevStart = $start->modify('-1 month');

        $rows = $this->repo->paidBetween($workspaceId, $start->modify('-11 months')->format('Y-m-d'), $end->format('Y-m-d'));
        $byMonth = self::sumByMonth($rows);

        $current = $byMonth[$start->format('Y-m')] ?? ['income' => 0.0, 'expense' => 0.0];
        $previous = $byMonth[$prevStart->format('Y-m')] ?? ['income' => 0.0, 'expense' => 0.0];
        $monthRows = array_values(array_filter($rows, fn ($r) => str_starts_with($r['date'], $start->format('Y-m'))));

        // Últimos 12 meses terminando no mês escolhido (não em "hoje").
        $timeline = $this->timeline($workspaceId, $start->modify('-11 months'), 12, $byMonth, true);

        // Média dos 6 meses anteriores (sem o mês atual, para comparar o mês com o seu histórico).
        $history = [];
        for ($i = 6; $i >= 1; $i--) {
            $history[] = $byMonth[$start->modify("-{$i} months")->format('Y-m')] ?? ['income' => 0.0, 'expense' => 0.0];
        }
        $avgIncome = array_sum(array_column($history, 'income')) / 6;
        $avgExpense = array_sum(array_column($history, 'expense')) / 6;
        $avgNet = $avgIncome - $avgExpense;
        $net = $current['income'] - $current['expense'];

        $categories = self::categories($monthRows);
        $kpis = self::kpis($current, $previous);

        return [
            'type' => 'monthly',
            'period' => ['from' => $start->format('Y-m-d'), 'to' => $end->format('Y-m-d'), 'label' => self::monthLabel($start, true)],
            'kpis' => $kpis,
            'insights' => self::insights($kpis, $categories, 'mês anterior', $avgExpense),
            'timeline' => $timeline,
            'categories' => $categories,
            'pies' => self::pies($categories),
            'history' => [
                'months' => 6,
                'avgIncome' => round($avgIncome, 2),
                'avgExpense' => round($avgExpense, 2),
                'avgNet' => round($avgNet, 2),
                'incomeVsAvg' => self::change($current['income'], $avgIncome),
                'expenseVsAvg' => self::change($current['expense'], $avgExpense),
                'netVsAvg' => self::change($net, $avgNet),
            ],
            'byWeekday' => self::byWeekday($monthRows),
            'byMonthDay' => self::byMonthDay($monthRows, (int) $end->format('j')),
            'availableYears' => $this->availableYears($workspaceId, (int) $start->format('Y')),
        ];
    }

    public function annual(int $workspaceId, int $year, DateTimeImmutable $today): array {
        $start = new DateTimeImmutable("{$year}-01-01");
        $end = new DateTimeImmutable("{$year}-12-31");

        $rows = $this->repo->paidBetween($workspaceId, $start->format('Y-m-d'), $end->format('Y-m-d'));
        $prevRows = $this->repo->paidBetween($workspaceId, $start->modify('-1 year')->format('Y-m-d'), $start->modify('-1 day')->format('Y-m-d'));
        $byMonth = self::sumByMonth($rows);

        $current = self::totals($rows);
        $previous = self::totals($prevRows);
        $kpis = self::kpis($current, $previous);

        // Média mensal sobre os meses já vividos do ano (no ano corrente, não divide por 12).
        $months = $year === (int) $today->format('Y') ? (int) $today->format('n') : ($year < (int) $today->format('Y') ? 12 : 1);
        $kpis['monthlyAverage'] = round(($current['income'] - $current['expense']) / $months, 2);

        $categories = self::categories($rows);

        return [
            'type' => 'annual',
            'period' => ['from' => $start->format('Y-m-d'), 'to' => $end->format('Y-m-d'), 'label' => (string) $year],
            'kpis' => $kpis,
            'insights' => self::insights($kpis, $categories, 'ano anterior', null),
            'timeline' => $this->timeline($workspaceId, $start, 12, $byMonth, false),
            'categories' => $categories,
            'pies' => self::pies($categories),
            'availableYears' => $this->availableYears($workspaceId, $year),
        ];
    }

    /**
     * Saldo projetado dia a dia: saldo atual + pendentes (atrasados entram hoje) + próximas recorrências
     * + compras de cartão ainda sem fatura (no vencimento do cartão).
     */
    public function forecast(int $workspaceId, DateTimeImmutable $today, int $days): array {
        $today = $today->setTime(0, 0);
        $horizon = $today->modify("+{$days} days");
        $todayStr = $today->format('Y-m-d');
        $events = [];

        foreach ($this->repo->pendingUntil($workspaceId, $horizon->format('Y-m-d')) as $p) {
            $sign = $p['type'] === 'asset' ? 1 : -1;
            $overdue = $p['date'] < $todayStr;
            $events[] = ['date' => $overdue ? $todayStr : $p['date'], 'title' => $p['title'], 'amount' => $sign * $p['amount'], 'kind' => $overdue ? 'overdue' : 'pending'];

            // A próxima ocorrência só é criada ao pagar; projetamos as seguintes dentro do horizonte.
            $step = ['MONTHLY' => '+1 month', 'YEARLY' => '+1 year'][$p['recurrence']] ?? null;
            if ($step) {
                $next = (new DateTimeImmutable($p['date']))->modify($step);
                while ($next <= $horizon) {
                    if ($next >= $today) {
                        $events[] = ['date' => $next->format('Y-m-d'), 'title' => $p['title'], 'amount' => $sign * $p['amount'], 'kind' => 'recurring'];
                    }
                    $next = $next->modify($step);
                }
            }
        }

        // Cartão: a fatura do mês m vence no dia de vencimento de m (mesma regra do CreditCardService).
        $invoices = array_flip($this->repo->invoiceTitles($workspaceId));
        $byInvoice = [];
        foreach ($this->repo->cardPurchasesBetween($workspaceId, $today->modify('first day of this month')->format('Y-m-d'), $horizon->format('Y-m-d')) as $c) {
            [$y, $m] = array_map('intval', explode('-', substr($c['date'], 0, 7)));
            $title = "Fatura {$c['cardName']} ({$m}/{$y})";
            if (isset($invoices[$title])) continue; // já virou uma conta e está nos pendentes
            $due = (new DateTimeImmutable(sprintf('%04d-%02d-01', $y, $m)));
            $due = $due->setDate($y, $m, min($c['dueDay'], (int) $due->format('t')));
            $byInvoice[$title]['date'] = max($due->format('Y-m-d'), $todayStr);
            $byInvoice[$title]['amount'] = ($byInvoice[$title]['amount'] ?? 0) + $c['amount'];
        }
        foreach ($byInvoice as $title => $inv) {
            if ($inv['date'] <= $horizon->format('Y-m-d')) {
                $events[] = ['date' => $inv['date'], 'title' => $title, 'amount' => -$inv['amount'], 'kind' => 'card'];
            }
        }

        usort($events, fn ($a, $b) => [$a['date'], $a['amount']] <=> [$b['date'], $b['amount']]);

        $start = $this->repo->currentBalance($workspaceId);
        $byDay = [];
        foreach ($events as $e) {
            $byDay[$e['date']] = ($byDay[$e['date']] ?? 0) + $e['amount'];
        }

        $points = [];
        $balance = $start;
        $min = ['balance' => $start, 'date' => $todayStr];
        $firstNegative = null;
        for ($d = $today; $d <= $horizon; $d = $d->modify('+1 day')) {
            $key = $d->format('Y-m-d');
            $balance += $byDay[$key] ?? 0;
            $points[] = ['date' => $key, 'balance' => round($balance, 2)];
            if ($balance < $min['balance']) $min = ['balance' => round($balance, 2), 'date' => $key];
            if ($balance < 0 && $firstNegative === null) $firstNegative = $key;
        }

        $incoming = array_sum(array_map(fn ($e) => max($e['amount'], 0), $events));
        $outgoing = -array_sum(array_map(fn ($e) => min($e['amount'], 0), $events));

        return [
            'days' => $days,
            'startBalance' => round($start, 2),
            'endBalance' => round($balance, 2),
            'incoming' => round($incoming, 2),
            'outgoing' => round($outgoing, 2),
            'min' => $min,
            'firstNegativeDate' => $firstNegative,
            'points' => $points,
            'events' => array_map(fn ($e) => array_merge($e, ['amount' => round($e['amount'], 2)]), array_slice($events, 0, 50)),
        ];
    }

    // ---------------------------------------------------------------- helpers

    private function timeline(int $workspaceId, DateTimeImmutable $first, int $count, array $byMonth, bool $withYear): array {
        $balance = $this->repo->paidNetBefore($workspaceId, $first->format('Y-m-d'));
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $m = $first->modify("+{$i} months");
            $sums = $byMonth[$m->format('Y-m')] ?? ['income' => 0.0, 'expense' => 0.0];
            $net = $sums['income'] - $sums['expense'];
            $balance += $net;
            $out[] = [
                'period' => $m->format('Y-m'),
                'label' => self::monthLabel($m, $withYear),
                'income' => round($sums['income'], 2),
                'expense' => round($sums['expense'], 2),
                'net' => round($net, 2),
                'balance' => round($balance, 2),
            ];
        }
        return $out;
    }

    private static function sumByMonth(array $rows): array {
        $out = [];
        foreach ($rows as $r) {
            $key = substr($r['date'], 0, 7);
            $out[$key] ??= ['income' => 0.0, 'expense' => 0.0];
            $out[$key][$r['type'] === 'asset' ? 'income' : 'expense'] += $r['amount'];
        }
        return $out;
    }

    private static function totals(array $rows): array {
        $t = ['income' => 0.0, 'expense' => 0.0];
        foreach ($rows as $r) {
            $t[$r['type'] === 'asset' ? 'income' : 'expense'] += $r['amount'];
        }
        return $t;
    }

    private static function kpis(array $current, array $previous): array {
        $net = $current['income'] - $current['expense'];
        $prevNet = $previous['income'] - $previous['expense'];
        return [
            'income' => round($current['income'], 2),
            'expense' => round($current['expense'], 2),
            'balance' => round($net, 2),
            'savingsRate' => $current['income'] > 0 ? round($net / $current['income'] * 100, 1) : null,
            'incomeChange' => self::change($current['income'], $previous['income']),
            'expenseChange' => self::change($current['expense'], $previous['expense']),
            'balanceChange' => self::change($net, $prevNet),
        ];
    }

    /** Variação % sobre a base; null quando não há base (evita NaN/Infinity e "+100%" enganoso). */
    public static function change(float $current, float $base): ?float {
        if (abs($base) < 0.005) return null;
        return round(($current - $base) / abs($base) * 100, 1);
    }

    private static function categories(array $rows): array {
        $byCat = [];
        foreach ($rows as $r) {
            $byCat[$r['category']] ??= ['name' => $r['category'], 'income' => 0.0, 'expense' => 0.0];
            $byCat[$r['category']][$r['type'] === 'asset' ? 'income' : 'expense'] += $r['amount'];
        }
        $totalIn = array_sum(array_column($byCat, 'income'));
        $totalOut = array_sum(array_column($byCat, 'expense'));

        $out = array_map(fn ($c) => [
            'name' => $c['name'],
            'income' => round($c['income'], 2),
            'expense' => round($c['expense'], 2),
            'net' => round($c['income'] - $c['expense'], 2),
            'incomeShare' => $totalIn > 0 ? round($c['income'] / $totalIn * 100, 1) : 0.0,
            'expenseShare' => $totalOut > 0 ? round($c['expense'] / $totalOut * 100, 1) : 0.0,
        ], array_values($byCat));

        usort($out, fn ($a, $b) => ($b['expense'] + $b['income']) <=> ($a['expense'] + $a['income']));
        return $out;
    }

    /** Fatias por categoria; acima de 7, o resto vira "Outras". Categorias com valor zero ficam de fora. */
    private static function pies(array $categories): array {
        $pie = function (string $field) use ($categories): array {
            $items = array_values(array_filter($categories, fn ($c) => $c[$field] > 0));
            usort($items, fn ($a, $b) => $b[$field] <=> $a[$field]);
            $slices = array_map(fn ($c) => ['name' => $c['name'], 'value' => $c[$field]], array_slice($items, 0, self::PIE_SLICES));
            $rest = array_sum(array_map(fn ($c) => $c[$field], array_slice($items, self::PIE_SLICES)));
            if ($rest > 0) $slices[] = ['name' => 'Outras', 'value' => round($rest, 2)];
            return $slices;
        };
        return ['expense' => $pie('expense'), 'income' => $pie('income')];
    }

    private static function byWeekday(array $rows): array {
        $days = array_fill(0, 7, 0.0); // 0 = domingo
        foreach ($rows as $r) {
            if ($r['type'] === 'bill') $days[(int) (new DateTimeImmutable($r['date']))->format('w')] += $r['amount'];
        }
        return array_map(fn ($v) => round($v, 2), $days);
    }

    private static function byMonthDay(array $rows, int $daysInMonth): array {
        $days = array_fill(1, $daysInMonth, 0.0);
        foreach ($rows as $r) {
            if ($r['type'] === 'bill') $days[(int) substr($r['date'], 8, 2)] += $r['amount'];
        }
        $out = [];
        foreach ($days as $day => $v) $out[] = ['day' => $day, 'expense' => round($v, 2)];
        return $out;
    }

    /**
     * Até 3 avisos em texto, do mais importante para o menos.
     *
     * @return array<int, array{tone: string, title: string, message: string}>
     */
    private static function insights(array $kpis, array $categories, string $previousLabel, ?float $avgExpense): array {
        $out = [];
        $money = fn (float $v) => 'R$ ' . number_format(abs($v), 2, ',', '.');
        $pct = fn (float $v) => str_replace('.', ',', (string) round(abs($v), 1)) . '%';

        if ($kpis['income'] > 0 || $kpis['expense'] > 0) {
            if ($kpis['balance'] < 0) {
                $out[] = ['tone' => 'negative', 'title' => 'Gastos acima da receita', 'message' => "Você gastou {$money($kpis['balance'])} a mais do que recebeu no período."];
            } elseif ($kpis['savingsRate'] !== null && $kpis['savingsRate'] >= 20) {
                $out[] = ['tone' => 'positive', 'title' => 'Boa taxa de poupança', 'message' => "Você guardou {$pct($kpis['savingsRate'])} do que recebeu."];
            } elseif ($kpis['savingsRate'] !== null) {
                $out[] = ['tone' => 'info', 'title' => 'Poupança abaixo de 20%', 'message' => "Você guardou {$pct($kpis['savingsRate'])} do que recebeu. A referência comum é 20%."];
            }
        }

        if ($kpis['expenseChange'] !== null && $kpis['expenseChange'] >= 10) {
            $out[] = ['tone' => 'warning', 'title' => 'Despesas em alta', 'message' => "As despesas subiram {$pct($kpis['expenseChange'])} em relação ao {$previousLabel}."];
        } elseif ($kpis['expenseChange'] !== null && $kpis['expenseChange'] <= -10) {
            $out[] = ['tone' => 'positive', 'title' => 'Despesas em queda', 'message' => "As despesas caíram {$pct($kpis['expenseChange'])} em relação ao {$previousLabel}."];
        } elseif ($avgExpense !== null && $avgExpense > 0 && $kpis['expense'] > $avgExpense * 1.2) {
            $out[] = ['tone' => 'warning', 'title' => 'Mês acima da média', 'message' => "As despesas estão {$pct(self::change($kpis['expense'], $avgExpense))} acima da média dos últimos 6 meses."];
        }

        $topExpense = array_values(array_filter($categories, fn ($c) => $c['expense'] > 0));
        usort($topExpense, fn ($a, $b) => $b['expense'] <=> $a['expense']);
        if (count($topExpense) >= 2 && $topExpense[0]['expenseShare'] >= 40) {
            $out[] = ['tone' => 'info', 'title' => 'Categoria dominante', 'message' => "{$topExpense[0]['name']} concentra {$pct($topExpense[0]['expenseShare'])} das despesas."];
        }

        return array_slice($out, 0, 3);
    }

    private function availableYears(int $workspaceId, int $selected): array {
        $range = $this->repo->yearRange($workspaceId);
        $years = $range ? range($range[0], $range[1]) : [];
        $years[] = $selected;
        $years[] = (int) date('Y');
        $years = array_values(array_unique($years));
        rsort($years);
        return $years;
    }

    private static function monthLabel(DateTimeImmutable $d, bool $withYear): string {
        $m = self::MONTHS[(int) $d->format('n') - 1];
        return $withYear ? $m . '/' . $d->format('y') : $m;
    }
}
