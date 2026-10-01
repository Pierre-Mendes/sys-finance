<?php

namespace App\Security;

use RuntimeException;

/**
 * Emite e valida tokens JWT (HS256).
 *
 * Origem da chave de assinatura, em ordem:
 *   1. argumento explícito / variável JWT_SECRET (permite rotacionar manualmente);
 *   2. SecretStore: gerada automaticamente no primeiro uso e guardada no banco (sem configuração no deploy);
 *   3. chave fixa de desenvolvimento, só com APP_ENV testing/development/local.
 *
 * Substitui o antigo token base64("id:email"), que podia ser forjado por qualquer
 * pessoa que soubesse o id de um usuário (OWASP A07 - Identification and Authentication Failures).
 */
class TokenService {
    private const DEV_FALLBACK_SECRET = 'dev-only-insecure-secret-change-me-please-0123456789';
    private const STORE_KEY = 'jwt_signing_key';

    private ?string $secret = null;
    private ?SecretStore $store;
    private int $ttlSeconds;

    public function __construct(?string $secret = null, ?int $ttlSeconds = null, ?SecretStore $store = null) {
        $secret = $secret ?? (getenv('JWT_SECRET') ?: '');
        if ($secret !== '') {
            $this->secret = self::validated($secret);
        }

        $this->store = $store;
        $this->ttlSeconds = $ttlSeconds ?? (int) (getenv('JWT_TTL') ?: 60 * 60 * 24 * 7);

        if ($this->secret === null && $this->store === null) {
            $this->secret = self::devFallback();
        }
    }

    public function ttl(): int {
        return $this->ttlSeconds;
    }

    public function issue(int $userId, string $email): string {
        $now = time();
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload = [
            'sub' => $userId,
            'email' => $email,
            'iat' => $now,
            'exp' => $now + $this->ttlSeconds,
        ];

        $segments = [
            self::base64UrlEncode(json_encode($header)),
            self::base64UrlEncode(json_encode($payload)),
        ];
        $segments[] = self::base64UrlEncode($this->sign(implode('.', $segments)));

        return implode('.', $segments);
    }

    /**
     * @return array{sub:int,email:string,iat:int,exp:int}|null Claims válidos ou null se o token for inválido/expirado.
     */
    public function verify(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $header = json_decode(self::base64UrlDecode($encodedHeader), true);
        if (!is_array($header) || ($header['alg'] ?? null) !== 'HS256') return null;

        $expected = $this->sign($encodedHeader . '.' . $encodedPayload);
        if (!hash_equals($expected, self::base64UrlDecode($encodedSignature))) return null;

        $payload = json_decode(self::base64UrlDecode($encodedPayload), true);
        if (!is_array($payload) || empty($payload['sub']) || empty($payload['exp'])) return null;
        if ((int) $payload['exp'] < time()) return null;

        return $payload;
    }

    private function sign(string $data): string {
        return hash_hmac('sha256', $data, $this->secret(), true);
    }

    /** Resolvido sob demanda: rotas que não usam token (ex.: /api/health) não tocam o banco. */
    private function secret(): string {
        return $this->secret ??= self::validated($this->store->getOrCreate(self::STORE_KEY));
    }

    private static function validated(string $secret): string {
        if (strlen($secret) < 32) {
            throw new RuntimeException('JWT_SECRET deve ter pelo menos 32 caracteres.');
        }
        return $secret;
    }

    private static function devFallback(): string {
        // Sem banco e sem JWT_SECRET, só ambientes explicitamente locais podem usar a chave fixa.
        if (!in_array(getenv('APP_ENV'), ['testing', 'development', 'local'], true)) {
            throw new RuntimeException('Chave do JWT indisponível: defina JWT_SECRET ou forneça um SecretStore.');
        }
        return self::DEV_FALLBACK_SECRET;
    }

    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string {
        return (string) base64_decode(strtr($data, '-_', '+/'), true);
    }
}
