<?php

namespace App\UseCases\Transactions\Steps;

use App\UseCases\Transactions\ITransactionStep;
use App\UseCases\Transactions\TransactionContext;
use App\Contracts\ITransactionRepository;
use App\Models\Transaction;

class PersistMainTransactionStep implements ITransactionStep {
    private ITransactionRepository $assetRepo;
    private ITransactionRepository $billRepo;

    public function __construct(ITransactionRepository $assetRepo, ITransactionRepository $billRepo) {
        $this->assetRepo = $assetRepo;
        $this->billRepo = $billRepo;
    }

    public function handle(TransactionContext $context): void {
        $type = $context->dto->type;
        $t = new Transaction(
            $context->workspaceId, $type, $context->dto->title, $context->dto->date, 
            $context->dto->categoryId, $context->dto->accountId, 
            $context->dto->amount, $context->dto->description, null, $context->parentId,
            $context->dto->dueDate, $context->dto->status, $context->dto->priority, $context->dto->recurrenceType
        );

        $context->transaction = ($type === 'asset') ? $this->assetRepo->save($t) : $this->billRepo->save($t);
    }
}
