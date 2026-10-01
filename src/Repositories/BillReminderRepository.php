<?php

namespace App\Repositories;

use PDO;
use PDOException;

/**
 * Consultas dos lembretes de contas a vencer. SQL portável (MySQL e SQLite):
 * datas são comparadas como 'Y-m-d', sem CURDATE()/DATE_ADD().
 */
class BillReminderRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Contas pendentes com vencimento no intervalo, uma linha por membro do workspace.
     *
     * @return array<int, array{billId: int, title: string, amount: float, dueDate: string, workspaceId: int, workspaceName: string, userId: int}>
     */
    public function findPendingForMembers(string $fromDate, string $toDate): array {
        $stmt = $this->db->prepare("
            SELECT b.BillsId, b.Title, b.Amount, b.due_date, b.WorkspaceId, w.WorkspaceName, wu.UserId
            FROM bills b
            INNER JOIN workspace_users wu ON wu.WorkspaceId = b.WorkspaceId
            INNER JOIN workspaces w ON w.WorkspaceId = b.WorkspaceId
            WHERE b.status = 'PENDING'
              AND b.due_date IS NOT NULL
              AND b.due_date BETWEEN ? AND ?
            ORDER BY b.due_date, b.BillsId
        ");
        $stmt->execute([$fromDate, $toDate]);

        return array_map(fn ($r) => [
            'billId' => (int) $r['BillsId'],
            'title' => (string) $r['Title'],
            'amount' => (float) $r['Amount'],
            'dueDate' => substr((string) $r['due_date'], 0, 10),
            'workspaceId' => (int) $r['WorkspaceId'],
            'workspaceName' => (string) $r['WorkspaceName'],
            'userId' => (int) $r['UserId'],
        ], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Registra o envio. Devolve false se esse aviso já tinha sido enviado (chave única),
     * o que também protege contra duas execuções simultâneas do agendador.
     */
    public function markSent(int $billId, int $userId, string $kind, string $dueDate, string $sentAt): bool {
        try {
            $stmt = $this->db->prepare("INSERT INTO bill_reminders_sent (bill_id, user_id, kind, due_date, sent_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$billId, $userId, $kind, $dueDate, $sentAt]);
            return true;
        } catch (PDOException $e) {
            if ($this->wasSent($billId, $userId, $kind, $dueDate)) return false;
            throw $e;
        }
    }

    public function wasSent(int $billId, int $userId, string $kind, string $dueDate): bool {
        $stmt = $this->db->prepare("SELECT 1 FROM bill_reminders_sent WHERE bill_id = ? AND user_id = ? AND kind = ? AND due_date = ?");
        $stmt->execute([$billId, $userId, $kind, $dueDate]);
        return (bool) $stmt->fetchColumn();
    }
}
