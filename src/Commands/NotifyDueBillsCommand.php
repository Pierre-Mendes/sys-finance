<?php

namespace App\Commands;

use App\Database;
use PDO;
use Exception;

class NotifyDueBillsCommand {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function execute(): void {
        echo "Starting SLA Notification Check...\n";

        // Identify PENDING bills where due_date is within next 3 days
        $stmt = $this->db->query("
            SELECT b.BillsId, b.Title, b.due_date, b.priority, b.WorkspaceId, u.Email, u.FirstName, u.UserId
            FROM bills b
            INNER JOIN workspace_users wu ON b.WorkspaceId = wu.WorkspaceId
            INNER JOIN user u ON wu.UserId = u.UserId
            WHERE b.status = 'PENDING'
              AND b.due_date IS NOT NULL
              AND b.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
        ");

        $dueBills = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($dueBills)) {
            echo "No upcoming bills found within 3 days.\n";
            return;
        }

        foreach ($dueBills as $bill) {
            $this->sendNotification($bill);
        }

        echo "SLA Notification Check completed. Processed " . count($dueBills) . " alerts.\n";
    }

    private function sendNotification(array $billData): void {
        $checkStmt = $this->db->prepare("SELECT id FROM notifications WHERE user_id = :uid AND related_id = :bid AND type = 'SLA_WARNING'");
        $checkStmt->execute(['uid' => $billData['UserId'], 'bid' => $billData['BillsId']]);
        if ($checkStmt->fetch()) {
            return; // Já notificado
        }

        $title = "Aviso de Vencimento: " . $billData['Title'];
        $dataBr = date('d/m/Y', strtotime($billData['due_date']));
        $message = "Atenção: A sua conta/despesa vence próximo dia {$dataBr}. Prioridade: {$billData['priority']}!";

        $insert = $this->db->prepare("INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (:uid, :t, :m, 'SLA_WARNING', :rid)");
        $insert->execute([
            'uid' => $billData['UserId'],
            't' => $title,
            'm' => $message,
            'rid' => $billData['BillsId']
        ]);

        echo "Created In-App notification for User {$billData['UserId']} (Bill {$billData['BillsId']})\n";
    }
}
