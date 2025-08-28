<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self USER()
 * @method static self ASSISTANT()
 * @method static self SYSTEM()
 */
final class AiRole extends Enum
{
    protected static function values(): array
    {
        return [
            'USER' => 'user',
            'ASSISTANT' => 'assistant',
            'SYSTEM' => 'system',
        ];
    }

    protected static function labels(): array
    {
        return [
            'user' => 'Utilisateur',
            'assistant' => 'Assistant',
            'system' => 'Système',
        ];
    }
}
