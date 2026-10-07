<?php

/**
 * Registra o webhook e o menu de comandos do bot no Telegram.
 *
 *   php bin/telegram-setup.php https://seu-dominio.com.br     (na VPS: docker exec financas-web-prod php bin/telegram-setup.php https://...)
 *   php bin/telegram-setup.php --info                          mostra o estado atual do webhook
 *
 * Precisa de TELEGRAM_BOT_TOKEN e TELEGRAM_BOT_USERNAME no ambiente. O Telegram só entrega em HTTPS.
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Database;
use App\Security\SecretStore;
use App\Telegram\TelegramConfig;

$token = TelegramConfig::token();
if (!$token || !TelegramConfig::botUsername()) {
    fwrite(STDERR, "Defina TELEGRAM_BOT_TOKEN e TELEGRAM_BOT_USERNAME no .env e recrie o container.\n");
    exit(1);
}

$http = new GuzzleHttp\Client(['base_uri' => "https://api.telegram.org/bot{$token}/", 'timeout' => 15, 'http_errors' => false]);
$call = function (string $method, array $payload = []) use ($http): array {
    $body = json_decode((string) $http->post($method, ['json' => $payload])->getBody(), true) ?: [];
    if (!($body['ok'] ?? false)) {
        fwrite(STDERR, "Telegram recusou {$method}: " . ($body['description'] ?? 'sem resposta') . "\n");
        exit(1);
    }
    return $body['result'];
};

$me = $call('getMe');
if (strcasecmp($me['username'], (string) TelegramConfig::botUsername()) !== 0) {
    fwrite(STDERR, "Atenção: o token é do bot @{$me['username']}, mas TELEGRAM_BOT_USERNAME é " . TelegramConfig::botUsername() . "\n");
    exit(1);
}

if (($argv[1] ?? '') === '--info') {
    print_r($call('getWebhookInfo'));
    exit(0);
}

$baseUrl = rtrim($argv[1] ?? (getenv('APP_URL') ?: ''), '/');
if (!str_starts_with($baseUrl, 'https://')) {
    fwrite(STDERR, "Informe a URL pública HTTPS do sistema, ex.: php bin/telegram-setup.php https://financas.seudominio.com.br\n");
    exit(1);
}

$secret = TelegramConfig::webhookSecret(new SecretStore(Database::getConnection()));
$call('setWebhook', [
    'url' => "{$baseUrl}/api/telegram/webhook",
    'secret_token' => $secret,
    'allowed_updates' => ['message'],
    'drop_pending_updates' => true,
]);
$call('setMyCommands', ['commands' => [
    ['command' => 'saldo', 'description' => 'Saldo das contas'],
    ['command' => 'mes', 'description' => 'Resumo do mês'],
    ['command' => 'contas', 'description' => 'Contas a pagar (7 dias)'],
    ['command' => 'ultimos', 'description' => 'Últimos lançamentos'],
    ['command' => 'desfazer', 'description' => 'Apagar o último lançamento feito aqui'],
    ['command' => 'espaco', 'description' => 'Trocar de espaço'],
    ['command' => 'ajuda', 'description' => 'Como lançar e consultar'],
]]);
$call('setMyDescription', ['description' => 'Lance gastos e receitas escrevendo como numa conversa ("mercado 120 nubank") e consulte saldo, resumo do mês e contas a pagar.']);

echo "Webhook registrado: {$baseUrl}/api/telegram/webhook (bot @{$me['username']})\n";
echo "Agora vincule sua conta em Configurações → Telegram.\n";
