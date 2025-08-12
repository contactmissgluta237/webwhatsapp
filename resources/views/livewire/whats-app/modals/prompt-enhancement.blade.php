<!-- Modal d'amélioration de prompt -->
<div class="modal fade" 
     id="promptEnhancementModal" 
     tabindex="-1" 
     role="dialog" 
     aria-labelledby="promptEnhancementModalLabel" 
     aria-hidden="true"
     wire:ignore.self
     @if($showEnhancementModal) data-show="true" @endif>
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="promptEnhancementModalLabel">
                    <i class="la la-magic"></i> {{ __('Amélioration du Prompt') }}
                </h5>
                <button type="button" 
                        class="close text-white" 
                        wire:click="rejectEnhancedPrompt"
                        aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Prompt original -->
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">
                            <i class="la la-file-text-o"></i> {{ __('Prompt Original') }}
                        </h6>
                        <div class="prompt-comparison original">
                            <div class="prompt-content bg-light p-3 rounded">
                                <small class="text-muted">{{ $agent_prompt }}</small>
                            </div>
                            <div class="prompt-stats mt-2">
                                <small class="text-muted">
                                    <i class="la la-info-circle"></i>
                                    {{ strlen($agent_prompt) }} caractères
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Prompt amélioré -->
                    <div class="col-md-6">
                        <h6 class="text-success mb-3">
                            <i class="la la-sparkles"></i> {{ __('Prompt Amélioré') }}
                        </h6>
                        <div class="prompt-comparison enhanced">
                            <div class="prompt-content bg-success-light p-3 rounded border-success">
                                <small class="text-dark">{{ $enhancedPrompt }}</small>
                            </div>
                            <div class="prompt-stats mt-2">
                                <small class="text-success">
                                    <i class="la la-check-circle"></i>
                                    {{ strlen($enhancedPrompt) }} caractères
                                    <span class="badge badge-success ml-1">
                                        @if(strlen($enhancedPrompt) > strlen($agent_prompt))
                                            +{{ strlen($enhancedPrompt) - strlen($agent_prompt) }}
                                        @else
                                            {{ strlen($enhancedPrompt) - strlen($agent_prompt) }}
                                        @endif
                                    </span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Améliorations détectées -->
                <div class="improvements-section mt-4">
                    <h6 class="text-info mb-3">
                        <i class="la la-lightbulb"></i> {{ __('Améliorations Apportées') }}
                    </h6>
                    <div class="alert alert-info">
                        <ul class="mb-0">
                            <li>{{ __('Structure et clarté améliorées') }}</li>
                            <li>{{ __('Ton adapté pour WhatsApp') }}</li>
                            <li>{{ __('Instructions comportementales précisées') }}</li>
                            <li>{{ __('Gestion des cas spéciaux ajoutée') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" 
                        class="btn btn-secondary" 
                        wire:click="rejectEnhancedPrompt">
                    <i class="la la-times"></i> {{ __('Garder l\'original') }}
                </button>
                <button type="button" 
                        class="btn btn-success" 
                        wire:click="acceptEnhancedPrompt">
                    <i class="la la-check"></i> {{ __('Appliquer l\'amélioration') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.bg-success-light {
    background-color: #d4edda !important;
}

.prompt-comparison {
    max-height: 300px;
    overflow-y: auto;
}

.prompt-content {
    white-space: pre-wrap;
    word-wrap: break-word;
    font-size: 0.9rem;
    line-height: 1.4;
}

.modal-lg {
    max-width: 900px;
}

.improvements-section .alert {
    font-size: 0.9rem;
}

.improvements-section ul li {
    margin-bottom: 0.25rem;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('show-toast', (event) => {
        if (typeof toastr !== 'undefined') {
            toastr[event.type](event.message);
        }
    });
});

// Gestion automatique du modal
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('promptEnhancementModal');
    if (modal) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'data-show') {
                    if (modal.getAttribute('data-show') === 'true') {
                        $(modal).modal('show');
                    } else {
                        $(modal).modal('hide');
                    }
                }
            });
        });
        
        observer.observe(modal, {
            attributes: true,
            attributeFilter: ['data-show']
        });
    }
});
</script>
@endpush
