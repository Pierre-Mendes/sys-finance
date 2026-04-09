<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Database;
use PDO;

class DatabaseTest extends TestCase {
    
    public function testConnectionIsSuccessful() {
        $pdo = Database::getConnection();
        $this->assertInstanceOf(PDO::class, $pdo);
    }
    
    public function testIsSingleton() {
        $pdo1 = Database::getConnection();
        $pdo2 = Database::getConnection();
        $this->assertSame($pdo1, $pdo2);
    }
}
