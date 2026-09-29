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
     * @param int|null $workspaceId Current workspace context for AI hydration
     * @return array
     * @throws Exception
     */
    public function extractFromPdf(string $filePath, ?int $workspaceId = null): array {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            // Run pdftotext -layout to preserve column alignment.
            // proc_open com array executa o binário diretamente, sem shell (sem risco de command injection).
            // Comando fixo + argumentos em array (sem shell); $filePath é gerado pelo servidor.
            $process = proc_open( // nosemgrep
                ['pdftotext', '-layout', $filePath, '-'],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );
            if (!is_resource($process)) {
                throw new Exception("Unable to start pdftotext.");
            }

            $text = (string) stream_get_contents($pipes[1]);
            $errorOutput = (string) stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $returnVar = proc_close($process);

            if ($returnVar !== 0) {
                throw new Exception("Error executing pdftotext (Exit Code $returnVar). Output: $errorOutput");
            }
            $text = rtrim($text, "\n");
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
            $context = null;

            if ($workspaceId) {
                $hydrator = new \App\Services\Hydration\ContextHydrator();
                $context = $hydrator->hydrateForWorkspace($workspaceId);
            }

            $transactions = $engine->analyze($text, $context);
            
            if (empty($transactions)) {
                throw new Exception("Layout do banco não reconhecido e a Heurística não conseguiu extrair dados seguros.");
            }
            
            return $transactions;
        }

        $adapter = new $selectedAdapterClass();
        return $adapter->parse($text);
    }
}
