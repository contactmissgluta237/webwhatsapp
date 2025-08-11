<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface TokenRepositoryInterface
{
    /**
     * Create a new token for a user
     */
    public function createToken(User $user, string $name): string;

    /**
     * Revoke a specific token
     */
    public function revokeToken(string $tokenId, User $user): void;

    /**
     * Revoke all tokens for a user
     */
    public function revokeAllTokens(User $user): void;
}
