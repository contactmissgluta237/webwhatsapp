<?php

declare(strict_types=1);

namespace App\DTOs\WhatsApp;

use App\DTOs\BaseDTO;

final class ProductDataDTO extends BaseDTO
{
    private const CURRENCY_SUFFIX = ' XAF';

    /**
     * @param string[] $mediaUrls
     */
    public function __construct(
        public string $formattedProductMessage,
        public array $mediaUrls
    ) {
    }

    public static function fromUserProduct(\App\Models\UserProduct $product): self
    {
        $mediaUrls = [];
        $mediaCollection = $product->getMedia('medias');

        foreach ($mediaCollection as $media) {
            $mediaUrls[] = $media->getFullUrl();
        }

        $formattedProductMessage = self::formatProductMessage(
            $product->title,
            $product->description,
            self::formatPrice((float)$product->price)
        );

        return new self(
            formattedProductMessage: $formattedProductMessage,
            mediaUrls: $mediaUrls
        );
    }

    private static function formatPrice(float $price): string
    {
        return number_format($price, 0, ',', ' ') . self::CURRENCY_SUFFIX;
    }

    private static function formatProductMessage(string $title, string $description, string $formattedPrice): string
    {
        return sprintf(
            "🛍️ *%s*\n\n💰 **%s**\n\n📝 %s\n\n📞 Interested? Contact us for more information!",
            $title,
            $formattedPrice,
            $description
        );
    }
}
