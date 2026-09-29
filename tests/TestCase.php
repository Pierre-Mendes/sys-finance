<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use App\Database;
use PDO;
use Phinx\Config\Config;
use Phinx\Migration\Manager;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

abstract class TestCase extends BaseTestCase
{
    protected ?PDO $db = null;

    private static ?string $tempDbPath = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (self::$tempDbPath === null) {
            self::$tempDbPath = tempnam(sys_get_temp_dir(), 'test_db_');
        }

        // Reset Singleton
        Database::clearInstance();
        
        // Ensure we are in testing environment
        putenv('APP_ENV=testing');
        putenv('DB_NAME=' . self::$tempDbPath);
        
        // Initialize DB
        $this->db = Database::getConnection();
        
        // Run Migrations
        $this->runMigrations();

        // Setup VCR for external API recording
        if (class_exists('VCR\VCR')) {
            \VCR\VCR::configure()
                ->setCassettePath(__DIR__ . '/fixtures/vcr')
                ->enableLibraryHooks(['stream_wrapper', 'curl']);
            \VCR\VCR::turnOn();
        }
    }

    protected function tearDown(): void
    {
        if (class_exists('VCR\VCR')) {
            \VCR\VCR::turnOff();
        }
        Database::clearInstance();
        $this->db = null;
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$tempDbPath && file_exists(self::$tempDbPath)) {
            unlink(self::$tempDbPath);
        }
        self::$tempDbPath = null;
    }

    private function runMigrations(): void
    {
        $configData = [
            'paths' => [
                'migrations' => __DIR__ . '/../db/migrations'
            ],
            'environments' => [
                'default_migration_table' => 'phinxlog',
                'default_environment' => 'testing',
                'testing' => [
                    'adapter' => 'sqlite',
                    'connection' => $this->db
                ]
            ]
        ];

        $config = new Config($configData);
        $manager = new Manager($config, new StringInput(''), new NullOutput());
        $manager->migrate('testing');
    }
}
