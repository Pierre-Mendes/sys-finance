<?php

/**
 * Envia os lembretes de contas a vencer (no app e via Web Push).
 *
 * Seguro para rodar várias vezes: cada aviso sai uma única vez. Em produção roda a cada 15 minutos
 * pelo serviço "scheduler" do docker-compose. Uso manual: php bin/reminders.php
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Database;
use App\Notifications\WebPushSender;
use App\Repositories\BillReminderRepository;
use App\Repositories\NotificationSettingsRepository;
use App\Repositories\PushSubscriptionRepository;
use App\Security\SecretStore;
use App\Services\BillReminderService;
use App\Services\NotificationService;

$timezone = new DateTimeZone(getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo');
$db = Database::getConnection();

$service = new BillReminderService(
    new BillReminderRepository($db),
    new NotificationSettingsRepository($db),
    new PushSubscriptionRepository($db),
    new NotificationService($db),
    new WebPushSender(new SecretStore($db)),
);

$now = new DateTimeImmutable('now', $timezone);
$result = $service->run($now);
printf("[%s] lembretes: %d no app, push para %d usuário(s)\n", $now->format('Y-m-d H:i'), $result['inApp'], $result['pushUsers']);
