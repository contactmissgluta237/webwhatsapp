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
let simulationTimeout = null;
let typingTimeout = null;

// Utility functions
function scrollToBottom() {
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
        requestAnimationFrame(() => {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        });
    }
}

function clearAllTimeouts() {
    if (simulationTimeout) {
        clearTimeout(simulationTimeout);
        simulationTimeout = null;
    }
    if (typingTimeout) {
        clearTimeout(typingTimeout);
        typingTimeout = null;
    }
}

// Livewire event handlers
$wire.on('message-added', scrollToBottom);

$wire.on('conversation-cleared', clearAllTimeouts);

$wire.on('schedule-ai-response', (eventData) => {
    const data = Array.isArray(eventData) ? eventData[0] : eventData;
    const { userMessage, conversationContext = [] } = data;
    
    if (!userMessage) {
        console.error('❌ Missing userMessage');
        return;
    }
    
    clearAllTimeouts();
    
    // Short delay for UI update then generate response
    setTimeout(() => {
        $wire.call('generateAiResponse', userMessage, conversationContext);
    }, 100);
});

$wire.on('simulate-response-timing', (eventData) => {
    const data = Array.isArray(eventData) ? eventData[0] : eventData;
    const { waitTimeSeconds, typingDurationSeconds, responseMessage } = data;
    
    clearAllTimeouts();
    
    // Phase 1: Wait then start typing
    simulationTimeout = setTimeout(() => {
        $wire.call('startTyping');
        
        // Phase 2: Type then show response
        typingTimeout = setTimeout(() => {
            $wire.call('addAiResponse', responseMessage);
        }, typingDurationSeconds * 1000);
        
    }, waitTimeSeconds * 1000);
});

// Auto-scroll and cleanup
document.addEventListener('livewire:updated', scrollToBottom);
document.addEventListener('livewire:navigating', clearAllTimeouts);
</script>
@endscript
