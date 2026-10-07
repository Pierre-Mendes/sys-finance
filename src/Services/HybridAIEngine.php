<?php

namespace App\Services;

use App\Database;
use PDO;
use Exception;

use App\Domain\DTO\IAContext;
use App\Domain\Enums\TransactionType;
use App\Domain\Enums\TransactionStatus;

class HybridAIEngine {
    
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Tenta identificar o banco e extrair dados usando templates aprendidos ou heurística.
     */
    public function analyze(string $text, ?IAContext $context = null): ?array {
        // 1. Tenta encontrar um template já "aprendido" no banco de dados
        $templates = $context ? $context->bankTemplates : $this->db->query("SELECT * FROM bank_statement_templates")->fetchAll();
        
        foreach ($templates as $template) {
            if (preg_match("/" . $template['detection_pattern'] . "/i", $text)) {
                return $this->parseWithTemplate($text, $template);
            }
        }

        // 2. Se não encontrou template, tenta Heurística Genérica com Contexto
        return $this->heuristicsAnalysis($text, $context);
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
                    'type' => $this->detectType($match[$columnMap['amount']])->value
                ];
            }
        }

        return $transactions;
    }

    /**
     * Motor de heurística enriquecido com Contexto de Hydration.
     */
    private function heuristicsAnalysis(string $text, ?IAContext $context = null): array {
        $transactions = [];
        $lines = explode("\n", $text);
        
        $datePattern = '/^\s*(\d{2}[\/\-]\d{2}(?:[\/\-]\d{2,4})?)\b/';
        $amountPattern = '/(?:R\$\s*)?\(?(-?[\d\.]+,\d{2})\)?\s*([CD])?\b/i';
        
        $currentTx = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (preg_match($datePattern, $line, $matches)) {
                if ($currentTx && $currentTx['amount'] !== 0.0) {
                    $transactions[] = $this->enrichWithContext($currentTx, $context);
                }

                $rawDate = $matches[1];
                $remaining = trim(str_replace($rawDate, '', $line));

                $currentTx = [
                    'date' => $this->normalizeDate($rawDate, 'd/m/Y'),
                    'description' => '',
                    'amount' => 0.0,
                    'type' => TransactionType::BILL->value,
                    'categoryId' => null
                ];

                if (preg_match($amountPattern, $remaining, $vMatches)) {
                    $currentTx['amount'] = $this->parseAmount($vMatches[0]);
                    $currentTx['type'] = $this->detectType($vMatches[0])->value;
                    $currentTx['description'] = trim(str_replace($vMatches[0], '', $remaining));
                } else {
                    $currentTx['description'] = $remaining;
                }
            } else if ($currentTx) {
                if (preg_match($amountPattern, $line, $vMatches)) {
                    $currentTx['amount'] = $this->parseAmount($vMatches[0]);
                    $currentTx['type'] = $this->detectType($vMatches[0])->value;
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
            $transactions[] = $this->enrichWithContext($currentTx, $context);
        }

        return $transactions;
    }

    /**
     * Usa o contexto para prever categoria baseada em descrições frequentes.
     */
    private function enrichWithContext(array $tx, ?IAContext $context): array {
        if (!$context) return $tx;

        foreach ($context->frequentCategories as $freq) {
            if (stripos($tx['description'], $freq['description']) !== false) {
                $tx['categoryId'] = (int) $freq['CategoryId'];
                $tx['confidence'] = 'high';
                break;
            }
        }
        
        return $tx;
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

    private function detectType(string $val): TransactionType {
        $isIncome = (stripos($val, 'C') !== false || (strpos($val, '-') === false && strpos($val, '(') === false));
        return $isIncome ? TransactionType::ASSET : TransactionType::BILL;
    }
}
