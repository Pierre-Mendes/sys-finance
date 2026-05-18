<?php

namespace App\Controllers;

use App\Services\External\TelegramService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TelegramController {
    private TelegramService $telegramService;

    public function __construct(TelegramService $telegramService) {
        $this->telegramService = $telegramService;
    }

    /**
     * Recebe as atualizações do Telegram via Webhook.
     */
    public function webhook(Request $request, Response $response): Response {
        $payload = json_decode($request->getBody()->getContents(), true);
        
        if ($payload) {
            try {
                $this->telegramService->handleWebhook($payload);
            } catch (\Exception $e) {
                // Log error but must return 200 to Telegram
                error_log("Telegram Webhook Error: " . $e->getMessage());
            }
        }

        // Telegram exige que retornemos 200 OK para confirmar o recebimento
        $response->getBody()->write(json_encode(['ok' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
