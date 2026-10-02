<?php

namespace App\Repositories;

use PDO;

/**
 * Consultas dos relatórios. Devolvem linhas cruas; as contas (somas por mês, categoria, dia)
 * ficam no ReportService, em PHP, para o mesmo código valer no MySQL e no SQLite dos testes.
 * Datas sempre como 'Y-m-d' (sem funções de data específicas de banco).
 */
class ReportRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Lançamentos pagos no intervalo (inclusive), receitas e despesas.
     *
     * @return array<int, array{type: string, date: string, amount: float, category: string}>
     */
    public function paidBetween(int $workspaceId, string $from, string $to): array {
        $assets = $this->db->prepare("
            SELECT a.Date AS d, a.Amount AS amount, c.CategoryName AS category
            FROM assets a LEFT JOIN category c ON c.CategoryId = a.CategoryId
            WHERE a.WorkspaceId = ? AND a.status = 'PAID' AND a.Date BETWEEN ? AND ?
        ");
        $assets->execute([$workspaceId, $from, $to]);

        $bills = $this->db->prepare("
            SELECT b.Dates AS d, b.Amount AS amount, c.CategoryName AS category
            FROM bills b LEFT JOIN category c ON c.CategoryId = b.CategoryId
            WHERE b.WorkspaceId = ? AND b.status = 'PAID' AND b.Dates BETWEEN ? AND ?
        ");
        $bills->execute([$workspaceId, $from, $to]);

        $rows = [];
        foreach ([['asset', $assets], ['bill', $bills]] as [$type, $stmt]) {
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $rows[] = [
                    'type' => $type,
                    'date' => substr((string) $r['d'], 0, 10),
                    'amount' => (float) $r['amount'],
                    'category' => $r['category'] !== null ? (string) $r['category'] : 'Sem categoria',
                ];
            }
        }
        return $rows;
    }

    /** Saldo (receitas pagas - despesas pagas) antes da data, exclusive. */
    public function paidNetBefore(int $workspaceId, string $date): float {
        $in = $this->db->prepare("SELECT COALESCE(SUM(Amount), 0) FROM assets WHERE WorkspaceId = ? AND status = 'PAID' AND Date < ?");
        $in->execute([$workspaceId, $date]);
        $out = $this->db->prepare("SELECT COALESCE(SUM(Amount), 0) FROM bills WHERE WorkspaceId = ? AND status = 'PAID' AND Dates < ?");
        $out->execute([$workspaceId, $date]);
        return (float) $in->fetchColumn() - (float) $out->fetchColumn();
    }

    /** Saldo atual: tudo que já foi pago/recebido (mesma regra do AccountBalanceService). */
    public function currentBalance(int $workspaceId): float {
        $in = $this->db->prepare("SELECT COALESCE(SUM(Amount), 0) FROM assets WHERE WorkspaceId = ? AND status = 'PAID'");
        $in->execute([$workspaceId]);
        $out = $this->db->prepare("SELECT COALESCE(SUM(Amount), 0) FROM bills WHERE WorkspaceId = ? AND status = 'PAID'");
        $out->execute([$workspaceId]);
        return (float) $in->fetchColumn() - (float) $out->fetchColumn();
    }

    /** Primeiro e último ano com lançamentos (pagos ou não). */
    public function yearRange(int $workspaceId): ?array {
        $stmt = $this->db->prepare("
            SELECT MIN(d) AS first, MAX(d) AS last FROM (
                SELECT Date AS d FROM assets WHERE WorkspaceId = ?
                UNION ALL
                SELECT Dates AS d FROM bills WHERE WorkspaceId = ?
            ) t
        ");
        $stmt->execute([$workspaceId, $workspaceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['first'] === null) return null;
        return [(int) substr((string) $row['first'], 0, 4), (int) substr((string) $row['last'], 0, 4)];
    }

    /**
     * Lançamentos pendentes com data efetiva (vencimento ou, sem ele, a data do lançamento) até $to.
     *
     * @return array<int, array{type: string, title: string, amount: float, date: string, recurrence: string}>
     */
    public function pendingUntil(int $workspaceId, string $to): array {
        $rows = [];
        foreach ([
            'asset' => "SELECT Title, Amount, COALESCE(due_date, Date) AS d, recurrence_type FROM assets WHERE WorkspaceId = ? AND status = 'PENDING' AND COALESCE(due_date, Date) <= ?",
            'bill' => "SELECT Title, Amount, COALESCE(due_date, Dates) AS d, recurrence_type FROM bills WHERE WorkspaceId = ? AND status = 'PENDING' AND COALESCE(due_date, Dates) <= ?",
        ] as $type => $sql) {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$workspaceId, $to]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $rows[] = [
                    'type' => $type,
                    'title' => (string) $r['Title'],
                    'amount' => (float) $r['Amount'],
                    'date' => substr((string) $r['d'], 0, 10),
                    'recurrence' => (string) ($r['recurrence_type'] ?? 'NONE'),
                ];
            }
        }
        return $rows;
    }

    /**
     * Compras de cartão no intervalo, com o cartão (nome e dia de vencimento).
     *
     * @return array<int, array{cardName: string, closingDay: int, dueDay: int, date: string, amount: float}>
     */
    public function cardPurchasesBetween(int $workspaceId, string $from, string $to): array {
        $stmt = $this->db->prepare("
            SELECT c.Name AS card, c.ClosingDay AS closing_day, c.DueDay AS due_day, t.Date AS d, t.Amount AS amount
            FROM credit_card_transactions t
            INNER JOIN credit_cards c ON c.CardId = t.CardId AND c.WorkspaceId = t.WorkspaceId
            WHERE t.WorkspaceId = ? AND t.Date BETWEEN ? AND ?
        ");
        $stmt->execute([$workspaceId, $from, $to]);
        return array_map(fn ($r) => [
            'cardName' => (string) $r['card'],
            'closingDay' => (int) $r['closing_day'],
            'dueDay' => (int) $r['due_day'],
            'date' => substr((string) $r['d'], 0, 10),
            'amount' => (float) $r['amount'],
        ], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** Títulos das faturas já geradas ("Fatura <cartão> (<m>/<a>)"), para não contar a fatura duas vezes. */
    public function invoiceTitles(int $workspaceId): array {
        $stmt = $this->db->prepare("SELECT Title FROM bills WHERE WorkspaceId = ? AND Title LIKE 'Fatura %'");
        $stmt->execute([$workspaceId]);
        return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}
