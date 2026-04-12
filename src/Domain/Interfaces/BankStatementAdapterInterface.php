<?php

namespace App\Domain\Interfaces;

interface BankStatementAdapterInterface {
    /**
     * @param string $text Raw text from PDF
     * @return array Array of transactions: ['date', 'description', 'amount', 'type']
     */
    public function parse(string $text): array;

    /**
     * @param string $text
     * @return bool Whether the text matches this bank's format
     */
    public static function matches(string $text): bool;
}
