<?php

namespace App\Services\Auth;

use App\Constants\AuthConstants;
use App\DTOs\Auth\AuthDTO;
use App\DTOs\Auth\LoginCredentialsDTO;
use App\DTOs\Auth\TokenDTO;
use App\Models\User;
use App\Repositories\Contracts\TokenRepositoryInterface;
use App\Services\Auth\Contracts\AuthenticationServiceInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthenticationService implements AuthenticationServiceInterface
{
    public function __construct(
        protected TokenRepositoryInterface $tokenRepository,
    ) {}

    /**
     * Attempt to authenticate a user with email or phone
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function authenticate(LoginCredentialsDTO $credentials): AuthDTO
    {
        $user = $this->findUser($credentials->login);

        // TODO : instead of directly writing text, let's start using quickly translation!
        if (! $user || ! Hash::check($credentials->password, $user->password)) {
            throw new AuthenticationException('Les identifiants fournits sont invalides, vérifiez bien votre email ou téléphone et votre mot de passe.');
        }

        $plainTextToken = $this->tokenRepository->createToken($user, AuthConstants::API_TOKEN_NAME);

        $token = new TokenDTO(
            accessToken: $plainTextToken,
            tokenType: AuthConstants::TOKEN_TYPE
        );

        return new AuthDTO(
            token: $token,
            user: $user
        );
    }

    private function findUser(string $login): ?User
    {
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return User::where('email', $login)->first();
        } else {
            return User::where('phone_number', $login)->first();
        }
    }

    /**
     * Revoke the user's current access token
     */
    public function revokeCurrentToken(string $tokenId, User $user): void
    {
        $this->tokenRepository->revokeToken($tokenId, $user);
    }

    /**
     * Revoke all tokens for a user
     */
    public function revokeAllTokens(User $user): void
    {
        $this->tokenRepository->revokeAllTokens($user);
    }

    /**
     * Get authenticated user
     */
    public function getAuthenticatedUser(): ?User
    {
        return Auth::user();
    }
}
