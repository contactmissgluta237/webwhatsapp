<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Contracts\PromptEnhancementInterface;
use App\DTOs\AI\AiRequestDTO;
use App\Models\AiModel;
use App\Models\WhatsAppAccount;
use Illuminate\Support\Facades\Log;

class PromptEnhancementService implements PromptEnhancementInterface
{
    private const ENHANCEMENT_SYSTEM_PROMPT = "Tu es un expert en amélioration de prompts pour agents conversationnels WhatsApp.

    Améliore le prompt fourni en respectant ces règles :
    - Conserve le sens et l'intention originale
    - Améliore la clarité et la structure
    - Adapte le ton pour WhatsApp (naturel et direct)
    - Limite à 200 mots maximum
    - Assure-toi que l'agent se comporte comme un humain (jamais mentionner qu'il est une IA)

    RÈGLES DE RÉPONSE ABSOLUES :
    - Réponds UNIQUEMENT avec le texte du prompt amélioré
    - AUCUN titre, AUCUN préfixe, AUCUNE introduction
    - AUCUN formatage markdown, AUCUNE étoile, AUCUN emoji
    - AUCUNE section (Rôle:, Comportement:, etc.)
    - Juste le prompt directement utilisable";

    public function enhancePrompt(WhatsAppAccount $account, string $originalPrompt): string
    {
        $model = $this->getEnhancementModel($account);

        if (! $model) {
            throw new \Exception('Aucun modèle IA disponible pour l\'amélioration du prompt');
        }

        Log::info('🚀 Amélioration de prompt demandée', [
            'account_id' => $account->id,
            'model_id' => $model->id,
            'model_provider' => $model->provider->value, // ← Correction
            'original_length' => strlen($originalPrompt),
        ]);

        $userMessage = "Voici le prompt à améliorer pour un agent WhatsApp :\n\n".$originalPrompt;

        $request = new AiRequestDTO(
            systemPrompt: self::ENHANCEMENT_SYSTEM_PROMPT,
            userMessage: $userMessage,
            config: [
                'temperature' => 0.3,
                'max_tokens' => 1000,
            ]
        );

        return $this->executeEnhancementWithFallback($account, $model, $request, $originalPrompt);
    }

    private function executeEnhancementWithFallback(
        WhatsAppAccount $account,
        AiModel $primaryModel,
        AiRequestDTO $request,
        string $originalPrompt
    ): string {
        try {
            $service = $primaryModel->provider->createService();
            $response = $service->chat($primaryModel, $request);

            Log::info('✅ Prompt amélioré avec succès', [
                'account_id' => $account->id,
                'enhanced_length' => strlen($response->content),
                'model_used' => $primaryModel->name,
                'provider' => $primaryModel->provider->value, // ← Correction
            ]);

            return $this->cleanEnhancedPrompt($response->content);
        } catch (\Exception $e) {
            Log::warning('⚠️ Échec amélioration avec modèle principal', [
                'account_id' => $account->id,
                'primary_model' => $primaryModel->name,
                'error' => $e->getMessage(),
            ]);

            return $this->tryFallbackModels($account, $request, $originalPrompt, [$primaryModel->id]);
        }
    }

    private function tryFallbackModels(
        WhatsAppAccount $account,
        AiRequestDTO $request,
        string $originalPrompt,
        array $excludeModelIds = []
    ): string {
        $fallbackModels = $this->getFallbackModels($excludeModelIds);

        foreach ($fallbackModels as $model) {
            try {
                Log::info('🔄 Tentative fallback avec modèle', [
                    'account_id' => $account->id,
                    'fallback_model' => $model->name,
                    'provider' => $model->provider->value, // ← Correction
                ]);

                $service = $model->provider->createService();
                $response = $service->chat($model, $request);

                Log::info('✅ Amélioration réussie avec fallback', [
                    'account_id' => $account->id,
                    'enhanced_length' => strlen($response->content),
                    'fallback_model_used' => $model->name,
                    'provider' => $model->provider->value, // ← Correction
                ]);

                return $this->cleanEnhancedPrompt($response->content);
            } catch (\Exception $e) {
                Log::warning('⚠️ Échec fallback', [
                    'account_id' => $account->id,
                    'fallback_model' => $model->name,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        Log::error('❌ Tous les modèles de fallback ont échoué', [
            'account_id' => $account->id,
            'attempted_models' => $fallbackModels->pluck('name')->toArray(),
        ]);

        throw new \Exception('Impossible d\'améliorer le prompt : tous les services IA sont indisponibles');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<AiModel>
     */
    private function getFallbackModels(array $excludeModelIds = []): \Illuminate\Database\Eloquent\Collection
    {
        return AiModel::where('is_active', true)
            ->whereNotIn('id', $excludeModelIds)
            ->orderByRaw("CASE WHEN provider = 'ollama' THEN 1 WHEN provider = 'openai' THEN 2 ELSE 3 END")
            ->orderBy('is_default', 'desc')
            ->get();
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

    private function cleanEnhancedPrompt(string $enhancedPrompt): string
    {
        Log::info('🧹 DÉBUT NETTOYAGE', [
            'original_content' => $enhancedPrompt,
            'original_length' => strlen($enhancedPrompt),
        ]);

        $cleaned = $enhancedPrompt;

        // Étape 1: Supprimer les titres et préfixes indésirables
        Log::info('🧹 ÉTAPE 1: Suppression des préfixes indésirables');
        $unwantedPrefixes = [
            'Prompt amélioré pour agent WhatsApp',
            'Prompt amélioré',
            'Voici le prompt amélioré',
            'Le prompt amélioré',
            'Nouveau prompt',
            'Rôle :',
            'Comportement attendu :',
            'Gestion des interactions :',
            'Interdits :',
            'Exemple de réponse :',
            'Note :',
        ];

        foreach ($unwantedPrefixes as $prefix) {
            $beforeClean = $cleaned;
            $cleaned = preg_replace('/^'.preg_quote($prefix, '/').'\s*:?\s*/i', '', $cleaned);
            $cleaned = preg_replace('/\*\*'.preg_quote($prefix, '/').'\s*\*\*\s*:?\s*/i', '', $cleaned);

            if ($beforeClean !== $cleaned) {
                Log::info("🧹 Préfixe supprimé: '{$prefix}'", [
                    'before_length' => strlen($beforeClean),
                    'after_length' => strlen($cleaned),
                    'removed_chars' => strlen($beforeClean) - strlen($cleaned),
                ]);
            }
        }

        Log::info('🧹 APRÈS ÉTAPE 1 (préfixes)', [
            'content' => $cleaned,
            'length' => strlen($cleaned),
        ]);

        // Étape 2: Supprimer tout le formatage Markdown et étoiles
        Log::info('🧹 ÉTAPE 2: Suppression du formatage Markdown');

        $beforeMarkdown = $cleaned;
        $cleaned = preg_replace('/\*\*([^*]*)\*\*/', '$1', $cleaned); // **texte** -> texte
        Log::info('🧹 Suppression **texte**', [
            'stars_removed' => substr_count($beforeMarkdown, '**') - substr_count($cleaned, '**'),
        ]);

        $beforeItalic = $cleaned;
        $cleaned = preg_replace('/\*([^*]*)\*/', '$1', $cleaned);     // *texte* -> texte
        Log::info('🧹 Suppression *texte*', [
            'stars_removed' => substr_count($beforeItalic, '*') - substr_count($cleaned, '*'),
        ]);

        $beforeAllStars = $cleaned;
        $cleaned = preg_replace('/\*+/', '', $cleaned);              // Supprimer toutes les étoiles restantes
        Log::info('🧹 Suppression étoiles restantes', [
            'all_stars_removed' => substr_count($beforeAllStars, '*'),
        ]);

        Log::info('🧹 APRÈS ÉTAPE 2 (markdown)', [
            'content' => $cleaned,
            'length' => strlen($cleaned),
        ]);

        // Étape 3: Supprimer tous les emojis et symboles
        Log::info('🧹 ÉTAPE 3: Suppression des emojis');
        $beforeEmojis = $cleaned;
        $cleaned = preg_replace('/[✅❌🔥💡📱⚡🚀💼📞👋😊🎯]/u', '', $cleaned);

        if ($beforeEmojis !== $cleaned) {
            Log::info('🧹 Emojis supprimés', [
                'before_length' => strlen($beforeEmojis),
                'after_length' => strlen($cleaned),
            ]);
        }

        Log::info('🧹 APRÈS ÉTAPE 3 (emojis)', [
            'content' => $cleaned,
            'length' => strlen($cleaned),
        ]);

        // Étape 4: Supprimer les sections structurées
        Log::info('🧹 ÉTAPE 4: Suppression des sections structurées');
        $beforeSections = $cleaned;
        $cleaned = preg_replace('/\*\*[^:]+:\*\*\s*/', '', $cleaned);
        // Regex plus spécifique pour les vraies sections de structure (lignes entières qui commencent par des mots clés)
        $cleaned = preg_replace('/^(Rôle|Comportement|Gestion|Interdits|Exemple|Note|Objectifs|Tonalité)\s*:\s*.*$/m', '', $cleaned);

        if ($beforeSections !== $cleaned) {
            Log::info('🧹 Sections supprimées', [
                'before_length' => strlen($beforeSections),
                'after_length' => strlen($cleaned),
                'removed_chars' => strlen($beforeSections) - strlen($cleaned),
            ]);
        }

        Log::info('🧹 APRÈS ÉTAPE 4 (sections)', [
            'content' => $cleaned,
            'length' => strlen($cleaned),
        ]);

        // Étape 5: Nettoyer les listes avec tirets/puces
        Log::info('🧹 ÉTAPE 5: Suppression des puces');
        $beforeBullets = $cleaned;
        $cleaned = preg_replace('/^[\s]*[-•]\s*/m', '', $cleaned);

        if ($beforeBullets !== $cleaned) {
            Log::info('🧹 Puces supprimées', [
                'before_length' => strlen($beforeBullets),
                'after_length' => strlen($cleaned),
            ]);
        }

        Log::info('🧹 APRÈS ÉTAPE 5 (puces)', [
            'content' => $cleaned,
            'length' => strlen($cleaned),
        ]);

        // Étape 6: Nettoyer les espaces multiples et retours à la ligne excessifs
        Log::info('🧹 ÉTAPE 6: Nettoyage des espaces');
        $beforeSpaces = $cleaned;
        $cleaned = preg_replace('/\n{3,}/', "\n\n", $cleaned);
        $cleaned = preg_replace('/[ \t]+/', ' ', $cleaned);
        $cleaned = preg_replace('/\s*\n\s*/', "\n", $cleaned);

        Log::info('🧹 APRÈS ÉTAPE 6 (espaces)', [
            'content' => $cleaned,
            'length' => strlen($cleaned),
        ]);

        // Étape 7: Trim final et supprimer les lignes vides en début/fin
        Log::info('🧹 ÉTAPE 7: Trim final');
        $beforeTrim = $cleaned;
        $cleaned = trim($cleaned);
        $cleaned = preg_replace('/^\s*\n+/', '', $cleaned);
        $cleaned = preg_replace('/\n+\s*$/', '', $cleaned);

        Log::info('🧹 RÉSULTAT FINAL', [
            'final_content' => $cleaned,
            'original_length' => strlen($enhancedPrompt),
            'final_length' => strlen($cleaned),
            'total_removed_chars' => strlen($enhancedPrompt) - strlen($cleaned),
            'has_stars_remaining' => substr_count($cleaned, '*'),
            'has_emojis_remaining' => preg_match('/[✅❌🔥💡📱⚡🚀💼📞👋😊🎯]/u', $cleaned),
        ]);

        return $cleaned;
    }
}
