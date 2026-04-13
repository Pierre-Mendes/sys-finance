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

        // Create a local temporary file to avoid system /tmp permission issues
        $tempDir = __DIR__ . '/../../storage/temp';
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        
        $originalName = $file->getClientFilename();
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $tempFile = $tempDir . '/stmt_' . uniqid() . '.' . $extension;
        
        $file->moveTo($tempFile);

        try {
            $transactions = $this->importService->extractFromPdf($tempFile);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'count' => count($transactions),
                'transactions' => $transactions,
                'source' => 'hybrid_engine' // Indica que passou pelo motor inteligente
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            // Se falhou mesmo com a heurística, retorna um erro amigável para a IA aprender
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage(),
                'needs_ai_learning' => true // Flag para o frontend mostrar tela de aprendizado
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
}
