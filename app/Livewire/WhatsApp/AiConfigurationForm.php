<?php

declare(strict_types=1);

namespace App\Livewire\WhatsApp;

use App\Enums\ResponseTime;
use App\Http\Requests\WhatsApp\AiConfigurationRequest;
use App\Models\AiModel;
use App\Models\WhatsAppAccount;
use App\Contracts\PromptEnhancementInterface;
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
    private bool $isProgrammaticUpdate = false;

    public function mount(WhatsAppAccount $account): void
    {
        $this->account = $account;
        $this->loadCurrentConfiguration();
        $this->setDefaultModel();
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

    public function updatedAgentEnabled(): void
    {
        $this->dispatch('ai-status-changed', enabled: $this->agent_enabled);
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

        $this->dispatch('config-changed-live', [
            'agent_prompt' => $this->agent_prompt,
        ]);
    }

    public function updatedContextualInformation(): void
    {
        $this->dispatch('config-changed-live', [
            'contextual_information' => $this->contextual_information,
        ]);
    }

    public function updatedIgnoreWords(): void
    {
        $this->dispatch('config-changed-live', [
            'ignore_words' => $this->ignore_words,
        ]);
    }

    public function updatedResponseTime(): void
    {
        $this->dispatch('config-changed-live', [
            'response_time' => $this->response_time,
        ]);
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
            $this->enhancedPrompt = $enhancementService->enhancePrompt($this->account, $this->agent_prompt);
            
            // Set flag to prevent updatedAgentPrompt interference
            $this->isProgrammaticUpdate = true;
            
            // Replace the original prompt with the enhanced one immediately
            $this->agent_prompt = $this->enhancedPrompt;
            $this->hasEnhancedPrompt = true;
            
            // Reset the flag
            $this->isProgrammaticUpdate = false;

            Log::info('✨ Prompt amélioré avec succès', [
                'account_id' => $this->account->id,
                'original_length' => strlen($this->originalPrompt),
                'enhanced_length' => strlen($this->enhancedPrompt),
            ]);

            $this->dispatch('config-changed-live', [
                'agent_prompt' => $this->agent_prompt,
            ]);

        } catch (\Exception $e) {
            // Reset the flag in case of exception
            $this->isProgrammaticUpdate = false;
            
            Log::error('❌ Erreur amélioration prompt', [
                'account_id' => $this->account->id,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('Erreur lors de l\'amélioration : :error', ['error' => $e->getMessage()]),
            ]);
        }
        
        // Important: Set isEnhancing to false AFTER hasEnhancedPrompt is set
        $this->isEnhancing = false;
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
        $this->validate([
            'agent_name' => 'required|string|max:100',
            'agent_enabled' => 'boolean',
            'ai_model_id' => $this->agent_enabled ? 'required|exists:ai_models,id' : 'nullable|exists:ai_models,id',
            'agent_prompt' => $this->agent_enabled ? 'required|string|max:2000' : 'nullable|string|max:2000',
            'trigger_words' => 'nullable|string|max:500',
            'contextual_information' => 'nullable|string|max:5000',
            'ignore_words' => 'nullable|string|max:500',
            'response_time' => 'required|string|in:instant,fast,random,slow',
            'stop_on_human_reply' => 'boolean',
        ]);

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

    public function render()
    {
        return view('livewire.whats-app.ai-configuration-form');
    }
}
