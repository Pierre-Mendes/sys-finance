<?php

namespace App\Notifications;

interface PushSender {
    /**
     * Envia a mesma mensagem para os dispositivos informados.
     *
     * @param array<int, array{endpoint: string, p256dh: string, auth: string}> $subscriptions
     * @param array{title: string, body: string, url?: string, tag?: string} $payload
     * @return string[] endpoints que o serviço de push informou como expirados (devem ser apagados)
     */
    public function send(array $subscriptions, array $payload): array;

    public function publicKey(): string;
}
