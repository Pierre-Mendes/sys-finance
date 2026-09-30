<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Category;
use App\Repositories\AccountRepository;
use App\Repositories\CategoryRepository;
use Exception;

/**
 * "Buscar ou criar": resolve as dependências de um lançamento (conta, categoria)
 * dentro do workspace ativo.
 *
 * - Se um ID for informado, ele PRECISA pertencer ao workspace (evita IDOR / referência cruzada entre tenants).
 * - Se só um nome for informado, reaproveita o registro existente (case-insensitive) ou cria um novo.
 *
 * Isso permite que o usuário registre uma transação ou um cartão sem ter
 * cadastrado previamente a conta/categoria em outra tela.
 */
class ReferenceResolver {
    private const MAX_NAME_LENGTH = 100;

    private AccountRepository $accountRepo;
    private CategoryRepository $categoryRepo;

    public function __construct(AccountRepository $accountRepo, CategoryRepository $categoryRepo) {
        $this->accountRepo = $accountRepo;
        $this->categoryRepo = $categoryRepo;
    }

    public function resolveAccount(int $workspaceId, int $accountId, ?string $accountName = null): Account {
        if ($accountId > 0) {
            $account = $this->accountRepo->findByIdAndWorkspaceId($accountId, $workspaceId);
            if (!$account) throw new Exception("Conta não encontrada neste workspace.");
            return $account;
        }

        $name = $this->normalizeName($accountName);
        if ($name === '') throw new Exception("Informe uma conta existente ou o nome de uma nova conta.");

        return $this->accountRepo->findByNameAndWorkspaceId($name, $workspaceId)
            ?? $this->accountRepo->save(new Account($workspaceId, $name));
    }

    public function resolveCategory(int $workspaceId, int $categoryId, ?string $categoryName, int $level): Category {
        if ($categoryId > 0) {
            $category = $this->categoryRepo->findByIdAndWorkspaceId($categoryId, $workspaceId);
            if (!$category) throw new Exception("Categoria não encontrada neste workspace.");
            return $category;
        }

        $name = $this->normalizeName($categoryName);
        if ($name === '') throw new Exception("Informe uma categoria existente ou o nome de uma nova categoria.");

        return $this->categoryRepo->findByNameAndWorkspaceIdAndLevel($name, $workspaceId, $level)
            ?? $this->categoryRepo->save(new Category($workspaceId, $name, $level));
    }

    private function normalizeName(?string $name): string {
        $name = trim(strip_tags((string) $name));
        return mb_substr($name, 0, self::MAX_NAME_LENGTH);
    }
}
