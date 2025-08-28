<?php

declare(strict_types=1);

namespace Tests\Unit\WhatsApp;

use App\Enums\MessageType;
use App\Services\WhatsApp\Helpers\MediaTypeHelper;
use PHPUnit\Framework\TestCase;

final class MediaTypeHelperTest extends TestCase
{
    /**
     * @dataProvider mediaTypeProvider
     */
    public function test_returns_correct_media_type(string $url, string $expectedType): void
    {
        $result = MediaTypeHelper::getMediaType($url);
        $this->assertInstanceOf(MessageType::class, $result);
        $this->assertEquals($expectedType, $result->value);
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
            ['https://example.com/unknown', 'text'],
        ];
    }
}
