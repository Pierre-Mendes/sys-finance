<?php

namespace App\UseCases\Transactions\Steps;

use App\Services\CategoryService;
use App\Services\ReferenceResolver;
use App\UseCases\Transactions\ITransactionStep;
use App\UseCases\Transactions\TransactionContext;

/**
 * Garante que conta e categoria existem no workspace do lançamento,
 * criando-as a partir do nome quando o usuário ainda não as cadastrou.
 */
class ResolveReferencesStep implements ITransactionStep {
    private ReferenceResolver $resolver;

    public function __construct(ReferenceResolver $resolver) {
        $this->resolver = $resolver;
    }

    public function handle(TransactionContext $context): void {
        $dto = $context->dto;

        $account = $this->resolver->resolveAccount($context->workspaceId, $dto->accountId, $dto->accountName);
        $category = $this->resolver->resolveCategory(
            $context->workspaceId,
            $dto->categoryId,
            $dto->categoryName,
            CategoryService::levelForType($dto->type)
        );

        $dto->accountId = (int) $account->getId();
        $dto->accountName = $account->getAccountName();
        $dto->categoryId = (int) $category->getId();
        $dto->categoryName = $category->getCategoryName();
    }
}
