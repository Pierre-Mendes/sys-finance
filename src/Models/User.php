<?php

namespace App\Models;

class User {
    private ?int $id;
    private string $firstName;
    private string $lastName;
    private string $email;
    private string $password;
    private ?string $currency;

    private ?string $userCode;

    public function __construct(string $firstName, string $lastName, string $email, string $password, ?string $currency = 'BRL', ?int $id = null, ?string $userCode = null) {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->password = $password;
        $this->currency = $currency;
        $this->id = $id;
        $this->userCode = $userCode;
    }

    public function getId(): ?int { return $this->id; }
    public function getFirstName(): string { return $this->firstName; }
    public function getLastName(): string { return $this->lastName; }
    public function getEmail(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getCurrency(): ?string { return $this->currency; }
    public function getUserCode(): ?string { return $this->userCode; }
    
    public function setPassword(string $password): void { $this->password = $password; }
    public function setUserCode(string $code): void { $this->userCode = $code; }
}
