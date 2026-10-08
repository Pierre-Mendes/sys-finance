<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AuthService;
use App\Services\WorkspaceService;
use App\Repositories\UserRepository;
use App\Services\NotificationService;
use App\Models\User;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Exception;

class AuthServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $userRepo;
    private $workspaceService;
    private $notificationService;
    private $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepo = Mockery::mock(UserRepository::class)->shouldIgnoreMissing();
        $this->workspaceService = Mockery::mock(WorkspaceService::class)->shouldIgnoreMissing();
        $this->notificationService = Mockery::mock(NotificationService::class)->shouldIgnoreMissing();
        $this->authService = new AuthService($this->userRepo, $this->workspaceService, $this->notificationService);
    }

    public function test_register_successful(): void
    {
        $this->userRepo->shouldReceive('findByEmail')->andReturn(null);
        $this->userRepo->shouldReceive('save')->andReturnUsing(function($user) {
            $user->setId(123);
            return $user;
        });
        $result = $this->authService->register([
            'firstName' => 'P', 'lastName' => 'M', 'email' => 'e@e.com', 'password' => '123'
        ]);
        $this->assertEquals(123, $result->getId());
    }

    public function test_register_fails_when_email_exists(): void
    {
        $this->expectException(Exception::class);
        $this->userRepo->shouldReceive('findByEmail')->andReturn(new User('x', 'x', 'e@e.com', 'x'));
        $this->authService->register(['firstName'=>'P','lastName'=>'M','email'=>'e@e.com','password'=>'123']);
    }

    public function test_login_successful(): void
    {
        $hashed = password_hash('pass', PASSWORD_DEFAULT);
        $user = new User('P', 'M', 'e@e.com', $hashed, 'BRL', 1, 'CODE');
        $this->userRepo->shouldReceive('findByEmail')->andReturn($user);
        $result = $this->authService->login('e@e.com', 'pass');
        $this->assertSame($user, $result);
    }

    public function test_login_rejects_the_stored_hash_used_as_password(): void
    {
        // Quem obtém o hash (vazamento de backup/banco) não pode entrar digitando o próprio hash
        $hashed = password_hash('pass', PASSWORD_DEFAULT);
        $user = new User('P', 'M', 'e@e.com', $hashed, 'BRL', 1, 'CODE');
        $this->userRepo->shouldReceive('findByEmail')->andReturn($user);

        $this->expectException(Exception::class);
        $this->authService->login('e@e.com', $hashed);
    }

    public function test_legacy_plain_text_password_still_logs_in_and_is_rehashed(): void
    {
        $user = new User('P', 'M', 'e@e.com', 'old-plain', 'BRL', 1, 'CODE');
        $this->userRepo->shouldReceive('findByEmail')->andReturn($user);

        $this->authService->login('e@e.com', 'old-plain');
        $this->assertTrue(password_verify('old-plain', $user->getPassword()));
    }

    public function test_login_rejects_empty_password_even_if_stored_value_is_empty(): void
    {
        $user = new User('P', 'M', 'e@e.com', '', 'BRL', 1, 'CODE');
        $this->userRepo->shouldReceive('findByEmail')->andReturn($user);

        $this->expectException(Exception::class);
        $this->authService->login('e@e.com', '');
    }
}
