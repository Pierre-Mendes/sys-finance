<?php

namespace App\Services;

use DateTimeImmutable;

/**
 * Ciclo de fatura do cartão (regra única usada por compras, fatura, limite e previsão).
 *
 * - Compra ANTES do dia de fechamento entra na fatura que fecha naquele mês;
 *   compra NO dia do fechamento ou depois já vai para a fatura seguinte.
 *   Por isso o "melhor dia de compra" é o próprio dia de fechamento: dá o maior prazo até pagar.
 * - O vencimento é o dia de vencimento logo após o fechamento: no mesmo mês se vencimento > fechamento,
 *   senão no mês seguinte.
 * - A fatura é identificada pelo mês de VENCIMENTO ("fatura de novembro" = vence em novembro).
 * - Dias 29-31 em meses curtos viram o último dia do mês.
 */
final class CardBillingCycle {
    public function __construct(private int $closingDay, private int $dueDay) {}

    /** Mês (1º dia) em que fecha a fatura onde a compra entra. */
    public function closingMonthFor(DateTimeImmutable $purchase): DateTimeImmutable {
        $month = $purchase->modify('first day of this month');
        $closing = self::dayIn($month, $this->closingDay);
        return $purchase->format('Y-m-d') < $closing->format('Y-m-d') ? $month : $month->modify('+1 month');
    }

    /** @return array{closing: DateTimeImmutable, due: DateTimeImmutable, year: int, month: int} fatura da compra */
    public function invoiceFor(DateTimeImmutable $purchase): array {
        return $this->invoiceClosingIn($this->closingMonthFor($purchase));
    }

    /** Fatura que fecha no mês informado (qualquer dia do mês). */
    public function invoiceClosingIn(DateTimeImmutable $month): array {
        $month = $month->modify('first day of this month');
        $closing = self::dayIn($month, $this->closingDay);
        $dueMonth = $this->dueDay > $this->closingDay ? $month : $month->modify('+1 month');
        $due = self::dayIn($dueMonth, $this->dueDay);
        return ['closing' => $closing, 'due' => $due, 'year' => (int) $due->format('Y'), 'month' => (int) $due->format('n')];
    }

    /** Fatura que vence no mês informado. */
    public function invoiceDueIn(int $year, int $month): array {
        $dueMonth = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        return $this->invoiceClosingIn($this->dueDay > $this->closingDay ? $dueMonth : $dueMonth->modify('-1 month'));
    }

    /** Última fatura já fechada até $today (inclusive o dia do fechamento). */
    public function lastClosedInvoice(DateTimeImmutable $today): array {
        $current = $this->invoiceClosingIn($today);
        return $today->format('Y-m-d') >= $current['closing']->format('Y-m-d')
            ? $current
            : $this->invoiceClosingIn($today->modify('first day of this month')->modify('-1 month'));
    }

    /** Fatura aberta agora: a que recebe uma compra feita hoje. */
    public function openInvoice(DateTimeImmutable $today): array {
        return $this->invoiceFor($today);
    }

    public function bestPurchaseDay(): int {
        return $this->closingDay;
    }

    public static function title(string $cardName, int $month, int $year): string {
        return "Fatura {$cardName} ({$month}/{$year})";
    }

    private static function dayIn(DateTimeImmutable $month, int $day): DateTimeImmutable {
        $first = $month->modify('first day of this month');
        return $first->setDate((int) $first->format('Y'), (int) $first->format('n'), min(max($day, 1), (int) $first->format('t')));
    }
}
