<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\AI;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\WhatsAppAccount;
use App\Services\AI\OllamaServiceInterface;
use Illuminate\Support\Facades\Log;

final class WhatsAppAIProcessorService implements WhatsAppAIProcessorServiceInterface
{
    public function __construct(
        private readonly OllamaServiceInterface $ollamaService
    ) {}

    public function processIncomingMessage(string $sessionId, string $sessionName, array $messageData): array
    {
        // Find WhatsApp account
        $whatsappAccount = WhatsAppAccount::where('session_name', $sessionName)
            ->orWhere('user_id', $sessionId)
            ->first();

        if (! $whatsappAccount) {
            Log::warning('WhatsApp account not found for incoming message', [
                'session_id' => $sessionId,
                'session_name' => $sessionName,
            ]);

            return [
                'has_ai_response' => false,
                'message_stored' => false,
            ];
        }

        // Find or create conversation
        $conversation = $this->findOrCreateConversation($whatsappAccount, $messageData);

        // Store incoming message
        $message = $this->storeIncomingMessage($conversation, $messageData);

        // Check if AI is enabled for this conversation
        if (! $conversation->is_ai_enabled) {
            return [
                'has_ai_response' => false,
                'message_stored' => true,
                'message_id' => $message->id,
            ];
        }

        // Process with AI
        $aiResponse = $this->generateAIResponse($conversation, $messageData['body']);

        if ($aiResponse) {
            // Store AI response message
            $this->storeAIResponseMessage($conversation, $aiResponse);

            return [
                'has_ai_response' => true,
                'ai_response' => $aiResponse['response'],
                'message_stored' => true,
                'message_id' => $message->id,
            ];
        }

        return [
            'has_ai_response' => false,
            'message_stored' => true,
            'message_id' => $message->id,
        ];
    }

    private function findOrCreateConversation(WhatsAppAccount $account, array $messageData): Conversation
    {
        $chatId = $messageData['from'];
        $contactPhone = str_replace(['@c.us', '@g.us'], '', $chatId);

        return Conversation::firstOrCreate(
            [
                'whatsapp_account_id' => $account->id,
                'chat_id' => $chatId,
            ],
            [
                'contact_phone' => $contactPhone,
                'is_group' => $messageData['isGroup'],
                'last_message_at' => now(),
                'unread_count' => 1,
                'is_ai_enabled' => true, // Default to AI enabled
            ]
        );
    }

    private function storeIncomingMessage(Conversation $conversation, array $messageData): Message
    {
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'whatsapp_message_id' => $messageData['id'],
            'direction' => 'incoming',
            'content' => $messageData['body'],
            'message_type' => 'text',
            'is_ai_generated' => false,
        ]);

        // Update conversation
        $conversation->update([
            'last_message_at' => now(),
            'unread_count' => $conversation->unread_count + 1,
        ]);

        return $message;
    }

    private function generateAIResponse(Conversation $conversation, string $messageText): ?array
    {
        try {
            // Get conversation context (last 10 messages)
            $context = $this->getConversationContext($conversation);

            $prompt = $this->buildPrompt($messageText, $context);

            $response = $this->ollamaService->generateResponse($prompt);

            if ($response && ! empty($response['response'])) {
                return [
                    'response' => $response['response'],
                    'model' => $response['model'] ?? 'unknown',
                    'confidence' => $response['confidence'] ?? null,
                ];
            }

            return null;

        } catch (\Exception $e) {
            Log::error('AI response generation failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function storeAIResponseMessage(Conversation $conversation, array $aiResponse): Message
    {
        return Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outgoing',
            'content' => $aiResponse['response'],
            'message_type' => 'text',
            'is_ai_generated' => true,
            'ai_model_used' => $aiResponse['model'],
            'ai_confidence' => $aiResponse['confidence'],
            'processed_at' => now(),
        ]);
    }

    private function getConversationContext(Conversation $conversation): array
    {
        return $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->reverse()
            ->map(function (Message $message) {
                return [
                    'role' => $message->direction === 'incoming' ? 'user' : 'assistant',
                    'content' => $message->content,
                    'timestamp' => $message->created_at->toISOString(),
                ];
            })
            ->toArray();
    }

    private function buildPrompt(string $messageText, array $context): string
    {
        $contextText = '';

        if (! empty($context)) {
            $contextText = "Contexte de la conversation:\n";
            foreach ($context as $msg) {
                $role = $msg['role'] === 'user' ? 'Client' : 'Assistant';
                $contextText .= "{$role}: {$msg['content']}\n";
            }
            $contextText .= "\n";
        }

        return $contextText."Nouveau message du client: {$messageText}\n\nRéponds de manière professionnelle et utile:";
    }
}
