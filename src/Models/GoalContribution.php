<?php

namespace App\Models;

class GoalContribution {
    private ?int $id;
    private int $goalId;
    private int $workspaceId;
    private float $amount;
    private string $date;
    private ?string $description;
    private string $createdAt;

    public function __construct(
        int $goalId,
        int $workspaceId,
        float $amount,
        string $date,
        ?string $description = null,
        ?int $id = null
    ) {
        $this->goalId = $goalId;
        $this->workspaceId = $workspaceId;
        $this->amount = $amount;
        $this->date = $date;
        $this->description = $description;
        $this->id = $id;
    }

    public function getId(): ?int { return $this->id; }
    public function getGoalId(): int { return $this->goalId; }
    public function getWorkspaceId(): int { return $this->workspaceId; }
    public function getAmount(): float { return $this->amount; }
    public function getDate(): string { return $this->date; }
    public function getDescription(): ?string { return $this->description; }
}
