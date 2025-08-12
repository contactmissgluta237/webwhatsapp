<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Contracts\PromptEnhancementInterface;
use App\DTOs\AI\AiRequestDTO;
use App\Models\AiModel;
use App\Models\WhatsAppAccount;
use Illuminate\Support\Facades\Log;

final class PromptEnhancementService implements PromptEnhancementInterface
{
    private const ENHANCEMENT_SYSTEM_PROMPT = "Tu es un expert en création de prompts pour agents conversationnels WhatsApp. 

Ta mission est d'améliorer le prompt fourni pour qu'il soit plus efficace, professionnel et adapté aux conversations WhatsApp.

RÈGLES IMPORTANTES :
- Garde le sens et l'intention originale du prompt
- Améliore la structure, la clarté et l'efficacité
- Adapte le ton pour WhatsApp (plus personnel et direct)
- Ajoute des instructions claires sur le comportement attendu
- Assure-toi que le prompt est en français
- Limite à 500 mots maximum
- Inclus des instructions sur la gestion des salutations, questions fréquentes, et transfert vers humain

Réponds UNIQUEMENT avec le prompt amélioré, sans explications supplémentaires.";

    public function enhancePrompt(WhatsAppAccount $account, string $originalPrompt): string
    {
        // Utiliser le modèle par défaut Ollama ou le modèle configuré de l'account
        $model = $this->getEnhancementModel($account);

        if (! $model) {
            throw new \Exception('Aucun modèle IA disponible pour l\'amélioration du prompt');
        }

        Log::info('🚀 Amélioration de prompt demandée', [
            'account_id' => $account->id,
            'model_id' => $model->id,
            'original_length' => strlen($originalPrompt),
        ]);

        $userMessage = "Voici le prompt à améliorer pour un agent WhatsApp :\n\n".$originalPrompt;

        $request = new AiRequestDTO(
            systemPrompt: self::ENHANCEMENT_SYSTEM_PROMPT,
            userMessage: $userMessage,
            config: [
                'temperature' => 0.3, // Plus déterministe pour les améliorations
                'max_tokens' => 1000,
            ]
        );

        try {
            $service = $model->provider->createService();
            $response = $service->chat($model, $request);

            Log::info('✅ Prompt amélioré avec succès', [
                'account_id' => $account->id,
                'enhanced_length' => strlen($response->content),
                'model_used' => $model->name,
            ]);

            return $response->content;

        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de l\'amélioration du prompt', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
                'model_id' => $model->id,
            ]);

            throw new \Exception('Impossible d\'améliorer le prompt : '.$e->getMessage());
        }
    }

    private function getEnhancementModel(WhatsAppAccount $account): ?AiModel
    {
        // Priorité 1: Modèle configuré sur le compte
        if ($account->ai_model_id && $account->aiModel?->is_active) {
            return $account->aiModel;
        }

        // Priorité 2: Modèle Ollama par défaut
        $ollamaModel = AiModel::where('provider', 'ollama')
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();

        if ($ollamaModel) {
            return $ollamaModel;
        }

        // Priorité 3: N'importe quel modèle actif Ollama
        $anyOllamaModel = AiModel::where('provider', 'ollama')
            ->where('is_active', true)
            ->first();

        if ($anyOllamaModel) {
            return $anyOllamaModel;
        }

        // Priorité 4: N'importe quel modèle par défaut actif
        return AiModel::where('is_active', true)
            ->where('is_default', true)
            ->first();
    }
}
