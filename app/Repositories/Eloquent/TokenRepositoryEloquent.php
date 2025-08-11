<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\TokenRepositoryInterface;
use Laravel\Sanctum\PersonalAccessToken;

class TokenRepositoryEloquent implements TokenRepositoryInterface
{
    /**
     * Create a new token for a user
     */
    public function createToken(User $user, string $name): string
    {
        $token = $user->createToken($name);

        return $token->plainTextToken;
    }

    /**
     * Revoke a specific token
     */
    public function revokeToken(string $tokenId, User $user): void
    {
        $token = PersonalAccessToken::findToken($tokenId);

        if ($token && $token->tokenable instanceof User && $token->tokenable->id === $user->id) {
            $token->delete();
        }
    }

    /**
     * Revoke all tokens for a user
     */
    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
        cache()->forget('sanctum:last_token_usage:'.$user->id);
    }
}
