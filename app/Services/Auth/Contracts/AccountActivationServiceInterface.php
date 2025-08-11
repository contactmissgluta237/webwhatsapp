<?php

namespace App\Services\Auth\Contracts;

interface AccountActivationServiceInterface
{
    /**
     * Send an activation code to the given email address.
     */
    public function sendActivationCode(string $email): bool;

    /**
     * Verify the activation code for the given email.
     */
    public function verifyActivationCode(string $email, string $code): bool;

    /**
     * Invalidate the activation code for the given email.
     */
    public function invalidateActivationCode(string $email): bool;

    /**
     * Generate a direct activation URL with embedded code.
     */
    public function generateActivationUrl(string $email, string $code): string;
}
