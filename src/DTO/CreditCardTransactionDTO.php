<?php

namespace App\DTO;

class CreditCardTransactionDTO {
    public int $cardId;
    public int $categoryId;
    public string $title;
    public float $amount;
    public string $date;
    public int $installments;
    public ?string $description;

    public function __construct(array $data) {
        $this->cardId = !empty($data['cardId']) ? (int)$data['cardId'] : 0;
        $this->categoryId = !empty($data['categoryId']) ? (int)$data['categoryId'] : 0;
        $this->title = !empty($data['title']) ? htmlspecialchars(strip_tags((string)$data['title'])) : '';
        $this->amount = !empty($data['amount']) ? (float)$data['amount'] : 0.0;
        $this->date = !empty($data['date']) ? htmlspecialchars(strip_tags((string)$data['date'])) : '';
        $this->installments = !empty($data['installments']) ? (int)$data['installments'] : 1;
        $this->description = !empty($data['description']) ? htmlspecialchars(strip_tags((string)$data['description'])) : null;
    }

    public function isValid(): bool {
        return $this->cardId > 0 && $this->categoryId > 0 && !empty($this->title) && 
               $this->amount > 0 && !empty($this->date) && $this->installments > 0;
    }
}
