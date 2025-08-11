<?php

namespace App\DTOs\Auth;

class TokenDTO
{
    private const DEFAULT_TOKEN_TYPE = 'Bearer';

    public function __construct(
        public readonly string $accessToken,
        public readonly string $tokenType = self::DEFAULT_TOKEN_TYPE
    ) {}
}
