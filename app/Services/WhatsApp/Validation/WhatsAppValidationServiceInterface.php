<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Validation;

interface WhatsAppValidationServiceInterface
{
    public function validateUniquePhoneNumber(string $phoneNumber, ?int $excludeUserId = null): array;

    public function validateSessionName(string $sessionName, int $userId): array;
}
