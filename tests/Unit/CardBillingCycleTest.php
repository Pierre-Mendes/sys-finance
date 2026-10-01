<?php

namespace Tests\Unit;

use App\Services\CardBillingCycle;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class CardBillingCycleTest extends TestCase
{
    private function due(CardBillingCycle $c, string $purchase): string
    {
        return $c->invoiceFor(new DateTimeImmutable($purchase))['due']->format('Y-m-d');
    }

    public function test_purchase_before_closing_goes_to_current_invoice_and_on_closing_day_to_the_next(): void
    {
        $nubank = new CardBillingCycle(3, 10); // fecha dia 3, vence dia 10
        $this->assertSame('2026-10-10', $this->due($nubank, '2026-10-02'));
        $this->assertSame('2026-11-10', $this->due($nubank, '2026-10-03'), 'No dia do fechamento já é a próxima fatura');
        $this->assertSame('2026-11-10', $this->due($nubank, '2026-10-25'));
        $this->assertSame(3, $nubank->bestPurchaseDay());
    }

    public function test_due_day_before_closing_day_means_due_next_month(): void
    {
        $card = new CardBillingCycle(28, 5); // fecha 28, vence 5 do mês seguinte
        $this->assertSame('2026-11-05', $this->due($card, '2026-10-20'));
        $this->assertSame('2026-12-05', $this->due($card, '2026-10-28'));
        $this->assertSame('2027-02-05', $this->due($card, '2026-12-30'), 'Depois do fechamento de dez: fecha 28/01, vence 05/02');
        $this->assertSame('2027-01-05', $this->due($card, '2026-12-20'), 'Virada de ano');
    }

    public function test_days_beyond_month_length_use_the_last_day(): void
    {
        $card = new CardBillingCycle(31, 10);
        $inv = $card->invoiceFor(new DateTimeImmutable('2026-02-27'));
        $this->assertSame('2026-02-28', $inv['closing']->format('Y-m-d'));
        $this->assertSame('2026-03-10', $inv['due']->format('Y-m-d'));
    }

    public function test_invoice_by_due_month_and_last_closed(): void
    {
        $card = new CardBillingCycle(28, 5);
        $this->assertSame('2026-10-28', $card->invoiceDueIn(2026, 11)['closing']->format('Y-m-d'));
        $this->assertSame('2026-09-28', $card->lastClosedInvoice(new DateTimeImmutable('2026-10-15'))['closing']->format('Y-m-d'));
        $this->assertSame('2026-10-28', $card->lastClosedInvoice(new DateTimeImmutable('2026-10-28'))['closing']->format('Y-m-d'));
        $this->assertSame('Fatura Roxinho (11/2026)', CardBillingCycle::title('Roxinho', 11, 2026));
    }
}
