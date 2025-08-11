<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self PASSWORD_RESET()
 * @method static self REGISTER()
 */
class VerificationType extends Enum
{
    /**
     * @return string[]
     */
    protected static function labels(): array
    {
        return [
            'PASSWORD_RESET' => 'Réinitialisation du mot de passe',
            'REGISTER' => 'Activation du compte',
        ];
    }

    /**
     * @return string[]
     */
    protected static function values(): array
    {
        return [
            'PASSWORD_RESET' => 'password_reset',
            'REGISTER' => 'register',
        ];
    }

    public function getDescription(): string
    {
        return match ($this->value) {
            'password_reset' => 'Vérification du code pour la réinitialisation du mot de passe',
            'register' => 'Activation du compte utilisateur',
            default => 'Type de vérification inconnu',
        };
    }
}
