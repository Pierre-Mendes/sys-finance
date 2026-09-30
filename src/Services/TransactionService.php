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

    public function __construct(AssetRepository $assetRepo, BillRepository $billRepo, PDO $db, ReferenceResolver $resolver, WorkspaceService $workspaceService) {
        $this->assetRepo = $assetRepo;
        $this->billRepo = $billRepo;
        $this->db = $db;
        $this->resolver = $resolver;
        
        $this->createUseCase = new CreateTransactionUseCase();
        $this->createUseCase->addStep(new AuthorizeSplitsStep($workspaceService));
        $this->createUseCase->addStep(new ResolveReferencesStep($resolver));
        $this->createUseCase->addStep(new PersistMainTransactionStep($assetRepo, $billRepo));
        $this->createUseCase->addStep(new SyncAccountBalanceStep($this->db));
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
        return $this->createUseCase->execute($workspaceId, $dto, null, $userId);
    }

    public function update(int $id, int $workspaceId, TransactionDTO $dto): Transaction {
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

        $t = new Transaction(
            $workspaceId, $type, $dto->title, $dto->date, 
            $dto->categoryId, $dto->accountId, 
            $dto->amount, $dto->description, $id
        );

        $saved = ($type === 'asset') ? $this->assetRepo->save($t) : $this->billRepo->save($t);
        
        $this->syncAccountBalance($saved->getAccountId(), $workspaceId);
        if ($oldAccountId !== $saved->getAccountId()) {
            $this->syncAccountBalance($oldAccountId, $workspaceId);
        }
        return $saved;
    }

    public function delete(int $id, int $workspaceId, string $type): void {
        $existing = ($type === 'asset') 
            ? $this->assetRepo->findByIdAndWorkspaceId($id, $workspaceId) 
            : $this->billRepo->findByIdAndWorkspaceId($id, $workspaceId);

        if (!$existing) throw new Exception("Transaction not found.");
        $accountId = $existing->getAccountId();

        $success = ($type === 'asset') ? $this->assetRepo->delete($id, $workspaceId) : $this->billRepo->delete($id, $workspaceId);
        if (!$success) throw new Exception("Transaction could not be deleted.");

        $this->syncAccountBalance($accountId, $workspaceId);
    }
    
    public function pay(int $id, int $workspaceId, string $type): Transaction {
        $existing = ($type === 'asset') 
            ? $this->assetRepo->findByIdAndWorkspaceId($id, $workspaceId) 
            : $this->billRepo->findByIdAndWorkspaceId($id, $workspaceId);

        if (!$existing) throw new Exception("Transaction not found.");
        if ($existing->getStatus() === 'PAID') throw new Exception("Transaction is already paid.");

        $t = new Transaction(
            $workspaceId, $type, $existing->getTitle(), $existing->getDate(), 
            $existing->getCategoryId(), $existing->getAccountId(), 
            $existing->getAmount(), $existing->getDescription(), $id,
            $existing->getParentTransactionId(), $existing->getDueDate(), 'PAID', 
            $existing->getPriority(), $existing->getRecurrenceType()
        );

        $savedId = ($type === 'asset') ? $this->assetRepo->save($t) : $this->billRepo->save($t);
        
        $this->syncAccountBalance($t->getAccountId(), $workspaceId);
        
        // Clone for recurrence
        if ($existing->getRecurrenceType() && $existing->getRecurrenceType() !== 'NONE') {
            $dateObj = new \DateTime($existing->getDate());
            $dueObj = $existing->getDueDate() ? new \DateTime($existing->getDueDate()) : null;
            
            if ($existing->getRecurrenceType() === 'MONTHLY') {
                $dateObj->modify('+1 month');
                if ($dueObj) $dueObj->modify('+1 month');
            } else if ($existing->getRecurrenceType() === 'YEARLY') {
                $dateObj->modify('+1 year');
                if ($dueObj) $dueObj->modify('+1 year');
            }
            
            $clone = new Transaction(
                $workspaceId, $type, $existing->getTitle(), $dateObj->format('Y-m-d'), 
                $existing->getCategoryId(), $existing->getAccountId(), 
                $existing->getAmount(), $existing->getDescription(), null,
                $id, $dueObj ? $dueObj->format('Y-m-d') : null, 'PENDING', 
                $existing->getPriority(), $existing->getRecurrenceType()
            );
            
            if ($type === 'asset') $this->assetRepo->save($clone);
            else $this->billRepo->save($clone);
        }

        return $t;
    }

    private function syncAccountBalance(int $accountId, int $workspaceId): void {
        $stmtIn = $this->db->prepare("SELECT SUM(Amount) FROM assets WHERE AccountId = :acc AND WorkspaceId = :userId AND status = 'PAID'");
        $stmtIn->execute(['acc' => $accountId, 'userId' => $workspaceId]);
        $incomes = (float) $stmtIn->fetchColumn();

        $stmtOut = $this->db->prepare("SELECT SUM(Amount) FROM bills WHERE AccountId = :acc AND WorkspaceId = :userId AND status = 'PAID'");
        $stmtOut->execute(['acc' => $accountId, 'userId' => $workspaceId]);
        $expenses = (float) $stmtOut->fetchColumn();

        $total = $incomes - $expenses;

        $stmtCheck = $this->db->prepare("SELECT TotalsId FROM totals WHERE AccountId = :acc AND WorkspaceId = :userId");
        $stmtCheck->execute(['acc' => $accountId, 'userId' => $workspaceId]);
        $exists = $stmtCheck->fetchColumn();

        if ($exists) {
            $stmtUp = $this->db->prepare("UPDATE totals SET Totals = :tot WHERE AccountId = :acc AND WorkspaceId = :userId");
            $stmtUp->execute(['tot' => $total, 'acc' => $accountId, 'userId' => $workspaceId]);
        } else {
            $stmtIns = $this->db->prepare("INSERT INTO totals (WorkspaceId, AccountId, Totals) VALUES (:userId, :acc, :tot)");
            $stmtIns->execute(['userId' => $workspaceId, 'acc' => $accountId, 'tot' => $total]);
        }
    }

    private function validateData(TransactionDTO $dto): void {
        if (empty($dto->title)) throw new Exception("Title cannot be empty");
        if (empty($dto->date)) throw new Exception("Date cannot be empty");
        if (empty($dto->categoryId) && empty($dto->categoryName)) throw new Exception("Category must be selected");
        if (empty($dto->accountId) && empty($dto->accountName)) throw new Exception("Account must be selected");
        if ($dto->amount <= 0) throw new Exception("Invalid amount");
    }
}
