<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Models\User;
use Exception;

class AuthService {
    private UserRepository $userRepository;
    private WorkspaceService $workspaceService;
    private NotificationService $notificationService;

    public function __construct(UserRepository $userRepository, WorkspaceService $workspaceService, NotificationService $notificationService) {
        $this->userRepository = $userRepository;
        $this->workspaceService = $workspaceService;
        $this->notificationService = $notificationService;
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
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $hashedAnswer = !empty($data['securityAnswer']) ? password_hash(strtolower(trim($data['securityAnswer'])), PASSWORD_BCRYPT) : null;

        $userCode = 'U-' . strtoupper(substr(base_convert(hash('crc32', uniqid((string)rand(), true)), 16, 36), 0, 6));

        $user = new User(
            $data['firstName'],
            $data['lastName'],
            $data['email'],
            $hashedPassword,
            $data['currency'] ?? 'BRL',
            null,
            $userCode,
            $data['securityQuestion'] ?? null,
            $hashedAnswer
        );

        $user = $this->userRepository->save($user);

        // Ensure a default workspace is created for the new user
        $this->workspaceService->createDefaultWorkspace($user->getId(), $user->getFirstName());

        return $user;
    }

    public function login(string $email, string $password): User {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            throw new Exception("Invalid credentials.");
        }

        // We check if it matches a hash, or fallback to plain text if dealing with legacy data
        if (password_verify($password, $user->getPassword())) {
            $user = $this->ensureUserCode($user);
            
            // Trigger security notification if needed
            $this->triggerSecurityNotification($user);

            // Retroactive fix: ensure user has a workspace (for those who signed up without one)
            $this->workspaceService->ensureHasWorkspace($user->getId(), $user->getFirstName());
            
            return $user;
        }

        // Fallback for incredibly old plain-text passwords. Only when the stored value is NOT a hash:
        // otherwise anyone holding the hash (leaked backup) could log in by typing the hash itself.
        $stored = (string) $user->getPassword();
        if ($stored !== '' && password_get_info($stored)['algo'] === null && hash_equals($stored, $password)) {
            $user->setPassword(password_hash($password, PASSWORD_BCRYPT));
            $user = $this->ensureUserCode($user);
            $this->userRepository->save($user);
            return $user;
        }

        throw new Exception("Invalid credentials.");
    }

    private function triggerSecurityNotification(User $user): void {
        if (empty($user->getSecurityQuestion()) && !$this->notificationService->hasUnsetSecurityNotification($user->getId())) {
            $this->notificationService->notify(
                $user->getId(), 
                "⚠️ Segurança: Ação Necessária", 
                "Você ainda não configurou uma pergunta de recuperação. Faça isso em Configurações para poder recuperar sua senha caso a perca.",
                "SYSTEM",
                null,
                "/settings"
            );
        }
    }

    public function getRecoveryQuestion(string $email): string {
        $user = $this->userRepository->findByEmail($email);
        if (!$user) throw new Exception("Usuário não encontrado.");
        if (empty($user->getSecurityQuestion())) throw new Exception("Esse usuário não possui uma pergunta de recuperação configurada. Entre em contato com o administrador.");
        return $user->getSecurityQuestion();
    }

    public function resetPassword(string $email, string $answer, string $newPassword): void {
        $user = $this->userRepository->findByEmail($email);
        if (!$user) throw new Exception("Usuário não encontrado.");
        if (empty($user->getSecurityAnswer())) throw new Exception("Redefinição não autorizada.");

        if (!password_verify(strtolower(trim($answer)), $user->getSecurityAnswer())) {
            throw new Exception("Resposta de segurança incorreta.");
        }

        $user->setPassword(password_hash($newPassword, PASSWORD_BCRYPT));
        $this->userRepository->save($user);
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
            $user->getUserCode(),
            !empty($data['securityQuestion']) ? $data['securityQuestion'] : $user->getSecurityQuestion(),
            !empty($data['securityAnswer']) ? password_hash(strtolower(trim($data['securityAnswer'])), PASSWORD_BCRYPT) : $user->getSecurityAnswer()
        );

        return $this->userRepository->save($updatedUser);
    }

    public function getUser(int $userId): User {
        $user = $this->userRepository->findById($userId);
        if (!$user) throw new Exception("User not found");
        
        $user = $this->ensureUserCode($user);
        $this->triggerSecurityNotification($user);
        
        return $user;
    }
    
    private function ensureUserCode(User $user): User {
        if (empty($user->getUserCode())) {
            $userCode = 'U-' . strtoupper(substr(base_convert(hash('crc32', uniqid((string)rand(), true)), 16, 36), 0, 6));
            $user->setUserCode($userCode);
            $this->userRepository->save($user);
        }
        return $user;
    }
}
