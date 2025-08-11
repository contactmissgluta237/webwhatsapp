<?php

declare(strict_types=1);

namespace App\Livewire\WhatsApp;

use App\Enums\ResponseTime;
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
    public string $response_time = 'random';

    public function mount(WhatsAppAccount $account): void
    {
        $this->account = $account;
        $this->loadCurrentConfiguration();
        $this->setDefaultModel();
    }

    public function rules(): array
    {
        return [
            'agent_name' => 'required|string|max:100',
            'agent_enabled' => 'boolean',
            'ai_model_id' => 'nullable|exists:ai_models,id',
            'agent_prompt' => 'nullable|string|max:2000',
            'trigger_words' => 'nullable|string|max:500',
            'response_time' => 'required|string|in:instant,fast,random,slow',
        ];
    }

    public function messages(): array
    {
        return [
            'agent_name.required' => __('Le nom de l\'agent est obligatoire.'),
            'agent_name.max' => __('Le nom de l\'agent ne peut pas dépasser 100 caractères.'),
            'ai_model_id.exists' => __('Le modèle sélectionné est invalide.'),
            'agent_prompt.max' => __('Le prompt ne peut pas dépasser 2000 caractères.'),
            'trigger_words.max' => __('Les mots déclencheurs ne peuvent pas dépasser 500 caractères.'),
            'response_time.in' => __('Le délai de réponse sélectionné est invalide.'),
        ];
    }

    #[Computed]
    public function availableModels(): Collection
    {
        return AiModel::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function selectedModel(): ?AiModel
    {
        if (!$this->ai_model_id) {
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
    }

    public function save(): void
    {
        $this->validate();

        try {
            $this->account->update([
                'session_name' => $this->agent_name, // Mise à jour du nom
                'agent_enabled' => $this->agent_enabled,
                'ai_model_id' => $this->ai_model_id,
                'agent_prompt' => $this->agent_prompt ?: null,
                'trigger_words' => $this->trigger_words ?: null,
                'response_time' => $this->response_time,
            ]);

            $this->dispatch('configuration-saved', [
                'type' => 'success',
                'message' => __('Configuration de l\'agent IA sauvegardée avec succès !')
            ]);

            // Broadcast configuration to simulator
            $this->dispatch('config-updated', [
                'agent_name' => $this->agent_name,
                'enabled' => $this->agent_enabled,
                'model_id' => $this->ai_model_id,
                'prompt' => $this->agent_prompt,
                'trigger_words' => $this->trigger_words,
                'response_time' => $this->response_time,
            ]);

            $this->account->refresh();

        } catch (\Exception $e) {
            Log::error('AI Configuration Error', [
                'account_id' => $this->account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->dispatch('configuration-saved', [
                'type' => 'error',
                'message' => __('Erreur lors de la sauvegarde : :error', ['error' => $e->getMessage()])
            ]);
        }
    }

    private function loadCurrentConfiguration(): void
    {
        $this->agent_name = $this->account->session_name ?? '';
        $this->agent_enabled = (bool) $this->account->agent_enabled;
        $this->ai_model_id = $this->account->ai_model_id;
        $this->agent_prompt = $this->account->agent_prompt ?? '';
        $this->trigger_words = $this->account->trigger_words ?? '';
        $this->response_time = $this->account->response_time ?? 'random';
    }

    private function setDefaultModel(): void
    {
        if (!$this->ai_model_id && $this->availableModels->isNotEmpty()) {
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
