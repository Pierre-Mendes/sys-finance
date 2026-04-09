<?php

namespace App\Models;

class Category {
    private ?int $id;
    private int $userId;
    private string $categoryName;
    private int $level; // 1 = Income, 2 = Expense

    public function __construct(int $userId, string $categoryName, int $level, ?int $id = null) {
        $this->userId = $userId;
        $this->categoryName = $categoryName;
        $this->level = $level;
        $this->id = $id;
    }

    public function getId(): ?int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getCategoryName(): string { return $this->categoryName; }
    public function getLevel(): int { return $this->level; }
    
    public function setCategoryName(string $categoryName): void { $this->categoryName = $categoryName; }
}
