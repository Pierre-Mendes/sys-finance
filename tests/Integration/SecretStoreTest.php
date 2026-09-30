<?php

namespace Tests\Integration;

use App\Security\SecretStore;
use App\Security\TokenService;
use Tests\TestCase;

class SecretStoreTest extends TestCase
{
    public function test_secret_is_generated_once_and_reused(): void
    {
        $first = (new SecretStore($this->db))->getOrCreate('jwt_signing_key');
        $second = (new SecretStore($this->db))->getOrCreate('jwt_signing_key');

        $this->assertSame($first, $second);
        $this->assertGreaterThanOrEqual(32, strlen($first));
        $this->assertNotSame($first, (new SecretStore($this->db))->getOrCreate('another'));
    }

    public function test_token_survives_a_new_instance_without_env_secret(): void
    {
        putenv('JWT_SECRET');
        // Simula dois "deploys": instâncias diferentes, mesmo banco.
        $token = (new TokenService(null, 3600, new SecretStore($this->db)))->issue(7, 'a@b.com');
        $claims = (new TokenService(null, 3600, new SecretStore($this->db)))->verify($token);

        $this->assertNotNull($claims);
        $this->assertSame(7, $claims['sub']);
    }

    public function test_env_secret_takes_precedence_over_stored_key(): void
    {
        $stored = new TokenService(null, 3600, new SecretStore($this->db));
        $fromEnv = new TokenService(str_repeat('k', 40), 3600, new SecretStore($this->db));

        $this->assertNull($stored->verify($fromEnv->issue(1, 'a@b.com')));
    }
}
