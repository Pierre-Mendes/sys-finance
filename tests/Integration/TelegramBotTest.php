<?php

namespace Tests\Integration;

use App\Controllers\TelegramController;
use App\Models\Account;
use App\Repositories\AccountRepository;
use App\Repositories\AssetRepository;
use App\Repositories\BillRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ReportRepository;
use App\Repositories\TelegramLinkRepository;
use App\Services\ReferenceResolver;
use App\Services\TransactionService;
use App\Services\WorkspaceService;
use App\Telegram\TelegramBot;
use DateTimeImmutable;
use DateTimeZone;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Response;
use Tests\TestCase;

class TelegramBotTest extends TestCase
{
    private const CHAT = 555000111;

    private TelegramBot $bot;
    private TelegramLinkRepository $links;
    private TransactionService $tx;
    private WorkspaceService $workspaces;
    private AccountRepository $accounts;
    private int $ws;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = new DateTimeImmutable('2026-10-07 10:00:00', new DateTimeZone('America/Sao_Paulo'));
        $this->accounts = new AccountRepository($this->db);
        $categories = new CategoryRepository($this->db);
        $this->workspaces = new WorkspaceService($this->db);
        $this->tx = new TransactionService(
            new AssetRepository($this->db), new BillRepository($this->db), $this->db,
            new ReferenceResolver($this->accounts, $categories), $this->workspaces
        );
        $this->links = new TelegramLinkRepository($this->db);
        $this->bot = new TelegramBot(
            $this->links, $this->tx, $this->accounts, $categories, new ReportRepository($this->db), $this->workspaces,
            new DateTimeZone('America/Sao_Paulo'), fn () => $this->now
        );
        $this->ws = $this->workspaces->createDefaultWorkspace(1, 'Ana');
    }

    private function send(string $text, int $chat = self::CHAT, string $chatType = 'private'): ?array
    {
        return $this->bot->handle(['message' => [
            'chat' => ['id' => $chat, 'type' => $chatType],
            'from' => ['id' => $chat, 'username' => 'ana_tg'],
            'text' => $text,
        ]]);
    }

    private function linkAna(): void
    {
        $code = $this->bot->createLinkCode(1, $this->ws)['code'];
        $reply = $this->send("/start $code");
        $this->assertStringContainsString('Telegram vinculado', $reply['text']);
    }

    private function bills(): array
    {
        return $this->db->query("SELECT b.Title, b.Amount, b.Dates, b.status, a.AccountName, c.CategoryName
            FROM bills b JOIN account a ON a.AccountId = b.AccountId JOIN category c ON c.CategoryId = b.CategoryId
            WHERE b.WorkspaceId = {$this->ws}")->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function test_unlinked_chat_gets_instructions_and_nothing_is_saved(): void
    {
        $reply = $this->send('mercado 120');

        $this->assertSame(self::CHAT, $reply['chat_id']);
        $this->assertStringContainsString('vincule sua conta', $reply['text']);
        $this->assertCount(0, $this->bills());
    }

    public function test_expense_in_free_text_creates_account_and_category(): void
    {
        $this->linkAna();

        $reply = $this->send('mercado 120,50 nubank');

        $this->assertStringContainsString('Despesa registrada', $reply['text']);
        $this->assertStringContainsString('R$ 120,50', $reply['text']);
        $bills = $this->bills();
        $this->assertCount(1, $bills);
        $this->assertSame('Mercado', $bills[0]['Title']);
        $this->assertSame('Nubank', $bills[0]['AccountName']);
        $this->assertSame('Alimentação', $bills[0]['CategoryName']);
        $this->assertSame('PAID', $bills[0]['status']);
        $this->assertSame('2026-10-07', substr($bills[0]['Dates'], 0, 10));

        $this->assertStringContainsString('-R$ 120,50', $this->send('/saldo')['text']);
    }

    public function test_existing_account_is_matched_without_accents_and_income_is_detected(): void
    {
        $this->accounts->save(new Account($this->ws, 'Itaú'));
        $this->linkAna();

        $reply = $this->send('recebi 3.000 de salário itau ontem');

        $this->assertStringContainsString('Receita registrada', $reply['text']);
        $row = $this->db->query("SELECT s.Title, s.Amount, s.Date, a.AccountName, c.CategoryName FROM assets s
            JOIN account a ON a.AccountId = s.AccountId JOIN category c ON c.CategoryId = s.CategoryId
            WHERE s.WorkspaceId = {$this->ws}")->fetch(\PDO::FETCH_ASSOC);
        $this->assertSame('Itaú', $row['AccountName']);
        $this->assertSame('Salário', $row['CategoryName']);
        $this->assertSame(3000.0, (float) $row['Amount']);
        $this->assertSame('2026-10-06', substr($row['Date'], 0, 10));
        $this->assertSame(1, $this->accounts->countByWorkspaceId($this->ws), 'Não pode criar "itau" duplicando "Itaú"');
    }

    public function test_undo_removes_the_last_transaction_made_by_the_bot(): void
    {
        $this->linkAna();
        $this->send('uber 25');
        $this->assertCount(1, $this->bills());

        $this->assertStringContainsString('apagado', $this->send('/desfazer')['text']);
        $this->assertCount(0, $this->bills());
        $this->assertStringContainsString('Não há lançamento recente', $this->send('/desfazer')['text']);
    }

    public function test_link_code_is_single_use_and_expires(): void
    {
        $code = $this->bot->createLinkCode(1, $this->ws)['code'];
        $this->now = $this->now->modify('+16 minutes');
        $this->assertStringContainsString('inválido ou expirado', $this->send("/start $code")['text']);

        $this->now = $this->now->modify('-16 minutes');
        $code = $this->bot->createLinkCode(1, $this->ws)['code'];
        $this->send("/start $code");
        $this->assertStringContainsString('inválido ou expirado', $this->send("/start $code", 999)['text'], 'Outro chat não pode reutilizar o código');
        $this->assertSame(self::CHAT, (int) $this->links->findByUserId(1)['chat_id']);
    }

    public function test_viewer_cannot_add_but_can_query(): void
    {
        $this->db->prepare("INSERT INTO workspace_users (WorkspaceId, UserId, Role, Permissions) VALUES (?, 2, 'member', '{}')")->execute([$this->ws]);
        $this->links->saveLinkCode(2, $this->ws, 'VIEWER2345', $this->now->modify('+5 minutes')->format('Y-m-d H:i:s'));
        $this->links->link(2, 777, null, $this->now->format('Y-m-d H:i:s'));
        // O espaço pessoal do usuário 2 não existe: o bot usa o espaço compartilhado em que ele é leitor.

        $this->assertStringContainsString('permissão de leitura', $this->send('mercado 50', 777)['text']);
        $this->assertCount(0, $this->bills());
        $this->accounts->save(new Account($this->ws, 'Banco'));
        $this->assertStringContainsString('Saldo', $this->send('/saldo', 777)['text']);
    }

    public function test_group_chats_are_refused(): void
    {
        $this->linkAna();
        $this->assertStringContainsString('chat privado', $this->send('mercado 10', -100123, 'group')['text']);
        $this->assertCount(0, $this->bills());
    }

    public function test_upcoming_bills_and_month_summary(): void
    {
        $this->linkAna();
        $this->send('mercado 200');
        $this->send('+1000 salário');
        $this->tx->create($this->ws, new \App\DTO\TransactionDTO([
            'type' => 'bill', 'title' => 'Aluguel <b>', 'amount' => 1500, 'date' => '2026-10-01', 'due_date' => '2026-10-10',
            'accountName' => 'Banco', 'categoryName' => 'Moradia', 'status' => 'PENDING',
        ]), 1);

        $bills = $this->send('/contas')['text'];
        $this->assertStringContainsString('R$ 1.500,00', $bills);
        $this->assertStringContainsString('vence 10/10', $bills);
        $this->assertStringNotContainsString('<b>Aluguel <b>', $bills, 'Texto do usuário precisa sair escapado no HTML do Telegram');

        $month = $this->send('/mes')['text'];
        $this->assertStringContainsString('Receitas: <b>R$ 1.000,00</b>', $month);
        $this->assertStringContainsString('Despesas: <b>R$ 200,00</b>', $month);
        $this->assertStringContainsString('Alimentação', $month);
    }

    public function test_message_without_amount_explains_how_to_use(): void
    {
        $this->linkAna();
        $this->assertStringContainsString('Não encontrei um valor', $this->send('oi, tudo bem?')['text']);
    }

    public function test_webhook_requires_the_secret_and_answers_inline(): void
    {
        $controller = new TelegramController($this->bot, $this->links, fn () => 's3cr3t', 'meu_bot');
        $body = json_encode(['message' => ['chat' => ['id' => self::CHAT, 'type' => 'private'], 'text' => '/ajuda']]);
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/telegram/webhook')
            ->withBody((new StreamFactory())->createStream($body));

        $this->assertSame(401, $controller->webhook($request, new Response())->getStatusCode());
        $this->assertSame(401, $controller->webhook($request->withHeader('X-Telegram-Bot-Api-Secret-Token', 'errado'), new Response())->getStatusCode());

        $response = $controller->webhook($request->withHeader('X-Telegram-Bot-Api-Secret-Token', 's3cr3t'), new Response());
        $payload = json_decode((string) $response->getBody(), true);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('sendMessage', $payload['method']);
        $this->assertSame(self::CHAT, $payload['chat_id']);
    }
}
