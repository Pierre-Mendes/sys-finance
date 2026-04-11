<?php

namespace App\Services;

use PDO;

class NotificationService {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function notify(int $userId, string $title, string $message, string $type = 'SYSTEM', ?int $relatedId = null, ?string $actionUrl = null): void {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, title, message, type, related_id, action_url) 
            VALUES (:uid, :title, :message, :type, :rid, :aurl)
        ");
        $stmt->execute([
            'uid' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'rid' => $relatedId,
            'aurl' => $actionUrl
        ]);
    }

    public function hasUnsetSecurityNotification(int $userId): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM notifications 
            WHERE user_id = :uid AND message LIKE '%pergunta de recuperação%' AND read_at IS NULL
        ");
        $stmt->execute(['uid' => $userId]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
