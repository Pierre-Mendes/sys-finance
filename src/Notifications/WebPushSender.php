<?php

namespace App\Notifications;

use App\Security\SecretStore;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;

/**
 * Web Push com VAPID. O par de chaves é gerado no primeiro uso e guardado em app_secrets,
 * então não há nada para configurar no deploy (VAPID_PUBLIC_KEY/VAPID_PRIVATE_KEY têm prioridade se definidos).
 */
class WebPushSender implements PushSender {
    private SecretStore $secrets;
    private ?array $keys = null;

    public function __construct(SecretStore $secrets) {
        $this->secrets = $secrets;
    }

    public function publicKey(): string {
        return $this->keys()['publicKey'];
    }

    public function send(array $subscriptions, array $payload): array {
        if (!$subscriptions) return [];

        $keys = $this->keys();
        $webPush = new WebPush([
            'VAPID' => [
                'subject' => getenv('VAPID_SUBJECT') ?: 'mailto:noreply@sys-finance.app',
                'publicKey' => $keys['publicKey'],
                'privateKey' => $keys['privateKey'],
            ],
        ], ['TTL' => 86400, 'urgency' => 'normal']);

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        foreach ($subscriptions as $sub) {
            $webPush->queueNotification(Subscription::create([
                'endpoint' => $sub['endpoint'],
                'publicKey' => $sub['p256dh'],
                'authToken' => $sub['auth'],
                'contentEncoding' => 'aes128gcm',
            ]), $json);
        }

        $expired = [];
        foreach ($webPush->flush() as $report) {
            if (!$report->isSuccess() && $report->isSubscriptionExpired()) {
                $expired[] = $report->getEndpoint();
            }
        }
        return $expired;
    }

    private function keys(): array {
        if ($this->keys !== null) return $this->keys;

        $public = getenv('VAPID_PUBLIC_KEY');
        $private = getenv('VAPID_PRIVATE_KEY');
        if ($public && $private) {
            return $this->keys = ['publicKey' => $public, 'privateKey' => $private];
        }

        $stored = $this->secrets->getOrCreateWith('vapid_keys', fn () => json_encode(VAPID::createVapidKeys()));
        return $this->keys = json_decode($stored, true);
    }
}
