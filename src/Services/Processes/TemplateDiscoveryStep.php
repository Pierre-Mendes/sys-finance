<?php

namespace App\Services\Processes;

use App\Database;
use PDO;

class TemplateDiscoveryStep {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Tenta descobrir o padrão de um novo extrato e salvar como template.
     */
    public function discover(string $rawText, array $validatedTransactions, string $bankName): bool {
        if (empty($validatedTransactions)) return false;

        $lines = explode("\n", $rawText);
        $sampleTx = $validatedTransactions[0]; // Usar a primeira como base
        
        $date = $sampleTx['date']; // Y-m-d
        $amount = number_format(abs($sampleTx['amount']), 2, ',', '.');
        $desc = $sampleTx['description'];

        // Encontrar a linha original no texto
        $patternLine = null;
        foreach ($lines as $line) {
            // Verifica se a linha contém as partes essenciais
            if ($this->isMatch($line, $sampleTx)) {
                $patternLine = $line;
                break;
            }
        }

        if (!$patternLine) return false;

        // Gerar Regex Genérico
        // Substituir Data por (\d{2}/\d{2})
        // Substituir Valor por ([\d\.]+,\d{2})
        // Substituir Descrição por (.*?)
        
        $rowPattern = $this->generateRowPattern($patternLine, $sampleTx);
        $detectionPattern = $bankName; // Simplificado: usa o nome do banco

        if (!$rowPattern) return false;

        // Salvar Template
        $stmt = $this->db->prepare("
            INSERT INTO bank_statement_templates (bank_name, detection_pattern, row_pattern, column_map, date_format)
            VALUES (?, ?, ?, ?, ?)
        ");

        $columnMap = json_encode([
            'date' => 1,
            'description' => 2,
            'amount' => 3
        ]);

        return $stmt->execute([
            $bankName,
            $detectionPattern,
            $rowPattern,
            $columnMap,
            'd/m' // Padronizado para a maioria dos bancos
        ]);
    }

    private function isMatch(string $line, array $tx): bool {
        $amountStr = number_format(abs($tx['amount']), 2, ',', '.');
        return stripos($line, $amountStr) !== false && stripos($line, $tx['description']) !== false;
    }

    private function generateRowPattern(string $line, array $tx): ?string {
        // Esta é uma implementação simplificada que busca os campos e os troca por grupos de captura
        // Ex: "10/04 PIX RECEBIDO 150,00 C" -> "^(\d{2}/\d{2})\s+(.*?)\s+([\d\.]+,\d{2})\s*([CD])?$"
        
        // 1. Identificar Posição da Data (DD/MM)
        if (!preg_match('/\d{2}\/\d{2}/', $line, $matches)) return null;
        $escapedLine = preg_quote($line, '/');
        
        // Trocamos os valores fixos por grupos de captura genericos
        $amountStr = number_format(abs($tx['amount']), 2, ',', '.');
        
        // Regex experimental
        $reg = '^';
        $reg .= '(\d{2}\/\d{2})'; // Grupo 1: Data
        $reg .= '.*?';
        $reg .= '(.*?)'; // Grupo 2: Descrição
        $reg .= '\s+';
        $reg .= '([\d\.]+,\d{2})'; // Grupo 3: Valor
        $reg .= '\s*([CDcd])?'; // Grupo 4: Tipo (opcional)
        $reg .= '$';

        return $reg;
    }
}
