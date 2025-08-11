<?php

namespace App\Services\SMS;

interface SmsServiceInterface
{
    /**
     * Send an SMS message.
     */
    public function sendSms(string $to, string $message): bool;
}
