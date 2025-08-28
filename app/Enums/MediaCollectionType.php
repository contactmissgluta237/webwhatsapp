<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self IMAGES()
 * @method static self DOCUMENTS()
 * @method static self AVATARS()
 * @method static self PRODUCTS()
 * @method static self ATTACHMENTS()
 */
final class MediaCollectionType extends Enum
{
    protected static function values(): array
    {
        return [
            'IMAGES' => 'images',
            'DOCUMENTS' => 'documents',
            'AVATARS' => 'avatars',
            'PRODUCTS' => 'products',
            'ATTACHMENTS' => 'attachments',
        ];
    }

    protected static function labels(): array
    {
        return [
            'images' => 'Images',
            'documents' => 'Documents',
            'avatars' => 'Avatars',
            'products' => 'Produits',
            'attachments' => 'Pièces jointes',
        ];
    }
}
