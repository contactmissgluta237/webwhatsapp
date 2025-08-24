<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Senders;

use App\DTOs\WhatsApp\ProductDataDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use App\Services\WhatsApp\NodeJS\WhatsAppNodeJSService;
use Illuminate\Support\Facades\Log;

final class WhatsAppMessageSender extends AbstractMessageSender
{
    public function __construct(
        private readonly WhatsAppNodeJSService $nodeJSService
    ) {}

    public function sendResponse(WhatsAppMessageResponseDTO $response): void
    {
        $this->logSendingInfo($response, 'WHATSAPP_SENDER');

        if (! $this->validateResponse($response)) {
            return;
        }

        if (! $response->hasValidWhatsAppData()) {
            Log::error('[WHATSAPP_SENDER] Invalid WhatsApp data', [
                'session_id' => $response->sessionId,
                'phone_number' => $response->phoneNumber,
            ]);

            return;
        }

        $this->sendTextMessage($response);

        if ($response->hasProducts()) {
            $enrichedProducts = $this->enrichProductsData($response->getProductIds());
            $this->sendProductsSequentially(
                $enrichedProducts,
                $response->sessionId,
                $response->phoneNumber
            );
        }

        $this->respectAntiSpamDelay();

        Log::info('[WHATSAPP_SENDER] Response sent successfully', [
            'session_id' => $response->sessionId,
            'phone_number' => $response->phoneNumber,
            'products_sent' => $response->getProductsCount(),
        ]);
    }

    protected function sendSingleProduct(
        ProductDataDTO $product,
        string $sessionId,
        string $phoneNumber
    ): void {
        Log::info('[WHATSAPP_SENDER] Sending single product', [
            'product_id' => $product->id,
            'product_title' => $product->title,
            'session_id' => $sessionId,
            'phone_number' => $phoneNumber,
        ]);

        // Send product text message
        $productMessage = $this->formatProductMessage($product);
        $textSent = $this->nodeJSService->sendTextMessage($sessionId, $phoneNumber, $productMessage);

        if (! $textSent) {
            Log::error('[WHATSAPP_SENDER] Failed to send product text message', [
                'product_id' => $product->id,
                'session_id' => $sessionId,
                'phone_number' => $phoneNumber,
            ]);

            return;
        }

        // Send product media if available
        if (! empty($product->mediaLinks)) {
            $this->sendProductMedia($product, $sessionId, $phoneNumber);
        }

        Log::info('[WHATSAPP_SENDER] Product sent successfully', [
            'product_id' => $product->id,
            'media_sent' => count($product->mediaLinks),
            'session_id' => $sessionId,
        ]);
    }

    private function sendTextMessage(WhatsAppMessageResponseDTO $response): void
    {
        $success = $this->nodeJSService->sendTextMessage(
            $response->sessionId,
            $response->phoneNumber,
            $response->aiResponse
        );

        if (! $success) {
            Log::error('[WHATSAPP_SENDER] Failed to send text message', [
                'session_id' => $response->sessionId,
                'phone_number' => $response->phoneNumber,
                'message_length' => strlen($response->aiResponse ?? ''),
            ]);
        }
    }

    private function sendProductMedia(
        ProductDataDTO $product,
        string $sessionId,
        string $phoneNumber
    ): void {
        $mediaDelay = $this->getConfigValue('products.media_delay_seconds', 2);

        foreach ($product->mediaLinks as $index => $mediaUrl) {
            if ($index > 0) {
                sleep($mediaDelay);
            }

            $caption = $index === 0 ? $product->title : '';
            $success = $this->nodeJSService->sendMediaMessage(
                $sessionId,
                $phoneNumber,
                $mediaUrl,
                'image',
                $caption
            );

            if (! $success) {
                Log::warning('[WHATSAPP_SENDER] Failed to send product media', [
                    'product_id' => $product->id,
                    'media_url' => $mediaUrl,
                    'media_index' => $index,
                    'session_id' => $sessionId,
                ]);
            }
        }
    }
}
