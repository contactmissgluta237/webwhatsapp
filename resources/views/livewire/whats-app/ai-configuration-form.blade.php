<div class="configuration-form-container">
    <form wire:submit.prevent="save" class="ai-configuration-form">
        
        <!-- Première ligne: Nom de l'agent (modifiable) + Activation -->
        <div class="form-row agent-header-row">
            <div class="col-8">
                <label for="agent_name" class="form-label agent-name-label required">
                    {{ __('Nom de l\'agent IA') }}
                </label>
                <input type="text" 
                       wire:model.defer="agent_name" 
                       id="agent_name" 
                       class="form-control @error('agent_name') is-invalid @enderror" 
                       placeholder="{{ __('Ex: Assistant Client, Support Technique...') }}">
                @error('agent_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-4 d-flex align-items-end justify-content-end">
                <div class="custom-control custom-switch">
                    <input type="checkbox" 
                           class="custom-control-input" 
                           id="agent_enabled" 
                           wire:model.live="agent_enabled">
                    <label class="custom-control-label" for="agent_enabled">
                        <span class="switch-text">
                            {{ $agent_enabled ? __('Activé') : __('Désactivé') }}
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Deuxième ligne: Modèle d'IA -->
        <div class="form-row model-selection-row">
            <div class="col-12">
                <label for="ai_model_id" class="form-label required">{{ __('Modèle d\'IA') }}</label>
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
                
                <!-- Description du modèle sélectionné -->
                @if($this->selectedModel)
                    <div class="model-description mt-2">
                        <small class="text-muted">
                            <i class="la la-info-circle"></i>
                            {{ $this->selectedModel->description }}
                            <br>
                            <strong>Coût:</strong> {{ number_format($this->selectedModel->cost_per_token * 1000, 4) }} USD / 1000 tokens
                        </small>
                    </div>
                @endif
            </div>
        </div>

        <!-- Prompt de l'IA -->
        <div class="form-row">
            <div class="col-12">
                <label for="agent_prompt" class="form-label required">{{ __('Prompt de l\'IA') }}</label>
                <textarea wire:model.defer="agent_prompt" 
                          id="agent_prompt" 
                          class="form-control @error('agent_prompt') is-invalid @enderror" 
                          rows="4" 
                          placeholder="{{ __('Exemple: Tu es un assistant WhatsApp professionnel pour une entreprise. Réponds de manière courtoise et utile...') }}"></textarea>
                @error('agent_prompt')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">
                    {{ __('Définit le comportement et la personnalité de votre agent IA') }}
                </small>
            </div>
        </div>

        <!-- Mots déclencheurs -->
        <div class="form-row">
            <div class="col-12">
                <label for="trigger_words" class="form-label">{{ __('Mots déclencheurs') }}</label>
                <input type="text" 
                       wire:model.defer="trigger_words" 
                       id="trigger_words" 
                       class="form-control @error('trigger_words') is-invalid @enderror" 
                       placeholder="{{ __('aide, support, info, prix (séparés par des virgules)') }}">
                @error('trigger_words')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">
                    {{ __('L\'IA ne répondra qu\'aux messages contenant ces mots. Laissez vide pour répondre à tous les messages.') }}
                </small>
            </div>
        </div>

        <!-- Délai de réponse -->
        <div class="form-row">
            <div class="col-12">
                <label for="response_time" class="form-label">{{ __('Délai de réponse') }}</label>
                <select wire:model.defer="response_time" 
                        id="response_time" 
                        class="form-control @error('response_time') is-invalid @enderror">
                    @foreach($this->responseTimeOptions as $option)
                        <option value="{{ $option['value'] }}">
                            {{ $option['label'] }} - {{ $option['description'] }}
                        </option>
                    @endforeach
                </select>
                @error('response_time')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">
                    {{ __('Délai avant que l\'IA réponde automatiquement') }}
                </small>
            </div>
        </div>

        <!-- Bouton de sauvegarde - Séparé avec espacement -->
        <div class="form-row save-row">
            <div class="col-12">
                <button type="submit" 
                        class="btn btn-primary btn-lg btn-block save-configuration-btn" 
                        wire:loading.attr="disabled"
                        wire:target="save">
                    <span wire:loading.remove wire:target="save">
                        <i class="la la-save"></i> {{ __('Sauvegarder la configuration') }}
                    </span>
                    <span wire:loading wire:target="save">
                        <i class="la la-spinner la-spin"></i> {{ __('Sauvegarde en cours...') }}
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>
