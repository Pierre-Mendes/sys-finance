<?php

namespace Tests\Integration;

use App\DTO\CreditCardDTO;
use App\DTO\CreditCardTransactionDTO;
use App\DTO\TransactionDTO;
use App\Models\Account;
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
use Exception;
use Tests\TestCase;

class ReferenceResolverTest extends TestCase
{
    private AccountRepository $accountRepo;
    private CategoryRepository $categoryRepo;
    private ReferenceResolver $resolver;
    private TransactionService $txService;
    private CreditCardService $cardService;
    private int $userId = 1;
    private int $workspaceId;
    private int $otherWorkspaceId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountRepo = new AccountRepository($this->db);
        $this->categoryRepo = new CategoryRepository($this->db);
        $this->resolver = new ReferenceResolver($this->accountRepo, $this->categoryRepo);
        $workspaceService = new WorkspaceService($this->db);
        $billRepo = new BillRepository($this->db);

        $this->txService = new TransactionService(new AssetRepository($this->db), $billRepo, $this->db, $this->resolver, $workspaceService);
        $this->cardService = new CreditCardService(
            new CreditCardRepository($this->db),
            new CreditCardTransactionRepository($this->db),
            $billRepo,
            $this->categoryRepo,
            $this->resolver
        );

        $this->workspaceId = $workspaceService->createDefaultWorkspace($this->userId, 'Owner');
        // Workspace de outra pessoa, onde o usuário 1 não é membro.
        $this->otherWorkspaceId = $workspaceService->createDefaultWorkspace(99, 'Stranger');
    }

    public function test_resolve_account_creates_when_missing_and_reuses_case_insensitively(): void
    {
        $created = $this->resolver->resolveAccount($this->workspaceId, 0, '  Nubank ');
        $reused = $this->resolver->resolveAccount($this->workspaceId, 0, 'nubank');

        $this->assertSame('Nubank', $created->getAccountName());
        $this->assertSame($created->getId(), $reused->getId());
        $this->assertSame(1, $this->accountRepo->countByWorkspaceId($this->workspaceId));
    }

    public function test_resolve_account_rejects_id_from_another_workspace(): void
    {
        $foreign = $this->accountRepo->save(new Account($this->otherWorkspaceId, 'Conta alheia'));

        $this->expectException(Exception::class);
        $this->resolver->resolveAccount($this->workspaceId, (int) $foreign->getId());
    }

    public function test_categories_are_separated_by_level(): void
    {
        $income = $this->resolver->resolveCategory($this->workspaceId, 0, 'Outros', 1);
        $expense = $this->resolver->resolveCategory($this->workspaceId, 0, 'Outros', 2);

        $this->assertNotSame($income->getId(), $expense->getId());
    }

    public function test_transaction_can_be_created_without_preexisting_account_or_category(): void
    {
        $tx = $this->txService->create($this->workspaceId, new TransactionDTO([
            'type' => 'bill',
            'title' => 'Mercado',
            'date' => '2026-09-01',
            'amount' => 150.0,
            'accountName' => 'Carteira',
            'categoryName' => 'Alimentação',
        ]), $this->userId);

        $account = $this->accountRepo->findByNameAndWorkspaceId('carteira', $this->workspaceId);
        $category = $this->categoryRepo->findByNameAndWorkspaceIdAndLevel('alimentação', $this->workspaceId, 2);

        $this->assertNotNull($account);
        $this->assertNotNull($category);
        $this->assertSame($account->getId(), $tx->getAccountId());
        $this->assertSame($category->getId(), $tx->getCategoryId());
    }

    public function test_split_into_workspace_without_permission_is_refused(): void
    {
        $foreignAccount = $this->accountRepo->save(new Account($this->otherWorkspaceId, 'Conta alheia'));

        try {
            $this->txService->create($this->workspaceId, new TransactionDTO([
            'type' => 'bill',
            'title' => 'Aluguel',
            'date' => '2026-09-01',
            'amount' => 1000.0,
            'accountName' => 'Carteira',
            'categoryName' => 'Moradia',
            'splits' => [['workspaceId' => $this->otherWorkspaceId, 'accountId' => $foreignAccount->getId(), 'amount' => 500]],
            ]), $this->userId);
            $this->fail('Rateio em workspace alheio deveria ser recusado.');
        } catch (Exception $e) {
            $this->assertStringContainsString('Sem permissão', $e->getMessage());
        }

        // Nada deve ter sido persistido em nenhum dos workspaces.
        $bills = (new BillRepository($this->db));
        $this->assertCount(0, $bills->findAllByWorkspaceId($this->workspaceId));
        $this->assertCount(0, $bills->findAllByWorkspaceId($this->otherWorkspaceId));
    }

    public function test_credit_card_and_purchase_create_missing_account_and_category(): void
    {
        $card = $this->cardService->createCard($this->workspaceId, new CreditCardDTO([
            'name' => 'Roxinho',
            'accountName' => 'Nubank',
            'limitAmount' => 5000,
            'closingDay' => 5,
            'dueDay' => 12,
        ]));

        $account = $this->accountRepo->findByNameAndWorkspaceId('Nubank', $this->workspaceId);
        $this->assertSame($account->getId(), $card->getAccountId());

        $txs = $this->cardService->addTransaction($this->workspaceId, new CreditCardTransactionDTO([
            'cardId' => $card->getId(),
            'title' => 'Streaming',
            'amount' => 39.9,
            'date' => '2026-09-10',
            'categoryName' => 'Assinaturas',
        ]));

        $category = $this->categoryRepo->findByNameAndWorkspaceIdAndLevel('Assinaturas', $this->workspaceId, 2);
        $this->assertSame($category->getId(), $txs[0]->getCategoryId());
    }
}
