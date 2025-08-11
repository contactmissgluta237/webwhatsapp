<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self FRIENDLY()
 * @method static self PROFESSIONAL()
 * @method static self CASUAL()
 */
final class ResponseTone extends Enum
{
    protected static function values(): array
    {
        return [
            'FRIENDLY' => 'friendly',
            'PROFESSIONAL' => 'professional',
            'CASUAL' => 'casual',
        ];
    }

    protected static function labels(): array
    {
        return [
            'FRIENDLY' => 'Amical',
            'PROFESSIONAL' => 'Professionnel',
            'CASUAL' => 'Décontracté',
        ];
    }

    public function getDescription(): string
    {
        return match ($this->value) {
            'friendly' => 'Réponses chaleureuses et bienveillantes',
            'professional' => 'Réponses formelles et professionnelles',
            'casual' => 'Réponses décontractées et informelles',
            default => 'Ton de réponse standard',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this->value) {
            'friendly' => 'bg-green-100 text-green-800',
            'professional' => 'bg-blue-100 text-blue-800',
            'casual' => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
