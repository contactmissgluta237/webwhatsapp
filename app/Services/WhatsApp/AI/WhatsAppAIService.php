<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\AI;

use App\DTOs\AI\AiRequestDTO;
use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\AI\Prompt\WhatsAppPromptBuilder;
use Illuminate\Support\Facades\Log;

final class WhatsAppAIService
{
    public function __construct(
        private readonly WhatsAppPromptBuilder $promptBuilder
    ) {}

    public function generateResponse(
        WhatsAppAccount $account,
        string $userMessage,
        array $conversationContext = []
    ): ?array {
        try {
            $prompt = $this->promptBuilder->buildPrompt($account, $userMessage, $conversationContext);
            
            Log::info('🤖 Génération réponse IA centralisée', [
                'account_id' => $account->id,
                'prompt_length' => strlen($prompt),
                'context_messages_count' => count($conversationContext),
            ]);

            // Obtenir le service IA depuis le modèle configuré
            $model = $account->aiModel ?? $account->getEffectiveAiModel();
            
            if (!$model) {
                Log::warning('❌ Aucun modèle IA configuré', ['account_id' => $account->id]);
                return $this->fallbackResponse($userMessage);
            }

            // Utiliser le service spécifique au provider du modèle
            $aiService = $model->getService();

            // Appel réel au service IA
            $aiRequest = new AiRequestDTO(
                systemPrompt: $prompt,
                userMessage: $userMessage,
                config: [],
                context: $conversationContext
            );

            $response = $aiService->chat($model, $aiRequest);

            if ($response && !empty($response->content)) {
                return [
                    'response' => $response->content,
                    'model' => $model->name,
                    'confidence' => $response->confidence ?? 0.9,
                ];
            }

            // Fallback si pas de réponse
            return $this->fallbackResponse($userMessage);

        } catch (\Exception $e) {
            Log::error('❌ Erreur génération réponse IA', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackResponse($userMessage);
        }
    }

    private function fallbackResponse(string $userMessage): array
    {
        return [
            'response' => "Désolé, je rencontre actuellement des difficultés techniques. Pouvez-vous reformuler votre demande ?",
            'model' => 'fallback',
            'confidence' => 0.0,
        ];
    }
}
