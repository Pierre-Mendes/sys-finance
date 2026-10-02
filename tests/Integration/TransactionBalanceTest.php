<?php

namespace Tests\Integration;

use App\DTO\GoalContributionDTO;
use App\DTO\GoalDTO;
use App\DTO\TransactionDTO;
use App\Repositories\AccountRepository;
use App\Repositories\AssetRepository;
use App\Repositories\BillRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\GoalContributionRepository;
use App\Repositories\GoalRepository;
use App\Services\AccountBalanceService;
use App\Services\GoalService;
use App\Services\ReferenceResolver;
use App\Services\SimulationService;
use App\Services\TransactionService;
use App\Services\WorkspaceService;
use Tests\TestCase;

class TransactionBalanceTest extends TestCase
{
    private TransactionService $txService;
    private BillRepository $billRepo;
    private int $workspaceId;
    private int $accountId;

    protected function setUp(): void
    {
        parent::setUp();

        $accountRepo = new AccountRepository($this->db);
        $categoryRepo = new CategoryRepository($this->db);
        $this->billRepo = new BillRepository($this->db);
        $workspaceService = new WorkspaceService($this->db);

        $this->txService = new TransactionService(
            new AssetRepository($this->db), $this->billRepo, $this->db,
            new ReferenceResolver($accountRepo, $categoryRepo), $workspaceService
        );
        $this->workspaceId = $workspaceService->createDefaultWorkspace(1, 'Ana');

        $this->txService->create($this->workspaceId, new TransactionDTO([
            'type' => 'asset', 'title' => 'Salário', 'amount' => 5000, 'date' => '2026-09-01',
            'accountName' => 'Banco', 'categoryName' => 'Salário', 'status' => 'PAID',
        ]), 1);
        $this->accountId = (int) $accountRepo->findByNameAndWorkspaceId('Banco', $this->workspaceId)->getId();
    }

    private function accountBalance(): float
    {
        $stmt = $this->db->prepare("SELECT Totals FROM totals WHERE AccountId = ? AND WorkspaceId = ?");
        $stmt->execute([$this->accountId, $this->workspaceId]);
        return (float) $stmt->fetchColumn();
    }

    private function pendingRent(): \App\Models\Transaction
    {
        return $this->txService->create($this->workspaceId, new TransactionDTO([
            'type' => 'bill', 'title' => 'Aluguel', 'amount' => 1500, 'date' => '2026-09-01',
            'due_date' => '2026-09-10', 'accountId' => $this->accountId, 'categoryName' => 'Moradia',
            'status' => 'PENDING', 'priority' => 'HIGH', 'recurrence_type' => 'MONTHLY',
        ]), 1);
    }

    public function test_pending_bill_does_not_reduce_account_balance_until_paid(): void
    {
        $rent = $this->pendingRent();
        $this->assertSame(5000.0, $this->accountBalance(), 'Conta a vencer não pode descontar do saldo antes de paga');

        $this->txService->pay((int) $rent->getId(), $this->workspaceId, 'bill');
        $this->assertSame(3500.0, $this->accountBalance());
    }

    public function test_editing_a_pending_bill_keeps_status_due_date_and_recurrence(): void
    {
        $rent = $this->pendingRent();

        // Edição parcial, como uma correção de título vinda de um cliente da API.
        $this->txService->update((int) $rent->getId(), $this->workspaceId, new TransactionDTO([
            'type' => 'bill', 'title' => 'Aluguel apto', 'amount' => 1500, 'date' => '2026-09-01',
            'accountId' => $this->accountId, 'categoryName' => 'Moradia',
        ]));

        $saved = $this->billRepo->findByIdAndWorkspaceId((int) $rent->getId(), $this->workspaceId);
        $this->assertSame('Aluguel apto', $saved->getTitle());
        $this->assertSame('PENDING', $saved->getStatus());
        $this->assertSame('2026-09-10', $saved->getDueDate());
        $this->assertSame('HIGH', $saved->getPriority());
        $this->assertSame('MONTHLY', $saved->getRecurrenceType());
        $this->assertSame(5000.0, $this->accountBalance());
    }

    public function test_goal_contribution_updates_account_balance(): void
    {
        $goalRepo = new GoalRepository($this->db);
        $goals = new GoalService(
            $goalRepo, new GoalContributionRepository($this->db),
            new SimulationService(new AssetRepository($this->db), $this->billRepo, $goalRepo),
            $this->billRepo, new CategoryRepository($this->db), new AccountBalanceService($this->db)
        );
        $goal = $goals->createGoal($this->workspaceId, new GoalDTO(['title' => 'Viagem', 'targetAmount' => 10000.0]));

        $goals->addContribution($this->workspaceId, new GoalContributionDTO([
            'goalId' => $goal->getId(), 'accountId' => $this->accountId, 'amount' => 500.0, 'date' => '2026-09-30',
        ]));

        $this->assertSame(4500.0, $this->accountBalance());
    }

    public function test_overdue_bill_can_be_rescheduled_or_canceled_without_touching_the_balance(): void
    {
        $rent = $this->pendingRent(); // MONTHLY, vence 10/09
        $this->txService->reschedule((int) $rent->getId(), $this->workspaceId, 'bill', '2026-10-05');
        $this->assertSame('2026-10-05', $this->billRepo->findByIdAndWorkspaceId((int) $rent->getId(), $this->workspaceId)->getDueDate());

        $this->txService->cancel((int) $rent->getId(), $this->workspaceId, 'bill');
        $canceled = $this->billRepo->findByIdAndWorkspaceId((int) $rent->getId(), $this->workspaceId);
        $this->assertSame('CANCELED', $canceled->getStatus(), 'Fica no histórico como desconsiderada');
        $this->assertSame(5000.0, $this->accountBalance(), 'Desconsiderar não mexe no saldo');

        $next = array_values(array_filter(
            $this->billRepo->findAllByWorkspaceId($this->workspaceId),
            fn ($b) => $b->getParentTransactionId() === (int) $rent->getId()
        ));
        $this->assertCount(1, $next, 'Recorrente: a próxima ocorrência continua');
        $this->assertSame('PENDING', $next[0]->getStatus());
        $this->assertSame('2026-11-05', $next[0]->getDueDate());

        $this->expectExceptionMessage('Só contas pendentes podem ser desconsideradas.');
        $this->txService->cancel((int) $rent->getId(), $this->workspaceId, 'bill');
    }
}
