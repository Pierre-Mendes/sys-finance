<?php

namespace App\Services;

use App\Repositories\AssetRepository;
use App\Repositories\BillRepository;
use App\Models\Transaction;
use App\UseCases\Transactions\CreateTransactionUseCase;
use App\UseCases\Transactions\Steps\PersistMainTransactionStep;
use App\UseCases\Transactions\Steps\SyncAccountBalanceStep;
use App\UseCases\Transactions\Steps\ProcessSplitsStep;
use App\UseCases\Transactions\Steps\ResolveReferencesStep;
use App\UseCases\Transactions\Steps\AuthorizeSplitsStep;
use App\DTO\TransactionDTO;
use PDO;
use Exception;

class TransactionService {
    private AssetRepository $assetRepo;
    private BillRepository $billRepo;
    private PDO $db;
    private CreateTransactionUseCase $createUseCase;
    private ReferenceResolver $resolver;
    private AccountBalanceService $balances;

    public function __construct(AssetRepository $assetRepo, BillRepository $billRepo, PDO $db, ReferenceResolver $resolver, WorkspaceService $workspaceService) {
        $this->assetRepo = $assetRepo;
        $this->billRepo = $billRepo;
        $this->db = $db;
        $this->resolver = $resolver;
        $this->balances = new AccountBalanceService($db);
        
        $this->createUseCase = new CreateTransactionUseCase();
        $this->createUseCase->addStep(new AuthorizeSplitsStep($workspaceService));
        $this->createUseCase->addStep(new ResolveReferencesStep($resolver));
        $this->createUseCase->addStep(new PersistMainTransactionStep($assetRepo, $billRepo));
        $this->createUseCase->addStep(new SyncAccountBalanceStep($this->balances));
        $this->createUseCase->addStep(new ProcessSplitsStep($this->createUseCase));
    }

    public function getAllForUser(int $workspaceId): array {
        return $this->getFilteredForUser($workspaceId, []);
    }

    public function getFilteredForUser(int $workspaceId, array $filters): array {
        $assets = $this->assetRepo->findAllByWorkspaceId($workspaceId);
        $bills = $this->billRepo->findAllByWorkspaceId($workspaceId);

        $transactions = array_merge($assets, $bills);
        
        // Filter in memory for simplicity given repo structure
        $filtered = array_filter($transactions, function($t) use ($filters) {
            if (!empty($filters['from_date']) && $t->getDate() < $filters['from_date']) return false;
            if (!empty($filters['to_date']) && $t->getDate() > $filters['to_date']) return false;
            if (!empty($filters['account_id']) && $t->getAccountId() != $filters['account_id']) return false;
            if (!empty($filters['category_id']) && $t->getCategoryId() != $filters['category_id']) return false;
            if (!empty($filters['type']) && $t->getType() != $filters['type']) return false;
            return true;
        });

        usort($filtered, function($a, $b) {
            return strtotime($b->getDate()) <=> strtotime($a->getDate()); 
        });

        return array_values($filtered);
    }

    public function create(int $workspaceId, TransactionDTO $dto, ?int $userId = null): Transaction {
        $this->validateData($dto);
        return $this->atomic(fn () => $this->createUseCase->execute($workspaceId, $dto, null, $userId));
    }

    public function update(int $id, int $workspaceId, TransactionDTO $dto): Transaction {
        return $this->atomic(fn () => $this->doUpdate($id, $workspaceId, $dto));
    }

    private function doUpdate(int $id, int $workspaceId, TransactionDTO $dto): Transaction {
        $this->validateData($dto);
        $type = $dto->type;

        $existing = ($type === 'asset') 
            ? $this->assetRepo->findByIdAndWorkspaceId($id, $workspaceId) 
            : $this->billRepo->findByIdAndWorkspaceId($id, $workspaceId);

        if (!$existing) throw new Exception("Transaction not found.");
        $oldAccountId = $existing->getAccountId();

        $dto->accountId = (int) $this->resolver->resolveAccount($workspaceId, $dto->accountId, $dto->accountName)->getId();
        $dto->categoryId = (int) $this->resolver
            ->resolveCategory($workspaceId, $dto->categoryId, $dto->categoryName, CategoryService::levelForType($type))
            ->getId();

        // Campos não enviados mantêm o valor atual: editar o título de uma conta pendente
        // não pode marcá-la como paga nem apagar vencimento/recorrência.
        $t = new Transaction(
            $workspaceId, $type, $dto->title, $dto->date, 
            $dto->categoryId, $dto->accountId, 
            $dto->amount, $dto->description, $id,
            $existing->getParentTransactionId(),
            $dto->has('due_date') ? $dto->dueDate : $existing->getDueDate(),
            $dto->has('status') ? $dto->status : $existing->getStatus(),
            $dto->has('priority') ? $dto->priority : $existing->getPriority(),
            $dto->has('recurrence_type') ? $dto->recurrenceType : $existing->getRecurrenceType()
        );

        $saved = ($type === 'asset') ? $this->assetRepo->save($t) : $this->billRepo->save($t);
        
        $this->balances->sync($saved->getAccountId(), $workspaceId);
        if ($oldAccountId !== $saved->getAccountId()) {
            $this->balances->sync($oldAccountId, $workspaceId);
        }
        return $saved;
    }

    public function delete(int $id, int $workspaceId, string $type): void {
        $this->atomic(fn () => $this->doDelete($id, $workspaceId, $type));
    }

    private function doDelete(int $id, int $workspaceId, string $type): void {
        $existing = ($type === 'asset') 
            ? $this->assetRepo->findByIdAndWorkspaceId($id, $workspaceId) 
            : $this->billRepo->findByIdAndWorkspaceId($id, $workspaceId);

        if (!$existing) throw new Exception("Transaction not found.");
        $accountId = $existing->getAccountId();

        $success = ($type === 'asset') ? $this->assetRepo->delete($id, $workspaceId) : $this->billRepo->delete($id, $workspaceId);
        if (!$success) throw new Exception("Transaction could not be deleted.");

        $this->balances->sync($accountId, $workspaceId);
    }
    
    public function pay(int $id, int $workspaceId, string $type): Transaction {
        return $this->atomic(function () use ($id, $workspaceId, $type) {
            $existing = $this->findPending($id, $workspaceId, $type, 'Transaction is already paid.');
            $t = $this->copyWith($existing, $type, $id, 'PAID', $existing->getDueDate());
            $this->save($t, $type);
            $this->balances->sync($t->getAccountId(), $workspaceId);
            $this->scheduleNextOccurrence($existing, $type, $id, $workspaceId);
            return $t;
        });
    }

    /** Conta pendente com novo vencimento (o usuário decidiu pagar depois). */
    public function reschedule(int $id, int $workspaceId, string $type, string $dueDate): Transaction {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate) || !strtotime($dueDate)) throw new Exception('Data de vencimento inválida.');
        $existing = $this->findPending($id, $workspaceId, $type, 'Só contas pendentes podem ser reagendadas.');
        $t = $this->copyWith($existing, $type, $id, 'PENDING', $dueDate);
        $this->save($t, $type);
        return $t;
    }

    /**
     * Desconsidera uma conta pendente (não vai ser paga): fica no histórico com status CANCELED e sai do saldo,
     * da previsão e dos lembretes. Em contas recorrentes, a próxima ocorrência continua sendo criada.
     */
    public function cancel(int $id, int $workspaceId, string $type): Transaction {
        return $this->atomic(function () use ($id, $workspaceId, $type) {
            $existing = $this->findPending($id, $workspaceId, $type, 'Só contas pendentes podem ser desconsideradas.');
            $t = $this->copyWith($existing, $type, $id, 'CANCELED', $existing->getDueDate());
            $this->save($t, $type);
            $this->scheduleNextOccurrence($existing, $type, $id, $workspaceId);
            return $t;
        });
    }

    /** Tudo ou nada: uma falha no meio (rateio, saldo, recorrência) não deixa o lançamento pela metade. */
    private function atomic(callable $operation): mixed {
        if ($this->db->inTransaction()) {
            return $operation();
        }
        $this->db->beginTransaction();
        try {
            $result = $operation();
            $this->db->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function findPending(int $id, int $workspaceId, string $type, string $notPendingMessage): Transaction {
        $existing = ($type === 'asset')
            ? $this->assetRepo->findByIdAndWorkspaceId($id, $workspaceId)
            : $this->billRepo->findByIdAndWorkspaceId($id, $workspaceId);
        if (!$existing) throw new Exception("Transaction not found.");
        if ($existing->getStatus() !== 'PENDING') throw new Exception($notPendingMessage);
        return $existing;
    }

    private function copyWith(Transaction $e, string $type, int $id, string $status, ?string $dueDate): Transaction {
        return new Transaction(
            $e->getUserId(), $type, $e->getTitle(), $e->getDate(), // "userId" do model guarda o WorkspaceId
            $e->getCategoryId(), $e->getAccountId(), $e->getAmount(), $e->getDescription(), $id,
            $e->getParentTransactionId(), $dueDate, $status, $e->getPriority(), $e->getRecurrenceType()
        );
    }

    private function save(Transaction $t, string $type): void {
        if ($type === 'asset') $this->assetRepo->save($t);
        else $this->billRepo->save($t);
    }

    /** Recorrência: ao encerrar uma ocorrência (paga ou desconsiderada), cria a próxima como pendente. */
    private function scheduleNextOccurrence(Transaction $existing, string $type, int $id, int $workspaceId): void {
        $step = ['MONTHLY' => '+1 month', 'YEARLY' => '+1 year'][$existing->getRecurrenceType() ?? 'NONE'] ?? null;
        if (!$step) return;

        $date = (new \DateTime($existing->getDate()))->modify($step);
        $due = $existing->getDueDate() ? (new \DateTime($existing->getDueDate()))->modify($step) : null;
        $this->save(new Transaction(
            $workspaceId, $type, $existing->getTitle(), $date->format('Y-m-d'),
            $existing->getCategoryId(), $existing->getAccountId(),
            $existing->getAmount(), $existing->getDescription(), null,
            $id, $due ? $due->format('Y-m-d') : null, 'PENDING',
            $existing->getPriority(), $existing->getRecurrenceType()
        ), $type);
    }

    private function validateData(TransactionDTO $dto): void {
        if (empty($dto->title)) throw new Exception("Title cannot be empty");
        if (empty($dto->date)) throw new Exception("Date cannot be empty");
        if (empty($dto->categoryId) && empty($dto->categoryName)) throw new Exception("Category must be selected");
        if (empty($dto->accountId) && empty($dto->accountName)) throw new Exception("Account must be selected");
        if ($dto->amount <= 0) throw new Exception("Invalid amount");
    }
}
