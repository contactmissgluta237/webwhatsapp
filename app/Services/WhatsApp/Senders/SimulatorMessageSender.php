<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Senders;

use App\DTOs\WhatsApp\ProductDataDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

final class SimulatorMessageSender extends AbstractMessageSender
{
    public function __construct(
        private readonly object $livewireComponent
    ) {}

    /**
     * Envoie la réponse enrichie dans l'interface du simulateur
     */
    public function sendResponse(WhatsAppMessageResponseDTO $response): void
    {
        if (! $response->processed || $response->processingError) {
            $this->sendErrorMessage($response->processingError ?? 'Erreur inconnue');

            return;
        }

        if (! $response->hasAiResponse || ! $response->aiResponse) {
            $this->sendErrorMessage('Aucune réponse AI générée');

            return;
        }

        Log::info('[SIMULATOR_SENDER] Envoi réponse dans simulateur', [
            'message_length' => strlen($response->aiResponse),
            'products_count' => count($response->products),
            'wait_time' => $response->waitTimeSeconds,
            'typing_duration' => $response->typingDurationSeconds,
        ]);

        $this->scheduleMessagesDisplay($response);
    }

    /**
     * Schedule message display with realistic timing
     */
    private function scheduleMessagesDisplay(WhatsAppMessageResponseDTO $response): void
    {
        $waitTimeMs = $response->waitTimeSeconds * 100;
        $typingDurationMs = $response->typingDurationSeconds * 100;

        $this->livewireComponent->dispatch('simulate-response-timing', [
            'waitTimeMs' => $waitTimeMs,
            'typingDurationMs' => $typingDurationMs,
            'responseMessage' => $response->aiResponse,
        ]);

        if (! empty($response->products)) {
            $this->scheduleProductsDisplay($response->products, $waitTimeMs + $typingDurationMs + 2000);
        }
    }

    /**
     * Programme l'affichage des produits
     */
    private function scheduleProductsDisplay(array $products, int $delayMs): void
    {
        Log::info('[SIMULATOR_SENDER] Programmation envoi produits', [
            'products_count' => count($products),
            'delay_ms' => $delayMs,
        ]);

        $formattedProducts = [];
        foreach ($products as $product) {
            /** @var ProductDataDTO $product */
            $formattedProducts[] = [
                'message' => $product->formattedProductMessage,
                'media_urls' => $product->mediaUrls,
            ];
        }

        $this->livewireComponent->dispatch('simulate-products-display', [
            'products' => $formattedProducts,
            'delayAfterMessage' => $delayMs,
        ]);
    }

    /**
     * Format products for simulator display
     */
    private function formatProductsForDisplay(array $products): array
    {
        return array_map(function (ProductDataDTO $product, int $index): array {
            return [
                'id' => $index + 1,
                'formatted_message' => $product->formattedProductMessage,
                'media_links' => $product->mediaUrls,
            ];
        }, $products, array_keys($products));
    }

    /**
     * Format a single product message for display
     */
    private function formatSingleProductMessage(ProductDataDTO $product): string
    {
        return $product->formattedProductMessage;
    }

    /**
     * Ajoute un message d'erreur dans le simulateur
     */
    private function sendErrorMessage(string $error): void
    {
        Log::error('[SIMULATOR_SENDER] Erreur dans simulateur', ['error' => $error]);

        $this->livewireComponent->simulationMessages[] = [
            'type' => 'system',
            'content' => "❌ Erreur: {$error}",
            'time' => Carbon::now()->format('H:i:s'),
        ];
    }

    /**
     * Ajoute directement un message dans le simulateur (pour usage interne)
     */
    public function addMessage(string $type, string $content): void
    {
        $this->livewireComponent->simulationMessages[] = [
            'type' => $type,
            'content' => $content,
            'time' => Carbon::now()->format('H:i:s'),
        ];

        if (count($this->livewireComponent->simulationMessages) > $this->livewireComponent->maxMessages) {
            array_shift($this->livewireComponent->simulationMessages);
        }

        $this->livewireComponent->dispatch('message-added');
    }

    /**
     * Add a formatted product message
     */
    public function addProductMessage(ProductDataDTO $product): void
    {
        $formattedMessage = $this->formatSingleProductMessage($product);
        $this->addMessage('product', $formattedMessage);

        Log::info('[SIMULATOR_SENDER] Message produit ajouté', [
            'formatted_message_length' => strlen($formattedMessage),
            'media_count' => count($product->mediaUrls),
        ]);
    }

    /**
     * Send a single product to a specific recipient (implements abstract method)
     */
    protected function sendSingleProduct(
        ProductDataDTO $product,
        string $sessionId,
        string $phoneNumber
    ): void {
        $this->addProductMessage($product);
    }
}
