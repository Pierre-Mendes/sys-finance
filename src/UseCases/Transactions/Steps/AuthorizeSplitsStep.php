<?php

namespace App\UseCases\Transactions\Steps;

use App\Services\WorkspaceService;
use App\UseCases\Transactions\ITransactionStep;
use App\UseCases\Transactions\TransactionContext;
use Exception;

/**
 * Antes de persistir qualquer coisa, garante que o usuário só espelha despesas
 * (rateio) em workspaces onde é owner ou editor de transações.
 */
class AuthorizeSplitsStep implements ITransactionStep {
    private WorkspaceService $workspaceService;

    public function __construct(WorkspaceService $workspaceService) {
        $this->workspaceService = $workspaceService;
    }

    public function handle(TransactionContext $context): void {
        if ($context->dto->type !== 'bill' || empty($context->dto->splits)) return;

        foreach ($context->dto->splits as $split) {
            if (empty($split['workspaceId'])) continue;

            $targetWorkspaceId = (int) $split['workspaceId'];
            if (!$context->userId || !$this->workspaceService->canEdit($context->userId, $targetWorkspaceId, 'transactions')) {
                throw new Exception("Sem permissão para lançar rateio no workspace {$targetWorkspaceId}.");
            }
        }
    }
}
