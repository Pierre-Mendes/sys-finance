<?php

namespace App\UseCases\Transactions\Steps;

use App\UseCases\Transactions\ITransactionStep;
use App\UseCases\Transactions\TransactionContext;
use App\UseCases\Transactions\CreateTransactionUseCase;
use App\DTO\TransactionDTO;

class ProcessSplitsStep implements ITransactionStep {
    private CreateTransactionUseCase $useCase;

    public function __construct(CreateTransactionUseCase $useCase) {
        $this->useCase = $useCase;
    }

    public function handle(TransactionContext $context): void {
        if ($context->dto->type !== 'bill' || empty($context->dto->splits) || !$context->transaction) {
            return;
        }

        foreach ($context->dto->splits as $split) {
            if (empty($split['workspaceId']) || empty($split['accountId']) || empty($split['amount'])) continue;

            // Permissão no workspace de destino já validada pelo AuthorizeSplitsStep.
            $targetWorkspaceId = (int) $split['workspaceId'];

            $childData = new TransactionDTO([
                'type' => 'bill',
                'title' => $context->dto->title . ' (Rateio Cruzado)',
                'date' => $context->dto->date,
                // A categoria do workspace de origem não existe no destino: resolve pelo nome (buscar ou criar).
                'categoryName' => $context->dto->categoryName,
                'accountId' => (int)$split['accountId'],
                'amount' => (float)$split['amount'],
                'description' => 'Parcela do rateio com o Workspace ' . $context->workspaceId . '. Referência: ' . $context->transaction->getId(),
            ]);

            // Synchronously execute another instance of the transaction
            // Note: Since this executes a full usecase loop, the SyncAccountBalanceStep will trigger properly for children too!
            $this->useCase->execute($targetWorkspaceId, $childData, $context->transaction->getId(), $context->userId);
        }
    }
}
