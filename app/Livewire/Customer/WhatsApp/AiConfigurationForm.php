<?php

declare(strict_types=1);

namespace App\Livewire\Customer\WhatsApp;

use App\Enums\AgentType;
use App\Enums\ResponseTime;
use App\Http\Requests\Customer\WhatsApp\AiConfigurationRequest;
use App\Models\AiModel;
use App\Models\WhatsAppAccount;
use App\Rules\PromptLengthRule;
use App\Services\AI\Contracts\PromptEnhancementInterface;
use App\Services\AI\Helpers\AgentPromptHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property \Illuminate\Database\Eloquent\Collection $availableModels
 */
final class AiConfigurationForm extends Component
{
    public WhatsAppAccount $account;

    // Form properties
    public string $agent_name = '';
    public bool $agent_enabled = false;
    public ?int $ai_model_id = null;
    public string $agent_prompt = '';
    public string $trigger_words = '';
    public string $contextual_information = '';
    public string $ignore_words = '';
    public string $response_time = 'random';
    public bool $stop_on_human_reply = false;

    // File upload
    public $contextDocuments;

    // Prompt enhancement properties
    public string $enhancedPrompt = '';
    public string $originalPrompt = '';
    public bool $isEnhancing = false;
    public bool $hasEnhancedPrompt = false;
    public bool $isPromptValidated = false;
    public bool $isFormValid = true;
    private bool $isProgrammaticUpdate = false;

    public function mount(WhatsAppAccount $account): void
    {
        $this->account = $account;
        $this->loadCurrentConfiguration();
        $this->setDefaultModel();
        $this->updateFormValidity();
    }

    #[Computed]
    public function availableModels(): Collection
    {
        return AiModel::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function selectedModel(): ?AiModel
    {
        if (! $this->ai_model_id) {
            return null;
        }

        /** @var ?AiModel $model */
        $model = $this->availableModels->find($this->ai_model_id);

        return $model;
    }

    #[Computed]
    public function responseTimeOptions(): array
    {
        return collect(ResponseTime::cases())->map(function ($case) {
            return [
                'value' => $case->value,
                'label' => $case->label,
                'description' => $case->getDescription(),
            ];
        })->toArray();
    }

    #[Computed]
    public function availablePromptTypes(): array
    {
        return AgentPromptHelper::getAllPromptTypes();
    }

    public function updatedAgentEnabled(): void
    {
        $this->dispatch('ai-status-changed', enabled: $this->agent_enabled);
        $this->updateFormValidity();
    }

    public function updatedAiModelId(): void
    {
        $this->dispatch('model-changed', modelId: $this->ai_model_id);

        Log::info('🤖 Modèle IA mis à jour en temps réel', [
            'account_id' => $this->account->id,
            'new_model_id' => $this->ai_model_id,
        ]);

        $this->dispatch('config-changed-live', [
            'ai_model_id' => $this->ai_model_id,
        ]);

        $this->updateFormValidity();
    }

    public function updatedAgentPrompt(): void
    {
        // Skip processing during programmatic updates
        if ($this->isProgrammaticUpdate) {
            $this->dispatch('config-changed-live', [
                'agent_prompt' => $this->agent_prompt,
            ]);

            return;
        }

        // Reset enhancement state when prompt is manually modified
        if ($this->hasEnhancedPrompt && $this->agent_prompt !== $this->enhancedPrompt) {
            $this->resetEnhancementState();
        }

        // Reset validated state when prompt is manually modified after validation
        if ($this->isPromptValidated) {
            $this->isPromptValidated = false;
        }

        // Real-time validation using custom rule
        $this->validatePromptInRealTime();

        $this->dispatch('config-changed-live', [
            'agent_prompt' => $this->agent_prompt,
        ]);
    }

    public function updatedContextualInformation(): void
    {
        $this->dispatch('config-changed-live', [
            'contextual_information' => $this->contextual_information,
        ]);

        $this->updateFormValidity();
    }

    public function updatedIgnoreWords(): void
    {
        $this->dispatch('config-changed-live', [
            'ignore_words' => $this->ignore_words,
        ]);

        $this->updateFormValidity();
    }

    public function updatedResponseTime(): void
    {
        $this->dispatch('config-changed-live', [
            'response_time' => $this->response_time,
        ]);

        $this->updateFormValidity();
    }

    public function enhancePrompt(): void
    {
        if (empty(trim($this->agent_prompt))) {
            $this->dispatch('show-toast', [
                'type' => 'warning',
                'message' => __('Veuillez d\'abord saisir un prompt à améliorer'),
            ]);

            return;
        }

        $this->isEnhancing = true;
        $this->originalPrompt = $this->agent_prompt;

        try {
            $enhancementService = app(PromptEnhancementInterface::class);

            Log::info('🎯 Demande d\'amélioration de prompt via interface', [
                'account_id' => $this->account->id,
                'original_prompt_length' => strlen($this->agent_prompt),
                'user_interface' => 'livewire',
            ]);

            $this->enhancedPrompt = $enhancementService->enhancePrompt($this->account, $this->agent_prompt);

            $this->isProgrammaticUpdate = true;
            $this->agent_prompt = $this->enhancedPrompt;
            $this->hasEnhancedPrompt = true;
            $this->isProgrammaticUpdate = false;

            Log::info('✨ Prompt amélioré avec succès via interface', [
                'account_id' => $this->account->id,
                'original_length' => strlen($this->originalPrompt),
                'enhanced_length' => strlen($this->enhancedPrompt),
                'improvement_ratio' => round(strlen($this->enhancedPrompt) / strlen($this->originalPrompt), 2),
            ]);

            $this->dispatch('config-changed-live', [
                'agent_prompt' => $this->agent_prompt,
            ]);

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => __('Prompt amélioré avec succès ! +:count caractères ajoutés.', [
                    'count' => strlen($this->enhancedPrompt) - strlen($this->originalPrompt),
                ]),
            ]);

        } catch (\Exception $e) {
            $this->isProgrammaticUpdate = false;

            Log::error('❌ Erreur amélioration prompt via interface', [
                'account_id' => $this->account->id,
                'error' => $e->getMessage(),
                'original_length' => strlen($this->originalPrompt),
            ]);

            $isTimeoutError = str_contains($e->getMessage(), 'timeout') || str_contains($e->getMessage(), 'timed out');
            $isServiceUnavailable = str_contains($e->getMessage(), 'indisponibles');

            if ($isTimeoutError) {
                $errorMessage = __('L\'amélioration a pris trop de temps. Les services IA sont peut-être surchargés. Réessayez dans quelques instants.');
            } elseif ($isServiceUnavailable) {
                $errorMessage = __('Tous les services d\'IA sont temporairement indisponibles. Réessayez plus tard ou contactez le support.');
            } else {
                $errorMessage = __('Erreur lors de l\'amélioration : :error', ['error' => $e->getMessage()]);
            }

            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => $errorMessage,
            ]);
        }

        $this->isEnhancing = false;
    }

    public function insertPromptByType(string $agentType): void
    {
        try {
            $type = AgentType::from($agentType);
            $this->agent_prompt = AgentPromptHelper::getPromptByType($type);

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => __('Prompt :type inséré avec succès !', ['type' => $type->label]),
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('Type d\'agent non supporté'),
            ]);
        }
    }

    public function acceptEnhancedPrompt(): void
    {
        $this->isPromptValidated = true;
        $this->hasEnhancedPrompt = false;

        $this->dispatch('config-changed-live', [
            'agent_prompt' => $this->agent_prompt,
        ]);

        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => __('Prompt amélioré validé avec succès'),
        ]);

        Log::info('✅ Prompt amélioré accepté', [
            'account_id' => $this->account->id,
            'final_prompt_length' => strlen($this->agent_prompt),
        ]);
    }

    public function rejectEnhancedPrompt(): void
    {
        $this->isProgrammaticUpdate = true;
        $this->agent_prompt = $this->originalPrompt;
        $this->isProgrammaticUpdate = false;

        $this->resetEnhancementState();

        $this->dispatch('config-changed-live', [
            'agent_prompt' => $this->agent_prompt,
        ]);

        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => __('Prompt original restauré'),
        ]);

        Log::info('🔄 Prompt amélioré rejeté', [
            'account_id' => $this->account->id,
            'restored_prompt_length' => strlen($this->agent_prompt),
        ]);
    }

    public function removeDocument(int $mediaId): void
    {
        $media = $this->account->getMedia('context_documents')->firstWhere('id', $mediaId);

        if ($media) {
            $media->delete();
            $this->dispatch('document-removed', [
                'message' => __('Document supprimé avec succès'),
            ]);
        }
    }

    public function save(): void
    {
        $request = new AiConfigurationRequest;
        $this->validate($request->rules(), $request->messages());

        try {
            $this->account->update([
                'session_name' => $this->agent_name,
                'agent_enabled' => $this->agent_enabled,
                'ai_model_id' => $this->ai_model_id,
                'agent_prompt' => $this->agent_prompt ?: null,
                'trigger_words' => $this->trigger_words ? explode(',', $this->trigger_words) : null,
                'contextual_information' => $this->contextual_information ?: null,
                'ignore_words' => $this->ignore_words ? explode(',', $this->ignore_words) : null,
                'response_time' => $this->response_time,
                'stop_on_human_reply' => $this->stop_on_human_reply,
            ]);

            $this->dispatch('configuration-saved', [
                'type' => 'success',
                'message' => __('Configuration de l\'agent IA sauvegardée avec succès !'),
            ]);

            // Broadcast configuration to simulator
            $this->dispatch('config-updated', [
                'agent_name' => $this->agent_name,
                'enabled' => $this->agent_enabled,
                'model_id' => $this->ai_model_id,
                'prompt' => $this->agent_prompt,
                'trigger_words' => $this->trigger_words,
                'contextual_information' => $this->contextual_information,
                'ignore_words' => $this->ignore_words,
                'response_time' => $this->response_time,
            ]);

            $this->account->refresh();

        } catch (\Exception $e) {
            Log::error('AI Configuration Error', [
                'account_id' => $this->account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->dispatch('configuration-saved', [
                'type' => 'error',
                'message' => __('Erreur lors de la sauvegarde : :error', ['error' => $e->getMessage()]),
            ]);
        }
    }

    private function resetEnhancementState(): void
    {
        $this->hasEnhancedPrompt = false;
        $this->isPromptValidated = false;
        $this->enhancedPrompt = '';
        $this->originalPrompt = '';
    }

    private function loadCurrentConfiguration(): void
    {
        $this->agent_name = $this->account->session_name ?? '';
        $this->agent_enabled = (bool) $this->account->agent_enabled;
        $this->ai_model_id = $this->account->ai_model_id;
        $this->agent_prompt = $this->account->agent_prompt ?? '';
        $this->trigger_words = $this->account->trigger_words ? implode(', ', $this->account->trigger_words) : '';
        $this->contextual_information = $this->account->contextual_information ?? '';
        $this->ignore_words = $this->account->ignore_words ? implode(', ', $this->account->ignore_words) : '';
        $this->response_time = $this->account->response_time ?? 'random';
        $this->stop_on_human_reply = (bool) $this->account->stop_on_human_reply;
    }

    private function setDefaultModel(): void
    {
        if (! $this->ai_model_id && $this->availableModels->isNotEmpty()) {
            /** @var AiModel $defaultModel */
            $defaultModel = $this->availableModels->where('is_default', true)->first()
                ?? $this->availableModels->first();

            $this->ai_model_id = $defaultModel->id;
        }
    }

    private function validatePromptInRealTime(): void
    {
        $this->resetErrorBag('agent_prompt');

        if ($this->agent_prompt) {
            $promptRule = new PromptLengthRule;

            $promptRule->validate('agent_prompt', $this->agent_prompt, function (string $message) {
                $this->addError('agent_prompt', $message);
            });
        }

        // Update form validity based on ALL errors in the error bag
        $this->updateFormValidity();
    }

    private function updateFormValidity(): void
    {
        $this->isFormValid = $this->getErrorBag()->isEmpty();
    }

    public function render()
    {
        return view('livewire.customer.whats-app.ai-configuration-form');
    }
}
