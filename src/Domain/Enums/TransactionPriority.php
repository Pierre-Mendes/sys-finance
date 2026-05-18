<?php

namespace App\Domain\Enums;

enum TransactionPriority: string {
    case LOW = 'LOW';
    case NORMAL = 'NORMAL';
    case HIGH = 'HIGH';
    case CRITICAL = 'CRITICAL';
}
