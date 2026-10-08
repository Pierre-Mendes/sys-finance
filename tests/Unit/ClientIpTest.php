<?php

namespace Tests\Unit;

use App\Security\ClientIp;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

class ClientIpTest extends TestCase
{
    private function request(string $remote, ?string $forwardedFor = null)
    {
        $r = (new ServerRequestFactory())->createServerRequest('POST', '/api/auth/login', ['REMOTE_ADDR' => $remote]);
        return $forwardedFor === null ? $r : $r->withHeader('X-Forwarded-For', $forwardedFor);
    }

    public function test_without_trusted_proxies_ignores_forwarded_header(): void
    {
        $this->assertSame('203.0.113.9', ClientIp::from($this->request('203.0.113.9', '1.2.3.4'), ''));
    }

    public function test_untrusted_remote_cannot_spoof_its_ip(): void
    {
        $this->assertSame('203.0.113.9', ClientIp::from($this->request('203.0.113.9', '1.2.3.4'), '172.16.0.0/12'));
    }

    public function test_trusted_proxy_uses_the_client_seen_by_the_proxy(): void
    {
        // tailscale serve no host -> Docker: o container vê o gateway da bridge
        $this->assertSame('100.101.102.103', ClientIp::from($this->request('172.18.0.1', '100.101.102.103'), '172.16.0.0/12,127.0.0.1'));
    }

    public function test_spoofed_left_entries_are_ignored(): void
    {
        $req = $this->request('172.18.0.1', '9.9.9.9, 100.101.102.103');
        $this->assertSame('100.101.102.103', ClientIp::from($req, '172.16.0.0/12'));
    }

    public function test_skips_chained_trusted_proxies(): void
    {
        $req = $this->request('172.18.0.1', '100.64.0.7, 127.0.0.1');
        $this->assertSame('100.64.0.7', ClientIp::from($req, '172.16.0.0/12,127.0.0.1'));
    }

    public function test_malformed_header_falls_back_to_the_proxy(): void
    {
        $this->assertSame('172.18.0.1', ClientIp::from($this->request('172.18.0.1', 'not-an-ip'), '172.16.0.0/12'));
        $this->assertSame('172.18.0.1', ClientIp::from($this->request('172.18.0.1'), '172.16.0.0/12'));
    }

    public function test_ipv6_cidr(): void
    {
        $this->assertSame('2001:db8::42', ClientIp::from($this->request('fd00::1', '2001:db8::42'), 'fd00::/8'));
        $this->assertSame('fe80::1', ClientIp::from($this->request('fe80::1', '2001:db8::42'), 'fd00::/8'));
    }
}
