<?php

namespace App\Models;

class CreditCardTransaction {
    private int $cardId;
    private int $workspaceId;
    private int $categoryId;
    private string $title;
    private ?string $description;
    private float $amount;
    private int $installments;
    private int $currentInstallment;
    private string $date;
    private ?int $id;

    public function __construct(
        int $cardId,
        int $workspaceId,
        int $categoryId,
        string $title,
        float $amount,
        string $date,
        int $installments = 1,
        int $currentInstallment = 1,
        ?string $description = null,
        ?int $id = null
    ) {
        $this->cardId = $cardId;
        $this->workspaceId = $workspaceId;
        $this->categoryId = $categoryId;
        $this->title = $title;
        $this->amount = $amount;
        $this->date = $date;
        $this->installments = $installments;
        $this->currentInstallment = $currentInstallment;
        $this->description = $description;
        $this->id = $id;
    }

    public function getId(): ?int { return $this->id; }
    public function getCardId(): int { return $this->cardId; }
    public function getWorkspaceId(): int { return $this->workspaceId; }
    public function getCategoryId(): int { return $this->categoryId; }
    public function getTitle(): string { return $this->title; }
    public function getAmount(): float { return $this->amount; }
    public function getDate(): string { return $this->date; }
    public function getInstallments(): int { return $this->installments; }
    public function getCurrentInstallment(): int { return $this->currentInstallment; }
    public function getDescription(): ?string { return $this->description; }
}
