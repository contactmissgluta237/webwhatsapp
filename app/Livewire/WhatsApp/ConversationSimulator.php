<?php

declare(strict_types=1);

namespace App\Livewire\WhatsApp;

use App\Enums\ResponseTime;
use App\Models\AiModel;
use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\AI\WhatsAppAIService;
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

    // Configuration en temps réel (pour la simulation)
    public string $currentPrompt = '';
    public string $currentContextualInfo = '';
    public ?int $currentModelId = null;
    public string $currentResponseTime = '';

    // Configuration
    public int $maxMessages = 10;

    public function mount(WhatsAppAccount $account): void
    {
        $this->account = $account;
        $this->loadCurrentConfiguration();
    }

    private function loadCurrentConfiguration(): void
    {
        $this->currentPrompt = $this->account->agent_prompt ?? 'Tu es un assistant WhatsApp utile et professionnel.';
        $this->currentContextualInfo = $this->account->contextual_information ?? '';
        $this->currentModelId = $this->account->getEffectiveAiModelId();
        $this->currentResponseTime = $this->account->response_time ?? 'random';

        Log::info('🔄 Configuration simulateur chargée', [
            'account_id' => $this->account->id,
            'current_prompt_length' => strlen($this->currentPrompt),
            'current_contextual_info_length' => strlen($this->currentContextualInfo),
            'current_model_id' => $this->currentModelId,
            'current_response_time' => $this->currentResponseTime,
        ]);
    }

    #[On('config-updated')]
    public function configUpdated(): void
    {
        Log::info('🔔 Event config-updated reçu');
        $this->account->refresh();
        $this->loadCurrentConfiguration();
        $this->addMessage('system', '⚙️ Configuration mise à jour. Nouvelle conversation avec les paramètres actuels.');
    }

    #[On('config-changed-live')]
    public function configChangedLive(array $data): void
    {
        Log::info('🔔 Event config-changed-live reçu', $data);

        // Mettre à jour la configuration en temps réel pour la simulation
        if (isset($data['agent_prompt'])) {
            $this->currentPrompt = $data['agent_prompt'] ?: 'Tu es un assistant WhatsApp utile et professionnel.';
            Log::info('📝 Prompt mis à jour en temps réel', [
                'new_prompt_length' => strlen($this->currentPrompt),
                'new_prompt_preview' => substr($this->currentPrompt, 0, 100) . '...',
            ]);
        }

        if (isset($data['ai_model_id'])) {
            $this->currentModelId = $data['ai_model_id'] ?: $this->account->getEffectiveAiModelId();
            Log::info('🤖 Modèle IA mis à jour en temps réel', [
                'new_model_id' => $this->currentModelId,
            ]);
        }

        if (isset($data['response_time'])) {
            $this->currentResponseTime = $data['response_time'] ?: 'random';
            Log::info('⏰ Temps de réponse mis à jour en temps réel', [
                'new_response_time' => $this->currentResponseTime,
            ]);
        }

        if (isset($data['contextual_information'])) {
            $this->currentContextualInfo = $data['contextual_information'] ?: '';
            Log::info('📋 Informations contextuelles mises à jour en temps réel', [
                'new_contextual_info_length' => strlen($this->currentContextualInfo),
            ]);
        }

        Log::info('✅ Configuration simulateur mise à jour en temps réel');
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
            $responseTime = ResponseTime::from($this->currentResponseTime);
            $delayInSeconds = $responseTime->getDelay();

            Log::info('⏰ Configuration délai de réponse', [
                'current_response_time_config' => $this->currentResponseTime,
                'response_time_enum' => $responseTime->value,
                'delay_seconds' => $delayInSeconds,
                'delay_ms' => $delayInSeconds * 1000,
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
        Log::info('🤖 Début simulateAiResponse() - Service centralisé', [
            'userMessage' => $userMessage,
            'account_id' => $this->account->id,
        ]);

        try {
            Log::info('🚀 Appel service IA centralisé');

            // Construire le contexte conversationnel
            $conversationContext = $this->buildConversationContext();

            // Utiliser le service centralisé pour la génération
            $whatsappAIService = app(WhatsAppAIService::class);
            $aiResponse = $whatsappAIService->generateResponse(
                $this->account,
                $userMessage,
                $conversationContext
            );

            if ($aiResponse && !empty($aiResponse['response'])) {
                Log::info('✅ Réponse IA générée via service centralisé', [
                    'response_length' => strlen($aiResponse['response']),
                    'response_preview' => substr($aiResponse['response'], 0, 100) . (strlen($aiResponse['response']) > 100 ? '...' : ''),
                ]);

                $this->addMessage('ai', $aiResponse['response']);
                $this->dispatch('message-added');

                Log::info('📡 Event message-added dispatché après réponse IA');
            } else {
                throw new \Exception('Aucune réponse générée par le service IA');
            }

        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de la simulation IA', [
                'error' => $e->getMessage(),
                'user_message' => $userMessage,
                'account_id' => $this->account->id,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addMessage('ai', '❌ Erreur de simulation : '.$e->getMessage());
            throw $e;
        }
    }

    private function buildConversationContext(): array
    {
        $context = [];

        // Prendre les 10 derniers messages pour le contexte
        $recentMessages = array_slice($this->simulationMessages, -10);

        foreach ($recentMessages as $message) {
            $context[] = [
                'role' => $message['type'] === 'user' ? 'user' : 'assistant',
                'content' => $message['content'],
            ];
        }

        return $context;
    }

    public function render()
    {
        return view('livewire.whats-app.conversation-simulator');
    }
}
