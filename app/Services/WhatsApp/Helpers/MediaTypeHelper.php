<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Helpers;

use App\Enums\MessageType;

final class MediaTypeHelper
{
    private const IMAGE_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'ico', 'tiff', 'tif',
    ];

    private const VIDEO_EXTENSIONS = [
        'mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm', 'm4v', 'mpg', 'mpeg', '3gp',
    ];

    private const AUDIO_EXTENSIONS = [
        'mp3', 'wav', 'ogg', 'aac', 'm4a', 'wma', 'flac', 'opus', 'amr',
    ];

    private const DOCUMENT_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'csv', 'rtf', 'odt', 'ods', 'odp',
    ];

    private const IMAGE_URL_PATTERNS = [
        '/images/', '/photos/', '/photo-gallery/', 'images.unsplash.com', 'picsum.photos',
    ];

    private const VIDEO_URL_PATTERNS = ['/videos/'];

    private const AUDIO_URL_PATTERNS = ['/audio/', '/voice/'];

    private const DOCUMENT_URL_PATTERNS = ['/documents/', '/files/'];

    public static function getMediaType(string $url): MessageType
    {
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $extension = explode('?', $extension)[0];

        if (in_array($extension, self::IMAGE_EXTENSIONS)) {
            return MessageType::IMAGE();
        }

        if (in_array($extension, self::VIDEO_EXTENSIONS)) {
            return MessageType::VIDEO();
        }

        if (in_array($extension, self::AUDIO_EXTENSIONS)) {
            return MessageType::AUDIO();
        }

        if (in_array($extension, self::DOCUMENT_EXTENSIONS)) {
            return MessageType::DOCUMENT();
        }

        foreach (self::IMAGE_URL_PATTERNS as $pattern) {
            if (str_contains($url, $pattern)) {
                return MessageType::IMAGE();
            }
        }

        foreach (self::VIDEO_URL_PATTERNS as $pattern) {
            if (str_contains($url, $pattern)) {
                return MessageType::VIDEO();
            }
        }

        foreach (self::AUDIO_URL_PATTERNS as $pattern) {
            if (str_contains($url, $pattern)) {
                return MessageType::AUDIO();
            }
        }

        foreach (self::DOCUMENT_URL_PATTERNS as $pattern) {
            if (str_contains($url, $pattern)) {
                return MessageType::DOCUMENT();
            }
        }

        return MessageType::TEXT();
    }
}
