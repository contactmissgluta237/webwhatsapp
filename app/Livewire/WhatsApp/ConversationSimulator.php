<?php

declare(strict_types=1);

namespace App\Livewire\WhatsApp;

use App\Contracts\WhatsApp\WhatsAppMessageOrchestratorInterface;
use App\DTOs\WhatsApp\WhatsAppAccountMetadataDTO;
use App\Models\WhatsAppAccount;
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
    public bool $showTyping = false;
    public bool $isProcessing = false;

    // Dynamic configuration
    public string $currentPrompt = '';
    public string $currentContextualInfo = '';
    public ?int $currentModelId = null;
    public string $currentResponseTime = '';

    // Constants
    private const MAX_EXCHANGES = 10;
    private const MAX_CONTEXT_MESSAGES = 10;
    private const DEFAULT_PROMPT = 'You are a helpful and professional WhatsApp assistant. Never provide false information like fake contact details (addresses, phones, emails, websites). If you don\'t know specific information, say so honestly.';

    public function mount(WhatsAppAccount $account): void
    {
        $this->account = $account;
        $this->loadCurrentConfiguration();
    }

    private function loadCurrentConfiguration(): void
    {
        $this->currentPrompt = $this->account->agent_prompt ?? self::DEFAULT_PROMPT;
        $this->currentContextualInfo = $this->account->contextual_information ?? '';
        $this->currentModelId = $this->account->getEffectiveAiModelId();
        $this->currentResponseTime = $this->account->response_time ?? 'random';
    }

    #[On('config-updated')]
    public function configUpdated(): void
    {
        $this->account->refresh();
        $this->loadCurrentConfiguration();
        $this->addMessage('system', '⚙️ Configuration updated. New conversation with current parameters.');
    }

    #[On('config-changed-live')]
    public function configChangedLive(array $data): void
    {
        if (isset($data['agent_prompt'])) {
            $this->currentPrompt = $data['agent_prompt'] ?: self::DEFAULT_PROMPT;
        }

        if (isset($data['ai_model_id'])) {
            $this->currentModelId = $data['ai_model_id'] ?: $this->account->getEffectiveAiModelId();
        }

        if (isset($data['response_time'])) {
            $this->currentResponseTime = $data['response_time'] ?: 'random';
        }

        if (isset($data['contextual_information'])) {
            $this->currentContextualInfo = $data['contextual_information'] ?: '';
        }
    }

    public function clearConversation(): void
    {
        $this->simulationMessages = [];
        $this->resetState();
        $this->dispatch('conversation-cleared');
    }

    public function sendMessage(): void
    {
        $userMessage = trim($this->newMessage);

        if (empty($userMessage)) {
            return;
        }

        // Check exchange limit
        if (count($this->simulationMessages) >= self::MAX_EXCHANGES * 2) {
            $this->addMessage('system', '⚠️ Conversation limit reached ('.self::MAX_EXCHANGES.' exchanges maximum)');

            return;
        }

        $this->newMessage = '';

        try {
            // Get context before adding new message
            $conversationContext = array_slice($this->simulationMessages, -self::MAX_CONTEXT_MESSAGES);

            // Add user message
            $this->addMessage('user', $userMessage);
            $this->dispatch('message-added');

            // Schedule AI response
            $this->dispatch('schedule-ai-response', [
                'userMessage' => $userMessage,
                'conversationContext' => $conversationContext,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in sendMessage', ['error' => $e->getMessage()]);
            $this->addMessage('ai', '❌ Simulation error: '.$e->getMessage());
        }
    }

    public function startTyping(): void
    {
        $this->showTyping = true;
        $this->isProcessing = true;
    }

    public function generateAiResponse(string $userMessage, array $conversationContext = []): void
    {
        try {
            $accountMetadata = $this->createAccountMetadata();
            $orchestrator = app(WhatsAppMessageOrchestratorInterface::class);

            $response = $orchestrator->processSimulatedMessage(
                $accountMetadata,
                $userMessage,
                $conversationContext
            );

            if ($response->hasAiResponse) {
                // Calculate timings (minimum 1 second each for better UX)
                $waitTimeSeconds = max(1, ceil($response->waitTimeSeconds / 10));
                $typingDurationSeconds = max(1, ceil($response->typingDurationSeconds / 10));

                $this->dispatch('simulate-response-timing', [
                    'waitTimeSeconds' => $waitTimeSeconds,
                    'typingDurationSeconds' => $typingDurationSeconds,
                    'responseMessage' => $response->aiResponse,
                ]);

            } else {
                throw new \Exception('No response generated by orchestrator');
            }

        } catch (\Exception $e) {
            Log::error('AI generation error', [
                'error' => $e->getMessage(),
                'user_message' => $userMessage,
                'account_id' => $this->account->id,
            ]);

            $this->addMessage('ai', '❌ Simulation error: '.$e->getMessage());
        }
    }

    public function addAiResponse(string $responseMessage): void
    {
        $this->addMessage('ai', $responseMessage);
        $this->resetState();
        $this->dispatch('message-added');
    }

    private function createAccountMetadata(): WhatsAppAccountMetadataDTO
    {
        return new WhatsAppAccountMetadataDTO(
            sessionId: 'simulator_'.$this->account->id,
            sessionName: 'simulator_'.$this->account->session_name,
            accountId: $this->account->id,
            agentEnabled: true,
            agentPrompt: $this->currentPrompt,
            aiModelId: $this->currentModelId,
            responseTime: $this->currentResponseTime,
            contextualInformation: $this->currentContextualInfo,
            settings: []
        );
    }

    private function addMessage(string $type, string $content): void
    {
        $this->simulationMessages[] = [
            'type' => $type,
            'content' => $content,
            'time' => Carbon::now()->format('H:i'),
            'timestamp' => time(),
        ];
    }

    private function resetState(): void
    {
        $this->showTyping = false;
        $this->isProcessing = false;
    }

    public function render()
    {
        return view('livewire.whats-app.conversation-simulator');
    }
}
