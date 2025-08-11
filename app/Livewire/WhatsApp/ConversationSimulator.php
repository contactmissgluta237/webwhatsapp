<?php

declare(strict_types=1);

namespace App\Livewire\WhatsApp;

use App\Enums\ResponseTime;
use App\Models\AiModel;
use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\AI\AiResponseSimulator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

final class ConversationSimulator extends Component
{
    public WhatsAppAccount $account;

    // Simulation state
    public array $simulationMessages = [];
    public string $newMessage = '';
    public bool $isProcessing = false;

    // Current configuration (synced from form)
    public array $currentConfig = [];

    public function mount(WhatsAppAccount $account): void
    {
        $this->account = $account;
        $this->loadCurrentConfig();
    }

    #[On('config-updated')]
    public function onConfigUpdated(array $config): void
    {
        $this->currentConfig = $config;
    }

    #[On('ai-status-changed')]
    public function onAiStatusChanged(bool $enabled): void
    {
        $this->currentConfig['enabled'] = $enabled;
    }

    #[On('model-changed')]
    public function onModelChanged(?int $modelId): void
    {
        $this->currentConfig['model_id'] = $modelId;
    }

    public function sendMessage(): void
    {
        if (empty(trim($this->newMessage))) {
            return;
        }

        $userMessage = trim($this->newMessage);
        $this->newMessage = '';
        $this->isProcessing = true;

        try {
            // Add user message
            $this->addMessage('user', $userMessage);
            $this->dispatch('message-added');

            // Always simulate AI response (for testing purposes)
            $this->simulateAiResponse($userMessage);

        } catch (\Exception $e) {
            Log::error('Simulation Error', [
                'account_id' => $this->account->id,
                'message' => $userMessage,
                'error' => $e->getMessage()
            ]);

            $this->addMessage('ai', '❌ Erreur de simulation : ' . $e->getMessage());
        } finally {
            $this->isProcessing = false;
        }
    }

    public function clearSimulation(): void
    {
        $this->simulationMessages = [];
        $this->newMessage = '';
    }

    public function generateSampleConversation(): void
    {
        $this->clearSimulation();

        $sampleMessages = [
            'Bonjour, j\'ai besoin d\'aide',
            'Quels sont vos horaires d\'ouverture ?',
            'Pouvez-vous me donner plus d\'informations sur vos services ?',
            'Merci pour les informations'
        ];

        foreach ($sampleMessages as $message) {
            $this->addMessage('user', $message);
            $this->simulateAiResponse($message, false);
        }

        $this->dispatch('message-added');
    }

    private function loadCurrentConfig(): void
    {
        $this->currentConfig = [
            'enabled' => (bool) $this->account->ai_agent_enabled,
            'model_id' => $this->account->ai_model_id,
            'prompt' => $this->account->ai_prompt ?? 'Tu es un assistant WhatsApp utile et professionnel.',
            'trigger_words' => $this->account->ai_trigger_words ?? '',
            'response_time' => $this->account->ai_response_time ?? 'random',
        ];
    }

    private function addMessage(string $type, string $content): void
    {
        $this->simulationMessages[] = [
            'type' => $type,
            'content' => $content,
            'time' => Carbon::now()->format('H:i'),
            'timestamp' => time()
        ];
    }

    private function simulateAiResponse(string $userMessage, bool $checkTriggers = true): void
    {
        // Check trigger words if configured and checkTriggers is true
        if ($checkTriggers && !$this->shouldTriggerResponse($userMessage)) {
            $this->addMessage('ai', '⚠️ Message ignoré - Aucun mot déclencheur détecté dans la configuration actuelle');
            return;
        }

        $modelId = $this->currentConfig['model_id'] ?? $this->account->ai_model_id;

        if (!$modelId) {
            $this->addMessage('ai', '❌ Aucun modèle sélectionné pour la simulation');
            return;
        }

        $model = AiModel::find($modelId);

        if (!$model) {
            $this->addMessage('ai', '❌ Modèle non trouvé');
            return;
        }

        // Get current configuration
        $prompt = $this->currentConfig['prompt'] ?? 'Tu es un assistant WhatsApp utile et professionnel.';
        $responseTime = ResponseTime::from($this->currentConfig['response_time'] ?? 'random');

        // Simulate response using service
        $simulator = app(AiResponseSimulator::class);
        $response = $simulator->simulate($model, $prompt, $userMessage, $responseTime);

        $this->addMessage('ai', $response);
    }

    private function shouldTriggerResponse(string $message): bool
    {
        $triggerWords = $this->currentConfig['trigger_words'] ?? '';

        if (empty(trim($triggerWords))) {
            return true; // No trigger words = respond to all
        }

        $triggers = array_map('trim', explode(',', strtolower($triggerWords)));
        $messageWords = str_word_count(strtolower($message), 1);

        foreach ($triggers as $trigger) {
            if (in_array($trigger, $messageWords, true)) {
                return true;
            }
        }

        return false;
    }

    public function render()
    {
        return view('livewire.whats-app.conversation-simulator');
    }
}
