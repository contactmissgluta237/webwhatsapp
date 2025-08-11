<?php

namespace App\Services\Auth;

use App\Exceptions\Auth\OtpDeliveryException;
use App\Exceptions\UserNotFoundException;
use App\Mail\AccountActivationMail;
use App\Models\User;
use App\Services\Auth\Contracts\AccountActivationServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AccountActivationService implements AccountActivationServiceInterface
{
    private const CODE_LENGTH = 6;
    private const CODE_TTL_MINUTES = 10;
    private const CACHE_PREFIX = 'account_activation_';

    public function sendActivationCode(string $email): bool
    {
        $user = User::findByEmailOrPhone($email);

        if (! $user) {
            throw new UserNotFoundException($email);
        }

        $code = $this->generateActivationCode();
        $maskedEmail = $this->maskEmail($email);
        $cacheKey = $this->generateCacheKey($email);

        Cache::put($cacheKey, $code, now()->addMinutes(self::CODE_TTL_MINUTES));

        try {
            $activationUrl = $this->generateActivationUrl($email, $code);

            Mail::to($email)->send(new AccountActivationMail($code, $maskedEmail, $activationUrl));

            Log::info('AccountActivationService: Activation code sent', [
                'email' => $maskedEmail,
                'user_id' => $user->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Cache::forget($cacheKey);
            Log::error('AccountActivationService: Failed to send activation code', [
                'email' => $maskedEmail,
                'error' => $e->getMessage(),
            ]);
            throw new OtpDeliveryException($e->getMessage());
        }
    }

    public function verifyActivationCode(string $email, string $code): bool
    {
        $cacheKey = $this->generateCacheKey($email);
        $storedCode = Cache::get($cacheKey);

        if (! $storedCode) {
            return false;
        }

        if ($storedCode === $code) {
            $this->invalidateActivationCode($email);

            return true;
        }

        return false;
    }

    public function invalidateActivationCode(string $email): bool
    {
        $cacheKey = $this->generateCacheKey($email);

        return Cache::forget($cacheKey);
    }

    public function generateActivationUrl(string $email, string $code): string
    {
        return route('account.activate', [
            'identifier' => $email,
            'code' => $code,
        ]);
    }

    private function generateActivationCode(): string
    {
        return (string) random_int(
            (int) pow(10, self::CODE_LENGTH - 1),
            (int) pow(10, self::CODE_LENGTH) - 1
        );
    }

    private function generateCacheKey(string $email): string
    {
        return self::CACHE_PREFIX.md5($email);
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
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
    }
}
