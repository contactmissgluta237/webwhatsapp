<div class="tab-content-section">
    <!-- Session et Nom de l'agent -->
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="session_number" class="form-label">
                    <i class="la la-whatsapp"></i> {{ __('Numéro de la session') }}
                </label>
                <input type="text" 
                       id="session_number" 
                       class="form-control" 
                       value="{{ $account->phone_number }}" 
                       readonly>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="agent_name" class="form-label required">
                    <i class="la la-robot"></i> {{ __('Nom de l\'agent IA') }}
                    <i class="la la-info-circle text-muted" 
                       data-toggle="tooltip" 
                       data-placement="top" 
                       title="{{ __('le nom est juste à titre indicatif pour se répérer!') }}"></i>
                </label>
                <input type="text" 
                       wire:model.live="agent_name" 
                       id="agent_name" 
                       class="form-control @error('agent_name') is-invalid @enderror" 
                       placeholder="{{ __('Ex: Assistant Client, Support Technique...') }}">
                @error('agent_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <!-- Statut d'activation -->
    <div class="form-group">
        <div class="d-flex align-items-center justify-content-between">
            <label class="form-label mb-0">
                <i class="la la-power-off"></i> {{ __('Statut de l\'agent') }}
            </label>
            <div class="custom-control custom-switch">
                <input type="checkbox" 
                       class="custom-control-input" 
                       id="agent_enabled" 
                       wire:model.live="agent_enabled">
                <label class="custom-control-label" for="agent_enabled">
                    <span class="switch-text {{ $agent_enabled ? 'text-success' : 'text-muted' }}">
                        {{ $agent_enabled ? __('Activé') : __('Désactivé') }}
                    </span>
                </label>
            </div>
        </div>
    </div>

    @if(auth()->user()->isAdmin())
    <!-- Modèle d'IA -->
    <div class="form-group">
        <label for="ai_model_id" class="form-label required">
            <i class="la la-brain"></i> {{ __('Modèle d\'IA') }}
        </label>
        <select wire:model.live="ai_model_id" 
                id="ai_model_id" 
                class="form-control @error('ai_model_id') is-invalid @enderror">
            <option value="">{{ __('Sélectionner un modèle') }}</option>
            @foreach($this->availableModels as $model)
                <option value="{{ $model->id }}">
                    {{ $model->name }} - {{ $model->provider }}
                </option>
            @endforeach
        </select>
        @error('ai_model_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        
        @if($this->selectedModel)
            <div class="model-info mt-3 p-3 bg-whatsapp-light rounded">
                <div class="row">
                    <div class="col-md-8">
                        <small class="text-muted">
                            <i class="la la-info-circle text-info"></i>
                            {{ $this->selectedModel->description }}
                        </small>
                    </div>
                    <div class="col-md-4 text-right">
                        <small class="text-muted">
                            <strong>{{ __('Coût') }}:</strong> 
                            <span class="text-primary">{{ number_format($this->selectedModel->cost_per_token * 1000, 4) }} USD / 1000 tokens</span>
                        </small>
                    </div>
                </div>
            </div>
        @endif
    </div>
    @endif


    <!-- Prompt de l'IA -->
    <div class="form-group">
        <div class="d-flex align-items-center justify-content-between">
            <label for="agent_prompt" class="form-label required mb-0">
                <i class="la la-comment-dots"></i> {{ __('Instructions pour l\'IA') }}
            </label>
            <div class="prompt-enhancement-controls">
                {{-- Boutons d'action pour le prompt --}}
                @if(!$hasEnhancedPrompt && !$isPromptValidated)
                    @if(empty(trim($agent_prompt)))
                        {{-- Si le prompt est vide, afficher le dropdown pour insérer un prompt type --}}
                        <div class="dropdown">
                            <button type="button"
                                    class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                    data-toggle="dropdown"
                                    aria-expanded="false">
                                <i class="la la-plus-circle"></i> {{ __('Insérer un prompt type') }}
                            </button>
                            <div class="dropdown-menu">
                                @foreach($this->availablePromptTypes as $type)
                                    <a class="dropdown-item"
                                       href="#"
                                       wire:click.prevent="insertPromptByType('{{ $type['value'] }}')">
                                        <i class="{{ $type['icon'] }}"></i>
                                        <strong>{{ $type['label'] }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $type['description'] }}</small>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        {{-- Si le prompt n'est pas vide, afficher le bouton "Améliorer" --}}
                        <button type="button"
                                class="btn btn-outline-primary btn-sm"
                                wire:click="enhancePrompt"
                                wire:loading.attr="disabled"
                                wire:target="enhancePrompt">
                            <span wire:loading.remove wire:target="enhancePrompt">
                                <i class="la la-magic"></i> {{ __('Améliorer le prompt') }}
                            </span>
                            <span wire:loading wire:target="enhancePrompt">
                                <i class="la la-spinner la-spin"></i> {{ __('Amélioration...') }}
                            </span>
                        </button>
                    @endif
                @endif

                {{-- Boutons validation/rejet (après amélioration) --}}
                @if($hasEnhancedPrompt && !$isPromptValidated)
                    <div class="btn-group">
                        <button type="button" 
                                class="btn btn-success btn-sm" 
                                wire:click="acceptEnhancedPrompt">
                            <i class="la la-check"></i> {{ __('Accepter') }}
                        </button>
                        <button type="button" 
                                class="btn btn-outline-secondary btn-sm" 
                                wire:click="rejectEnhancedPrompt">
                            <i class="la la-times"></i> {{ __('Annuler') }}
                        </button>
                    </div>
                @endif

                {{-- État validé - aucun bouton jusqu'à modification --}}
                @if($isPromptValidated)
                    <small class="text-success">
                        <i class="la la-check-circle"></i> {{ __('Prompt validé') }}
                    </small>
                @endif
            </div>
        </div>
        <textarea wire:model.live="agent_prompt" 
                  id="agent_prompt" 
                  class="form-control @error('agent_prompt') is-invalid @enderror @if($hasEnhancedPrompt) border-success @endif" 
                  rows="20" 
                  placeholder="{{ __('Ex: Tu es un assistant professionnel pour AFRIK SOLUTIONS, spécialisé dans le développement web et mobile. Réponds de manière courtoise et professionnelle...') }}"></textarea>
        @error('agent_prompt')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        
        {{-- Indicateur d'amélioration --}}
        @if($hasEnhancedPrompt)
            <div class="alert alert-success alert-sm mt-2 mb-0">
                <i class="la la-sparkles"></i> 
                {{ __('Prompt amélioré automatiquement. Acceptez pour valider ou annulez pour revenir à l\'original.') }}
            </div>
        @endif
        
        <small class="form-text text-muted">
            <i class="la la-lightbulb"></i> {{ __('Définissez le comportement, la personnalité et les instructions principales de votre agent IA') }}
        </small>
        <div class="char-counter mt-2">
            <small class="{{ strlen($agent_prompt ?? '') > 10000 ? 'text-danger' : 'text-muted' }}">
                <i class="la {{ strlen($agent_prompt ?? '') > 10000 ? 'la-exclamation-triangle' : 'la-info-circle' }}"></i>
                {{ strlen($agent_prompt ?? '') }} / 10 000 caractères
                @if(strlen($agent_prompt ?? '') > 10000)
                    <span class="badge badge-danger ml-1">{{ __('Limite dépassée') }}</span>
                @elseif($hasEnhancedPrompt)
                    <span class="badge badge-success ml-1">{{ __('Amélioré') }}</span>
                @endif
            </small>
            @if(strlen($agent_prompt ?? '') > 10000)
                <div class="text-danger mt-1">
                    <small>
                        <i class="la la-exclamation-circle"></i>
                        {{ __('Le prompt dépasse la limite de 10 000 caractères. Veuillez le raccourcir.') }}
                    </small>
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
.prompt-enhancement-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

.border-success {
    border-color: #28a745 !important;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.btn-group .btn {
    margin-left: 0;
}

/* Dropdown menu styling for agent types */
.dropdown-menu .dropdown-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #eee;
}

.dropdown-menu .dropdown-item:last-child {
    border-bottom: none;
}

.dropdown-menu .dropdown-item:hover {
    background-color: #f8f9fa;
}

/* Character counter styling */
.char-counter small.text-danger {
    font-weight: 500;
}

.char-counter .la-exclamation-triangle {
    color: #dc3545;
}

/* Save button disabled state */
.btn.disabled {
    background-color: #6c757d !important;
    border-color: #6c757d !important;
    opacity: 0.65;
    cursor: not-allowed;
}
</style>
@endpush
