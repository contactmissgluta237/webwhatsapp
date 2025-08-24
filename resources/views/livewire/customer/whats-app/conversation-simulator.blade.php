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
                        <div class="message-content {{ !empty($message['media_urls']) ? 'message-content-with-media' : '' }}">
                            {{-- Afficher les médias d'abord --}}
                            @if(!empty($message['media_urls']))
                                <div class="message-media">
                                    @foreach($message['media_urls'] as $mediaUrl)
                                        <div class="media-item">
                                            <img src="{{ $mediaUrl }}" 
                                                 alt="Product image" 
                                                 class="product-image"
                                                 loading="lazy"
                                                 onclick="showImageModal('{{ $mediaUrl }}')">
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            
                            {{-- Puis le contenu texte --}}
                            @if($message['type'] === 'system')
                                <div class="message-text">
                                    <em>{!! nl2br(e($message['content'])) !!}</em>
                                </div>
                            @elseif($message['type'] === 'product')
                                <div class="product-message">
                                    <div class="message-text">
                                        {!! nl2br(e($message['content'])) !!}
                                    </div>
                                </div>
                            @else
                                <div class="message-text">
                                    {!! nl2br(e($message['content'])) !!}
                                </div>
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

    <!-- Modal pour agrandir les images -->
    <div id="imageModal" class="image-modal" onclick="closeImageModal()">
        <div class="modal-content">
            <span class="close" onclick="closeImageModal()">&times;</span>
            <img id="modalImage" class="modal-image" alt="Image agrandie">
        </div>
    </div>
</div>

@script
<script>
let activeTimeout = null;
let typingTimeout = null;

console.log('🎬 ConversationSimulator script chargé');

// Fonctions pour le modal d'images
window.showImageModal = function(imageUrl) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    modalImage.src = imageUrl;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
};

window.closeImageModal = function() {
    const modal = document.getElementById('imageModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
};

// Fermer le modal avec la touche Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeImageModal();
    }
});

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
    console.log('⏰ Event schedule-ai-response reçu (sans délai préfix):', eventData);
    
    // Extraire les données selon le format
    let userMessage, conversationContext;
    
    if (Array.isArray(eventData) && eventData.length > 0) {
        // Format tableau: [{ userMessage: "...", conversationContext: [...] }]
        const data = eventData[0];
        userMessage = data.userMessage;
        conversationContext = data.conversationContext || [];
    } else if (eventData.userMessage) {
        // Format objet direct: { userMessage: "...", conversationContext: [...] }
        userMessage = eventData.userMessage;
        conversationContext = eventData.conversationContext || [];
    } else {
        console.error('❌ Format d\'event inattendu:', eventData);
        return;
    }
    
    if (!userMessage) {
        console.error('❌ userMessage manquant:', eventData);
        return;
    }
    
    // Nettoyer timeouts précédents
    clearAllTimeouts();
    
    console.log(`📝 Traitement immédiat du message: "${userMessage}" avec ${conversationContext.length} messages de contexte`);
    
    // Traitement immédiat - l'orchestrateur calculera les timings
    $wire.call('processAiResponse', userMessage, conversationContext).then(() => {
        console.log('✅ processAiResponse appelé - timings gérés par le backend');
    }).catch(error => {
        console.error('❌ Erreur processAiResponse:', error);
    });
});

// Nouveau: Gérer la simulation de timing avec les données du backend
$wire.on('simulate-response-timing', (eventData) => {
    console.log('⏰ Event simulate-response-timing reçu:', eventData);
    
    // Extraire les données selon le format
    let waitTimeMs, typingDurationMs, responseMessage;
    
    if (Array.isArray(eventData) && eventData.length > 0) {
        const data = eventData[0];
        waitTimeMs = data.waitTimeMs;
        typingDurationMs = data.typingDurationMs;
        responseMessage = data.responseMessage;
    } else if (eventData.waitTimeMs !== undefined) {
        waitTimeMs = eventData.waitTimeMs;
        typingDurationMs = eventData.typingDurationMs;
        responseMessage = eventData.responseMessage;
    } else {
        console.error('❌ Format de timing inattendu:', eventData);
        return;
    }
    
    console.log(`⏰ Timings UI (divisés par 10): attente=${waitTimeMs}ms, typing=${typingDurationMs}ms`);
    
    // Nettoyer timeouts précédents
    clearAllTimeouts();
    
    // ÉTAPE 1: Attendre le délai de réponse (wait_time)
    activeTimeout = setTimeout(() => {
        console.log('💭 Délai de réponse écoulé - Démarrage du typing');
        
        // Démarrer le typing indicator
        $wire.call('startTyping').then(() => {
            console.log('✅ Typing indicator démarré');
        }).catch(error => {
            console.error('❌ Erreur startTyping:', error);
        });
        
        // ÉTAPE 2: Typing pendant la durée calculée
        typingTimeout = setTimeout(() => {
            console.log('🤖 Fin du typing - Affichage de la réponse');
            
            // Arrêter le typing explicitement
            $wire.call('stopTyping').then(() => {
                console.log('✅ Typing indicator arrêté');
                
                // Puis afficher la réponse
                $wire.call('addAiResponse', responseMessage).then(() => {
                    console.log('✅ Réponse IA affichée après simulation timing');
                }).catch(error => {
                    console.error('❌ Erreur addAiResponse:', error);
                });
            }).catch(error => {
                console.error('❌ Erreur stopTyping:', error);
            });
            
        }, typingDurationMs);
        
    }, waitTimeMs);
    
    console.log('✅ Simulation timing programmée');
});

// Nouveau: Gérer l'affichage de produits avec délai
$wire.on('simulate-products-display', (eventData) => {
    console.log('📦 Event simulate-products-display reçu:', eventData);
    
    // Extraire les données selon le format
    let products, delayAfterMessage;
    
    if (Array.isArray(eventData) && eventData.length > 0) {
        const data = eventData[0];
        products = data.products;
        delayAfterMessage = data.delayAfterMessage || 2000;
    } else if (eventData.products) {
        products = eventData.products;
        delayAfterMessage = eventData.delayAfterMessage || 2000;
    } else {
        console.error('❌ Format de produits inattendu:', eventData);
        return;
    }
    
    console.log(`📦 Affichage de ${products.length} produits dans ${delayAfterMessage}ms`);
    
    // Programmer l'affichage des produits après le délai
    setTimeout(() => {
        console.log('📦 Affichage des produits maintenant');
        
        $wire.call('displayFormattedProducts', products).then(() => {
            console.log('✅ Produits affichés avec succès');
        }).catch(error => {
            console.error('❌ Erreur displayFormattedProducts:', error);
        });
        
    }, delayAfterMessage);
    
    console.log('✅ Affichage de produits programmé');
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
