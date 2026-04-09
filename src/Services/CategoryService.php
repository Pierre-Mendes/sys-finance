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

    public function create(int $workspaceId, string $categoryName, string $type): Category {
        if (empty(trim($categoryName))) {
            throw new Exception("Category name cannot be empty.");
        }
        
        $level = ($type === 'expense') ? 2 : 1;
        $cat = new Category($workspaceId, trim($categoryName), $level);
        return $this->categoryRepo->save($cat);
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
