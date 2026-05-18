<?php

namespace App\Services\External;

use App\Services\HybridAIEngine;
use App\Services\Hydration\ContextHydrator;
use App\Services\TransactionService;
use App\Models\Transaction;
use App\Domain\Enums\TransactionType;
use Exception;

class TelegramService {
    private string $token;
    private string $apiUrl;
    private HybridAIEngine $aiEngine;
    private ContextHydrator $contextHydrator;

    public function __construct(string $token, HybridAIEngine $aiEngine, ContextHydrator $contextHydrator) {
        $this->token = $token;
        $this->apiUrl = "https://api.telegram.org/bot$token/";
        $this->aiEngine = $aiEngine;
        $this->contextHydrator = $contextHydrator;
    }

    public function handleWebhook(array $update): void {
        if (!isset($update['message'])) return;

        $message = $update['message'];
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $userId = $this->getUserIdFromTelegram($chatId); // Precisamos de um mapeamento TelegramID -> UserId

        if (!$userId) {
            $this->sendMessage($chatId, "Desculpe, não reconheci sua conta. Por favor, vincule seu Telegram no painel do sistema.");
            return;
        }

        if (str_starts_with($text, '/')) {
            $this->handleCommand($chatId, $text, $userId);
        } else {
            $this->handleNaturalLanguage($chatId, $text, $userId);
        }
    }

    private function handleCommand(int $chatId, string $text, int $userId): void {
        switch (explode(' ', $text)[0]) {
            case '/start':
                $this->sendMessage($chatId, "Olá! Eu sou o seu Assistente Financeiro IA. Você pode registrar gastos digitando coisas como: 'Gastei 50 no posto' ou 'Almoço 35'.");
                break;
            case '/resumo':
                // Chamar DashboardService para um resumo rápido
                $this->sendMessage($chatId, "Seu saldo atual é de R$ XXX,XX.");
                break;
            default:
                $this->sendMessage($chatId, "Comando não reconhecido.");
        }
    }

    private function handleNaturalLanguage(int $chatId, string $text, int $userId): void {
        $context = $this->contextHydrator->hydrateForWorkspace($userId); // Assumindo WorkspaceId = UserId para simplificar
        $result = $this->aiEngine->analyze($text, $context);

        if (!empty($result) && isset($result[0])) {
            $tx = $result[0];
            $amount = abs($tx['amount']);
            $desc = $tx['description'];
            
            $this->sendMessage($chatId, "Entendi! Registrei um gasto de R$ " . number_format($amount, 2, ',', '.') . " em '$desc'.");
            // Aqui chamaríamos o TransactionService para persistir
        } else {
            $this->sendMessage($chatId, "Hmm, não consegui entender esse lançamento. Pode tentar de novo com mais detalhes? Ex: '50 reais na padaria'");
        }
    }

    public function sendMessage(int $chatId, string $text): void {
        $url = $this->apiUrl . "sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }

    private function getUserIdFromTelegram(int $chatId): ?int {
        // Implementar busca no banco de dados
        // SELECT UserId FROM users WHERE telegram_chat_id = ?
        return 1; // Mock para testes
    }
}
