<?php

namespace App\Domain\Enums;

enum TransactionType: string {
    case ASSET = 'asset';
    case BILL = 'bill';
}
