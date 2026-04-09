<?php

namespace App\DTO;

class CreditCardDTO {
    public string $name;
    public int $accountId;
    public float $limitAmount;
    public int $closingDay;
    public int $dueDay;
    public ?string $brand;
    public string $color;

    public function __construct(array $data) {
        $this->name = !empty($data['name']) ? htmlspecialchars(strip_tags((string)$data['name'])) : '';
        $this->accountId = !empty($data['accountId']) ? (int)$data['accountId'] : 0;
        $this->limitAmount = !empty($data['limitAmount']) ? (float)$data['limitAmount'] : 0.0;
        $this->closingDay = !empty($data['closingDay']) ? (int)$data['closingDay'] : 0;
        $this->dueDay = !empty($data['dueDay']) ? (int)$data['dueDay'] : 0;
        $this->brand = !empty($data['brand']) ? htmlspecialchars(strip_tags((string)$data['brand'])) : null;
        $this->color = !empty($data['color']) ? htmlspecialchars(strip_tags((string)$data['color'])) : '#4f46e5';
    }

    public function isValid(): bool {
        return !empty($this->name) && $this->accountId > 0 && $this->limitAmount > 0 &&
               $this->closingDay > 0 && $this->closingDay <= 31 &&
               $this->dueDay > 0 && $this->dueDay <= 31;
    }
}
