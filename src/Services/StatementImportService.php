<?php

namespace App\Services;

use App\Adapters\Bank\ItauPDFAdapter;
use App\Adapters\Bank\SicoobPDFAdapter;
use Exception;

class StatementImportService {
    
    private array $adapters = [
        ItauPDFAdapter::class,
        SicoobPDFAdapter::class
    ];

    /**
     * @param string $filePath Path to the PDF file
     * @return array
     * @throws Exception
     */
    public function extractFromPdf(string $filePath): array {
        // Run pdftotext -layout to preserve column alignment
        $output = [];
        $returnVar = 0;
        
        // Escape the file path for security
        $safePath = escapeshellarg($filePath);
        exec("pdftotext -layout $safePath -", $output, $returnVar);

        if ($returnVar !== 0) {
            throw new Exception("Error executing pdftotext. Make sure poppler-utils is installed.");
        }

        $text = implode("\n", $output);
        
        // Select adapter
        $selectedAdapterClass = null;
        foreach ($this->adapters as $adapterClass) {
            if ($adapterClass::matches($text)) {
                $selectedAdapterClass = $adapterClass;
                break;
            }
        }

        if (!$selectedAdapterClass) {
            throw new Exception("Bank layout not recognized. We currently support Itaú and Sicoob.");
        }

        $adapter = new $selectedAdapterClass();
        return $adapter->parse($text);
    }
}
