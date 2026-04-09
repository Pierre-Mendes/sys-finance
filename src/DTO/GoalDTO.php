<?php

namespace App\DTO;

class GoalDTO {
    public string $title;
    public float $targetAmount;
    public ?string $targetDate = null;
    public ?int $sharedWithWorkspaceId = null;

    public function __construct(array $data) {
        $this->title = $data['title'] ?? '';
        $this->targetAmount = (float) ($data['targetAmount'] ?? 0);
        $this->targetDate = $data['targetDate'] ?? null;
        $this->sharedWithWorkspaceId = isset($data['sharedWithWorkspaceId']) ? (int) $data['sharedWithWorkspaceId'] : null;
    }
}
