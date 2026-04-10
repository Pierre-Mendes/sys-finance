<?php

namespace App\DTO;

class GoalDTO {
    public string $title;
    public float $targetAmount;
    public ?string $targetDate = null;
    public ?int $sharedWithWorkspaceId = null;
    public ?int $accountId = null;
    public bool $isFavorite = false;

    public function __construct(array $data) {
        $this->title = $data['title'] ?? '';
        $this->targetAmount = (float) ($data['targetAmount'] ?? 0);
        $this->targetDate = $data['targetDate'] ?? null;
        $this->sharedWithWorkspaceId = isset($data['sharedWithWorkspaceId']) ? (int) $data['sharedWithWorkspaceId'] : null;
        $this->accountId = isset($data['accountId']) ? (int) $data['accountId'] : null;
        $this->isFavorite = (bool) ($data['isFavorite'] ?? false);
    }
}
