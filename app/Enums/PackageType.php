<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self TRIAL()
 * @method static self STARTER()
 * @method static self BUSINESS()
 * @method static self PRO()
 */
final class PackageType extends Enum
{
    protected static function values(): array
    {
        return [
            'TRIAL' => 'trial',
            'STARTER' => 'starter',
            'BUSINESS' => 'business',
            'PRO' => 'pro',
        ];
    }

    protected static function labels(): array
    {
        return [
            'trial' => 'Essai gratuit',
            'starter' => 'Starter',
            'business' => 'Business',
            'pro' => 'Pro',
        ];
    }

    protected static function descriptions(): array
    {
        return [
            'trial' => 'Package d\'essai gratuit',
            'starter' => 'Package de démarrage',
            'business' => 'Package pour entreprises',
            'pro' => 'Package professionnel avancé',
        ];
    }

    protected static function colors(): array
    {
        return [
            'trial' => 'info',
            'starter' => 'primary',
            'business' => 'success',
            'pro' => 'warning',
        ];
    }
}
