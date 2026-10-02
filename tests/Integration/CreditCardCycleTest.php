<?php

namespace Tests\Integration;

use App\DTO\CreditCardDTO;
use App\DTO\CreditCardTransactionDTO;
use App\Repositories\AccountRepository;
use App\Repositories\AssetRepository;
use App\Repositories\BillRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\CreditCardRepository;
use App\Repositories\CreditCardTransactionRepository;
use App\Services\CreditCardService;
use App\Services\ReferenceResolver;
use App\Services\TransactionService;
use App\Services\WorkspaceService;
use DateTimeImmutable;
use Tests\TestCase;

class CreditCardCycleTest extends TestCase
{
    private CreditCardService $cards;
    private BillRepository $bills;
    private TransactionService $tx;
    private int $ws;
    private int $cardId;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['DELETE FROM bills', 'DELETE FROM credit_card_transactions', 'DELETE FROM credit_cards', 'DELETE FROM account', 'DELETE FROM category'] as $sql) {
            $this->db->exec($sql);
        }
        $categoryRepo = new CategoryRepository($this->db);
        $resolver = new ReferenceResolver(new AccountRepository($this->db), $categoryRepo);
        $this->bills = new BillRepository($this->db);
        $this->cards = new CreditCardService(new CreditCardRepository($this->db), new CreditCardTransactionRepository($this->db), $this->bills, $categoryRepo, $resolver);
        $workspaces = new WorkspaceService($this->db);
        $this->tx = new TransactionService(new AssetRepository($this->db), $this->bills, $this->db, $resolver, $workspaces);
        $this->ws = $workspaces->createDefaultWorkspace(1, 'Ana');

        $this->cardId = (int) $this->cards->createCard($this->ws, new CreditCardDTO([
            'name' => 'Roxinho', 'accountName' => 'Nubank', 'limitAmount' => 5000, 'closingDay' => 28, 'dueDay' => 5,
        ]))->getId();
    }

    private function buy(string $title, float $amount, string $date): void
    {
        $this->cards->addTransaction($this->ws, new CreditCardTransactionDTO([
            'cardId' => $this->cardId, 'title' => $title, 'amount' => $amount, 'date' => $date, 'categoryName' => 'Compras',
        ]));
    }

    public function test_invoice_uses_closing_day_not_calendar_month(): void
    {
        $this->buy('Mercado', 300, '2026-09-29'); // depois do fechamento de set → fatura que vence 05/11
        $this->buy('Farmácia', 100, '2026-10-10');
        $this->buy('Sapato', 200, '2026-10-28');  // no fechamento → vence 05/12

        $bill = $this->cards->generateInvoice($this->cardId, $this->ws, null, null, new DateTimeImmutable('2026-10-30'));

        $this->assertNotNull($bill);
        $this->assertSame('Fatura Roxinho (11/2026)', $bill->getTitle());
        $this->assertSame(400.0, $bill->getAmount(), 'Mercado (29/09) + Farmácia; o Sapato é da próxima');
        $this->assertSame('2026-11-05', $bill->getDueDate());
        $this->assertSame('2026-10-28', $bill->getDate());
    }

    public function test_paid_invoice_frees_the_limit_and_open_invoice_is_reported(): void
    {
        $this->buy('Mercado', 300, '2026-10-10');  // vence 05/11
        $this->buy('Sapato', 200, '2026-10-29');   // vence 05/12

        $bill = $this->cards->generateInvoice($this->cardId, $this->ws, 11, 2026);
        $card = $this->cards->getAllCards($this->ws, new DateTimeImmutable('2026-10-30'))[0];
        $this->assertSame(500.0, $card['usedAmount'], 'Fatura gerada mas não paga ainda ocupa o limite');
        $this->assertSame(28, $card['bestPurchaseDay']);
        $this->assertSame(['closingDate' => '2026-11-28', 'dueDate' => '2026-12-05', 'total' => 200.0], $card['openInvoice']);

        $this->tx->pay((int) $bill->getId(), $this->ws, 'bill');
        $card = $this->cards->getAllCards($this->ws, new DateTimeImmutable('2026-10-30'))[0];
        $this->assertSame(200.0, $card['usedAmount'], 'Pagar a fatura libera o limite');
    }
}
