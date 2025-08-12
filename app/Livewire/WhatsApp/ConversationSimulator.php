<?php

declare(strict_types=1);

namespace App\Livewire\WhatsApp;

use App\Enums\ResponseTime;
use App\Models\AiModel;
use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\AI\AiResponseSimulator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

final class ConversationSimulator extends Component
{
    public WhatsAppAccount $account;

    // Simulation state
    public array $simulationMessages = [];
    public string $newMessage = '';
    public bool $isProcessing = false;
    public bool $showTyping = false;

    // Configuration
    public int $maxMessages = 10;

    public function mount(WhatsAppAccount $account): void
    {
        $this->account = $account;
    }

    #[On('config-updated')]
    public function configUpdated(): void
    {
        $this->account->refresh();
        $this->addMessage('system', '⚙️ Configuration mise à jour. Nouvelle conversation avec les paramètres actuels.');
    }

    public function clearConversation(): void
    {
        $this->simulationMessages = [];
        $this->showTyping = false;
        $this->isProcessing = false;
        $this->dispatch('conversation-cleared');
    }

    public function sendMessage(): void
    {
        Log::info('🚀 Début sendMessage()', [
            'newMessage' => $this->newMessage,
            'account_id' => $this->account->id,
        ]);

        if (empty(trim($this->newMessage))) {
            Log::warning('❌ Message vide, abandon');
            return;
        }

        // Vérifier la limite de messages
        if (count($this->simulationMessages) >= $this->maxMessages * 2) {
            Log::warning('⚠️ Limite de messages atteinte');
            $this->addMessage('system', '⚠️ Limite de conversation atteinte (10 échanges maximum)');
            return;
        }

        $userMessage = trim($this->newMessage);
        $this->newMessage = '';

        try {
            // Ajouter le message utilisateur
            $this->addMessage('user', $userMessage);
            $this->dispatch('message-added');

            // Calculer délai de réponse selon configuration
            $responseTime = ResponseTime::from($this->account->response_time ?? 'random');
            $delayInSeconds = $responseTime->getDelay();

            Log::info('⏰ Configuration délai de réponse', [
                'response_time_config' => $this->account->response_time,
                'delay_seconds' => $delayInSeconds,
                'user_message' => $userMessage,
            ]);

            // Programmer la réponse avec le bon délai
            $this->dispatch('schedule-ai-response', [
                'userMessage' => $userMessage,
                'delayMs' => $delayInSeconds * 1000,
            ]);

            Log::info('✅ Réponse IA programmée avec succès');

        } catch (\Exception $e) {
            Log::error('❌ Erreur dans sendMessage()', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $this->addMessage('ai', '❌ Erreur de simulation : '.$e->getMessage());
        }
    }

    public function startTyping(): void
    {
        Log::info('💭 Démarrage du typing');
        $this->showTyping = true;
        $this->isProcessing = true;
    }

    public function stopTyping(): void
    {
        Log::info('🛑 Arrêt du typing');
        $this->showTyping = false;
    }

    public function processAiResponse(?string $userMessage = null): void
    {
        Log::info('🤖 Génération de la réponse IA', [
            'userMessage' => $userMessage,
            'userMessage_type' => gettype($userMessage),
            'userMessage_length' => $userMessage ? strlen($userMessage) : 0,
        ]);

        if (!$userMessage) {
            Log::error('❌ userMessage est null dans processAiResponse');
            $this->addMessage('ai', '❌ Erreur : message utilisateur manquant');
            $this->showTyping = false;
            $this->isProcessing = false;
            return;
        }

        try {
            $this->simulateAiResponse($userMessage);
            Log::info('✅ Réponse IA générée avec succès');
        } catch (\Exception $e) {
            Log::error('❌ Erreur dans processAiResponse()', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $this->addMessage('ai', '❌ Erreur de simulation : '.$e->getMessage());
        } finally {
            $this->showTyping = false;
            $this->isProcessing = false;
        }
    }

    private function addMessage(string $type, string $content): void
    {
        $this->simulationMessages[] = [
            'type' => $type,
            'content' => $content,
            'time' => Carbon::now()->format('H:i'),
            'timestamp' => time(),
        ];

        Log::info('✅ Message ajouté', [
            'type' => $type,
            'total_messages' => count($this->simulationMessages),
        ]);
    }

    private function simulateAiResponse(string $userMessage): void
    {
        $modelId = $this->account->getEffectiveAiModelId();

        if (!$modelId) {
            $this->addMessage('ai', '❌ Aucun modèle IA configuré');
            return;
        }

        $model = AiModel::find($modelId);
        if (!$model) {
            $this->addMessage('ai', '❌ Modèle IA non trouvé');
            return;
        }

        $prompt = $this->account->agent_prompt ?? 'Tu es un assistant WhatsApp utile et professionnel.';
        $responseTime = ResponseTime::from($this->account->response_time ?? 'random');

        try {
            $simulator = app(AiResponseSimulator::class);
            $response = $simulator->simulate($model, $prompt, $userMessage, $responseTime);

            $this->addMessage('ai', $response);
            $this->dispatch('message-added');

        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de la simulation IA', [
                'model' => $model->name,
                'error' => $e->getMessage(),
            ]);

            $this->addMessage('ai', '❌ Erreur de simulation : '.$e->getMessage());
            throw $e;
        }
    }

    public function render()
    {
        return view('livewire.whats-app.conversation-simulator');
    }
}
