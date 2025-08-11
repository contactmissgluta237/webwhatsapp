<div class="conversation-simulator-container">
    <div class="simulator-header">
        <h5 class="simulator-title">
            <i class="la la-comments"></i> {{ __('Simulateur de conversation') }}
        </h5>
        <div class="simulator-actions-header">
            <button type="button" 
                    class="btn btn-sm btn-outline-secondary" 
                    wire:click="generateSampleConversation"
                    wire:loading.attr="disabled">
                <i class="la la-magic"></i> {{ __('Exemple') }}
            </button>
            <button type="button" 
                    class="btn btn-sm btn-outline-danger" 
                    wire:click="clearSimulation">
                <i class="la la-trash"></i> {{ __('Vider') }}
            </button>
        </div>
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
                            {!! nl2br(e($message['content'])) !!}
                        </div>
                        <div class="message-time">{{ $message['time'] }}</div>
                    </div>
                @endforeach
            @endif

            @if($isProcessing)
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
                           wire:model.defer="newMessage" 
                           class="form-control chat-input" 
                           placeholder="{{ __('Tapez votre message de test...') }}"
                           wire:loading.attr="disabled"
                           wire:target="sendMessage">
                    <div class="input-group-append">
                        <button type="submit" 
                                class="btn btn-primary chat-send-btn"
                                wire:loading.attr="disabled"
                                wire:target="sendMessage"
                                @if(empty(trim($newMessage))) disabled @endif>
                            <span wire:loading.remove wire:target="sendMessage">
                                <i class="la la-paper-plane"></i>
                            </span>
                            <span wire:loading wire:target="sendMessage">
                                <i class="la la-spinner la-spin"></i>
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Info sur la simulation -->
    <div class="simulation-info">
        <small class="text-muted">
            <i class="la la-info-circle"></i>
            {{ __('Cette simulation utilise vos paramètres actuels. Les messages ne sont pas sauvegardés.') }}
        </small>
    </div>
</div>

@script
<script>
// Auto-scroll vers le bas quand nouveaux messages
$wire.on('message-added', () => {
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
});

// Scroll automatique après chaque update
document.addEventListener('livewire:updated', () => {
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
        setTimeout(() => {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }, 100);
    }
});
</script>
@endscript
