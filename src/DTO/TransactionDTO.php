<?php

namespace App\DTO;

class TransactionDTO
{
    public string $type;
    public string $title;
    public string $date;
    public int $categoryId;
    public int $accountId;
    public ?string $categoryName;
    public ?string $accountName;
    public float $amount;
    public string $description;
    public array $splits;
    public ?string $dueDate;
    public string $status;
    public string $priority;
    public string $recurrenceType;
    /** @var string[] chaves presentes na requisição (para updates parciais) */
    private array $provided;

    public function __construct(array $data)
    {
        $this->provided = array_keys($data);
        $this->type = in_array($data['type'] ?? '', ['asset', 'bill']) ? $data['type'] : 'bill';
        $this->title = htmlspecialchars(strip_tags($data['title'] ?? ''));
        $this->date = htmlspecialchars(strip_tags($data['date'] ?? date('Y-m-d')));
        $this->categoryId = (int)($data['categoryId'] ?? 0);
        $this->accountId = (int)($data['accountId'] ?? 0);
        $this->categoryName = !empty($data['categoryName']) ? strip_tags((string) $data['categoryName']) : null;
        $this->accountName = !empty($data['accountName']) ? strip_tags((string) $data['accountName']) : null;
        $this->amount = (float)($data['amount'] ?? 0.0);
        $this->description = htmlspecialchars(strip_tags($data['description'] ?? ''));
        $this->splits = isset($data['splits']) && is_array($data['splits']) ? $data['splits'] : [];
        
        $this->dueDate = !empty($data['due_date']) ? htmlspecialchars(strip_tags($data['due_date'])) : null;
        $this->status = in_array($data['status'] ?? '', ['PENDING', 'PAID']) ? $data['status'] : 'PAID';
        $this->priority = in_array($data['priority'] ?? '', ['LOW', 'NORMAL', 'HIGH']) ? $data['priority'] : 'NORMAL';
        $this->recurrenceType = in_array($data['recurrence_type'] ?? '', ['NONE', 'MONTHLY', 'YEARLY']) ? $data['recurrence_type'] : 'NONE';
    }

    public function has(string $key): bool
    {
        return in_array($key, $this->provided, true);
    }

    public function isValid(): bool
    {
        return !empty($this->title) && !empty($this->date)
            && ($this->categoryId > 0 || !empty($this->categoryName))
            && ($this->accountId > 0 || !empty($this->accountName));
    }
}
