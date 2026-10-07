<?php

namespace App\Repositories;

use PDO;

/**
 * Vínculos usuário ↔ chat do Telegram e consultas que o bot precisa sobre o usuário.
 */
class TelegramLinkRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /** Gera (ou troca) o código de vínculo do usuário. Mantém o chat atual até o novo código ser usado. */
    public function saveLinkCode(int $userId, int $workspaceId, string $code, string $expiresAt): void {
        $existing = $this->findByUserId($userId);
        if ($existing) {
            $stmt = $this->db->prepare("UPDATE telegram_links SET link_code = ?, link_code_expires_at = ?, workspace_id = ? WHERE user_id = ?");
            $stmt->execute([$code, $expiresAt, $workspaceId, $userId]);
            return;
        }
        $stmt = $this->db->prepare("INSERT INTO telegram_links (user_id, workspace_id, link_code, link_code_expires_at, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $workspaceId, $code, $expiresAt, date('Y-m-d H:i:s')]);
    }

    public function findByUserId(int $userId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM telegram_links WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByChatId(int $chatId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM telegram_links WHERE chat_id = ?");
        $stmt->execute([$chatId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Código ainda válido (não expirado). */
    public function findByValidCode(string $code, string $now): ?array {
        $stmt = $this->db->prepare("SELECT * FROM telegram_links WHERE link_code = ? AND link_code_expires_at >= ?");
        $stmt->execute([$code, $now]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Conclui o vínculo: o chat passa a pertencer a este usuário (e sai de qualquer outro) e o código é consumido. */
    public function link(int $userId, int $chatId, ?string $username, string $now): void {
        $this->db->prepare("UPDATE telegram_links SET chat_id = NULL WHERE chat_id = ? AND user_id <> ?")->execute([$chatId, $userId]);
        $stmt = $this->db->prepare("
            UPDATE telegram_links
            SET chat_id = ?, telegram_username = ?, link_code = NULL, link_code_expires_at = NULL, linked_at = ?
            WHERE user_id = ?
        ");
        $stmt->execute([$chatId, $username, $now, $userId]);
    }

    public function unlink(int $userId): void {
        $this->db->prepare("DELETE FROM telegram_links WHERE user_id = ?")->execute([$userId]);
    }

    public function setWorkspace(int $userId, int $workspaceId): void {
        $this->db->prepare("UPDATE telegram_links SET workspace_id = ? WHERE user_id = ?")->execute([$workspaceId, $userId]);
    }

    public function setLastTransaction(int $userId, ?string $type, ?int $id, ?string $at): void {
        $this->db->prepare("UPDATE telegram_links SET last_tx_type = ?, last_tx_id = ?, last_tx_at = ? WHERE user_id = ?")
            ->execute([$type, $id, $at, $userId]);
    }

    /** @return array<int, array{id: int, name: string, role: string}> espaços de que o usuário participa */
    public function workspacesOf(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT w.WorkspaceId AS id, w.WorkspaceName AS name, wu.Role AS role
            FROM workspaces w INNER JOIN workspace_users wu ON wu.WorkspaceId = w.WorkspaceId
            WHERE wu.UserId = ? ORDER BY wu.JoinedAt ASC, w.WorkspaceId ASC
        ");
        $stmt->execute([$userId]);
        return array_map(fn ($r) => ['id' => (int) $r['id'], 'name' => (string) $r['name'], 'role' => (string) $r['role']], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function firstName(int $userId): string {
        $stmt = $this->db->prepare("SELECT FirstName FROM user WHERE UserId = ?");
        $stmt->execute([$userId]);
        return (string) ($stmt->fetchColumn() ?: '');
    }

    /** @return array<int, array{name: string, balance: float}> saldo atual de cada conta (só lançamentos pagos) */
    public function accountBalances(int $workspaceId): array {
        $stmt = $this->db->prepare("
            SELECT a.AccountName AS name,
                COALESCE((SELECT SUM(s.Amount) FROM assets s WHERE s.AccountId = a.AccountId AND s.WorkspaceId = a.WorkspaceId AND s.status = 'PAID'), 0)
              - COALESCE((SELECT SUM(b.Amount) FROM bills b WHERE b.AccountId = a.AccountId AND b.WorkspaceId = a.WorkspaceId AND b.status = 'PAID'), 0) AS balance
            FROM account a WHERE a.WorkspaceId = ? ORDER BY a.AccountName ASC
        ");
        $stmt->execute([$workspaceId]);
        return array_map(fn ($r) => ['name' => (string) $r['name'], 'balance' => (float) $r['balance']], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Últimos lançamentos (receitas e despesas) do espaço, mais recentes primeiro.
     *
     * @return array<int, array{type: string, title: string, amount: float, date: string, status: string, category: string, account: string}>
     */
    public function recentTransactions(int $workspaceId, int $limit): array {
        $limit = max(1, min($limit, 20));
        $rows = [];
        foreach ([
            'asset' => "SELECT t.Title, t.Amount, t.Date AS d, t.status, t.AssetsId AS id, c.CategoryName AS category, a.AccountName AS account
                        FROM assets t LEFT JOIN category c ON c.CategoryId = t.CategoryId LEFT JOIN account a ON a.AccountId = t.AccountId
                        WHERE t.WorkspaceId = ? ORDER BY t.Date DESC, t.AssetsId DESC LIMIT $limit",
            'bill' => "SELECT t.Title, t.Amount, t.Dates AS d, t.status, t.BillsId AS id, c.CategoryName AS category, a.AccountName AS account
                       FROM bills t LEFT JOIN category c ON c.CategoryId = t.CategoryId LEFT JOIN account a ON a.AccountId = t.AccountId
                       WHERE t.WorkspaceId = ? ORDER BY t.Dates DESC, t.BillsId DESC LIMIT $limit",
        ] as $type => $sql) {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$workspaceId]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $rows[] = [
                    'type' => $type,
                    'id' => (int) $r['id'],
                    'title' => (string) $r['Title'],
                    'amount' => (float) $r['Amount'],
                    'date' => substr((string) $r['d'], 0, 10),
                    'status' => (string) $r['status'],
                    'category' => (string) ($r['category'] ?? ''),
                    'account' => (string) ($r['account'] ?? ''),
                ];
            }
        }
        usort($rows, fn ($a, $b) => [$b['date'], $b['id']] <=> [$a['date'], $a['id']]);
        return array_slice($rows, 0, $limit);
    }
}
