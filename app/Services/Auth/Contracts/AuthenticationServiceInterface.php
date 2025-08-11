<?php

namespace App\Services\Auth\Contracts;

use App\DTOs\Auth\AuthDTO;
use App\DTOs\Auth\LoginCredentialsDTO;
use App\Models\User;

interface AuthenticationServiceInterface
{
    /**
     * Attempt to authenticate a user with email or phone
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function authenticate(LoginCredentialsDTO $credentials): AuthDTO;

    /**
     * Revoke the user's current access token
     */
    public function revokeCurrentToken(string $tokenId, User $user): void;

    /**
     * Revoke all tokens for a user
     */
    public function revokeAllTokens(User $user): void;

    /**
     * Get authenticated user
     */
    public function getAuthenticatedUser(): ?User;
}
