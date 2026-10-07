<?php

namespace App\Notifications;

/** Canal de mensagens em chat (ex.: Telegram) para avisos ao usuário. */
interface ChatNotifier {
    /** @return bool true se o usuário tem o canal vinculado e a mensagem saiu */
    public function notify(int $userId, string $title, string $body): bool;
}
