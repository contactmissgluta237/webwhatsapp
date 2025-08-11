<?php

namespace App\Services\Auth;

use App\Enums\LoginChannel;
use App\Exceptions\Auth\OtpDeliveryException;
use App\Exceptions\SmsDeliveryException;
use App\Exceptions\UserNotFoundException;
use App\Mail\OtpMail;
use App\Models\User;
use App\Services\Auth\Contracts\OtpServiceInterface;
use App\Services\SMS\SmsServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpService implements OtpServiceInterface
{
    private const OTP_LENGTH = 6;
    private const OTP_TTL_MINUTES = 10;
    private const OTP_CACHE_PREFIX = 'otp_';
    private const SMS_MESSAGE_TEMPLATE = 'Your verification code is %s. This code will expire in 10 minutes.';

    public function __construct(
        private SmsServiceInterface $smsService
    ) {}

    public function sendOtp(string $identifier, ?LoginChannel $channel = null, string $verificationType = 'password_reset'): bool
    {
        $user = User::findByEmailOrPhone($identifier);

        if (! $user) {
            throw new UserNotFoundException($identifier);
        }

        $otp = $this->generateOtpCode();
        $channel = $channel ?? $this->determineChannel($identifier);
        $maskedIdentifier = $this->maskIdentifier($identifier);
        $cacheKey = $this->generateCacheKey($identifier);
        Cache::put($cacheKey, $otp, now()->addMinutes(self::OTP_TTL_MINUTES));

        try {
            if ($channel->equals(LoginChannel::EMAIL())) {
                $url = $this->generateVerificationUrl($identifier, $otp, $verificationType);
                Mail::to($identifier)->send(new OtpMail(
                    otp: $otp,
                    maskedIdentifier: $maskedIdentifier,
                    resetUrl: $url,
                    userName: $user->first_name,
                    verificationType: $verificationType
                ));
            } else {
                $message = sprintf(self::SMS_MESSAGE_TEMPLATE, $otp);

                if (! $this->smsService->sendSms($identifier, $message)) {
                    Log::debug('Sending OTP via SMS failed', [
                        'identifier' => $maskedIdentifier,
                        'message' => $message,
                    ]);
                    throw new SmsDeliveryException("Failed to deliver SMS to {$maskedIdentifier}");
                }
            }

            return true;
        } catch (\Exception $e) {
            Cache::forget($cacheKey);
            throw new OtpDeliveryException($e->getMessage());
        }
    }

    /**
     * Generate a random OTP code
     */
    private function generateOtpCode(): string
    {
        return str_pad(
            (string) rand(
                (int) pow(10, self::OTP_LENGTH - 1),
                (int) pow(10, self::OTP_LENGTH) - 1
            ),
            self::OTP_LENGTH,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * {@inheritdoc}
     */
    public function determineChannel(string $identifier): LoginChannel
    {
        return filter_var($identifier, FILTER_VALIDATE_EMAIL)
            ? LoginChannel::EMAIL()
            : LoginChannel::PHONE();
    }

    /**
     * {@inheritdoc}
     */
    public function verifyOtp(string $identifier, string $otp, ?LoginChannel $channel = null): bool
    {
        $cacheKey = $this->generateCacheKey($identifier);
        $storedOtp = Cache::get($cacheKey);

        if (! $storedOtp) {
            return false;
        }

        if ($storedOtp === $otp) {
            $this->invalidateOtp($identifier);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function resendOtp(string $identifier): bool
    {
        $this->invalidateOtp($identifier);

        return $this->sendOtp($identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateOtp(string $identifier): bool
    {
        $cacheKey = $this->generateCacheKey($identifier);

        return Cache::forget($cacheKey);
    }

    /**
     * Generate a cache key for storing OTPs
     *
     * @param  string  $identifier  Email or phone number
     */
    private function generateCacheKey(string $identifier): string
    {
        return self::OTP_CACHE_PREFIX.md5($identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function maskIdentifier(string $identifier): string
    {
        $channel = $this->determineChannel($identifier);

        if ($channel->equals(LoginChannel::EMAIL())) {
            // Mask email: j***@e***le.com
            $parts = explode('@', $identifier);

            if (count($parts) !== 2) {
                return '******';
            }

            $name = $parts[0];
            $domain = $parts[1];

            $maskedName = substr($name, 0, 1).str_repeat('*', max(0, strlen($name) - 1));
            $domainParts = explode('.', $domain);

            if (count($domainParts) > 1) {
                $domainName = $domainParts[0];
                $extension = implode('.', array_slice($domainParts, 1));
                $maskedDomain = substr($domainName, 0, 1).str_repeat('*', max(0, strlen($domainName) - 1)).'.'.$extension;
            } else {
                $maskedDomain = substr($domain, 0, 1).str_repeat('*', max(0, strlen($domain) - 1));
            }

            return $maskedName.'@'.$maskedDomain;
        } else {
            // Mask phone number: +225*****1234
            if (strlen($identifier) < 4) {
                return str_repeat('*', strlen($identifier));
            }

            $start = substr($identifier, 0, 4);
            $end = substr($identifier, -4);
            $middle = str_repeat('*', max(0, strlen($identifier) - 8));

            return $start.$middle.$end;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generateResetToken(string $identifier): string
    {
        $user = User::findByEmailOrPhone($identifier);

        if (! $user) {
            throw new UserNotFoundException($identifier);
        }

        return hash('sha256', $identifier.now()->timestamp.uniqid());
    }

    private function generateVerificationUrl(string $identifier, string $otp, string $type): string
    {
        if ($type === 'register') {
            return route('auth.verify-otp', ['identifier' => $identifier, 'otp' => $otp, 'verificationType' => 'register']);
        }

        $token = $this->generateResetToken($identifier);

        return route('password.reset', ['token' => $token, 'email' => $identifier]);
    }
}
