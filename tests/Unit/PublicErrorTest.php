<?php

namespace Tests\Unit;

use App\Security\PublicError;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class PublicErrorTest extends TestCase
{
    private TestHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new TestHandler();
        PublicError::setLogger(new Logger('test', [$this->handler]));
    }

    protected function tearDown(): void
    {
        PublicError::setLogger(null);
    }

    public function test_domain_message_is_returned_as_is_and_not_logged(): void
    {
        $this->assertSame('Saldo insuficiente.', PublicError::message(new \Exception('Saldo insuficiente.')));
        $this->assertFalse($this->handler->hasErrorRecords());
    }

    public function test_database_error_is_hidden_from_client_and_logged_with_reference(): void
    {
        $e = new \PDOException("SQLSTATE[42S02]: Base table or view not found: 1146 Table 'financas.transactions'");

        $message = PublicError::message($e);

        $this->assertStringNotContainsString('SQLSTATE', $message);
        $this->assertStringNotContainsString('financas', $message);
        $this->assertMatchesRegularExpression('/\(ref\. [0-9a-f]{8}\)$/', $message);
        $this->assertTrue($this->handler->hasErrorThatContains('SQLSTATE[42S02]'));
        preg_match('/ref\. ([0-9a-f]{8})/', $message, $m);
        $this->assertSame($m[1], $this->handler->getRecords()[0]['context']['ref']);
    }

    public function test_wrapped_sql_message_is_also_hidden(): void
    {
        $message = PublicError::message(new \RuntimeException('Falha: SQLSTATE[23000]: Integrity constraint violation'));

        $this->assertStringStartsWith(PublicError::GENERIC, $message);
    }
}
