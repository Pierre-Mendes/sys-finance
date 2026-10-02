<?php

namespace App\Services;

use App\Notifications\PushSender;
use App\Repositories\BillReminderRepository;
use App\Repositories\NotificationSettingsRepository;
use App\Repositories\PushSubscriptionRepository;
use DateTimeImmutable;
use Throwable;

/**
 * Lembretes de contas a vencer (D-N, no dia e em atraso).
 *
 * Roda periodicamente (bin/reminders.php). Cada aviso é enviado uma única vez por conta, usuário,
 * tipo e vencimento; se o agendador ficar parado, os avisos do dia saem na próxima execução
 * depois do horário escolhido pelo usuário.
 */
class BillReminderService {
    /** Maior antecedência aceita nas preferências. */
    public const MAX_DAYS_BEFORE = 7;
    /**
     * Atrasada há mais que isso = "esquecida": não recebe o aviso diário de atraso; em vez disso o usuário
     * recebe, uma vez por mês, um pedido para decidir (pagar, reagendar ou desconsiderar). A previsão de saldo
     * também deixa essas contas de fora.
     */
    public const STALE_AFTER_DAYS = 30;
    private const OVERDUE_LOOKBACK_DAYS = self::STALE_AFTER_DAYS;

    public function __construct(
        private BillReminderRepository $reminders,
        private NotificationSettingsRepository $settings,
        private PushSubscriptionRepository $subscriptions,
        private NotificationService $notifications,
        private PushSender $push,
    ) {}

    /**
     * @return array{inApp: int, pushUsers: int} quantidade de avisos criados e de usuários que receberam push
     */
    public function run(DateTimeImmutable $now): array {
        $today = $now->format('Y-m-d');
        $rows = $this->reminders->findPendingForMembers(
            $now->modify('-' . self::OVERDUE_LOOKBACK_DAYS . ' days')->format('Y-m-d'),
            $now->modify('+' . self::MAX_DAYS_BEFORE . ' days')->format('Y-m-d')
        );
        $hour = (int) $now->format('G');
        $settings = $this->settings->getMany(array_column($rows, 'userId'));
        $staleCreated = $this->staleReview($now, $settings, $hour);
        if (!$rows) return ['inApp' => $staleCreated, 'pushUsers' => 0];

        $sentAt = $now->format('Y-m-d H:i:s');

        $created = 0;
        $pushByUser = [];
        foreach ($rows as $bill) {
            $prefs = $settings[$bill['userId']];
            if ($hour < $prefs['reminderHour']) continue;

            $kind = self::kindFor(self::daysUntil($today, $bill['dueDate']), $prefs);
            if ($kind === null) continue;
            if (!$this->reminders->markSent($bill['billId'], $bill['userId'], $kind, $bill['dueDate'], $sentAt)) continue;

            [$title, $message] = self::describe($bill, $kind);
            $this->notifications->notify($bill['userId'], $title, $message, 'SLA_WARNING', $bill['billId'], '/transactions?status=PENDING');
            $created++;

            if ($prefs['pushEnabled']) {
                $pushByUser[$bill['userId']][] = ['bill' => $bill, 'kind' => $kind, 'title' => $title, 'message' => $message];
            }
        }

        foreach ($pushByUser as $userId => $items) {
            $this->pushTo($userId, self::pushPayload($items));
        }

        $created += $staleCreated;

        return ['inApp' => $created, 'pushUsers' => count($pushByUser)];
    }

    /**
     * Uma vez por mês, para cada usuário com contas atrasadas há mais de STALE_AFTER_DAYS: um aviso pedindo
     * para revisar (Dashboard → "Contas atrasadas"). Registro com bill_id 0 + kind STALE + mês.
     */
    private function staleReview(DateTimeImmutable $now, array $settings, int $hour): int {
        $stale = $this->reminders->findPendingForMembers('1900-01-01', $now->modify('-' . (self::STALE_AFTER_DAYS + 1) . ' days')->format('Y-m-d'));
        if (!$stale) return 0;

        $byUser = [];
        foreach ($stale as $b) {
            $byUser[$b['userId']]['count'] = ($byUser[$b['userId']]['count'] ?? 0) + 1;
            $byUser[$b['userId']]['total'] = ($byUser[$b['userId']]['total'] ?? 0) + $b['amount'];
        }
        $settings += $this->settings->getMany(array_keys($byUser));
        $month = $now->format('Y-m-01');
        $created = 0;
        foreach ($byUser as $userId => $info) {
            if ($hour < ($settings[$userId]['reminderHour'] ?? 8)) continue;
            if (!$this->reminders->markSent(0, $userId, 'STALE', $month, $now->format('Y-m-d H:i:s'))) continue;
            $total = 'R$ ' . number_format($info['total'], 2, ',', '.');
            $plural = $info['count'] > 1;
            $this->notifications->notify(
                $userId,
                $plural ? "{$info['count']} contas atrasadas há mais de " . self::STALE_AFTER_DAYS . ' dias' : 'Conta atrasada há mais de ' . self::STALE_AFTER_DAYS . ' dias',
                "Somam {$total}. Diga o que fazer com " . ($plural ? 'elas' : 'ela') . ': marcar como paga, reagendar ou desconsiderar.',
                'SLA_WARNING', null, '/dashboard?review=overdue'
            );
            $created++;
        }
        return $created;
    }

    /**
     * Envia um push para todos os dispositivos do usuário e limpa os expirados.
     * Falha de rede no push não impede o aviso no app (que já foi gravado).
     *
     * @return int dispositivos para os quais o envio foi tentado
     */
    public function pushTo(int $userId, array $payload): int {
        $subs = $this->subscriptions->findByUser($userId);
        if (!$subs) return 0;
        try {
            $expired = $this->push->send($subs, $payload);
            if ($expired) $this->subscriptions->deleteByEndpoints($expired);
        } catch (Throwable $e) {
            error_log('[reminders] push falhou para o usuário ' . $userId . ': ' . $e->getMessage());
        }
        return count($subs);
    }

    public static function daysUntil(string $today, string $dueDate): int {
        return (int) (new DateTimeImmutable($today))->diff(new DateTimeImmutable($dueDate))->format('%r%a');
    }

    /** OVERDUE, D0 (vence hoje) ou D<n>; null se o usuário não quer aviso nesse dia. */
    public static function kindFor(int $daysLeft, array $prefs): ?string {
        if ($daysLeft < 0) return $prefs['notifyOverdue'] ? 'OVERDUE' : null;
        return in_array($daysLeft, $prefs['remindDays'], true) ? 'D' . $daysLeft : null;
    }

    private static function describe(array $bill, string $kind): array {
        $amount = 'R$ ' . number_format($bill['amount'], 2, ',', '.');
        $date = (new DateTimeImmutable($bill['dueDate']))->format('d/m');
        $when = match (true) {
            $kind === 'OVERDUE' => "venceu em {$date} e ainda está pendente",
            $kind === 'D0' => 'vence hoje',
            $kind === 'D1' => 'vence amanhã',
            default => 'vence em ' . substr($kind, 1) . " dias ({$date})",
        };
        $title = $kind === 'OVERDUE' ? "Conta atrasada: {$bill['title']}" : "Conta a vencer: {$bill['title']}";
        return [$title, "{$bill['title']} ({$amount}) {$when}. Workspace: {$bill['workspaceName']}."];
    }

    /** Um push por usuário: vários avisos viram um resumo, para não encher a tela de notificações. */
    private static function pushPayload(array $items): array {
        if (count($items) === 1) {
            return ['title' => $items[0]['title'], 'body' => $items[0]['message'], 'url' => '/transactions?status=PENDING', 'tag' => 'bill-' . $items[0]['bill']['billId']];
        }

        $overdue = count(array_filter($items, fn ($i) => $i['kind'] === 'OVERDUE'));
        $total = array_sum(array_map(fn ($i) => $i['bill']['amount'], $items));
        $titles = array_map(fn ($i) => $i['bill']['title'], array_slice($items, 0, 3));
        $more = count($items) > 3 ? ' e mais ' . (count($items) - 3) : '';

        return [
            'title' => count($items) . ' contas pedem atenção' . ($overdue ? " ({$overdue} atrasada" . ($overdue > 1 ? 's' : '') . ')' : ''),
            'body' => implode(', ', $titles) . $more . '. Total: R$ ' . number_format($total, 2, ',', '.'),
            'url' => '/transactions?status=PENDING',
            'tag' => 'bills-summary',
        ];
    }
}
