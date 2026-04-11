<?php

namespace App\DTO;

class GoalContributionDTO {
    public int $goalId;
    public ?int $accountId = null;
    public float $amount;
    public string $date;
    public ?string $description = null;

    public function __construct(array $data) {
        $this->goalId = (int) ($data['goalId'] ?? 0);
        $this->accountId = isset($data['accountId']) ? (int) $data['accountId'] : null;
        $this->amount = (float) ($data['amount'] ?? 0);
        $this->date = $data['date'] ?? date('Y-m-d');
        $this->description = $data['description'] ?? null;
    }
}
