<?php

namespace App\DTOs\Auth;

use App\Models\User;

class AuthDTO
{
    public function __construct(
        public readonly TokenDTO $token,
        public readonly User $user,
    ) {}
}
