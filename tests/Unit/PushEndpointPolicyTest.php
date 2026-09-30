<?php

namespace Tests\Unit;

use App\Notifications\PushEndpointPolicy;
use PHPUnit\Framework\TestCase;

class PushEndpointPolicyTest extends TestCase
{
    public function test_accepts_browser_push_services(): void
    {
        foreach ([
            'https://fcm.googleapis.com/fcm/send/abc:123',
            'https://updates.push.services.mozilla.com/wpush/v2/gAAA',
            'https://web.push.apple.com/QGx',
            'https://wns2-bl2p.notify.windows.com/w/?token=x',
        ] as $endpoint) {
            $this->assertTrue(PushEndpointPolicy::isAllowed($endpoint), $endpoint);
        }
    }

    public function test_rejects_endpoints_that_would_make_the_server_call_arbitrary_hosts(): void
    {
        foreach ([
            'http://fcm.googleapis.com/fcm/send/abc',          // sem TLS
            'https://169.254.169.254/latest/meta-data',        // metadados da nuvem
            'https://localhost/api/admin',
            'https://fcm.googleapis.com.evil.com/x',
            'https://evilpush.apple.com/x',                    // sufixo sem o ponto
            'https://fcm.googleapis.com:8443/x',
            'https://user:pass@fcm.googleapis.com/x',
            'file:///etc/passwd',
            '',
        ] as $endpoint) {
            $this->assertFalse(PushEndpointPolicy::isAllowed($endpoint), $endpoint);
        }
    }
}
