<?php

namespace App\Notifications;

use App\Repositories\TelegramLinkRepository;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

/**
 * Envia avisos (ex.: contas a vencer) para o chat do Telegram vinculado ao usuário.
 * Falha de rede vira log: o aviso no app já foi gravado e não pode ser perdido por causa do Telegram.
 */
class TelegramNotifier implements ChatNotifier {
    private ClientInterface $http;

    public function __construct(private TelegramLinkRepository $links, private string $token, ?ClientInterface $http = null) {
        $this->http = $http ?? new Client(['timeout' => 5, 'connect_timeout' => 3, 'http_errors' => false]);
    }

    public function notify(int $userId, string $title, string $body): bool {
        $chatId = $this->links->findByUserId($userId)['chat_id'] ?? null;
        if (!$chatId) return false;

        $text = '🔔 <b>' . self::e($title) . "</b>\n" . self::e($body) . "\n\n/contas mostra todas as contas a pagar";
        try {
            $response = $this->http->request('POST', "https://api.telegram.org/bot{$this->token}/sendMessage", [
                'json' => ['chat_id' => (int) $chatId, 'text' => $text, 'parse_mode' => 'HTML'],
            ]);
            if ($response->getStatusCode() === 403) {
                // O usuário bloqueou o bot: desfaz o vínculo para não insistir.
                $this->links->unlink($userId);
                return false;
            }
            return $response->getStatusCode() < 300;
        } catch (\Throwable $e) {
            error_log('[reminders] Telegram falhou para o usuário ' . $userId . ': ' . $e->getMessage());
            return false;
        }
    }

    private static function e(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
