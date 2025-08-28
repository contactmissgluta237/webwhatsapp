<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Helpers;

use App\DTOs\WhatsApp\WhatsAppAIResponseDTO;
use App\DTOs\WhatsApp\WhatsAppAIStructuredResponseDTO;
use Illuminate\Support\Facades\Log;

class AIResponseParserHelper
{
    public function parseStructuredResponse(WhatsAppAIResponseDTO $aiResponse): WhatsAppAIStructuredResponseDTO
    {
        Log::info('[AI_PARSER] Parsing AI response', [
            'response_length' => strlen($aiResponse->response),
            'model_used' => $aiResponse->model,
        ]);

        try {
            $result = WhatsAppAIStructuredResponseDTO::fromAIResponse($aiResponse);

            Log::info('[AI_PARSER] AI response parsed successfully', [
                'message_preview' => substr($result->message, 0, 100),
                'action' => $result->action->value,
                'products_count' => count($result->productIds),
            ]);

            return $result;

        } catch (\InvalidArgumentException $e) {
            Log::error('[AI_PARSER] AI parsing error', [
                'error' => $e->getMessage(),
                'response_content' => $aiResponse->response,
                'model' => $aiResponse->model,
            ]);

            throw $e;
        }
    }
}
