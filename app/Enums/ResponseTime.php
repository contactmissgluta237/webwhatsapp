<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self RANDOM()
 * @method static self ONE_MINUTE()
 * @method static self TWO_MINUTES()
 * @method static self THREE_MINUTES()
 * @method static self FOUR_MINUTES()
 * @method static self FIVE_MINUTES()
 */
final class ResponseTime extends Enum
{
    protected static function values(): array
    {
        return [
            'RANDOM' => 'random',
            'ONE_MINUTE' => '60',
            'TWO_MINUTES' => '120',
            'THREE_MINUTES' => '180',
            'FOUR_MINUTES' => '240',
            'FIVE_MINUTES' => '300',
        ];
    }

    protected static function labels(): array
    {
        return [
            'random' => 'Au hasard (Recommandé)',
            '60' => '1 minute',
            '120' => '2 minutes',
            '180' => '3 minutes',
            '240' => '4 minutes',
            '300' => '5 minutes',
        ];
    }

    public function getMinDelay(): int
    {
        return match ($this->value) {
            'random' => 30,
            default => (int) $this->value,
        };
    }

    public function getMaxDelay(): int
    {
        return match ($this->value) {
            'random' => 180,
            default => (int) $this->value,
        };
    }

    public function getRandomDelay(): int
    {
        if ($this->value === 'random') {
            // Génère un nouveau délai aléatoire à chaque appel
            $possibleDelays = range(30, 180, 15); // [30, 45, 60, 75, 90, 105, 120, 135, 150, 165, 180]
            return $possibleDelays[array_rand($possibleDelays)];
        }
        
        return (int) $this->value;
    }

    public function getDescription(): string
    {
        return match ($this->value) {
            'random' => 'Délai différent à chaque message (30-180s) pour paraître naturel',
            '60' => 'Réponse systématique après 1 minute',
            '120' => 'Réponse systématique après 2 minutes',
            '180' => 'Réponse systématique après 3 minutes',
            '240' => 'Réponse systématique après 4 minutes',
            '300' => 'Réponse systématique après 5 minutes',
            default => 'Délai personnalisé',
        };
    }
}
