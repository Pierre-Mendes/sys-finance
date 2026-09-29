<?php

namespace App\Controllers;

use App\Services\StatementImportService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class StatementImportController {
    private const ALLOWED_EXTENSIONS = ['pdf', 'csv', 'ofx', 'txt'];
    private const MAX_UPLOAD_BYTES = 10 * 1024 * 1024;
    
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
            mkdir($tempDir, 0770, true);
        }
        
        // Nunca confiar no nome/extensão enviados pelo cliente (OWASP A04/A08: upload irrestrito).
        $originalName = (string) $file->getClientFilename();
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $response->getBody()->write(json_encode(['error' => 'Formato não suportado. Envie PDF, CSV, OFX ou TXT.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(415);
        }
        if ((int) $file->getSize() > self::MAX_UPLOAD_BYTES) {
            $response->getBody()->write(json_encode(['error' => 'Arquivo muito grande (máx. 10 MB).']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(413);
        }

        $tempFile = $tempDir . '/stmt_' . bin2hex(random_bytes(16)) . '.' . $extension;
        
        $file->moveTo($tempFile);

        $workspaceId = $request->getAttribute('workspaceId');

        try {
            $transactions = $this->importService->extractFromPdf($tempFile, $workspaceId);
            
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
