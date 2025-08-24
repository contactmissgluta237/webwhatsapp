<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Contracts\WhatsApp\AIProviderServiceInterface;
use App\Contracts\WhatsApp\MessageBuildServiceInterface;
use App\Contracts\WhatsApp\WhatsAppMessageOrchestratorInterface;
use App\DTOs\WhatsApp\ProductDataDTO;
use App\DTOs\WhatsApp\WhatsAppAIStructuredResponseDTO;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use App\Models\UserProduct;
use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\Helpers\AIResponseParserHelper;
use App\Services\WhatsApp\Helpers\ResponseTimingHelper;
use Exception;
use Illuminate\Support\Facades\Log;

final readonly class WhatsAppMessageOrchestrator implements WhatsAppMessageOrchestratorInterface
{
    public function __construct(
        private MessageBuildServiceInterface $messageBuildService,
        private AIProviderServiceInterface $aiProviderService,
        private AIResponseParserHelper $aiResponseParser,
        private ResponseTimingHelper $responseTimingHelper,
    ) {}

    public function processMessage(
        WhatsAppAccount $account,
        WhatsAppMessageRequestDTO $messageRequest,
        string $conversationHistory
    ): WhatsAppMessageResponseDTO {
        Log::info('[ORCHESTRATOR] Processing message', [
            'session_id' => $account->session_id,
            'from' => $messageRequest->from,
            'message_id' => $messageRequest->id,
            'history_length' => strlen($conversationHistory),
            'ai_model_id' => $account->ai_model_id,
        ]);

        try {
            $aiResponse = $this->generateAIResponse($account, $conversationHistory, $messageRequest->body);

            if (! $aiResponse?->hasValidResponse()) {
                Log::warning('[ORCHESTRATOR] No valid AI response', [
                    'session_id' => $account->session_id,
                ]);

                return WhatsAppMessageResponseDTO::processedWithoutResponse();
            }

            $structuredResponse = $this->aiResponseParser->parseStructuredResponse($aiResponse);
            $this->logStructuredResponse($account->session_id, $structuredResponse);
            $enrichedProducts = $this->getEnrichedProducts($structuredResponse);

            return WhatsAppMessageResponseDTO::success(
                $structuredResponse->message,
                $aiResponse,
                $this->responseTimingHelper->calculateWaitTime($account),
                $this->responseTimingHelper->calculateTypingDuration($aiResponse),
                $enrichedProducts,
                $account->session_id,
                $messageRequest->from
            );

        } catch (Exception $exception) {
            Log::error('[ORCHESTRATOR] Error processing message', [
                'session_id' => $account->session_id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return WhatsAppMessageResponseDTO::error('Erreur traitement message: '.$exception->getMessage());
        }
    }

    private function generateAIResponse(WhatsAppAccount $account, string $conversationHistory, string $messageBody): mixed
    {
        $aiRequest = $this->messageBuildService->buildAiRequest(
            $account,
            $conversationHistory,
            $messageBody
        );

        return $this->aiProviderService->generateResponse($aiRequest);
    }

    private function logStructuredResponse(string $sessionId, WhatsAppAIStructuredResponseDTO $structuredResponse): void
    {
        Log::info('[ORCHESTRATOR] Structured response parsed', [
            'session_id' => $sessionId,
            'product_count' => count($structuredResponse->productIds),
        ]);
    }

    /**
     * @return ProductDataDTO[]
     */
    private function getEnrichedProducts(mixed $structuredResponse): array
    {
        return $structuredResponse->shouldSendProducts()
            ? $this->enrichProductsData($structuredResponse->productIds)
            : [];
    }

    /**
     * Enrich product IDs with complete data
     *
     * @param  array<int>  $productIds
     * @return ProductDataDTO[]
     */
    private function enrichProductsData(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        Log::info('[ORCHESTRATOR] Enriching products data', [
            'product_ids' => $productIds,
            'count' => count($productIds),
        ]);

        $products = UserProduct::with('media')
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->get();

        $enrichedProducts = $products->map(
            fn (UserProduct $product) => ProductDataDTO::fromUserProduct($product)
        )->values()->all();

        Log::info('[ORCHESTRATOR] Products enriched successfully', [
            'input_count' => count($productIds),
            'output_count' => count($enrichedProducts),
        ]);

        return $enrichedProducts;
    }
}
