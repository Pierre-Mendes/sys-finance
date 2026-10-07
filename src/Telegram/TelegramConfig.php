<?php

namespace App\Telegram;

use App\Security\SecretStore;

/**
 * Configuração do bot vinda do ambiente:
 *   TELEGRAM_BOT_TOKEN      token do @BotFather (obrigatório para ligar o bot)
 *   TELEGRAM_BOT_USERNAME   usuário do bot, sem @ (monta o link t.me/<bot>?start=<código>)
 *   TELEGRAM_WEBHOOK_SECRET opcional; sem ele é gerado uma vez e guardado em app_secrets
 */
final class TelegramConfig {
    public const SECRET_NAME = 'telegram_webhook_secret';

    public static function token(): ?string {
        return getenv('TELEGRAM_BOT_TOKEN') ?: null;
    }

    public static function botUsername(): ?string {
        $name = getenv('TELEGRAM_BOT_USERNAME');
        return $name ? ltrim($name, '@') : null;
    }

    public static function isEnabled(): bool {
        return self::token() !== null && self::botUsername() !== null;
    }

    public static function webhookSecret(SecretStore $store): string {
        $fromEnv = getenv('TELEGRAM_WEBHOOK_SECRET');
        if ($fromEnv) return $fromEnv;
        // O Telegram aceita só [A-Za-z0-9_-] no secret_token: base64url sem padding atende.
        return $store->getOrCreate(self::SECRET_NAME, 32);
    }
}
