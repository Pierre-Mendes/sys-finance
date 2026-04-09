<?php

namespace App\Repositories;

use App\Models\User;
use App\Contracts\IUserRepository;
use PDO;

class UserRepository implements IUserRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function findById(int $id): ?User {
        $stmt = $this->db->prepare("SELECT * FROM user WHERE UserId = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        return new User(
            $data['FirstName'],
            $data['LastName'],
            $data['Email'],
            $data['Password'],
            $data['Currency'],
            $data['UserId'],
            $data['UserCode'] ?? null
        );
    }

    public function findByEmail(string $email): ?User {
        $stmt = $this->db->prepare("SELECT * FROM user WHERE Email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        return new User(
            $data['FirstName'],
            $data['LastName'],
            $data['Email'],
            $data['Password'],
            $data['Currency'],
            $data['UserId'],
            $data['UserCode'] ?? null
        );
    }

    public function save(User $user): User {
        if ($user->getId()) {
            // Update
            $stmt = $this->db->prepare("UPDATE user SET FirstName = :firstName, LastName = :lastName, Email = :email, Password = :password, Currency = :currency, UserCode = :userCode WHERE UserId = :id");
            $stmt->execute([
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'email' => $user->getEmail(),
                'password' => $user->getPassword(),
                'currency' => $user->getCurrency(),
                'userCode' => $user->getUserCode(),
                'id' => $user->getId()
            ]);
            return $user;
        }

        // Insert
        $stmt = $this->db->prepare("INSERT INTO user (FirstName, LastName, Email, Password, Currency, UserCode) VALUES (:firstName, :lastName, :email, :password, :currency, :userCode)");
        $stmt->execute([
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
            'currency' => $user->getCurrency(),
            'userCode' => $user->getUserCode()
        ]);
        
        // Return a new instance with the inserted ID
        return new User(
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmail(),
            $user->getPassword(),
            $user->getCurrency(),
            (int) $this->db->lastInsertId(),
            $user->getUserCode()
        );
    }
}
