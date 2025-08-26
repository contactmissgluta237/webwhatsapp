<?php

declare(strict_types=1);

namespace Tests\Unit\WhatsApp;

use App\Services\WhatsApp\MediaTypeDetector;
use PHPUnit\Framework\TestCase;

final class MediaTypeDetectorTest extends TestCase
{
    /**
     * @dataProvider imageUrlProvider
     */
    public function test_detects_images_correctly(string $url, bool $expected): void
    {
        $this->assertEquals($expected, MediaTypeDetector::isImage($url));
    }

    /**
     * @dataProvider videoUrlProvider
     */
    public function test_detects_videos_correctly(string $url, bool $expected): void
    {
        $this->assertEquals($expected, MediaTypeDetector::isVideo($url));
    }

    /**
     * @dataProvider audioUrlProvider
     */
    public function test_detects_audio_correctly(string $url, bool $expected): void
    {
        $this->assertEquals($expected, MediaTypeDetector::isAudio($url));
    }

    /**
     * @dataProvider mediaTypeProvider
     */
    public function test_returns_correct_media_type(string $url, string $expectedType): void
    {
        $this->assertEquals($expectedType, MediaTypeDetector::getMediaType($url));
    }

    public static function imageUrlProvider(): array
    {
        return [
            // Standard image extensions
            ['https://example.com/image.jpg', true],
            ['https://example.com/image.jpeg', true],
            ['https://example.com/image.png', true],
            ['https://example.com/image.gif', true],
            ['https://example.com/image.webp', true],
            ['https://example.com/image.bmp', true],
            ['https://example.com/image.svg', true],

            // Special image services
            ['https://images.unsplash.com/photo-123456', true],
            ['https://picsum.photos/300/300', true],
            ['https://example.com/photo-gallery/image', true],

            // Non-images
            ['https://example.com/video.mp4', false],
            ['https://example.com/audio.mp3', false],
            ['https://example.com/document.pdf', false],
        ];
    }

    public static function videoUrlProvider(): array
    {
        return [
            // Video extensions
            ['https://example.com/video.mp4', true],
            ['https://example.com/video.mov', true],
            ['https://example.com/video.avi', true],
            ['https://example.com/video.wmv', true],
            ['https://example.com/video.flv', true],
            ['https://example.com/video.webm', true],
            ['https://example.com/video.mkv', true],

            // Non-videos
            ['https://example.com/image.jpg', false],
            ['https://example.com/audio.mp3', false],
        ];
    }

    public static function audioUrlProvider(): array
    {
        return [
            // Audio extensions
            ['https://example.com/audio.mp3', true],
            ['https://example.com/audio.wav', true],
            ['https://example.com/audio.ogg', true],
            ['https://example.com/audio.m4a', true],
            ['https://example.com/audio.aac', true],
            ['https://example.com/audio.flac', true],

            // Non-audio
            ['https://example.com/image.jpg', false],
            ['https://example.com/video.mp4', false],
        ];
    }

    public static function mediaTypeProvider(): array
    {
        return [
            ['https://example.com/image.jpg', 'image'],
            ['https://images.unsplash.com/photo-123', 'image'],
            ['https://example.com/video.mp4', 'video'],
            ['https://example.com/audio.mp3', 'audio'],
            ['https://example.com/document.pdf', 'document'],
            ['https://example.com/file.docx', 'document'],
            ['https://example.com/unknown', 'document'],
        ];
    }
}
