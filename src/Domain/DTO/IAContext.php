<?php

namespace App\Domain\DTO;

class IAContext {
    public function __construct(
        public array $frequentCategories = [],
        public array $activeGoals = [],
        public array $bankTemplates = [],
        public array $recentTransactions = []
    ) {}

    public static function fromArray(array $data): self {
        return new self(
            $data['frequentCategories'] ?? [],
            $data['activeGoals'] ?? [],
            $data['bankTemplates'] ?? [],
            $data['recentTransactions'] ?? []
        );
    }
}
