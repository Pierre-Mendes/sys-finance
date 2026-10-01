<?php

namespace App\Controllers;

use App\Notifications\PushEndpointPolicy;
use App\Notifications\PushSender;
use App\Repositories\NotificationSettingsRepository;
use App\Repositories\PushSubscriptionRepository;
use App\Services\BillReminderService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

/**
 * Preferências de lembrete e dispositivos do Web Push. Tudo é do usuário logado (não depende de workspace).
 */
class ReminderController {
    public function __construct(
        private NotificationSettingsRepository $settings,
        private PushSubscriptionRepository $subscriptions,
        private PushSender $push,
        private BillReminderService $reminders,
    ) {}

    public function getSettings(Request $request, Response $response): Response {
        $userId = (int) $request->getAttribute('userId');
        return $this->json($response, ['data' => $this->settings->get($userId) + [
            'devices' => $this->subscriptions->countByUser($userId),
        ]]);
    }

    public function updateSettings(Request $request, Response $response): Response {
        $userId = (int) $request->getAttribute('userId');
        $input = (array) $request->getParsedBody();

        $days = $input['remindDays'] ?? null;
        $hour = $input['reminderHour'] ?? null;
        if (!is_array($days) || !is_int($hour) || $hour < 0 || $hour > 23) {
            return $this->json($response, ['error' => 'Informe os dias de aviso e um horário entre 0 e 23.'], 400);
        }
        foreach ($days as $d) {
            if (!is_int($d) || $d < 0 || $d > BillReminderService::MAX_DAYS_BEFORE) {
                return $this->json($response, ['error' => 'Dias de aviso devem ficar entre 0 e ' . BillReminderService::MAX_DAYS_BEFORE . '.'], 400);
            }
        }
        $days = array_values(array_unique($days));
        rsort($days);

        $settings = [
            'remindDays' => $days,
            'notifyOverdue' => (bool) ($input['notifyOverdue'] ?? true),
            'pushEnabled' => (bool) ($input['pushEnabled'] ?? true),
            'reminderHour' => $hour,
        ];
        $this->settings->save($userId, $settings);
        return $this->json($response, ['data' => $settings]);
    }

    public function publicKey(Request $request, Response $response): Response {
        return $this->json($response, ['publicKey' => $this->push->publicKey()]);
    }

    public function subscribe(Request $request, Response $response): Response {
        $userId = (int) $request->getAttribute('userId');
        $input = (array) $request->getParsedBody();
        $endpoint = (string) ($input['endpoint'] ?? '');
        $p256dh = (string) ($input['keys']['p256dh'] ?? '');
        $auth = (string) ($input['keys']['auth'] ?? '');

        if (!PushEndpointPolicy::isAllowed($endpoint)) {
            return $this->json($response, ['error' => 'Endereço de notificação não suportado.'], 422);
        }
        $b64url = '/^[A-Za-z0-9_-]+={0,2}$/';
        if (!preg_match($b64url, $p256dh) || strlen($p256dh) > 255 || !preg_match($b64url, $auth) || strlen($auth) > 64) {
            return $this->json($response, ['error' => 'Chaves de inscrição inválidas.'], 422);
        }

        $this->subscriptions->save($userId, $endpoint, $p256dh, $auth, $request->getHeaderLine('User-Agent') ?: null);
        return $this->json($response, ['success' => true], 201);
    }

    public function unsubscribe(Request $request, Response $response): Response {
        $userId = (int) $request->getAttribute('userId');
        $endpoint = (string) (((array) $request->getParsedBody())['endpoint'] ?? '');
        if ($endpoint !== '') {
            $this->subscriptions->deleteForUser($userId, $endpoint);
        }
        return $response->withStatus(204);
    }

    public function test(Request $request, Response $response): Response {
        $userId = (int) $request->getAttribute('userId');
        try {
            $devices = $this->reminders->pushTo($userId, [
                'title' => 'Notificações ativadas',
                'body' => 'Você vai receber aqui os lembretes das contas a vencer.',
                'url' => '/settings',
                'tag' => 'push-test',
            ]);
        } catch (Throwable $e) {
            return $this->json($response, ['error' => 'Não foi possível enviar a notificação de teste.'], 502);
        }
        if ($devices === 0) {
            return $this->json($response, ['error' => 'Nenhum dispositivo com notificações ativadas.'], 404);
        }
        return $this->json($response, ['success' => true, 'devices' => $devices]);
    }

    private function json(Response $response, array $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
