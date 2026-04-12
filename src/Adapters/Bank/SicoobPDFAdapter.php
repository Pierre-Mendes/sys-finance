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

        // Patterns:
        // DD/MM   DESCRIPTION                                  VALUE
        // (Next line might have C or D)
        $datePattern = '/^(\d{2}\/\d{2})\s+(.+?)\s+([\d\.]+,\d{2})?$/';

        for ($i = 0; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;

            if (preg_match($datePattern, $line, $matches)) {
                $rawDate = $matches[1]; // DD/MM
                $description = trim($matches[2]);
                $valueStr = isset($matches[3]) ? $matches[3] : '';

                // Look ahead for C or D
                $type = 'bill'; // Default
                $foundType = false;
                
                // Sometimes the value or C/D is on the next line or indented
                $lookahead = $i + 1;
                while ($lookahead < count($lines) && $lookahead < $i + 5) {
                    $nextLine = trim($lines[$lookahead]);
                    if (empty($nextLine)) {
                        $lookahead++;
                        continue;
                    }
                    
                    // If next line starts with a date, we stop looking
                    if (preg_match('/^\d{2}\/\d{2}\s+/', $nextLine)) break;

                    if (preg_match('/\b([CD])\b/', $nextLine, $typeMatches)) {
                        $type = ($typeMatches[1] === 'C') ? 'asset' : 'bill';
                        $foundType = true;
                        
                        // If value was missing in first line, maybe it's here
                        if (empty($valueStr) && preg_match('/([\d\.]+,\d{2})/', $nextLine, $vMatches)) {
                            $valueStr = $vMatches[1];
                        }
                        break;
                    }
                    $lookahead++;
                }

                if (empty($valueStr)) continue; // Can't find value

                // Skip summary lines
                if (stripos($description, 'SALDO') !== false) continue;

                $amount = (float) str_replace(['.', ','], ['', '.'], $valueStr);

                $transactions[] = [
                    'date' => \DateTime::createFromFormat('d/m/Y', "$rawDate/$year")->format('Y-m-d'),
                    'description' => $description,
                    'amount' => abs($amount),
                    'type' => $type
                ];
            }
        }

        return $transactions;
    }
}
