<?php

namespace App\DTOs\Auth;

class LoginCredentialsDTO
{
    public function __construct(
        public readonly string $login,
        public readonly string $password
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            login: $data['login'] ?? '',
            password: $data['password'] ?? ''
        );
    }
}
