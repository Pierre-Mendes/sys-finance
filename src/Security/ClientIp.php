<?php

namespace App\Security;

use Psr\Http\Message\ServerRequestInterface;

/**
 * IP real do cliente, para limitar tentativas por pessoa e não por proxy.
 *
 * Atrás de um proxy (tailscale serve, Caddy, Nginx, Cloudflare Tunnel) todas as requisições chegam com o
 * REMOTE_ADDR do proxy. Se o REMOTE_ADDR estiver em TRUSTED_PROXIES (IPs/CIDRs separados por vírgula), o IP vem
 * do X-Forwarded-For: o primeiro endereço da DIREITA que não é proxy confiável (o que o último proxy viu).
 * O que o cliente escreve à esquerda no cabeçalho é ignorado, então não dá para forjar outro IP.
 * Sem TRUSTED_PROXIES (padrão) o cabeçalho nunca é lido: vale o REMOTE_ADDR.
 */
final class ClientIp
{
    public static function from(ServerRequestInterface $request, ?string $trustedProxies = null): string
    {
        $remote = (string) ($request->getServerParams()['REMOTE_ADDR'] ?? '');
        if (!filter_var($remote, FILTER_VALIDATE_IP)) {
            return '0.0.0.0';
        }

        $trusted = self::parseList($trustedProxies ?? (string) getenv('TRUSTED_PROXIES'));
        if (!$trusted || !self::inAny($remote, $trusted)) {
            return $remote;
        }

        $chain = array_reverse(array_map('trim', explode(',', $request->getHeaderLine('X-Forwarded-For'))));
        foreach ($chain as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                break; // cabeçalho malformado: não confia no que vem antes
            }
            if (!self::inAny($ip, $trusted)) {
                return $ip;
            }
            $remote = $ip;
        }
        return $remote;
    }

    /** @return string[] */
    private static function parseList(string $list): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $list)), fn($v) => $v !== ''));
    }

    /** @param string[] $cidrs */
    private static function inAny(string $ip, array $cidrs): bool
    {
        foreach ($cidrs as $cidr) {
            if (self::inCidr($ip, $cidr)) {
                return true;
            }
        }
        return false;
    }

    private static function inCidr(string $ip, string $cidr): bool
    {
        [$net, $bits] = str_contains($cidr, '/') ? explode('/', $cidr, 2) : [$cidr, null];
        $ipBin = @inet_pton($ip);
        $netBin = @inet_pton($net);
        if ($ipBin === false || $netBin === false || strlen($ipBin) !== strlen($netBin)) {
            return false;
        }
        $maxBits = strlen($ipBin) * 8;
        $bits = $bits === null ? $maxBits : (int) $bits;
        if ($bits < 0 || $bits > $maxBits) {
            return false;
        }
        $bytes = intdiv($bits, 8);
        if (substr($ipBin, 0, $bytes) !== substr($netBin, 0, $bytes)) {
            return false;
        }
        $rest = $bits % 8;
        if ($rest === 0) {
            return true;
        }
        $mask = (0xFF << (8 - $rest)) & 0xFF;
        return (ord($ipBin[$bytes]) & $mask) === (ord($netBin[$bytes]) & $mask);
    }
}
