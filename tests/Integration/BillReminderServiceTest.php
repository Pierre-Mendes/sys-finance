<?php

namespace Tests\Integration;

use App\DTO\TransactionDTO;
use App\Notifications\PushSender;
use App\Repositories\AccountRepository;
use App\Repositories\AssetRepository;
use App\Repositories\BillReminderRepository;
use App\Repositories\BillRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\NotificationSettingsRepository;
use App\Repositories\PushSubscriptionRepository;
use App\Services\BillReminderService;
use App\Services\NotificationService;
use App\Services\ReferenceResolver;
use App\Services\TransactionService;
use App\Services\WorkspaceService;
use DateTimeImmutable;
use Tests\TestCase;

class FakePushSender implements PushSender {
    public array $sent = [];
    public array $expire = [];

    public function send(array $subscriptions, array $payload): array {
        $this->sent[] = ['endpoints' => array_column($subscriptions, 'endpoint'), 'payload' => $payload];
        return array_values(array_intersect(array_column($subscriptions, 'endpoint'), $this->expire));
    }

    public function publicKey(): string {
        return 'test-key';
    }
}

class BillReminderServiceTest extends TestCase
{
    private const OWNER = 1;
    private const PARTNER = 2;
    private const STRANGER = 3;

    private TransactionService $tx;
    private WorkspaceService $workspaces;
    private NotificationSettingsRepository $settings;
    private PushSubscriptionRepository $subs;
    private FakePushSender $push;
    private BillReminderService $service;
    private int $ws;

    protected function setUp(): void
    {
        parent::setUp();
        // O banco temporário é compartilhado pelos testes da classe.
        foreach ([
            'DELETE FROM bills', 'DELETE FROM assets', 'DELETE FROM totals', 'DELETE FROM account', 'DELETE FROM category',
            'DELETE FROM notifications', 'DELETE FROM bill_reminders_sent', 'DELETE FROM push_subscriptions',
            'DELETE FROM notification_settings', 'DELETE FROM workspace_users', 'DELETE FROM workspaces',
        ] as $sql) {
            $this->db->exec($sql);
        }
        $this->workspaces = new WorkspaceService($this->db);
        $this->tx = new TransactionService(
            new AssetRepository($this->db), new BillRepository($this->db), $this->db,
            new ReferenceResolver(new AccountRepository($this->db), new CategoryRepository($this->db)), $this->workspaces
        );
        $this->settings = new NotificationSettingsRepository($this->db);
        $this->subs = new PushSubscriptionRepository($this->db);
        $this->push = new FakePushSender();
        $this->service = new BillReminderService(
            new BillReminderRepository($this->db), $this->settings, $this->subs, new NotificationService($this->db), $this->push
        );

        $this->ws = $this->workspaces->createDefaultWorkspace(self::OWNER, 'Casa');
        $this->db->prepare("INSERT INTO workspace_users (WorkspaceId, UserId, Role) VALUES (?, ?, 'viewer')")->execute([$this->ws, self::PARTNER]);
        $this->workspaces->createDefaultWorkspace(self::STRANGER, 'Outro');
    }

    private function bill(string $title, string $due, string $status = 'PENDING', float $amount = 100): int
    {
        return (int) $this->tx->create($this->ws, new TransactionDTO([
            'type' => 'bill', 'title' => $title, 'amount' => $amount, 'date' => '2026-10-01', 'due_date' => $due,
            'accountName' => 'Banco', 'categoryName' => 'Casa', 'status' => $status,
        ]), self::OWNER)->getId();
    }

    private function notificationsFor(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT title, message, related_id, action_url FROM notifications WHERE user_id = ? AND type = 'SLA_WARNING' ORDER BY id");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    private function at(string $datetime): DateTimeImmutable
    {
        return new DateTimeImmutable($datetime);
    }

    public function test_reminds_every_workspace_member_on_default_days_only_once(): void
    {
        $this->bill('Aluguel', '2026-10-13');   // D-3
        $this->bill('Luz', '2026-10-11');       // D-1
        $this->bill('Internet', '2026-10-10');  // vence hoje
        $this->bill('Academia', '2026-10-12');  // D-2: fora do padrão (3,1,0)
        $this->bill('Água', '2026-10-13', 'PAID');

        $first = $this->service->run($this->at('2026-10-10 09:00'));
        $second = $this->service->run($this->at('2026-10-10 15:00'));

        $this->assertSame(6, $first['inApp'], '3 contas x 2 membros');
        $this->assertSame(0, $second['inApp'], 'O mesmo aviso não pode sair duas vezes');
        $titles = array_column($this->notificationsFor(self::PARTNER), 'title');
        sort($titles);
        $this->assertSame(['Conta a vencer: Aluguel', 'Conta a vencer: Internet', 'Conta a vencer: Luz'], $titles);
        $this->assertSame([], $this->notificationsFor(self::STRANGER), 'Quem não é do workspace não recebe');
        $this->assertSame('/transactions?status=PENDING', $this->notificationsFor(self::OWNER)[0]['action_url']);
    }

    public function test_waits_for_the_user_hour_and_catches_up_later_the_same_day(): void
    {
        $this->bill('Internet', '2026-10-10');
        $this->settings->save(self::OWNER, ['remindDays' => [0], 'notifyOverdue' => true, 'pushEnabled' => true, 'reminderHour' => 18]);

        $this->service->run($this->at('2026-10-10 08:00'));
        $this->assertSame([], $this->notificationsFor(self::OWNER), 'Antes do horário escolhido não avisa');
        $this->assertCount(1, $this->notificationsFor(self::PARTNER), 'O outro membro usa o padrão (8h)');

        $this->service->run($this->at('2026-10-10 21:00'));
        $this->assertCount(1, $this->notificationsFor(self::OWNER), 'Agendador atrasado ainda envia no mesmo dia');
    }

    public function test_overdue_bill_is_reminded_once_and_respects_preference(): void
    {
        $this->bill('Condomínio', '2026-10-05');
        $this->settings->save(self::PARTNER, ['remindDays' => [3, 1, 0], 'notifyOverdue' => false, 'pushEnabled' => true, 'reminderHour' => 8]);

        $this->service->run($this->at('2026-10-10 09:00'));
        $this->service->run($this->at('2026-10-11 09:00'));

        $owner = $this->notificationsFor(self::OWNER);
        $this->assertCount(1, $owner);
        $this->assertSame('Conta atrasada: Condomínio', $owner[0]['title']);
        $this->assertStringContainsString('venceu em 05/10', $owner[0]['message']);
        $this->assertSame([], $this->notificationsFor(self::PARTNER));
    }

    public function test_push_is_one_summary_per_user_and_expired_devices_are_removed(): void
    {
        $this->bill('Aluguel', '2026-10-13', 'PENDING', 1500);
        $this->bill('Luz', '2026-10-11', 'PENDING', 200);
        $this->subs->save(self::OWNER, 'https://fcm.googleapis.com/fcm/send/celular', 'p', 'a', 'Android');
        $this->subs->save(self::OWNER, 'https://fcm.googleapis.com/fcm/send/velho', 'p', 'a', null);
        $this->settings->save(self::PARTNER, ['remindDays' => [3, 1, 0], 'notifyOverdue' => true, 'pushEnabled' => false, 'reminderHour' => 8]);
        $this->subs->save(self::PARTNER, 'https://web.push.apple.com/iphone', 'p', 'a', 'iPhone');
        $this->push->expire = ['https://fcm.googleapis.com/fcm/send/velho'];

        $result = $this->service->run($this->at('2026-10-10 09:00'));

        $this->assertSame(1, $result['pushUsers'], 'Quem desligou o push só recebe no app');
        $this->assertCount(1, $this->push->sent);
        $this->assertSame('2 contas pedem atenção', $this->push->sent[0]['payload']['title']);
        $this->assertStringContainsString('R$ 1.700,00', $this->push->sent[0]['payload']['body']);
        $this->assertSame(1, $this->subs->countByUser(self::OWNER), 'Dispositivo expirado é apagado');
        $this->assertCount(2, $this->notificationsFor(self::PARTNER));
    }

    public function test_days_until_handles_month_boundaries(): void
    {
        $this->assertSame(1, BillReminderService::daysUntil('2026-10-31', '2026-11-01'));
        $this->assertSame(-3, BillReminderService::daysUntil('2026-10-10', '2026-10-07'));
        $this->assertSame(0, BillReminderService::daysUntil('2026-10-10', '2026-10-10'));
    }
}
