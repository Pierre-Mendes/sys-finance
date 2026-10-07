<?php

namespace App\Services;

use App\Adapters\Bank\ItauPDFAdapter;
use App\Adapters\Bank\SicoobPDFAdapter;
use App\Adapters\Bank\CSVStatementAdapter;
use App\Adapters\Bank\OFXStatementAdapter;
use Exception;

class StatementImportService {
    
    private array $adapters = [
        ItauPDFAdapter::class,
        SicoobPDFAdapter::class,
        CSVStatementAdapter::class,
        OFXStatementAdapter::class
    ];

    /**
     * Compatibilidade: lê o arquivo do disco e delega para extractFromContent().
     *
     * @param string $filePath Path to the statement file (pdf/csv/ofx/txt)
     * @param int|null $workspaceId Current workspace context for AI hydration
     * @return array
     * @throws Exception
     */
    public function extractFromPdf(string $filePath, ?int $workspaceId = null): array {
        $contents = file_get_contents($filePath);
        if ($contents === false) {
            throw new Exception("Unable to read file contents.");
        }
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return $this->extractFromContent($contents, $extension, $workspaceId);
    }

    /**
     * Extrai transações do conteúdo do extrato em memória (sem arquivo temporário no servidor).
     *
     * @param string $contents Raw file bytes
     * @param string $extension pdf|csv|ofx|txt
     * @param int|null $workspaceId Current workspace context for AI hydration
     * @return array
     * @throws Exception
     */
    public function extractFromContent(string $contents, string $extension, ?int $workspaceId = null): array {
        $text = strtolower($extension) === 'pdf' ? $this->pdfToText($contents) : $contents;

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

    /**
     * Converte o PDF em texto com `pdftotext -layout` (preserva o alinhamento das colunas).
     * O PDF entra pelo stdin e o texto sai pelo stdout: o comando é uma constante, sem nenhum
     * dado do usuário na linha de comando (sem risco de command injection) e sem arquivo temporário.
     *
     * @throws Exception
     */
    public function pdfToText(string $pdfBytes): string {
        $process = proc_open(
            'pdftotext -layout - -',
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        if (!is_resource($process)) {
            throw new Exception("Unable to start pdftotext.");
        }

        // Escreve no stdin em paralelo com a leitura do stdout/stderr para não travar com PDFs grandes.
        stream_set_blocking($pipes[0], false);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $text = '';
        $errorOutput = '';
        $offset = 0;
        $length = strlen($pdfBytes);
        $stdinOpen = true;
        if ($length === 0) {
            fclose($pipes[0]);
            $stdinOpen = false;
        }

        while (true) {
            $read = [];
            foreach ([1, 2] as $i) {
                if (!feof($pipes[$i])) {
                    $read[] = $pipes[$i];
                }
            }
            $write = $stdinOpen ? [$pipes[0]] : [];
            if (!$read && !$write) {
                break;
            }
            $except = null;
            if (stream_select($read, $write, $except, 30) === false) {
                break;
            }
            foreach ($write as $_) {
                $written = fwrite($pipes[0], substr($pdfBytes, $offset, 65536));
                $offset += (int) $written;
                if ($written === false || $offset >= $length) {
                    fclose($pipes[0]);
                    $stdinOpen = false;
                }
            }
            foreach ($read as $stream) {
                $chunk = (string) fread($stream, 65536);
                if ($stream === $pipes[1]) {
                    $text .= $chunk;
                } else {
                    $errorOutput .= $chunk;
                }
            }
        }
        if ($stdinOpen) {
            fclose($pipes[0]);
        }
        fclose($pipes[1]);
        fclose($pipes[2]);
        $returnVar = proc_close($process);

        if ($returnVar !== 0) {
            error_log("pdftotext failed (exit $returnVar): " . substr($errorOutput, 0, 500));
            throw new Exception("Não foi possível ler o PDF. Verifique se o arquivo não está corrompido ou protegido por senha.");
        }
        return rtrim($text, "\n");
    }
}
