<?php

declare(strict_types=1);

namespace App\Livewire\WhatsApp;

use App\Enums\ResponseTime;
use App\Http\Requests\WhatsApp\AiConfigurationRequest;
use App\Models\AiModel;
use App\Models\WhatsAppAccount;
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
    
    // File upload
    public $contextDocuments;

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

    public function save(AiConfigurationRequest $request): void
    {
        $validatedData = $request->validated();

        try {
            $this->account->update([
                'session_name' => $validatedData['agent_name'],
                'agent_enabled' => $validatedData['agent_enabled'],
                'ai_model_id' => $validatedData['ai_model_id'],
                'agent_prompt' => $validatedData['agent_prompt'] ?: null,
                'trigger_words' => $validatedData['trigger_words'] ?: null,
                'contextual_information' => $validatedData['contextual_information'] ?: null,
                'ignore_words' => $validatedData['ignore_words'] ?: null,
                'response_time' => $validatedData['response_time'],
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
