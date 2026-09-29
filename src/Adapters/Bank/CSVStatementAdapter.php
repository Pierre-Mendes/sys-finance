<?php

namespace App\Adapters\Bank;

use App\Domain\Interfaces\BankStatementAdapterInterface;

class CSVStatementAdapter implements BankStatementAdapterInterface {
    
    public static function matches(string $text): bool {
        // Simple check: if it has commas and newlines, it might be a CSV
        // In the context of the service, we will rely on file extension 
        // but this is a fallback detection.
        $lines = explode("\n", $text);
        if (count($lines) < 2) return false;
        
        $commas = substr_count($lines[0], ',');
        $semicolons = substr_count($lines[0], ';');
        
        return ($commas > 1 || $semicolons > 1);
    }

    public function parse(string $text): array {
        $lines = explode("\n", trim($text));
        $transactions = [];
        $delimiter = $this->detectDelimiter($lines[0]);

        foreach ($lines as $i => $line) {
            $data = str_getcsv($line, $delimiter, "\"", "");
            
            // Skip header if it contains non-numeric values in common amount columns
            if ($i === 0 && !preg_match('/\d/', $line)) continue;

            if (count($data) >= 3) {
                // Heuristic: try to find Date, Description, and Amount
                $date = $this->findDate($data);
                $amount = $this->findAmount($data);
                $description = $this->findDescription($data, [$date['index'], $amount['index']]);

                if ($date['value'] && $amount['value'] !== null) {
                    $transactions[] = [
                        'date' => $date['value'],
                        'description' => $description,
                        'amount' => abs($amount['value']),
                        'type' => $amount['value'] < 0 ? 'bill' : 'asset'
                    ];
                }
            }
        }

        return $transactions;
    }

    private function detectDelimiter(string $line): string {
        $commas = substr_count($line, ',');
        $semicolons = substr_count($line, ';');
        return ($semicolons > $commas) ? ';' : ',';
    }

    private function findDate(array $data): array {
        foreach ($data as $i => $val) {
            $val = trim($val);
            // Try common date formats
            foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $fmt) {
                $d = \DateTime::createFromFormat($fmt, $val);
                if ($d) return ['index' => $i, 'value' => $d->format('Y-m-d')];
            }
        }
        return ['index' => -1, 'value' => null];
    }

    private function findAmount(array $data): array {
        foreach ($data as $i => $val) {
            $val = str_replace(['R$', ' ', '.'], '', $val);
            $val = str_replace(',', '.', $val);
            if (is_numeric($val) && strpos($val, '.') !== false) {
                return ['index' => $i, 'value' => (float)$val];
            }
        }
        return ['index' => -1, 'value' => null];
    }

    private function findDescription(array $data, array $skipIndices): string {
        $desc = [];
        foreach ($data as $i => $val) {
            if (!in_array($i, $skipIndices) && !empty(trim($val))) {
                $desc[] = trim($val);
            }
        }
        return implode(' - ', $desc);
    }
}
