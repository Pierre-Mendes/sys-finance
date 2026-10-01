<?php

namespace App\Repositories;

use PDO;

/**
 * Dispositivos (navegadores/PWA) inscritos no Web Push de cada usuário.
 */
class PushSubscriptionRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Grava ou reatribui o dispositivo: o mesmo navegador pode trocar de usuário no login.
     */
    public function save(int $userId, string $endpoint, string $p256dh, string $auth, ?string $userAgent): void {
        $hash = hash('sha256', $endpoint);
        $userAgent = $userAgent !== null ? mb_substr($userAgent, 0, 255) : null;

        $upd = $this->db->prepare("UPDATE push_subscriptions SET user_id = ?, p256dh = ?, auth = ?, user_agent = ? WHERE endpoint_hash = ?");
        $upd->execute([$userId, $p256dh, $auth, $userAgent, $hash]);
        if ($upd->rowCount() > 0) return;

        $ins = $this->db->prepare("INSERT INTO push_subscriptions (user_id, endpoint, endpoint_hash, p256dh, auth, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$userId, $endpoint, $hash, $p256dh, $auth, $userAgent, date('Y-m-d H:i:s')]);
    }

    public function deleteForUser(int $userId, string $endpoint): void {
        $stmt = $this->db->prepare("DELETE FROM push_subscriptions WHERE endpoint_hash = ? AND user_id = ?");
        $stmt->execute([hash('sha256', $endpoint), $userId]);
    }

    /** Remove inscrições que o serviço de push informou como expiradas. */
    public function deleteByEndpoints(array $endpoints): void {
        $stmt = $this->db->prepare("DELETE FROM push_subscriptions WHERE endpoint_hash = ?");
        foreach ($endpoints as $endpoint) {
            $stmt->execute([hash('sha256', $endpoint)]);
        }
    }

    /** @return array<int, array{endpoint: string, p256dh: string, auth: string}> */
    public function findByUser(int $userId): array {
        $stmt = $this->db->prepare("SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE user_id = ? ORDER BY id");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByUser(int $userId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM push_subscriptions WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }
}
