<?php

declare(strict_types=1);

namespace App\Livewire\WhatsApp;

use App\Enums\ResponseTime;
use App\Http\Requests\WhatsApp\ConfigureAiAgentRequest;
use App\Models\AiModel;
use App\Models\WhatsAppAccount;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Livewire\Component;

final class ConfigureAiAgent extends Component
{
    public WhatsAppAccount $account;
    public Collection $availableModels;

    // Form properties
    public bool $ai_agent_enabled = false;
    public ?int $ai_model_id = null;
    public string $ai_prompt = '';
    public string $ai_trigger_words = '';
    public string $ai_response_time = 'random';

    // UI state
    public bool $showSimulation = false;
    public string $simulationMessage = '';
    public string $simulationResponse = '';
    public bool $isSimulating = false;

    public function mount(WhatsAppAccount $account): void
    {
        $this->account = $account;
        $this->availableModels = AiModel::getActiveModels();

        // Load current configuration
        $this->ai_agent_enabled = (bool) $account->agent_enabled;
        $this->ai_model_id = $account->ai_model_id;
        $this->ai_prompt = $account->agent_prompt ?? '';
        $this->ai_trigger_words = $account->trigger_words ?? '';
        $this->ai_response_time = $account->response_time ?? 'random';

        // Set default model if none selected
        if (! $this->ai_model_id && $this->availableModels->isNotEmpty()) {
            /** @var AiModel $defaultModel */
            $defaultModel = $this->availableModels->where('is_default', true)->first()
                ?? $this->availableModels->first();
            $this->ai_model_id = $defaultModel->id;
        }
    }

    protected function customRequest(): FormRequest
    {
        return new ConfigureAiAgentRequest;
    }

    public function rules(): array
    {
        // @phpstan-ignore-next-line
        return $this->customRequest()->rules();
    }

    public function messages(): array
    {
        return $this->customRequest()->messages();
    }

    public function updatedAiAgentEnabled(): void
    {
        if (! $this->ai_agent_enabled) {
            $this->showSimulation = false;
        }
    }

    public function save(): void
    {
        $this->validate();

        try {
            if ($this->ai_agent_enabled) {
                $this->account->enableAiAgent(
                    $this->ai_model_id,
                    $this->ai_prompt ?: null,
                    $this->ai_trigger_words ?: null,
                    $this->ai_response_time
                );
            } else {
                $this->account->disableAiAgent();
            }

            $this->dispatch('ai-agent-configured', [
                'type' => 'success',
                'message' => 'Configuration de l\'agent AI sauvegardée avec succès !',
            ]);

        } catch (\Exception $e) {
            $this->dispatch('ai-agent-configured', [
                'type' => 'error',
                'message' => 'Erreur lors de la sauvegarde : '.$e->getMessage(),
            ]);
        }
    }

    public function toggleSimulation(): void
    {
        $this->showSimulation = ! $this->showSimulation;

        if ($this->showSimulation) {
            $this->simulationMessage = '';
            $this->simulationResponse = '';
        }
    }

    public function simulateResponse(): void
    {
        if (empty($this->simulationMessage)) {
            return;
        }

        $this->isSimulating = true;

        try {
            /** @var AiModel $selectedModel */
            $selectedModel = $this->availableModels->find($this->ai_model_id);

            if (! $selectedModel) {
                $this->simulationResponse = 'Erreur : Modèle non trouvé.';

                return;
            }

            // Check if trigger words match
            if (! empty($this->ai_trigger_words)) {
                $triggerWords = array_map('trim', explode(',', strtolower($this->ai_trigger_words)));
                $messageWords = str_word_count(strtolower($this->simulationMessage), 1);
                $hasMatch = false;

                foreach ($triggerWords as $trigger) {
                    if (in_array($trigger, $messageWords, true)) {
                        $hasMatch = true;
                        break;
                    }
                }

                if (! $hasMatch) {
                    $this->simulationResponse = '⚠️ Message ignoré - Aucun mot déclencheur détecté.';

                    return;
                }
            }

            // Simulate AI response based on model and prompt
            $prompt = $this->ai_prompt ?: 'Tu es un assistant WhatsApp utile et professionnel. Tu ne donnes jamais de fausses informations comme des coordonnées inventées (adresses, téléphones, emails, sites web). Si tu ne connais pas une information précise, tu le dis honnêtement.';
            $responseTime = ResponseTime::from($this->ai_response_time);

            $this->simulationResponse = "✅ Réponse simulée du modèle {$selectedModel->name}:\n\n";
            $this->simulationResponse .= "Prompt utilisé: \"{$prompt}\"\n";
            $this->simulationResponse .= "Message reçu: \"{$this->simulationMessage}\"\n\n";

            if ($this->ai_response_time === 'random') {
                $delayExample1 = $responseTime->getDelay();
                $delayExample2 = $responseTime->getDelay();
                $delayExample3 = $responseTime->getDelay();
                $this->simulationResponse .= "Délai de réponse: {$responseTime->label}\n";
                $this->simulationResponse .= "Exemples: {$delayExample1}s, {$delayExample2}s, {$delayExample3}s (varie à chaque message)\n";
            } else {
                $this->simulationResponse .= "Délai de réponse: {$responseTime->label} ({$responseTime->getDelay()}s fixe)\n";
            }

            $this->simulationResponse .= 'Coût estimé: '.number_format($selectedModel->getEstimatedCostFor(150), 6).' USD';

        } finally {
            $this->isSimulating = false;
        }
    }

    public function getResponseTimeOptions(): array
    {
        return collect(ResponseTime::cases())->map(function ($case) {
            return [
                'value' => $case->value,
                'label' => $case->label,
                'description' => $case->getDescription(),
            ];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.whats-app.configure-ai-agent', [
            'responseTimeOptions' => $this->getResponseTimeOptions(),
        ]);
    }
}
