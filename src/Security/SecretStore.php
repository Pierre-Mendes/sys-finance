<?php

namespace App\Security;

use PDO;
use PDOException;

/**
 * Guarda segredos gerados pela aplicação na tabela app_secrets.
 *
 * O primeiro acesso gera um valor aleatório forte e persiste; os seguintes reutilizam o mesmo.
 * Como o banco fica em volume persistente, o segredo sobrevive a rebuilds e deploys.
 */
class SecretStore {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getOrCreate(string $name, int $bytes = 48): string {
        $existing = $this->find($name);
        if ($existing !== null) return $existing;

        $value = rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
        try {
            $stmt = $this->db->prepare("INSERT INTO app_secrets (name, value, created_at) VALUES (?, ?, ?)");
            $stmt->execute([$name, $value, date('Y-m-d H:i:s')]);
            return $value;
        } catch (PDOException $e) {
            // Outra requisição criou ao mesmo tempo (chave primária): vale o que foi gravado primeiro.
            $winner = $this->find($name);
            if ($winner !== null) return $winner;
            throw $e;
        }
    }

    private function find(string $name): ?string {
        $stmt = $this->db->prepare("SELECT value FROM app_secrets WHERE name = ?");
        $stmt->execute([$name]);
        $value = $stmt->fetchColumn();
        return $value === false ? null : (string) $value;
    }
}
