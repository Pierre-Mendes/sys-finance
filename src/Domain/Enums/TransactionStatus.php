<?php

namespace App\Domain\Enums;

enum TransactionStatus: string {
    case PAID = 'PAID';
    case PENDING = 'PENDING';
    case OVERDUE = 'OVERDUE';
    case CANCELED = 'CANCELED';
}
