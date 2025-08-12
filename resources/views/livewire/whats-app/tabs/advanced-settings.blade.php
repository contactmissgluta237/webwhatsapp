<div class="tab-content-section">
    <!-- Mots déclencheurs -->
    <div class="form-group">
        <label for="trigger_words" class="form-label">
            <i class="la la-flag"></i> {{ __('Mots déclencheurs') }}
        </label>
        <input type="text" 
               wire:model.live="trigger_words" 
               id="trigger_words" 
               class="form-control @error('trigger_words') is-invalid @enderror" 
               placeholder="{{ __('aide, support, info, prix, service, contact') }}">
        @error('trigger_words')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="form-text text-muted">
            <i class="la la-info-circle"></i> {{ __('Mots qui déclenchent une réponse automatique (séparés par des virgules). Laissez vide pour répondre à tous les messages.') }}
        </small>
    </div>

    <!-- Mots d'ignorance -->
    <div class="form-group">
        <label for="ignore_words" class="form-label">
            <i class="la la-ban"></i> {{ __('Mots d\'exclusion') }}
        </label>
        <input type="text" 
               wire:model.live="ignore_words" 
               id="ignore_words" 
               class="form-control @error('ignore_words') is-invalid @enderror" 
               placeholder="{{ __('stop, arrêt, humain, agent, transfert') }}">
        @error('ignore_words')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="form-text text-muted">
            <i class="la la-exclamation-triangle text-warning"></i> {{ __('Mots qui empêchent l\'IA de répondre automatiquement (séparés par des virgules). Utile pour transférer vers un humain.') }}
        </small>
    </div>

    <!-- Délai de réponse -->
    <div class="form-group">
        <label for="response_time" class="form-label">
            <i class="la la-clock"></i> {{ __('Délai de réponse') }}
        </label>
        <select wire:model.live="response_time" 
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
            <i class="la la-info-circle"></i> {{ __('Temps d\'attente avant que l\'IA réponde automatiquement') }}
        </small>
    </div>

    <!-- Stopper les réponses quand l'humain répond -->
    <div class="form-group">
        <div class="custom-control custom-checkbox">
            <input type="checkbox" 
                   wire:model.live="stop_on_human_reply" 
                   id="stop_on_human_reply" 
                   class="custom-control-input">
            <label class="custom-control-label" for="stop_on_human_reply">
                <i class="la la-hand-stop-o text-warning"></i>
                {{ __('Stopper les réponses une fois que l\'être humain a répondu') }}
            </label>
        </div>
        <small class="form-text text-muted">
            <i class="la la-info-circle"></i> {{ __('L\'IA cessera de répondre automatiquement dès qu\'un humain intervient dans la conversation') }}
        </small>
    </div>

    <!-- Aperçu des paramètres -->
    <div class="settings-preview bg-light rounded p-4 mt-4">
        <h6 class="text-primary mb-3">
            <i class="la la-eye"></i> {{ __('Aperçu de la configuration') }}
        </h6>
        
        <div class="row">
            <div class="col-md-6">
                <div class="config-item mb-3">
                    <strong class="text-sm">{{ __('Mots déclencheurs') }}:</strong>
                    <div class="mt-1">
                        @if(empty(trim($trigger_words ?? '')))
                            <span class="badge badge-info">{{ __('Tous les messages') }}</span>
                        @else
                            @foreach(array_map('trim', explode(',', $trigger_words ?? '')) as $word)
                                @if(!empty($word))
                                    <span class="badge badge-success mr-1">#{{ $word }}</span>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="config-item mb-3">
                    <strong class="text-sm">{{ __('Mots d\'exclusion') }}:</strong>
                    <div class="mt-1">
                        @if(empty(trim($ignore_words ?? '')))
                            <span class="badge badge-secondary">{{ __('Aucun') }}</span>
                        @else
                            @foreach(array_map('trim', explode(',', $ignore_words ?? '')) as $word)
                                @if(!empty($word))
                                    <span class="badge badge-danger mr-1">!{{ $word }}</span>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <div class="config-item">
            <strong class="text-sm">{{ __('Comportement') }}:</strong>
            <p class="mb-0 text-sm text-muted mt-1">
                @if($agent_enabled)
                    <span class="text-success">{{ __('L\'agent est activé et répondra') }}</span>
                    @if(!empty(trim($trigger_words ?? '')))
                        {{ __('uniquement aux messages contenant les mots déclencheurs') }}
                    @else
                        {{ __('à tous les messages reçus') }}
                    @endif
                    
                    @if(!empty(trim($ignore_words ?? '')))
                        {{ __(', sauf si le message contient un mot d\'exclusion') }}.
                    @else
                        .
                    @endif
                    
                    {{ __('Délai de réponse') }}: 
                    <strong>{{ collect($this->responseTimeOptions)->firstWhere('value', $response_time)['label'] ?? $response_time }}</strong>
                @else
                    <span class="text-muted">{{ __('L\'agent est désactivé et ne répondra à aucun message') }}</span>
                @endif
            </p>
        </div>
    </div>

    <!-- Conseils d'utilisation -->
    <div class="usage-tips bg-info-light rounded p-3 mt-4">
        <h6 class="text-info mb-2">
            <i class="la la-lightbulb"></i> {{ __('Conseils d\'utilisation') }}
        </h6>
        <ul class="mb-0 text-sm">
            <li><strong>{{ __('Mots déclencheurs') }}:</strong> {{ __('Utilisez des mots-clés liés à votre activité (commande, devis, support...)') }}</li>
            <li><strong>{{ __('Mots d\'exclusion') }}:</strong> {{ __('Prévoyez des mots pour que les clients puissent demander un humain') }}</li>
            <li><strong>{{ __('Délai optimal') }}:</strong> {{ __('2-5 secondes pour paraître humain, instantané pour l\'efficacité') }}</li>
            <li><strong>{{ __('Test') }}:</strong> {{ __('Utilisez le simulateur pour tester votre configuration avant activation') }}</li>
        </ul>
    </div>
</div>
