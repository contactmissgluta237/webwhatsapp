<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Helpers;

use App\DTOs\WhatsApp\WhatsAppAIResponseDTO;
use App\Enums\ResponseTime;
use App\Models\WhatsAppAccount;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ResponseTimingHelper
{
    /**
     * Calculate wait time before starting to type
     */
    public function calculateWaitTime(WhatsAppAccount $account): int
    {
        $waitTime = ResponseTime::from($account->response_time)->getDelay();

        Log::debug('[RESPONSE_TIMING_HELPER] Calculated wait time', [
            'session_id' => $account->session_id,
            'wait_time_seconds' => $waitTime,
        ]);

        return $waitTime;
    }

    /**
     * Calculate typing duration based on response length
     */
    public function calculateTypingDuration(WhatsAppAIResponseDTO $aiResponse): int
    {
        $responseLength = strlen($aiResponse->response);
        $typingDuration = $this->calculateTypingDurationFromLength($responseLength);

        Log::debug('[RESPONSE_TIMING_HELPER] Calculated typing duration', [
            'response_length' => $responseLength,
            'typing_duration_seconds' => $typingDuration,
        ]);

        return $typingDuration;
    }

    /**
     * Calculate realistic typing duration based on message length
     * Simulates human typing speed with natural variation
     */
    private function calculateTypingDurationFromLength(int $messageLength): int
    {
        $baseTypingSpeed = 20; // characters per second
        $variationPercent = Arr::random(range(70, 130)); // 70% to 130% variation
        $actualTypingSpeed = $baseTypingSpeed * ($variationPercent / 100);

        $typingDuration = (int) ceil($messageLength / $actualTypingSpeed);

        return max(2, $typingDuration); // Minimum 2 seconds
    }
}
