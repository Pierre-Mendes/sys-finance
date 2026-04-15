<?php

namespace App\Services;

use App\Database;
use PDO;
use Exception;

class HybridAIEngine {
    
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Tenta identificar o banco e extrair dados usando templates aprendidos ou heurística.
     */
    public function analyze(string $text): ?array {
        // 1. Tenta encontrar um template já "aprendido" no banco de dados
        $templates = $this->db->query("SELECT * FROM bank_statement_templates")->fetchAll();
        
        foreach ($templates as $template) {
            if (preg_match("/" . $template['detection_pattern'] . "/i", $text)) {
                return $this->parseWithTemplate($text, $template);
            }
        }

        // 2. Se não encontrou template, tenta Heurística Genérica (Regex inteligente)
        return $this->heuristicsAnalysis($text);
    }

    /**
     * Extrai transações usando um template salvo.
     */
    private function parseWithTemplate(string $text, array $template): array {
        $transactions = [];
        $columnMap = json_decode($template['column_map'], true);
        
        if (preg_match_all("/" . $template['row_pattern'] . "/", $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $transactions[] = [
                    'date' => $this->normalizeDate($match[$columnMap['date']], $template['date_format']),
                    'description' => trim($match[$columnMap['description']]),
                    'amount' => $this->parseAmount($match[$columnMap['amount']]),
                    'type' => $this->detectType($match[$columnMap['amount']])
                ];
            }
        }

        return $transactions;
    }

    /**
     * Motor de heurística para detectar padrões de transações em textos desconhecidos.
     */
    private function heuristicsAnalysis(string $text): array {
        $transactions = [];
        $lines = explode("\n", $text);
        
        $datePattern = '/^\s*(\d{2}[\/\-]\d{2}(?:[\/\-]\d{2,4})?)\b/';
        $amountPattern = '/(?:R\$\s*)?\(?(-?[\d\.]+,\d{2})\)?\s*([CD])?\b/i';
        
        $currentTx = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (preg_match($datePattern, $line, $matches)) {
                // Save previous if valid
                if ($currentTx && $currentTx['amount'] !== 0.0) {
                    $transactions[] = $currentTx;
                }

                $rawDate = $matches[1];
                $remaining = trim(str_replace($rawDate, '', $line));

                $currentTx = [
                    'date' => $this->normalizeDate($rawDate, 'd/m/Y'),
                    'description' => '',
                    'amount' => 0.0,
                    'type' => 'liability'
                ];

                // Check for amount on the same line
                if (preg_match($amountPattern, $remaining, $vMatches)) {
                    $currentTx['amount'] = $this->parseAmount($vMatches[0]);
                    $currentTx['type'] = $this->detectType($vMatches[0]);
                    $currentTx['description'] = trim(str_replace($vMatches[0], '', $remaining));
                } else {
                    $currentTx['description'] = $remaining;
                }
            } else if ($currentTx) {
                // Look for amount or append to description
                if (preg_match($amountPattern, $line, $vMatches)) {
                    $currentTx['amount'] = $this->parseAmount($vMatches[0]);
                    $currentTx['type'] = $this->detectType($vMatches[0]);
                    $descPart = trim(str_replace($vMatches[0], '', $line));
                    if (!empty($descPart)) {
                        $currentTx['description'] .= ' ' . $descPart;
                    }
                } else {
                    if (!preg_match('/SALDO|EXTRATO|PÁGINA|CONTA/i', $line)) {
                        $currentTx['description'] .= ' ' . $line;
                    }
                }
            }
        }

        if ($currentTx && $currentTx['amount'] !== 0.0) {
            $transactions[] = $currentTx;
        }

        return $transactions;
    }

    private function normalizeDate(string $dateStr, string $format): string {
        $dateStr = str_replace('-', '/', $dateStr);
        $d = \DateTime::createFromFormat($format, $dateStr);
        if (!$d) {
            $d = \DateTime::createFromFormat('d/m', $dateStr);
            if (!$d) return date('Y-m-d');
        }
        return $d->format('Y-m-d');
    }

    private function parseAmount(string $val): float {
        $isNegative = (strpos($val, '-') !== false || strpos($val, '(') !== false || stripos($val, 'D') !== false);
        $cleanVal = preg_replace('/[^\d,]/', '', $val);
        $floatVal = (float) str_replace(',', '.', $cleanVal);
        return $isNegative ? -$floatVal : $floatVal;
    }

    private function detectType(string $val): string {
        $isIncome = (stripos($val, 'C') !== false || (strpos($val, '-') === false && strpos($val, '(') === false));
        return $isIncome ? 'asset' : 'liability';
    }
}
