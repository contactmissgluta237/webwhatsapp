<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

final class MediaTypeDetector
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
    private const VIDEO_EXTENSIONS = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'mkv'];
    private const AUDIO_EXTENSIONS = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'];

    private const IMAGE_URL_PATTERNS = [
        'images.unsplash.com',
        'picsum.photos',
        '/photo-',
    ];

    public static function isImage(string $url): bool
    {
        $extension = self::getExtension($url);

        return in_array($extension, self::IMAGE_EXTENSIONS) ||
               self::matchesPattern($url, self::IMAGE_URL_PATTERNS);
    }

    public static function isVideo(string $url): bool
    {
        $extension = self::getExtension($url);

        return in_array($extension, self::VIDEO_EXTENSIONS);
    }

    public static function isAudio(string $url): bool
    {
        $extension = self::getExtension($url);

        return in_array($extension, self::AUDIO_EXTENSIONS);
    }

    public static function getMediaType(string $url): string
    {
        if (self::isImage($url)) {
            return 'image';
        }

        if (self::isVideo($url)) {
            return 'video';
        }

        if (self::isAudio($url)) {
            return 'audio';
        }

        return 'document';
    }

    private static function getExtension(string $url): string
    {
        return strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    }

    private static function matchesPattern(string $url, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (str_contains($url, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
