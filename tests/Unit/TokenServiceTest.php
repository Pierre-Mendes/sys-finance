<?php

namespace Tests\Unit;

use App\Security\TokenService;
use PHPUnit\Framework\TestCase;

class TokenServiceTest extends TestCase
{
    private const SECRET = 'test-secret-with-at-least-thirty-two-chars!!';

    public function test_issued_token_is_verified(): void
    {
        $service = new TokenService(self::SECRET, 3600);
        $claims = $service->verify($service->issue(42, 'user@example.com'));

        $this->assertNotNull($claims);
        $this->assertSame(42, $claims['sub']);
        $this->assertSame('user@example.com', $claims['email']);
    }

    public function test_legacy_base64_token_is_rejected(): void
    {
        $service = new TokenService(self::SECRET);
        $this->assertNull($service->verify(base64_encode('1:admin@example.com')));
    }

    public function test_tampered_payload_is_rejected(): void
    {
        $service = new TokenService(self::SECRET);
        [$header, , $signature] = explode('.', $service->issue(1, 'a@a.com'));
        $forgedPayload = rtrim(strtr(base64_encode(json_encode(['sub' => 2, 'exp' => time() + 3600])), '+/', '-_'), '=');

        $this->assertNull($service->verify("$header.$forgedPayload.$signature"));
    }

    public function test_token_signed_with_other_secret_is_rejected(): void
    {
        $other = new TokenService(str_repeat('x', 40));
        $this->assertNull((new TokenService(self::SECRET))->verify($other->issue(1, 'a@a.com')));
    }

    public function test_expired_token_is_rejected(): void
    {
        $service = new TokenService(self::SECRET, -10);
        $this->assertNull($service->verify($service->issue(1, 'a@a.com')));
    }

    public function test_short_secret_is_refused(): void
    {
        $this->expectException(\RuntimeException::class);
        new TokenService('short');
    }
}
