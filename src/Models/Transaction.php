<?php

namespace App\Models;

class Transaction {
    private ?int $id;
    private string $type; // 'asset' or 'bill'
    private int $userId;
    private string $title;
    private string $date;
    private int $categoryId;
    private int $accountId;
    private float $amount;
    private string $description;
    private ?int $parentTransactionId;
    
    private ?string $dueDate;
    private string $status;
    private string $priority;
    private string $recurrenceType;

    // Optional joins for aggregation rendering
    private string $categoryName = '';
    private string $accountName = '';

    public function __construct(
        int $userId, string $type, string $title, string $date, 
        int $categoryId, int $accountId, float $amount, string $description, ?int $id = null,
        ?int $parentTransactionId = null, ?string $dueDate = null, string $status = 'PAID',
        string $priority = 'NORMAL', string $recurrenceType = 'NONE'
    ) {
        $this->userId = $userId;
        $this->type = $type;
        $this->title = $title;
        $this->date = $date;
        $this->categoryId = $categoryId;
        $this->accountId = $accountId;
        $this->amount = $amount;
        $this->description = $description;
        $this->id = $id;
        $this->parentTransactionId = $parentTransactionId;
        $this->dueDate = $dueDate;
        $this->status = $status;
        $this->priority = $priority;
        $this->recurrenceType = $recurrenceType;
    }

    public function getId(): ?int { return $this->id; }
    public function getType(): string { return $this->type; }
    public function getUserId(): int { return $this->userId; }
    public function getTitle(): string { return $this->title; }
    public function getDate(): string { return $this->date; }
    public function getCategoryId(): int { return $this->categoryId; }
    public function getAccountId(): int { return $this->accountId; }
    public function getAmount(): float { return $this->amount; }
    public function getDescription(): string { return $this->description; }
    public function getParentTransactionId(): ?int { return $this->parentTransactionId; }
    public function getDueDate(): ?string { return $this->dueDate; }
    public function getStatus(): string { return $this->status; }
    public function getPriority(): string { return $this->priority; }
    public function getRecurrenceType(): string { return $this->recurrenceType; }

    public function setCategoryName(string $name): void { $this->categoryName = $name; }
    public function getCategoryName(): string { return $this->categoryName; }
    
    public function setAccountName(string $name): void { $this->accountName = $name; }
    public function getAccountName(): string { return $this->accountName; }
}
