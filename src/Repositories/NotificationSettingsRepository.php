<?php

namespace App\Repositories;

use PDO;

/**
 * Preferências de lembrete por usuário. Quem nunca salvou recebe os padrões.
 */
class NotificationSettingsRepository {
    public const DEFAULTS = [
        'remindDays' => [3, 1, 0],
        'notifyOverdue' => true,
        'pushEnabled' => true,
        'reminderHour' => 8,
    ];

    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function get(int $userId): array {
        $stmt = $this->db->prepare("SELECT remind_days, notify_overdue, push_enabled, reminder_hour FROM notification_settings WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? self::fromRow($row) : self::DEFAULTS;
    }

    /**
     * @param int[] $userIds
     * @return array<int, array> preferências indexadas por usuário (padrões para quem não salvou)
     */
    public function getMany(array $userIds): array {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        $result = array_fill_keys($userIds, self::DEFAULTS);
        if (!$userIds) return $result;

        $in = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $this->db->prepare("SELECT user_id, remind_days, notify_overdue, push_enabled, reminder_hour FROM notification_settings WHERE user_id IN ($in)");
        $stmt->execute($userIds);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[(int) $row['user_id']] = self::fromRow($row);
        }
        return $result;
    }

    public function save(int $userId, array $settings): void {
        $params = [
            implode(',', $settings['remindDays']),
            $settings['notifyOverdue'] ? 1 : 0,
            $settings['pushEnabled'] ? 1 : 0,
            $settings['reminderHour'],
            date('Y-m-d H:i:s'),
            $userId,
        ];
        $upd = $this->db->prepare("UPDATE notification_settings SET remind_days = ?, notify_overdue = ?, push_enabled = ?, reminder_hour = ?, updated_at = ? WHERE user_id = ?");
        $upd->execute($params);
        if ($upd->rowCount() === 0) {
            $ins = $this->db->prepare("INSERT INTO notification_settings (remind_days, notify_overdue, push_enabled, reminder_hour, updated_at, user_id) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->execute($params);
        }
    }

    private static function fromRow(array $row): array {
        $days = array_values(array_filter(
            array_map('intval', explode(',', (string) $row['remind_days'])),
            fn ($d) => $d >= 0
        ));
        return [
            'remindDays' => $days,
            'notifyOverdue' => (bool) $row['notify_overdue'],
            'pushEnabled' => (bool) $row['push_enabled'],
            'reminderHour' => (int) $row['reminder_hour'],
        ];
    }
}
