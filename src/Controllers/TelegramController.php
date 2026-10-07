<?php

namespace App\Controllers;

use App\Repositories\TelegramLinkRepository;
use App\Security\PublicError;
use App\Telegram\TelegramBot;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TelegramController {
    private TelegramBot $bot;
    private TelegramLinkRepository $links;
    /** @var ?callable(): string */
    private $webhookSecret;
    private ?string $botUsername;

    /**
     * @param ?callable(): string $webhookSecret devolve o valor que o Telegram repete no header
     *        X-Telegram-Bot-Api-Secret-Token (o mesmo usado no setWebhook por bin/telegram-setup.php).
     *        Lido só quando preciso (sem consulta ao banco nas outras rotas); null = bot desligado.
     */
    public function __construct(TelegramBot $bot, TelegramLinkRepository $links, ?callable $webhookSecret, ?string $botUsername) {
        $this->bot = $bot;
        $this->links = $links;
        $this->webhookSecret = $webhookSecret;
        $this->botUsername = $botUsername ? ltrim($botUsername, '@') : null;
    }

    /** Recebe as mensagens do Telegram e responde no próprio corpo da resposta (método sendMessage). */
    public function webhook(Request $request, Response $response): Response {
        $received = $request->getHeaderLine('X-Telegram-Bot-Api-Secret-Token');
        if ($this->webhookSecret === null || $received === '' || !hash_equals(($this->webhookSecret)(), $received)) {
            return $response->withStatus(401);
        }

        $update = json_decode((string) $request->getBody(), true);
        try {
            $reply = is_array($update) ? $this->bot->handle($update) : null;
        } catch (\Throwable $e) {
            // Sempre 200: senão o Telegram reenvia a mesma mensagem e o lançamento poderia duplicar.
            PublicError::message($e);
            $reply = null;
        }

        if ($reply === null) return $response->withStatus(200);
        $response->getBody()->write(json_encode(['method' => 'sendMessage'] + $reply, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /** GET /api/telegram — estado do vínculo do usuário logado. */
    public function status(Request $request, Response $response): Response {
        $link = $this->links->findByUserId((int) $request->getAttribute('userId'));
        return $this->json($response, [
            'enabled' => $this->isEnabled(),
            'botUsername' => $this->botUsername,
            'linked' => (bool) ($link['chat_id'] ?? null),
            'telegramUsername' => $link['telegram_username'] ?? null,
            'linkedAt' => $link['linked_at'] ?? null,
        ]);
    }

    /** POST /api/telegram/link — gera o código de vínculo e o link que abre o bot já com o código. */
    public function createLink(Request $request, Response $response): Response {
        if (!$this->isEnabled()) {
            return $this->json($response, ['error' => 'O bot do Telegram ainda não foi configurado no servidor.'], 409);
        }
        $result = $this->bot->createLinkCode((int) $request->getAttribute('userId'), (int) $request->getAttribute('workspaceId'));
        return $this->json($response, $result + [
            'deepLink' => "https://t.me/{$this->botUsername}?start={$result['code']}",
            'botUsername' => $this->botUsername,
        ], 201);
    }

    /** DELETE /api/telegram/link — desconecta o Telegram do usuário. */
    public function deleteLink(Request $request, Response $response): Response {
        $this->links->unlink((int) $request->getAttribute('userId'));
        return $this->json($response, ['success' => true]);
    }

    private function isEnabled(): bool {
        return $this->webhookSecret !== null && $this->botUsername !== null;
    }

    private function json(Response $response, array $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
