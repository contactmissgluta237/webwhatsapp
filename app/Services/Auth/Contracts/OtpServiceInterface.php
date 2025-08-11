<?php

namespace App\Services\Auth\Contracts;

use App\Enums\LoginChannel;

interface OtpServiceInterface
{
    /**
     * Send an OTP to the given identifier (email or phone number).
     */
    public function sendOtp(string $identifier, ?LoginChannel $channel = null, string $verificationType = 'password_reset'): bool;

    /**
     * Verify the given OTP for the given identifier.
     */
    public function verifyOtp(string $identifier, string $otp, ?LoginChannel $channel = null): bool;

    /**
     * Invalidate the OTP for the given identifier.
     */
    public function invalidateOtp(string $identifier): bool;

    /**
     * Determine the login channel (email or phone) based on the identifier.
     */
    public function determineChannel(string $identifier): LoginChannel;

    /**
     * Mask the identifier for display purposes.
     */
    public function maskIdentifier(string $identifier): string;

    /**
     * Generate a password reset token for the given identifier.
     */
    public function generateResetToken(string $identifier): string;
}
