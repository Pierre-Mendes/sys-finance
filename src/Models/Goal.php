<?php

namespace App\Models;

class Goal implements \JsonSerializable {
    private ?int $id;
    private int $workspaceId;
    private ?int $sharedWithWorkspaceId;
    private ?int $accountId;
    private bool $isFavorite;
    private string $title;
    private float $targetAmount;
    private float $accumulatedAmount;
    private ?string $targetDate;
    private string $createdAt;
    private string $updatedAt;

    public function __construct(
        int $workspaceId,
        string $title,
        float $targetAmount,
        float $accumulatedAmount = 0.0,
        ?string $targetDate = null,
        ?int $sharedWithWorkspaceId = null,
        ?int $id = null,
        ?int $accountId = null,
        bool $isFavorite = false
    ) {
        $this->workspaceId = $workspaceId;
        $this->title = $title;
        $this->targetAmount = $targetAmount;
        $this->accumulatedAmount = $accumulatedAmount;
        $this->targetDate = $targetDate;
        $this->sharedWithWorkspaceId = $sharedWithWorkspaceId;
        $this->id = $id;
        $this->accountId = $accountId;
        $this->isFavorite = $isFavorite;
    }

    public function getId(): ?int { return $this->id; }
    public function getWorkspaceId(): int { return $this->workspaceId; }
    public function getSharedWithWorkspaceId(): ?int { return $this->sharedWithWorkspaceId; }
    public function getAccountId(): ?int { return $this->accountId; }
    public function isFavorite(): bool { return $this->isFavorite; }
    public function getTitle(): string { return $this->title; }
    public function getTargetAmount(): float { return $this->targetAmount; }
    public function getAccumulatedAmount(): float { return $this->accumulatedAmount; }
    public function getTargetDate(): ?string { return $this->targetDate; }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'workspaceId' => $this->workspaceId,
            'sharedWithWorkspaceId' => $this->sharedWithWorkspaceId,
            'accountId' => $this->accountId,
            'isFavorite' => $this->isFavorite,
            'title' => $this->title,
            'targetAmount' => $this->targetAmount,
            'accumulatedAmount' => $this->accumulatedAmount,
            'targetDate' => $this->targetDate
        ];
    }
}
