<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Exception;

class CategoryService {
    private CategoryRepository $categoryRepo;

    public function __construct(CategoryRepository $categoryRepo) {
        $this->categoryRepo = $categoryRepo;
    }

    public function getAllForUser(int $workspaceId, string $type): array {
        $level = ($type === 'expense') ? 2 : 1;
        return $this->categoryRepo->findAllByWorkspaceIdAndLevel($workspaceId, $level);
    }

    /**
     * @param int $workspaceId
     * @param string|array $categoryInput
     * @param string $type
     * @return Category|Category[]
     * @throws Exception
     */
    public function create(int $workspaceId, $categoryInput, string $type) {
        $level = ($type === 'expense') ? 2 : 1;
        $createdCategories = [];

        // Normalize input into an array of names
        $names = [];
        if (is_array($categoryInput)) {
            $names = $categoryInput;
        } elseif (is_string($categoryInput)) {
            $names = explode(',', $categoryInput);
        }

        foreach ($names as $name) {
            $trimmedName = trim($name);
            if (!empty($trimmedName)) {
                $cat = new Category($workspaceId, $trimmedName, $level);
                $createdCategories[] = $this->categoryRepo->save($cat);
            }
        }

        if (empty($createdCategories)) {
            throw new Exception("Nenhum nome de categoria válido foi fornecido.");
        }

        // Se o input foi uma única string sem vírgulas originalmente (compatibilidade com frontend antigo ou APIs restritas), retorna apenas 1 objeto
        if (is_string($categoryInput) && count($createdCategories) === 1 && strpos($categoryInput, ',') === false) {
            return $createdCategories[0];
        }

        return $createdCategories;
    }

    public function update(int $id, int $workspaceId, string $categoryName): Category {
        if (empty(trim($categoryName))) {
            throw new Exception("Category name cannot be empty.");
        }

        $cat = $this->categoryRepo->findByIdAndWorkspaceId($id, $workspaceId);
        if (!$cat) {
            throw new Exception("Category not found or access denied.");
        }

        $cat->setCategoryName(trim($categoryName));
        return $this->categoryRepo->save($cat);
    }

    public function delete(int $id, int $workspaceId): void {
        $success = $this->categoryRepo->delete($id, $workspaceId);
        if (!$success) {
            throw new Exception("Category not found or could not be deleted.");
        }
    }
}
