<?php

namespace App\Services;

use App\Models\Account;
use App\Repositories\AccountRepository;
use Exception;

class AccountService {
    private AccountRepository $accountRepo;

    public function __construct(AccountRepository $accountRepo) {
        $this->accountRepo = $accountRepo;
    }

    public function getAllForUser(int $workspaceId): array {
        return $this->accountRepo->findAllByWorkspaceId($workspaceId);
    }

    public function create(int $workspaceId, string $accountName): Account {
        if (empty(trim($accountName))) {
            throw new Exception("Account name cannot be empty.");
        }
        
        $account = new Account($workspaceId, trim($accountName));
        return $this->accountRepo->save($account);
    }

    public function update(int $id, int $workspaceId, string $accountName): Account {
        if (empty(trim($accountName))) {
            throw new Exception("Account name cannot be empty.");
        }

        $account = $this->accountRepo->findByIdAndWorkspaceId($id, $workspaceId);
        if (!$account) {
            throw new Exception("Account not found or access denied.");
        }

        $account->setAccountName(trim($accountName));
        return $this->accountRepo->save($account);
    }

    public function delete(int $id, int $workspaceId): void {
        $success = $this->accountRepo->delete($id, $workspaceId);
        if (!$success) {
            throw new Exception("Account not found or could not be deleted.");
        }
    }
}
