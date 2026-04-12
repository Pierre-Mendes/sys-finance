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
        
        // Padrão genérico: Data (DD/MM ou DD/MM/AAAA) + Descrição + Valor (R$ 0,00 ou -0,00)
        // Este é um motor simplificado que busca linhas que começam com data e terminam com valor
        $lines = explode("\n", $text);
        
        foreach ($lines as $line) {
            // Tenta capturar: Data | Descrição | Valor
            // Ex: 10/04/2026 PIX RECEBIDO 150,00
            if (preg_match('/^(\d{2}[\/\-]\d{2}(?:[\/\-]\d{2,4})?)\s+(.+?)\s+(-?[\d\.]+,\d{2})$/', trim($line), $m)) {
                $transactions[] = [
                    'date' => $this->normalizeDate($m[1], 'd/m/Y'), // Detectado via Regex
                    'description' => trim($m[2]),
                    'amount' => $this->parseAmount($m[3]),
                    'type' => $this->detectType($m[3])
                ];
            }
        }

        return $transactions;
    }

    private function normalizeDate(string $dateStr, string $format): string {
        $d = \DateTime::createFromFormat($format, $dateStr);
        if (!$d) {
            // Fallback para datas curtas (DD/MM) assumindo o ano atual
            $d = \DateTime::createFromFormat('d/m', $dateStr);
            if (!$d) return date('Y-m-d');
        }
        return $d->format('Y-m-d');
    }

    private function parseAmount(string $val): float {
        $val = str_replace(['R$', ' ', '.'], '', $val);
        $val = str_replace(',', '.', $val);
        return (float) $val;
    }

    private function detectType(string $val): string {
        return (strpos($val, '-') !== false) ? 'liability' : 'asset';
    }
}
