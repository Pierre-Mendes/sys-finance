<?php

namespace Tests\Unit;

use App\Monitoring\SentryTunnel;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class SentryTunnelTest extends TestCase
{
    private const DSN = 'http://abc123@glitchtip:8000/2';

    private array $sent = [];

    private function tunnel(?string $dsn = self::DSN, int $upstreamStatus = 200): SentryTunnel
    {
        $stack = HandlerStack::create(new MockHandler([new Response($upstreamStatus)]));
        $stack->push(Middleware::history($this->sent));
        return new SentryTunnel($dsn, new Client(['handler' => $stack]));
    }

    private static function envelope(string $dsn): string
    {
        return json_encode(['dsn' => $dsn, 'event_id' => 'x']) . "\n" . '{"type":"event"}' . "\n" . '{"message":"boom"}';
    }

    public function test_forwards_envelope_of_the_configured_dsn_to_its_envelope_endpoint(): void
    {
        $status = $this->tunnel()->forward(self::envelope(self::DSN));

        $this->assertSame(200, $status);
        $this->assertCount(1, $this->sent);
        $this->assertSame('http://glitchtip:8000/api/2/envelope/', (string) $this->sent[0]['request']->getUri());
    }

    public function test_rejects_envelopes_for_other_dsns_without_calling_anyone(): void
    {
        $this->assertSame(400, $this->tunnel()->forward(self::envelope('http://outra@glitchtip:8000/2')));
        $this->assertSame(400, $this->tunnel()->forward(self::envelope('http://abc123@glitchtip:8000/9')));
        $this->assertSame(400, $this->tunnel()->forward("lixo\n{}"));
        $this->assertCount(0, $this->sent);
    }

    public function test_destination_always_comes_from_configuration(): void
    {
        // Mesma chave/projeto com outro host no envelope: repassa ao host configurado (sem SSRF / proxy aberto).
        $this->assertSame(200, $this->tunnel()->forward(self::envelope('http://abc123@evil.example/2')));
        $this->assertSame('glitchtip', $this->sent[0]['request']->getUri()->getHost());
    }

    public function test_disabled_without_dsn_and_limits_size(): void
    {
        $this->assertSame(404, $this->tunnel(null)->forward(self::envelope(self::DSN)));
        $this->assertSame(413, $this->tunnel()->forward(str_repeat('a', SentryTunnel::MAX_BYTES + 1)));
        $this->assertSame(413, $this->tunnel()->forward(''));
    }

    public function test_upstream_failure_becomes_502(): void
    {
        $this->assertSame(502, $this->tunnel(self::DSN, 500)->forward(self::envelope(self::DSN)));
    }

    public function test_parses_dsn_with_path_prefix_and_https(): void
    {
        $this->assertSame(
            'https://o1.ingest.sentry.io/api/42/envelope/',
            SentryTunnel::parseDsn('https://key@o1.ingest.sentry.io/42')['envelopeUrl']
        );
        $this->assertSame('http://host:9000/sentry/api/7/envelope/', SentryTunnel::parseDsn('http://k@host:9000/sentry/7')['envelopeUrl']);
        $this->assertNull(SentryTunnel::parseDsn('ftp://k@host/1'));
        $this->assertNull(SentryTunnel::parseDsn('http://host/1'));
    }
}
