<?php

namespace App\Controllers;

use App\Services\StatementImportService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class StatementImportController {
    
    private StatementImportService $importService;

    public function __construct(StatementImportService $importService) {
        $this->importService = $importService;
    }

    public function upload(Request $request, Response $response): Response {
        $uploadedFiles = $request->getUploadedFiles();
        
        if (empty($uploadedFiles['file'])) {
            $response->getBody()->write(json_encode(['error' => 'No file uploaded']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $file = $uploadedFiles['file'];
        
        if ($file->getError() !== UPLOAD_ERR_OK) {
            $response->getBody()->write(json_encode(['error' => 'File upload error']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Create a temporary file to work with pdftotext
        $tempFile = tempnam(sys_get_temp_dir(), 'statement_');
        $file->moveTo($tempFile);

        try {
            $transactions = $this->importService->extractFromPdf($tempFile);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'count' => count($transactions),
                'transactions' => $transactions
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
}
