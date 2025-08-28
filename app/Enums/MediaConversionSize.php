<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self THUMBNAIL()
 * @method static self MEDIUM()
 * @method static self LARGE()
 */
final class MediaConversionSize extends Enum
{
    protected static function values(): array
    {
        return [
            'THUMBNAIL' => 'thumbnail',
            'MEDIUM' => 'medium',
            'LARGE' => 'large',
        ];
    }

    protected static function dimensions(): array
    {
        return [
            'thumbnail' => [150, 150],
            'medium' => [500, 500],
            'large' => [1200, 1200],
        ];
    }

    public function width(): int
    {
        $dimensions = self::dimensions();

        return $dimensions[$this->value][0];
    }

    public function height(): int
    {
        $dimensions = self::dimensions();

        return $dimensions[$this->value][1];
    }
}
