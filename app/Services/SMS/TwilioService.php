<?php

namespace App\Services\SMS;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class TwilioService implements SmsServiceInterface
{
    protected ?Client $client = null;
    protected ?string $from = null;

    /**
     * Create a new Twilio service instance.
     */
    public function __construct()
    {
        try {
            $sid = config('services.twilio.sid');
            $token = config('services.twilio.token');
            $from = config('services.twilio.from');

            if ($sid && $token && $from) {
                $this->client = new Client($sid, $token);
                $this->from = $from;
            } else {
                Log::warning('Twilio configuration incomplete', [
                    'sid' => $sid ? 'present' : 'missing',
                    'token' => $token ? 'present' : 'missing',
                    'from' => $from ? 'present' : 'missing',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to initialize Twilio service: '.$e->getMessage());
        }
    }

    /**
     * Send an SMS message.
     */
    public function sendSms(string $to, string $message): bool
    {
        if (! $this->client || ! $this->from) {
            Log::warning('SMS sending skipped - Twilio not configured', [
                'to' => $to,
                'message_length' => strlen($message),
            ]);

            return false;
        }

        try {
            $this->client->messages->create(
                $to,
                [
                    'from' => $this->from,
                    'body' => $message,
                ]
            );

            return true;
        } catch (\Exception $e) {
            Log::error('SMS sending failed: '.$e->getMessage());

            return false;
        }
    }
}
