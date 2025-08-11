<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Validation;

use App\Models\WhatsAppAccount;

final class WhatsAppValidationService implements WhatsAppValidationServiceInterface
{
    public function validateUniquePhoneNumber(string $phoneNumber, ?int $excludeUserId = null): array
    {
        $query = WhatsAppAccount::where('phone_number', $phoneNumber)
            ->whereIn('status', ['connected', 'connecting', 'initializing']);

        if ($excludeUserId) {
            $query->where('user_id', '!=', $excludeUserId);
        }

        $existingAccount = $query->first();

        if ($existingAccount) {
            return [
                'valid' => false,
                'message' => 'Ce numéro de téléphone a déjà une session active',
                'existing_account' => [
                    'session_name' => $existingAccount->session_name,
                    'status' => $existingAccount->status->value,
                    'user_id' => $existingAccount->user_id,
                    'last_seen_at' => $existingAccount->last_seen_at,
                ],
            ];
        }

        return [
            'valid' => true,
            'message' => 'Numéro de téléphone disponible',
        ];
    }

    public function validateSessionName(string $sessionName, int $userId): array
    {
        $existingSession = WhatsAppAccount::where('user_id', $userId)
            ->where('session_name', $sessionName)
            ->first();

        if ($existingSession) {
            return [
                'valid' => false,
                'message' => 'Ce nom de session est déjà utilisé pour votre compte',
                'existing_session' => [
                    'id' => $existingSession->id,
                    'status' => $existingSession->status->value,
                    'phone_number' => $existingSession->phone_number,
                ],
            ];
        }

        return [
            'valid' => true,
            'message' => 'Nom de session disponible',
        ];
    }
}
