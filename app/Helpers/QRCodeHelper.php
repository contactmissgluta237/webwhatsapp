<?php

declare(strict_types=1);

namespace App\Helpers;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

final class QRCodeHelper
{
    public static function generateDataUrl(string $text): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode(
            QrCode::format('svg')
                ->size(300)
                ->margin(2)
                ->generate($text)
        );
    }

    public static function generateSvg(string $text): string
    {
        return QrCode::format('svg')
            ->size(300)
            ->margin(2)
            ->generate($text);
    }

    public static function generatePng(string $text): string
    {
        return QrCode::format('png')
            ->size(300)
            ->margin(2)
            ->generate($text);
    }
}
