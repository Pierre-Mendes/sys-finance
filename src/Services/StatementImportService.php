<?php

namespace App\Services;

use App\Adapters\Bank\ItauPDFAdapter;
use App\Adapters\Bank\SicoobPDFAdapter;
use Exception;

class StatementImportService {
    
    private array $adapters = [
        ItauPDFAdapter::class,
        SicoobPDFAdapter::class,
        CSVStatementAdapter::class,
        OFXStatementAdapter::class
    ];

    /**
     * @param string $filePath Path to the PDF file
     * @return array
     * @throws Exception
     */
    public function extractFromPdf(string $filePath): array {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            // Run pdftotext -layout to preserve column alignment
            $output = [];
            $returnVar = 0;
            $safePath = escapeshellarg($filePath);
            exec("pdftotext -layout $safePath - 2>&1", $output, $returnVar);

            if ($returnVar !== 0) {
                $errorOutput = implode("\n", $output);
                throw new Exception("Error executing pdftotext (Exit Code $returnVar). Output: $errorOutput");
            }
            $text = implode("\n", $output);
        } else {
            // Plain text formats (CSV, OFX)
            $text = file_get_contents($filePath);
            if ($text === false) {
                throw new Exception("Unable to read file contents.");
            }
        }
        
        // Select adapter
        $selectedAdapterClass = null;
        foreach ($this->adapters as $adapterClass) {
            if ($adapterClass::matches($text)) {
                $selectedAdapterClass = $adapterClass;
                break;
            }
        }

        if (!$selectedAdapterClass) {
            // Se nenhum adapter fixo bater, recorre ao Motor Híbrido (Heurística + Templates Aprendidos)
            $engine = new HybridAIEngine();
            $transactions = $engine->analyze($text);
            
            if (empty($transactions)) {
                throw new Exception("Layout do banco não reconhecido e a Heurística não conseguiu extrair dados seguros.");
            }
            
            return $transactions;
        }

        $adapter = new $selectedAdapterClass();
        return $adapter->parse($text);
    }
}
