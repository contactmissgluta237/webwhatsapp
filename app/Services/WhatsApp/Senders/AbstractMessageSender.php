<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Senders;

use App\DTOs\WhatsApp\ProductDataDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use App\Models\UserProduct;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Abstract base class for WhatsApp message senders
 *
 * Provides common functionality for sending WhatsApp messages including
 * product formatting, data enrichment, and sequential sending capabilities.
 */
abstract class AbstractMessageSender
{
    private const CURRENCY_SUFFIX = ' XAF';
    private const MEDIA_COLLECTION = 'images';

    /**
     * Send a WhatsApp response message
     *
     * @param  WhatsAppMessageResponseDTO  $response  The response to send
     */
    abstract public function sendResponse(WhatsAppMessageResponseDTO $response): void;

    /**
     * Send a single product to a specific recipient
     *
     * @param  ProductDataDTO  $product  The product to send
     * @param  string  $sessionId  The session identifier
     * @param  string  $phoneNumber  The recipient's phone number
     */
    abstract protected function sendSingleProduct(
        ProductDataDTO $product,
        string $sessionId,
        string $phoneNumber
    ): void;

    /**
     * Enrich product data by fetching complete information from database
     *
     * @param  int[]  $productIds  Array of product IDs to enrich
     * @return ProductDataDTO[] Array of enriched product DTOs
     */
    protected function enrichProductsData(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $this->logEnrichmentStart($productIds);

        $products = $this->fetchActiveProducts($productIds);
        $enrichedProducts = $this->transformProductsToDTO($products);

        $this->logEnrichmentResult($productIds, $enrichedProducts);

        return $enrichedProducts;
    }

    /**
     * Send multiple products sequentially with configured delays
     *
     * @param  ProductDataDTO[]  $products  Array of products to send
     * @param  string  $sessionId  The session identifier
     * @param  string  $phoneNumber  The recipient's phone number
     *
     * @throws InvalidArgumentException If session ID or phone number is empty
     */
    protected function sendProductsSequentially(
        array $products,
        string $sessionId,
        string $phoneNumber
    ): void {
        if (empty($products)) {
            return;
        }

        $this->validateSequentialSendingParams($sessionId, $phoneNumber);

        $delaySeconds = $this->getConfigValue('products.send_delay_seconds');
        $this->logSequentialSendingStart($products, $delaySeconds, $sessionId, $phoneNumber);

        $this->processProductsWithDelay($products, $delaySeconds, $sessionId, $phoneNumber);

        Log::info('[SENDER] Sequential product sending completed');
    }

    /**
     * Apply anti-spam delay based on configuration
     */
    protected function respectAntiSpamDelay(): void
    {
        $delayMs = $this->getConfigValue('messaging.anti_spam_delay_ms');
        Log::debug('[SENDER] Anti-spam delay applied', ['delay_ms' => $delayMs]);
        usleep($delayMs * 1000);
    }

    /**
     * Get a configuration value from the whatsapp config
     *
     * @param  string  $key  The configuration key (dot notation)
     * @param  mixed  $default  The default value if key doesn't exist
     * @return mixed The configuration value
     */
    protected function getConfigValue(string $key, mixed $default = null): mixed
    {
        return config("whatsapp.{$key}", $default);
    }

    /**
     * Format a price according to local standards
     *
     * @param  float  $price  The raw price value
     * @return string The formatted price with currency
     */
    protected function formatPrice(float $price): string
    {
        return number_format($price, 0, ',', ' ').self::CURRENCY_SUFFIX;
    }

    /**
     * Fetch active products from database with media relations
     *
     * @param  int[]  $productIds  Array of product IDs to fetch
     * @return Collection<int, UserProduct> Collection of user products
     */
    private function fetchActiveProducts(array $productIds): Collection
    {
        return UserProduct::with('media')
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Transform product models to DTOs
     *
     * @param  Collection<int, UserProduct>  $products  Collection of products to transform
     * @return ProductDataDTO[] Array of product DTOs
     */
    private function transformProductsToDTO(Collection $products): array
    {
        return $products->map(function (UserProduct $product) {
            $mediaLinks = $this->extractMediaLinks($product);

            $dto = new ProductDataDTO(
                id: $product->id,
                title: $product->title,
                description: $product->description,
                price: $this->formatPrice($product->price),
                mediaLinks: $mediaLinks,
                isActive: $product->is_active,
                createdAt: $product->created_at?->toISOString(),
                updatedAt: $product->updated_at?->toISOString(),
                rawPrice: $product->price
            );

            $this->logProductEnrichment($product, $mediaLinks);

            return $dto;
        })->toArray();
    }

    /**
     * Extract media links from a product
     *
     * @param  UserProduct  $product  The product to extract media from
     * @return string[] Array of media URLs
     */
    private function extractMediaLinks(UserProduct $product): array
    {
        return $product->getMedia(self::MEDIA_COLLECTION)
            ->map(fn ($media) => $media->getFullUrl())
            ->toArray();
    }

    /**
     * Check if a message is empty or whitespace only
     *
     * @param  string|null  $message  The message to check
     * @return bool True if message is empty
     */
    private function isEmptyMessage(?string $message): bool
    {
        return empty(trim($message ?? ''));
    }

    /**
     * Validate parameters for sequential sending
     *
     * @param  string  $sessionId  The session identifier
     * @param  string  $phoneNumber  The phone number
     *
     * @throws InvalidArgumentException If parameters are invalid
     */
    private function validateSequentialSendingParams(string $sessionId, string $phoneNumber): void
    {
        if (empty($sessionId) || empty($phoneNumber)) {
            throw new InvalidArgumentException('Session ID and phone number are required for sequential sending');
        }
    }

    /**
     * Process products with configured delays between sends
     *
     * @param  ProductDataDTO[]  $products  Array of products to process
     * @param  int  $delaySeconds  Delay between products in seconds
     * @param  string  $sessionId  The session identifier
     * @param  string  $phoneNumber  The recipient's phone number
     */
    private function processProductsWithDelay(
        array $products,
        int $delaySeconds,
        string $sessionId,
        string $phoneNumber
    ): void {
        $totalProducts = count($products);

        foreach ($products as $index => $product) {
            $this->sendSingleProduct($product, $sessionId, $phoneNumber);

            if ($this->shouldApplyDelay($index, $totalProducts)) {
                $this->applyProductDelay($delaySeconds, $index + 1, $totalProducts);
            }
        }
    }

    /**
     * Determine if delay should be applied after current product
     *
     * @param  int  $currentIndex  Current product index (0-based)
     * @param  int  $totalProducts  Total number of products
     * @return bool True if delay should be applied
     */
    private function shouldApplyDelay(int $currentIndex, int $totalProducts): bool
    {
        return $currentIndex < $totalProducts - 1;
    }

    /**
     * Apply delay between product sends
     *
     * @param  int  $delaySeconds  Duration of delay in seconds
     * @param  int  $currentProduct  Current product number (1-based)
     * @param  int  $totalProducts  Total number of products
     */
    private function applyProductDelay(int $delaySeconds, int $currentProduct, int $totalProducts): void
    {
        Log::info('[SENDER] Applying delay before next product', [
            'delay_seconds' => $delaySeconds,
            'current_product' => $currentProduct,
            'total_products' => $totalProducts,
        ]);

        sleep($delaySeconds);
    }

    // ================================
    // LOGGING METHODS
    // ================================

    /**
     * Log sending information for debugging
     *
     * @param  WhatsAppMessageResponseDTO  $response  The response being sent
     * @param  string  $senderType  The type of sender
     */
    protected function logSendingInfo(WhatsAppMessageResponseDTO $response, string $senderType): void
    {
        Log::info("[{$senderType}] Preparing response", [
            'message_length' => strlen($response->aiResponse ?? ''),
            'products_count' => count($response->products),
            'wait_time_seconds' => $response->waitTimeSeconds,
            'typing_duration_seconds' => $response->typingDurationSeconds,
            'has_ai_details' => $response->aiDetails !== null,
            'ai_model' => $response->aiDetails?->model,
            'session_id' => $response->sessionId,
            'phone_number' => $response->phoneNumber,
        ]);
    }

    /**
     * Log the start of product enrichment process
     *
     * @param  int[]  $productIds  Array of product IDs being enriched
     */
    private function logEnrichmentStart(array $productIds): void
    {
        Log::info('[SENDER] Starting product data enrichment', [
            'product_ids' => $productIds,
            'count' => count($productIds),
        ]);
    }

    /**
     * Log individual product enrichment details
     *
     * @param  UserProduct  $product  The product that was enriched
     * @param  string[]  $mediaLinks  Array of media URLs for the product
     */
    private function logProductEnrichment(UserProduct $product, array $mediaLinks): void
    {
        Log::info('[SENDER] Product enriched', [
            'product_id' => $product->id,
            'title' => $product->title,
            'price' => $product->price,
            'is_active' => $product->is_active,
            'media_count' => count($mediaLinks),
        ]);
    }

    /**
     * Log the results of product enrichment process
     *
     * @param  int[]  $requestedIds  Array of originally requested product IDs
     * @param  ProductDataDTO[]  $enrichedProducts  Array of successfully enriched products
     */
    private function logEnrichmentResult(array $requestedIds, array $enrichedProducts): void
    {
        $foundIds = array_column($enrichedProducts, 'id');

        Log::info('[SENDER] Enrichment completed', [
            'requested_count' => count($requestedIds),
            'found_count' => count($enrichedProducts),
            'missing_ids' => array_diff($requestedIds, $foundIds),
        ]);
    }

    /**
     * Log the start of sequential product sending
     *
     * @param  ProductDataDTO[]  $products  Array of products to be sent
     * @param  int  $delaySeconds  Configured delay between products
     * @param  string  $sessionId  The session identifier
     * @param  string  $phoneNumber  The recipient's phone number
     */
    private function logSequentialSendingStart(
        array $products,
        int $delaySeconds,
        string $sessionId,
        string $phoneNumber
    ): void {
        Log::info('[SENDER] Starting sequential product sending', [
            'products_count' => count($products),
            'delay_seconds' => $delaySeconds,
            'session_id' => $sessionId,
            'phone_number' => $phoneNumber,
        ]);
    }
}
