<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\InvestmentQuoteService;
use PDO;
use Exception;

class InvestmentController
{
    private PDO $db;
    private InvestmentQuoteService $quoteService;

    public function __construct(PDO $db, InvestmentQuoteService $quoteService)
    {
        $this->db = $db;
        $this->quoteService = $quoteService;
    }

    public function index(Request $request, Response $response): Response
    {
        $workspaceId = $request->getAttribute('workspaceId');
        if (!$workspaceId) return $this->error($response, 'Workspace Context Missing', 403);

        $stmt = $this->db->prepare("SELECT * FROM investments WHERE workspaceId = :wid ORDER BY type ASC, ticker ASC");
        $stmt->execute(['wid' => $workspaceId]);
        $investments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch their transactions structure
        $ids = array_column($investments, 'id');
        $transactions = [];
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $tStmt = $this->db->prepare("SELECT * FROM investment_transactions WHERE investmentId IN ($placeholders) ORDER BY date DESC, id DESC");
            $tStmt->execute(array_map('intval', $ids));
            $tLog = $tStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($tLog as $t) {
                $transactions[$t['investmentId']][] = $t;
            }
        }
        
        foreach ($investments as &$inv) {
            $inv['transactions'] = $transactions[$inv['id']] ?? [];
        }

        return $this->json($response, ['data' => $investments]);
    }

    public function create(Request $request, Response $response): Response
    {
        $workspaceId = $request->getAttribute('workspaceId');
        if (!$workspaceId) return $this->error($response, 'Workspace Context Missing', 403);

        $data = json_decode((string)$request->getBody(), true);
        $type = $data['type'] ?? 'OUTRO';
        $ticker = !empty($data['ticker']) ? strtoupper(trim($data['ticker'])) : null;
        $name = $data['name'] ?? 'Investimento';

        try {
            $stmt = $this->db->prepare("INSERT INTO investments (workspaceId, type, ticker, name) VALUES (:wid, :t, :tick, :n)");
            $stmt->execute([
                'wid' => $workspaceId,
                't' => $type,
                'tick' => $ticker,
                'n' => $name
            ]);
            
            $id = $this->db->lastInsertId();
            return $this->json($response, ['message' => 'Criado com sucesso', 'id' => $id]);
        } catch (Exception $e) {
            return $this->error($response, 'Erro ao criar investimento: ' . \App\Security\PublicError::message($e), 500);
        }
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $workspaceId = $request->getAttribute('workspaceId');
        $id = $args['id'];

        $stmt = $this->db->prepare("DELETE FROM investments WHERE id = :id AND workspaceId = :wid");
        $stmt->execute(['id' => $id, 'wid' => $workspaceId]);

        return $this->json($response, ['message' => 'Apagado com sucesso.']);
    }

    public function addTransaction(Request $request, Response $response, array $args): Response
    {
        $workspaceId = $request->getAttribute('workspaceId');
        $id = $args['id'];

        // Verify ownership
        $vStmt = $this->db->prepare("SELECT * FROM investments WHERE id = :id AND workspaceId = :wid");
        $vStmt->execute(['id' => $id, 'wid' => $workspaceId]);
        $inv = $vStmt->fetch(PDO::FETCH_ASSOC);

        if (!$inv) {
             return $this->error($response, 'Investimento não encontrado', 404);
        }

        $data = json_decode((string)$request->getBody(), true);
        $action = $data['action'] ?? 'BUY';
        $quantity = (float)($data['quantity'] ?? 0);
        $price = (float)($data['price'] ?? 0);
        $date = $data['date'] ?? date('Y-m-d');
        
        if ($quantity <= 0 || $price <= 0) {
             return $this->error($response, 'Valores inválidos para aportar/vender', 400);
        }

        $this->db->beginTransaction();
        try {
            // Register Transaction
            $tStmt = $this->db->prepare("INSERT INTO investment_transactions (investmentId, action, quantity, price, date) VALUES (:id, :a, :q, :p, :d)");
            $tStmt->execute([
                'id' => $inv['id'],
                'a' => $action,
                'q' => $quantity,
                'p' => $price,
                'd' => $date
            ]);

            // Re-calculate Average Price & Quantities natively in PHP
            $aStmt = $this->db->prepare("SELECT * FROM investment_transactions WHERE investmentId = :id ORDER BY date ASC, id ASC");
            $aStmt->execute(['id' => $inv['id']]);
            $allTransactions = $aStmt->fetchAll(PDO::FETCH_ASSOC);

            $currentQty = 0.0;
            $currentAvg = 0.0;

            foreach ($allTransactions as $t) {
                $q = (float)$t['quantity'];
                $p = (float)$t['price'];
                if ($t['action'] === 'BUY') {
                    $newQty = $currentQty + $q;
                    if ($newQty > 0) {
                        $currentAvg = (($currentQty * $currentAvg) + ($q * $p)) / $newQty;
                    }
                    $currentQty = $newQty;
                } elseif ($t['action'] === 'SELL') {
                    $currentQty -= $q;
                    if ($currentQty < 0) $currentQty = 0;
                    if ($currentQty == 0) $currentAvg = 0;
                }
            }

            // Update parent object
            $uStmt = $this->db->prepare("UPDATE investments SET quantity = :q, average_price = :avg WHERE id = :id");
            $uStmt->execute(['q' => $currentQty, 'avg' => $currentAvg, 'id' => $inv['id']]);

            $this->db->commit();
            return $this->json($response, ['message' => 'Movimentação registrada com sucesso.']);
        } catch (Exception $e) {
            $this->db->rollBack();
            return $this->error($response, 'Falha ao lançar registro: ' . \App\Security\PublicError::message($e), 500);
        }
    }

    public function syncQuotes(Request $request, Response $response): Response
    {
        $workspaceId = $request->getAttribute('workspaceId');
        
        $stmt = $this->db->prepare("SELECT id, ticker, type FROM investments WHERE workspaceId = :wid AND type IN ('ACAO', 'FII') AND ticker IS NOT NULL AND ticker != ''");
        $stmt->execute(['wid' => $workspaceId]);
        $investments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($investments)) {
             return $this->json($response, ['message' => 'Nenhum ativo variável para sincronizar.', 'updatedcount' => 0]);
        }

        $tickers = array_unique(array_column($investments, 'ticker'));
        try {
            $quotes = $this->quoteService->getQuotes($tickers);
            
            $updatedCount = 0;
            $uStmt = $this->db->prepare("UPDATE investments SET current_price = :p WHERE id = :id");

            foreach ($investments as $inv) {
                $symbol = $inv['ticker'];
                if (isset($quotes[$symbol])) {
                    $uStmt->execute(['p' => $quotes[$symbol], 'id' => $inv['id']]);
                    $updatedCount++;
                }
            }

            return $this->json($response, ['message' => 'Cotações em tempo real atualizadas!', 'updatedcount' => $updatedCount]);
        } catch (Exception $e) {
            return $this->error($response, 'Erro na conexão com API Mercado: ' . \App\Security\PublicError::message($e), 500);
        }
    }

    public function manualQuote(Request $request, Response $response, array $args): Response
    {
        $workspaceId = $request->getAttribute('workspaceId');
        $id = $args['id'];
        
        $data = json_decode((string)$request->getBody(), true);
        $price = (float)($data['current_price'] ?? 0);

        $stmt = $this->db->prepare("UPDATE investments SET current_price = :p WHERE id = :id AND workspaceId = :wid");
        $stmt->execute(['p' => $price, 'id' => $id, 'wid' => $workspaceId]);

        return $this->json($response, ['message' => 'Cotação atualizada manualmente.']);
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    private function error(Response $response, string $message, int $status): Response
    {
        return $this->json($response, ['error' => $message], $status);
    }
}
