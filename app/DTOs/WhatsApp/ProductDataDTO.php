<?php

declare(strict_types=1);

namespace App\DTOs\WhatsApp;

use App\DTOs\BaseDTO;
use App\Services\CurrencyService;

final class ProductDataDTO extends BaseDTO
{
    /**
     * @param  string[]  $mediaUrls
     */
    public function __construct(
        public string $formattedProductMessage,
        public array $mediaUrls
    ) {}

    public static function fromUserProduct(\App\Models\UserProduct $product): self
    {
        $mediaUrls = [];
        $mediaCollection = $product->getMedia('medias');

        foreach ($mediaCollection as $media) {
            $mediaUrls[] = $media->getFullUrl();
        }

        $user = $product->user;
        $currencyService = app(CurrencyService::class);
        $userCurrency = $currencyService->getUserCurrency($user);

        $formattedProductMessage = self::formatProductMessage(
            $product->title,
            $product->description,
            $currencyService->formatPrice((float) $product->price, $userCurrency)
        );

        return new self(
            formattedProductMessage: $formattedProductMessage,
            mediaUrls: $mediaUrls
        );
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
