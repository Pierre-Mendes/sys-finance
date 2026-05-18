<?php

namespace App\Domain\Enums;

enum RecurrenceType: string {
    case NONE = 'NONE';
    case DAILY = 'DAILY';
    case WEEKLY = 'WEEKLY';
    case MONTHLY = 'MONTHLY';
    case YEARLY = 'YEARLY';
}
