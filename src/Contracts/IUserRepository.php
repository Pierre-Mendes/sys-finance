<?php

namespace App\Contracts;

use App\Models\User;

interface IUserRepository {
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function save(User $user): User;
}
