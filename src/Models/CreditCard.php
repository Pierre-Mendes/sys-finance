<?php

namespace App\Models;

class CreditCard {
    private int $workspaceId;
    private int $accountId;
    private string $name;
    private ?string $brand;
    private float $limitAmount;
    private int $closingDay;
    private int $dueDay;
    private string $color;
    private ?int $id;

    public function __construct(
        int $workspaceId,
        int $accountId,
        string $name,
        float $limitAmount,
        int $closingDay,
        int $dueDay,
        ?string $brand = null,
        string $color = '#4f46e5',
        ?int $id = null
    ) {
        $this->workspaceId = $workspaceId;
        $this->accountId = $accountId;
        $this->name = $name;
        $this->limitAmount = $limitAmount;
        $this->closingDay = $closingDay;
        $this->dueDay = $dueDay;
        $this->brand = $brand;
        $this->color = $color;
        $this->id = $id;
    }

    public function getId(): ?int { return $this->id; }
    public function getWorkspaceId(): int { return $this->workspaceId; }
    public function getAccountId(): int { return $this->accountId; }
    public function getName(): string { return $this->name; }
    public function getBrand(): ?string { return $this->brand; }
    public function getLimitAmount(): float { return $this->limitAmount; }
    public function getClosingDay(): int { return $this->closingDay; }
    public function getDueDay(): int { return $this->dueDay; }
    public function getColor(): string { return $this->color; }
    public function setColor(string $color): void { $this->color = $color; }
}
