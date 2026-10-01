<?php

namespace App\Notifications;

/**
 * O servidor faz POST para o endpoint enviado pelo navegador. Para não virar SSRF,
 * só aceitamos HTTPS nos serviços de push dos navegadores.
 */
final class PushEndpointPolicy {
    private const ALLOWED_HOSTS = [
        'fcm.googleapis.com',                 // Chrome, Android, Opera, Samsung
        'updates.push.services.mozilla.com',  // Firefox
    ];
    private const ALLOWED_SUFFIXES = [
        '.push.apple.com',                    // Safari / iOS (web.push.apple.com)
        '.notify.windows.com',                // Edge (WNS)
        '.push.services.mozilla.com',
    ];

    public static function isAllowed(string $endpoint): bool {
        if (strlen($endpoint) > 2048) return false;
        $parts = parse_url($endpoint);
        if (!$parts || ($parts['scheme'] ?? '') !== 'https') return false;
        if (isset($parts['user']) || isset($parts['pass'])) return false;
        if (isset($parts['port']) && (int) $parts['port'] !== 443) return false;

        $host = strtolower($parts['host'] ?? '');
        if (in_array($host, self::ALLOWED_HOSTS, true)) return true;
        foreach (self::ALLOWED_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix)) return true;
        }
        return false;
    }
}
