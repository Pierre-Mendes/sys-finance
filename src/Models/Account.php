<?php

namespace App\Models;

class Account {
    private ?int $id;
    private int $userId;
    private string $accountName;

    public function __construct(int $userId, string $accountName, ?int $id = null) {
        $this->userId = $userId;
        $this->accountName = $accountName;
        $this->id = $id;
    }

    public function getId(): ?int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getAccountName(): string { return $this->accountName; }
    
    public function setAccountName(string $accountName): void { $this->accountName = $accountName; }
}
