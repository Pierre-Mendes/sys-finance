<?php

namespace App\Models;

class Goal {
    private ?int $id;
    private int $workspaceId;
    private ?int $sharedWithWorkspaceId;
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
        ?int $id = null
    ) {
        $this->workspaceId = $workspaceId;
        $this->title = $title;
        $this->targetAmount = $targetAmount;
        $this->accumulatedAmount = $accumulatedAmount;
        $this->targetDate = $targetDate;
        $this->sharedWithWorkspaceId = $sharedWithWorkspaceId;
        $this->id = $id;
    }

    public function getId(): ?int { return $this->id; }
    public function getWorkspaceId(): int { return $this->workspaceId; }
    public function getSharedWithWorkspaceId(): ?int { return $this->sharedWithWorkspaceId; }
    public function getTitle(): string { return $this->title; }
    public function getTargetAmount(): float { return $this->targetAmount; }
    public function getAccumulatedAmount(): float { return $this->accumulatedAmount; }
    public function getTargetDate(): ?string { return $this->targetDate; }
}
