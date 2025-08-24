<?php

declare(strict_types=1);

namespace App\Livewire\Customer\WhatsApp;

use App\Contracts\WhatsApp\WhatsAppMessageOrchestratorInterface;
use App\Enums\SimulatorMessageType;
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
        $this->currentPrompt = $this->account->agent_prompt ?? 'Tu es un assistant WhatsApp utile et professionnel. Tu ne donnes jamais de fausses informations comme des coordonnées inventées (adresses, téléphones, emails, sites web). Si tu ne connais pas une information précise, tu le dis honnêtement.';
        $this->currentContextualInfo = $this->account->contextual_information ?? '';
        $this->currentModelId = $this->account->ai_model_id;
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
        $this->addMessage(SimulatorMessageType::SYSTEM()->value, '⚙️ Configuration mise à jour. Nouvelle conversation avec les paramètres actuels.');
    }

    #[On('config-changed-live')]
    public function configChangedLive(array $data): void
    {
        Log::info('🔔 Event config-changed-live reçu', $data);

        // Mettre à jour la configuration en temps réel pour la simulation
        if (isset($data['agent_prompt'])) {
            $this->currentPrompt = $data['agent_prompt'] ?: 'Tu es un assistant WhatsApp utile et professionnel. Tu ne donnes jamais de fausses informations comme des coordonnées inventées (adresses, téléphones, emails, sites web). Si tu ne connais pas une information précise, tu le dis honnêtement.';
            Log::info('📝 Prompt mis à jour en temps réel', [
                'new_prompt_length' => strlen($this->currentPrompt),
                'new_prompt_preview' => substr($this->currentPrompt, 0, 100).'...',
            ]);
        }

        if (isset($data['ai_model_id'])) {
            $this->currentModelId = $data['ai_model_id'] ?: $this->account->ai_model_id;
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
            $this->addMessage(SimulatorMessageType::SYSTEM()->value, '⚠️ Limite de conversation atteinte (10 échanges maximum)');

            return;
        }

        $userMessage = trim($this->newMessage);
        $this->newMessage = '';

        try {
            // IMPORTANT: Construire le contexte AVANT d'ajouter le nouveau message
            // Prendre les 10 derniers messages pour le contexte
            $conversationContext = array_slice($this->simulationMessages, -10);

            // Ajouter le message utilisateur
            $this->addMessage(SimulatorMessageType::USER()->value, $userMessage);
            $this->dispatch('message-added');

            $this->dispatch('schedule-ai-response', [
                'userMessage' => $userMessage,
                'conversationContext' => $conversationContext, // Passer le contexte
            ]);

            Log::info('✅ Réponse IA programmée avec succès');

        } catch (\Exception $e) {
            Log::error('❌ Erreur dans sendMessage()', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $this->addMessage(SimulatorMessageType::ASSISTANT()->value, '❌ Erreur de simulation : '.$e->getMessage());
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

    public function processAiResponse(?string $userMessage = null, ?array $conversationContext = null): void
    {
        Log::info('🤖 Génération de la réponse IA', [
            'userMessage' => $userMessage,
            'userMessage_type' => gettype($userMessage),
            'userMessage_length' => $userMessage ? strlen($userMessage) : 0,
            'context_provided' => $conversationContext !== null,
            'context_count' => $conversationContext ? count($conversationContext) : 0,
        ]);

        if (! $userMessage) {
            Log::error('❌ userMessage est null dans processAiResponse');
            $this->addMessage(SimulatorMessageType::ASSISTANT()->value, '❌ Erreur : message utilisateur manquant');
            $this->showTyping = false;
            $this->isProcessing = false;

            return;
        }

        try {
            $this->simulateAiResponse($userMessage, $conversationContext ?? []);
            Log::info('✅ Réponse IA générée avec succès');
        } catch (\Exception $e) {
            Log::error('❌ Erreur dans processAiResponse()', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $this->addMessage(SimulatorMessageType::ASSISTANT()->value, '❌ Erreur de simulation : '.$e->getMessage());
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

    private function simulateAiResponse(string $userMessage, array $conversationContext = []): void
    {
        Log::info('[SIMULATOR] Début simulation réponse IA via orchestrateur', [
            'userMessage' => $userMessage,
            'account_id' => $this->account->id,
            'provided_context_count' => count($conversationContext),
        ]);

        try {
            // Mettre à jour le compte avec la configuration actuelle
            $this->account->agent_prompt = $this->currentPrompt;
            $this->account->contextual_information = $this->currentContextualInfo;
            $this->account->ai_model_id = $this->currentModelId;
            $this->account->response_time = $this->currentResponseTime;

            // Préparer l'historique de conversation (format string)
            $conversationHistory = $this->prepareConversationHistory($conversationContext);

            // Créer le DTO de requête
            $messageRequest = new \App\DTOs\WhatsApp\WhatsAppMessageRequestDTO(
                id: 'sim_'.uniqid(),
                from: 'simulator_user',
                body: $userMessage,
                timestamp: time(),
                type: 'text',
                isGroup: false
            );

            // Appeler l'orchestrateur (API UNIFIÉE)
            $orchestrator = app(WhatsAppMessageOrchestratorInterface::class);
            $response = $orchestrator->processMessage(
                $this->account,
                $messageRequest,
                $conversationHistory
            );

            if ($response->hasAiResponse) {
                // Récupérer les timings calculés par l'orchestrateur et les diviser par 10 pour UI
                $waitTimeMs = ceil(($response->waitTimeSeconds * 1000) / 10); // Diviser par 10, arrondir par excès
                $typingDurationMs = ceil(($response->typingDurationSeconds * 1000) / 10); // Diviser par 10, arrondir par excès

                Log::info('[SIMULATOR] ⏰ Timings calculés par l\'orchestrateur', [
                    'original_wait_seconds' => $response->waitTimeSeconds,
                    'original_typing_seconds' => $response->typingDurationSeconds,
                    'ui_wait_ms' => $waitTimeMs,
                    'ui_typing_ms' => $typingDurationMs,
                    'response_length' => strlen($response->aiResponse),
                ]);

                // L'orchestrateur retourne déjà le message parsé dans aiResponse
                $displayMessage = $response->aiResponse;

                Log::info('[SIMULATOR] 📊 Réponse de l\'orchestrateur', [
                    'message_length' => strlen($displayMessage),
                    'has_products' => ! empty($response->products),
                    'product_count' => count($response->products),
                ]);

                // Si l'orchestrateur a enrichi des produits, on les programme aussi
                if (! empty($response->products)) {
                    Log::info('[SIMULATOR] 📦 Produits enrichis détectés', [
                        'product_count' => count($response->products),
                    ]);

                    // On programme l'envoi des produits après le message texte
                    $this->dispatch('simulate-products-sending', [
                        'products' => $response->products,
                        'delayAfterMessage' => 2000, // 2 secondes après le message
                    ]);
                }

                // Programmer la réponse avec les timings accélérés pour l'UI
                $this->dispatch('simulate-response-timing', [
                    'waitTimeMs' => $waitTimeMs,
                    'typingDurationMs' => $typingDurationMs,
                    'responseMessage' => $displayMessage,
                ]);

                Log::info('[SIMULATOR] ✅ Timings programmés pour la simulation UI');

            } else {
                throw new \Exception('Aucune réponse générée par l\'orchestrateur');
            }

        } catch (\Exception $e) {
            Log::error('[SIMULATOR] ❌ Erreur lors de la simulation IA', [
                'error' => $e->getMessage(),
                'user_message' => $userMessage,
                'account_id' => $this->account->id,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addMessage(SimulatorMessageType::ASSISTANT()->value, '❌ Erreur de simulation : '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Méthode appelée par le JavaScript après les délais de timing
     */
    public function addAiResponse(string $responseMessage): void
    {
        $this->addMessage(SimulatorMessageType::ASSISTANT()->value, $responseMessage);
        $this->dispatch('message-added');
        $this->showTyping = false;
        $this->isProcessing = false;

        Log::info('[SIMULATOR] 📡 Réponse IA ajoutée après simulation timing');
    }

    /**
     * Méthode pour simuler l'envoi de produits
     */
    public function simulateProductsSending(array $productIds): void
    {
        Log::info('[SIMULATOR] 📦 Début simulation envoi produits', [
            'product_ids' => $productIds,
            'count' => count($productIds),
        ]);

        // Récupérer les produits depuis la base de données
        $products = \App\Models\UserProduct::whereIn('id', $productIds)
            ->where('user_id', $this->account->user_id)
            ->get();

        Log::info('[SIMULATOR] 📦 Produits trouvés', [
            'requested_ids' => $productIds,
            'found_count' => $products->count(),
            'found_ids' => $products->pluck('id')->toArray(),
        ]);

        foreach ($products as $product) {
            $productMessage = $this->formatProductMessage($product);
            $this->addMessage('product', $productMessage);  // Note: 'product' n'est pas dans l'enum - garde tel quel

            Log::info('[SIMULATOR] 📦 Produit ajouté au simulateur', [
                'product_id' => $product->id,
                'product_title' => $product->title,
                'product_price' => $product->price,
            ]);
        }

        $this->dispatch('message-added');
    }

    /**
     * Formater un produit pour l'affichage dans le simulateur
     */
    private function formatProductMessage(\App\Models\UserProduct $product): string
    {
        return sprintf(
            "📱 *%s*\n💰 Prix: %s FCFA\n📝 %s",
            $product->title,
            number_format($product->price, 0, ',', ' '),
            $product->description ?? 'Aucune description disponible'
        );
    }

    /**
     * Prépare l'historique de conversation au format string pour l'orchestrateur
     */
    private function prepareConversationHistory(array $conversationContext): string
    {
        $history = [];

        foreach ($conversationContext as $message) {
            if (isset($message['type']) && isset($message['content'])) {
                $type = $message['type'] === 'user' ? 'user' : 'system';
                $history[] = $type.': '.$message['content'];
            }
        }

        return implode("\n", $history);
    }

    public function render()
    {
        return view('livewire.customer.whats-app.conversation-simulator');
    }
}
