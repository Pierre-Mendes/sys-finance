<?php

namespace App\Adapters\Bank;

use App\Domain\Interfaces\BankStatementAdapterInterface;

class ItauPDFAdapter implements BankStatementAdapterInterface {
    
    public static function matches(string $text): bool {
        return stripos($text, 'itaú') !== false || stripos($text, 'itau') !== false;
    }

    public function parse(string $text): array {
        $lines = explode("\n", $text);
        $transactions = [];
        
        // Pattern: DD/MM/YYYY Description                                 Value
        // Example: 08/04/2026 PIX TRANSF Pierre 08/04                                  -165,00
        $pattern = '/^(\d{2}\/\d{2}\/\d{4})\s+(.+?)\s+(-?[\d\.]+,\d{2})$/';

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (preg_match($pattern, $line, $matches)) {
                $rawDate = $matches[1];
                $description = trim($matches[2]);
                $rawValue = str_replace(['.', ','], ['', '.'], $matches[3]);
                $amount = (float) $rawValue;

                // Skip "SALDO DO DIA" et al
                if (stripos($description, 'SALDO DO DIA') !== false) continue;

                $transactions[] = [
                    'date' => \DateTime::createFromFormat('d/m/Y', $rawDate)->format('Y-m-d'),
                    'description' => $description,
                    'amount' => abs($amount),
                    'type' => $amount < 0 ? 'bill' : 'asset'
                ];
            }
        }

        return $transactions;
    }
}
