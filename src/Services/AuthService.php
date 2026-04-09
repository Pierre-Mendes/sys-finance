<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Models\User;
use Exception;

class AuthService {
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository) {
        $this->userRepository = $userRepository;
    }

    public function register(array $data): User {
        // Validation
        if (empty($data['firstName']) || empty($data['lastName']) || empty($data['email']) || empty($data['password'])) {
            throw new Exception("All fields are required.");
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        if ($this->userRepository->findByEmail($data['email'])) {
            throw new Exception("Email is already registered.");
        }

        // The old app seemed to probably not hash passwords, but we MUST hash them properly.
        // We will use password_hash.
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

        $userCode = 'U-' . strtoupper(substr(base_convert(hash('crc32', uniqid((string)rand(), true)), 16, 36), 0, 6));

        $user = new User(
            $data['firstName'],
            $data['lastName'],
            $data['email'],
            $hashedPassword,
            $data['currency'] ?? 'BRL',
            null,
            $userCode
        );

        return $this->userRepository->save($user);
    }

    public function login(string $email, string $password): User {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            throw new Exception("Invalid credentials.");
        }

        // We check if it matches a hash, or fallback to plain text if dealing with legacy data
        if (password_verify($password, $user->getPassword())) {
            return $this->ensureUserCode($user);
        }

        // Fallback for incredibly old plain-text passwords from the legacy app
        if ($user->getPassword() === $password) {
            // Re-hash for the future
            $user->setPassword(password_hash($password, PASSWORD_BCRYPT));
            $user = $this->ensureUserCode($user);
            $this->userRepository->save($user);
            return $user;
        }

        throw new Exception("Invalid credentials.");
    }

    public function getUser(int $userId): User {
        $user = $this->userRepository->findById($userId);
        if (!$user) throw new Exception("User not found");
        return $this->ensureUserCode($user);
    }
    
    private function ensureUserCode(User $user): User {
        if (empty($user->getUserCode())) {
            $userCode = 'U-' . strtoupper(substr(base_convert(hash('crc32', uniqid((string)rand(), true)), 16, 36), 0, 6));
            $user->setUserCode($userCode);
            $this->userRepository->save($user);
        }
        return $user;
    }

    public function updateProfile(int $userId, array $data): User {
        $user = $this->userRepository->findById($userId);
        if (!$user) throw new Exception("User not found");
        
        $email = !empty($data['email']) ? $data['email'] : $user->getEmail();
        if ($email !== $user->getEmail() && $this->userRepository->findByEmail($email)) {
            throw new Exception("Esse e-mail já está em uso por outra conta.");
        }

        $password = !empty($data['password']) ? password_hash($data['password'], PASSWORD_BCRYPT) : $user->getPassword();
        
        $updatedUser = new User(
            !empty($data['firstName']) ? $data['firstName'] : $user->getFirstName(),
            !empty($data['lastName']) ? $data['lastName'] : $user->getLastName(),
            $email,
            $password,
            !empty($data['currency']) ? $data['currency'] : $user->getCurrency(),
            $userId,
            $user->getUserCode()
        );

        return $this->userRepository->save($updatedUser);
    }
}
