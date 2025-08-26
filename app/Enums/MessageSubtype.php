<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self MAIN()
 * @method static self PRODUCT()
 */
final class MessageSubtype extends Enum
{
    protected static function values(): array
    {
        return [
            'MAIN' => 'main',
            'PRODUCT' => 'product',
        ];
    }

    protected static function labels(): array
    {
        return [
            'MAIN' => 'Principal',
            'PRODUCT' => 'Produit',
        ];
    }
}
