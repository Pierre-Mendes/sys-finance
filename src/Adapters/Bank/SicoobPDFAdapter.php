<?php

namespace App\Adapters\Bank;

use App\Domain\Interfaces\BankStatementAdapterInterface;

class SicoobPDFAdapter implements BankStatementAdapterInterface {
    
    public static function matches(string $text): bool {
        return stripos($text, 'SICOOB') !== false;
    }

    public function parse(string $text): array {
        $lines = explode("\n", $text);
        $transactions = [];
        
        // Find the period to determine the year
        $year = date('Y');
        foreach ($lines as $line) {
            if (preg_match('/PERÍODO:\s+\d{2}\/\d{2}\/(\d{4})/', $line, $matches)) {
                $year = $matches[1];
                break;
            }
        }

        $datePattern = '/^\s*(\d{2}\/\d{2})\b/';
        $valuePattern = '/([\d\.]+,\d{2})\s*([CD])?/';
        
        $currentTx = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Check if line starts with a date
            if (preg_match($datePattern, $line, $matches)) {
                // Save previous transaction if valid
                if ($currentTx && $currentTx['amount'] > 0) {
                    $transactions[] = $currentTx;
                }

                $rawDate = $matches[1];
                $remaining = trim(str_replace($rawDate, '', $line));
                
                $currentTx = [
                    'date' => \DateTime::createFromFormat('d/m/Y', "$rawDate/$year")->format('Y-m-d'),
                    'description' => '',
                    'amount' => 0,
                    'type' => 'bill'
                ];

                // Check if value is on the same line
                if (preg_match($valuePattern, $remaining, $vMatches)) {
                    $currentTx['amount'] = (float) str_replace(['.', ','], ['', '.'], $vMatches[1]);
                    if (isset($vMatches[2])) {
                        $currentTx['type'] = ($vMatches[2] === 'C') ? 'asset' : 'bill';
                    }
                    $currentTx['description'] = trim(str_replace($vMatches[0], '', $remaining));
                } else {
                    $currentTx['description'] = $remaining;
                }
            } else if ($currentTx) {
                // Multiline logic: check for value/type or just append description
                if (preg_match($valuePattern, $line, $vMatches)) {
                    $currentTx['amount'] = (float) str_replace(['.', ','], ['', '.'], $vMatches[1]);
                    if (isset($vMatches[2])) {
                        $currentTx['type'] = ($vMatches[2] === 'C') ? 'asset' : 'bill';
                    }
                    $descPart = trim(str_replace($vMatches[0], '', $line));
                    if (!empty($descPart)) {
                        $currentTx['description'] .= ' ' . $descPart;
                    }
                } else {
                    // Just more description text
                    if (!preg_match('/SALDO|EXTRATO|PÁGINA/i', $line)) {
                        $currentTx['description'] .= ' ' . trim($line);
                    }
                }
            }
        }

        // Add last transaction
        if ($currentTx && $currentTx['amount'] > 0) {
            // Final check to filter out non-transaction items
            if (stripos($currentTx['description'], 'SALDO') === false) {
                $transactions[] = $currentTx;
            }
        }

        return $transactions;
    }
}
