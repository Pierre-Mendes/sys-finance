<?php

namespace App;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $isTesting = (getenv('APP_ENV') === 'testing');
            
            if ($isTesting) {
                $dbPath = getenv('DB_NAME') ?: ':memory:';
                $dsn = "sqlite:$dbPath";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];
                self::$instance = new PDO($dsn, null, null, $options);
            } else {
                $host = getenv('DB_HOST') ?: 'db';
                $db   = getenv('DB_NAME') ?: 'money_manager';
                $user = getenv('DB_USER') ?: 'root';
                $pass = getenv('DB_PASS') ?: 'root';
                $charset = 'utf8mb4';

                $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false, 
                ];

                try {
                    self::$instance = new PDO($dsn, $user, $pass, $options);
                } catch (PDOException $e) {
                    throw new PDOException("Connection failed: " . $e->getMessage());
                }
            }
        }
        return self::$instance;
    }
    
    public static function clearInstance(): void {
        self::$instance = null;
    }
}
