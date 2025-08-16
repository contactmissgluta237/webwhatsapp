<div class="conversation-simulator-container">
    <div class="simulator-header">
        <h5 class="simulator-title">
            <i class="la la-comments"></i> {{ __('Simulateur de conversation') }}
        </h5>
        @if(!empty($simulationMessages))
            <button type="button" 
                    wire:click="clearConversation" 
                    class="btn btn-sm btn-outline-secondary"
                    title="{{ __('Vider la conversation') }}">
                <i class="la la-trash"></i>
            </button>
        @endif
    </div>

    <!-- Zone de conversation WhatsApp-like -->
    <div class="chat-container">
        <div class="chat-messages" id="chatMessages">
            @if(empty($simulationMessages))
                <div class="no-messages">
                    <i class="la la-comments-o la-3x text-muted mb-3"></i>
                    <p class="text-muted">{{ __('Commencez une conversation pour tester votre configuration IA') }}</p>
                </div>
            @else
                @foreach($simulationMessages as $message)
                    <div class="message message-{{ $message['type'] }}">
                        <div class="message-content">
                            @if($message['type'] === 'system')
                                <em>{!! nl2br(e($message['content'])) !!}</em>
                            @else
                                {!! nl2br(e($message['content'])) !!}
                            @endif
                        </div>
                        <div class="message-time">{{ $message['time'] }}</div>
                    </div>
                @endforeach
            @endif

            @if($showTyping)
                <div class="message message-ai">
                    <div class="message-content typing-indicator">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Zone de saisie -->
        <div class="chat-input-container">
            <form wire:submit.prevent="sendMessage" class="chat-input-form">
                <div class="input-group">
                    <input type="text" 
                           wire:model.live="newMessage" 
                           class="form-control chat-input" 
                           placeholder="{{ __('Tapez votre message de test...') }}"
                           wire:loading.attr="disabled"
                           wire:target="sendMessage"
                           maxlength="500">
                    <div class="input-group-append">
                        <button type="submit" 
                                class="btn btn-whatsapp chat-send-btn"
                                wire:loading.attr="disabled"
                                wire:target="sendMessage"
                                @disabled(empty(trim($newMessage)))>
                            <span wire:loading.remove wire:target="sendMessage">
                                <i class="la la-paper-plane"></i>
                            </span>
                            <span wire:loading wire:target="sendMessage">
                                <i class="la la-spinner la-spin"></i>
                            </span>
                        </button>
                    </div>
                </div>
                @if(!empty($newMessage) && strlen($newMessage) > 450)
                    <small class="text-warning">{{ strlen($newMessage) }}/500 caractères</small>
                @endif
            </form>
        </div>
    </div>
</div>

@script
<script>
let activeTimeout = null;
let typingTimeout = null;

console.log('🎬 ConversationSimulator script chargé');

// Auto-scroll vers le bas
function scrollToBottom() {
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
        requestAnimationFrame(() => {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        });
    }
}

// Nettoyer tous les timeouts
function clearAllTimeouts() {
    if (activeTimeout) {
        clearTimeout(activeTimeout);
        activeTimeout = null;
    }
    if (typingTimeout) {
        clearTimeout(typingTimeout);
        typingTimeout = null;
    }
}

// Événements Livewire
$wire.on('message-added', () => {
    console.log('📝 Message ajouté - scroll automatique');
    scrollToBottom();
});

$wire.on('conversation-cleared', () => {
    console.log('🧹 Conversation vidée');
    clearAllTimeouts();
});

// Programmer la réponse IA avec délai
$wire.on('schedule-ai-response', (eventData) => {
    console.log('⏰ Event schedule-ai-response reçu:', eventData);
    
    // Extraire les données selon le format
    let userMessage, delayMs, conversationContext;
    
    if (Array.isArray(eventData) && eventData.length > 0) {
        // Format tableau: [{ userMessage: "...", delayMs: 3000, conversationContext: [...] }]
        const data = eventData[0];
        userMessage = data.userMessage;
        delayMs = data.delayMs;
        conversationContext = data.conversationContext || [];
    } else if (eventData.userMessage && eventData.delayMs) {
        // Format objet direct: { userMessage: "...", delayMs: 3000, conversationContext: [...] }
        userMessage = eventData.userMessage;
        delayMs = eventData.delayMs;
        conversationContext = eventData.conversationContext || [];
    } else {
        console.error('❌ Format d\'event inattendu:', eventData);
        return;
    }
    
    console.log(`⏰ Configuration: délai=${delayMs}ms, message="${userMessage}", context=${conversationContext.length} msgs`);
    
    if (!userMessage || !delayMs) {
        console.error('❌ Paramètres manquants:', { userMessage, delayMs });
        return;
    }
    
    // Nettoyer timeouts précédents
    clearAllTimeouts();
    
    console.log(`⏱️ Attente de ${delayMs/1000}s avant de commencer le typing...`);
    
    // ÉTAPE 1: Attendre le délai configuré (3-5s selon ResponseTime)
    activeTimeout = setTimeout(() => {
        console.log('💭 Délai écoulé - Démarrage du typing indicator');
        
        // Démarrer le typing indicator
        $wire.call('startTyping').then(() => {
            console.log('✅ Typing indicator démarré');
        }).catch(error => {
            console.error('❌ Erreur startTyping:', error);
        });
        
        // ÉTAPE 2: Typing pendant 2-3 secondes
        const typingDuration = 2000 + Math.random() * 1000;
        console.log(`⌨️ Typing pendant ${typingDuration}ms...`);
        
        typingTimeout = setTimeout(() => {
            console.log('🤖 Fin du typing - Génération de la réponse IA');
            
            // D'abord arrêter le typing
            $wire.call('stopTyping');
            
            // Puis générer la réponse
            $wire.call('processAiResponse', userMessage, conversationContext).then(() => {
                console.log('✅ Réponse IA générée');
            }).catch(error => {
                console.error('❌ Erreur processAiResponse:', error);
            });
            
        }, typingDuration);
        
    }, delayMs);
    
    console.log('✅ Timeouts programmés');
});

// Scroll automatique après mise à jour
document.addEventListener('livewire:updated', () => {
    scrollToBottom();
});

// Nettoyer les timeouts
document.addEventListener('livewire:navigating', () => {
    console.log('🧹 Nettoyage des timeouts');
    clearAllTimeouts();
});
</script>
@endscript
