<div class="row">
    {{-- En-tête de la conversation --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <a href="{{ route('customer.whatsapp.conversations.index', $account->id) }}" 
                           class="btn btn-outline-secondary btn-sm mr-3">
                            <i class="la la-arrow-left"></i> Retour
                        </a>
                        
                        @if($conversation->is_group)
                            <i class="la la-users text-info mr-2" style="font-size: 1.5rem;"></i>
                        @else
                            <i class="la la-user text-primary mr-2" style="font-size: 1.5rem;"></i>
                        @endif
                        
                        <div>
                            <h4 class="mb-0">{{ $conversation->getDisplayName() }}</h4>
                            <small class="text-muted">
                                Session: {{ $account->session_name }} 
                                @if($conversation->is_ai_enabled)
                                    | <i class="la la-robot text-success"></i> IA Activée
                                @else
                                    | <i class="la la-robot text-muted"></i> IA Désactivée
                                @endif
                            </small>
                        </div>
                    </div>
                    
                    <div class="text-right">
                        <small class="text-muted d-block">
                            Messages aujourd'hui: {{ $conversationStats['total'] }}
                            ({{ $conversationStats['inbound'] }} reçus, {{ $conversationStats['outbound'] }} envoyés)
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Zone de messages --}}
    <div class="col-12 mt-3">
        <div class="card">
            <div class="card-body p-0" style="height: 600px; overflow-y: auto;" id="messages-container">
                @if($messages->count() > 0)
                    {{-- Bouton charger plus (si nécessaire) --}}
                    @if($messages->hasMorePages())
                        <div class="text-center p-3">
                            <button wire:click="loadMore" class="btn btn-outline-secondary btn-sm">
                                <i class="la la-arrow-up"></i> Charger plus de messages
                            </button>
                        </div>
                    @endif

                    {{-- Messages --}}
                    <div class="p-3">
                        @foreach($messages as $message)
                            <div class="message-row mb-3 {{ $message->isOutbound() ? 'outbound' : 'inbound' }}">
                                <div class="d-flex {{ $message->isOutbound() ? 'justify-content-end' : 'justify-content-start' }}">
                                    <div class="message-bubble {{ $message->isOutbound() ? 'message-outbound' : 'message-inbound' }}"
                                         style="max-width: 75%;">
                                        
                                        {{-- Contenu du message --}}
                                        <div class="message-content">
                                            {{ $message->content }}
                                        </div>
                                        
                                        {{-- Métadonnées du message --}}
                                        <div class="message-meta d-flex justify-content-between align-items-center mt-2">
                                            <small class="text-muted">
                                                {{ $message->getFormattedTime() }}
                                                @if($message->isFromAi())
                                                    <i class="la la-robot text-success ml-1" title="Message généré par IA"></i>
                                                @endif
                                            </small>
                                            
                                            @if($message->isOutbound())
                                                <i class="la la-check text-success" title="Envoyé"></i>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- Aucun message --}}
                    <div class="text-center p-5">
                        <i class="la la-comments text-muted" style="font-size: 3rem;"></i>
                        <h5 class="text-muted mt-3">Aucun message dans cette conversation</h5>
                        <p class="text-muted">Cette conversation n'a pas encore de messages.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Styles pour les messages (inspirés du simulateur) --}}
<style>
    .message-bubble {
        padding: 12px 16px;
        border-radius: 18px;
        position: relative;
        word-wrap: break-word;
    }
    
    .message-inbound {
        background-color: #f1f3f4;
        color: #333;
    }
    
    .message-outbound {
        background-color: #25d366;
        color: white;
    }
    
    .message-content {
        line-height: 1.4;
    }
    
    .message-meta {
        font-size: 0.75rem;
        margin-top: 4px;
    }
    
    .message-outbound .message-meta {
        color: rgba(255, 255, 255, 0.7);
    }
    
    .message-inbound .message-meta {
        color: #666;
    }
    
    #messages-container {
        background-color: #e5ddd5;
        background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60"><g fill-rule="evenodd"><g fill="%23ffffff" fill-opacity="0.05"><circle cx="30" cy="30" r="2"/></g></g></svg>');
    }
    
    .message-row.inbound {
        padding-left: 0;
        padding-right: 50px;
    }
    
    .message-row.outbound {
        padding-left: 50px;
        padding-right: 0;
    }
</style>

{{-- Auto-scroll vers le bas --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('messages-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    });
    
    // Auto-scroll après chaque mise à jour Livewire
    document.addEventListener('livewire:navigated', function() {
        const container = document.getElementById('messages-container');
        if (container) {
            setTimeout(() => {
                container.scrollTop = container.scrollHeight;
            }, 100);
        }
    });
</script>