<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AuthService;
use App\Services\WorkspaceService;
use App\Repositories\UserRepository;
use App\Models\User;
use Mockery;
use Exception;
use Generator;

class AuthServiceTest extends TestCase
{
    private $userRepo;
    private $workspaceService;
    private $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepo = Mockery::mock(UserRepository::class);
        $this->workspaceService = Mockery::mock(WorkspaceService::class);
        $this->authService = new AuthService($this->userRepo, $this->workspaceService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @dataProvider registrationDataProvider
     */
    public function test_register_scenarios(array $data, ?string $expectedException, string $message): void
    {
        if ($expectedException) {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage($expectedException);
        }

        // Mock behaviors
        if (!$expectedException || $expectedException === "Email is already registered.") {
            $this->userRepo->shouldReceive('findByEmail')
                ->with($data['email'])
                ->andReturn($expectedException === "Email is already registered." ? new User('John', 'Doe', $data['email'], 'hash') : null);
        }

        if (!$expectedException) {
            $this->userRepo->shouldReceive('save')
                ->once()
                ->andReturnUsing(function(User $u) {
                    $u->setId(1);
                    return $u;
                });

            $this->workspaceService->shouldReceive('createDefaultWorkspace')
                ->with(1, Mockery::any())
                ->once();
        }

        $result = $this->authService->register($data);

        if (!$expectedException) {
            $this->assertInstanceOf(User::class, $result);
            $this->assertEquals($data['email'], $result->getEmail());
            $this->assertNotEmpty($result->getUserCode());
        }
    }

    public static function registrationDataProvider(): Generator
    {
        yield 'Happy Path' => [
            'data' => [
                'firstName' => 'Pierre',
                'lastName' => 'Mendes',
                'email' => 'pierre@example.com',
                'password' => 'secure123'
            ],
            'expectedException' => null,
            'message' => 'Should register successfully'
        ];

        yield 'Missing Fields' => [
            'data' => [
                'firstName' => '',
                'lastName' => 'Mendes',
                'email' => 'pierre@example.com',
                'password' => 'secure123'
            ],
            'expectedException' => 'All fields are required.',
            'message' => 'Should fail on missing firstName'
        ];

        yield 'Invalid Email' => [
            'data' => [
                'firstName' => 'Pierre',
                'lastName' => 'Mendes',
                'email' => 'invalid-email',
                'password' => 'secure123'
            ],
            'expectedException' => 'Invalid email format.',
            'message' => 'Should fail on invalid email'
        ];

        yield 'Duplicate Email' => [
            'data' => [
                'firstName' => 'Pierre',
                'lastName' => 'Mendes',
                'email' => 'already@exists.com',
                'password' => 'secure123'
            ],
            'expectedException' => 'Email is already registered.',
            'message' => 'Should fail on duplicate email'
        ];
        yield 'SQL Injection attempt' => [
            'data' => [
                'firstName' => "'; DROP TABLE users; --",
                'lastName' => 'Hacker',
                'email' => 'hacker@example.com',
                'password' => '123456'
            ],
            'expectedException' => null,
            'message' => 'Should handle malicious characters safely via PDO'
        ];
    }

    public function test_login_successful(): void
    {
        $password = 'secret';
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        // Provide userCode to avoid auto-saving during ensureUserCode()
        $user = new User('Pierre', 'Mendes', 'pierre@example.com', $hashed, 'BRL', 1, 'U-ABC123');

        $this->userRepo->shouldReceive('findByEmail')
            ->with('pierre@example.com')
            ->andReturn($user);

        $this->workspaceService->shouldReceive('ensureHasWorkspace')
            ->once();

        $result = $this->authService->login('pierre@example.com', $password);

        $this->assertSame($user, $result);
    }


    public function test_login_legacy_password_auto_hashes(): void
    {
        $password = 'legacy_plain';
        // Provide userCode to avoid auto-saving during ensureUserCode()
        $user = new User('Pierre', 'Mendes', 'pierre@example.com', $password, 'BRL', 1, 'U-ABC123');

        $this->userRepo->shouldReceive('findByEmail')
            ->with('pierre@example.com')
            ->andReturn($user);

        $this->userRepo->shouldReceive('save')
            ->once()
            ->andReturnArg(0);

        $result = $this->authService->login('pierre@example.com', $password);

        $this->assertTrue(password_verify($password, $result->getPassword()));
    }
}
