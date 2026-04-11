<?php

namespace App\DTO;

class AuthDTO
{
    public string $email;
    public string $password;
    public ?string $firstName;
    public ?string $lastName;
    public ?string $securityQuestion;
    public ?string $securityAnswer;

    public function __construct(array $data)
    {
        $this->email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $this->password = $data['password'] ?? '';
        $this->firstName = isset($data['firstName']) ? htmlspecialchars(strip_tags($data['firstName'])) : null;
        $this->lastName = isset($data['lastName']) ? htmlspecialchars(strip_tags($data['lastName'])) : null;
        $this->securityQuestion = isset($data['securityQuestion']) ? htmlspecialchars(strip_tags($data['securityQuestion'])) : null;
        $this->securityAnswer = isset($data['securityAnswer']) ? $data['securityAnswer'] : null;
    }

    public function isValidForLogin(): bool
    {
        return !empty($this->email) && !empty($this->password) && filter_var($this->email, FILTER_VALIDATE_EMAIL);
    }

    public function isValidForSignup(): bool
    {
        return $this->isValidForLogin() && !empty($this->firstName) && !empty($this->lastName) && !empty($this->securityQuestion) && !empty($this->securityAnswer);
    }
}
