<div>
    <div class="row">
        {{-- Configuration Form (70%) --}}
        <div class="col-lg-8">
            <div class="ai-configuration-section">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="mb-0">
                        <i class="la la-cogs"></i> {{ __('Configuration de l\'Agent IA') }}
                    </h4>
                    <span class="badge badge-{{ $account->hasAiAgent() ? 'success' : 'secondary' }}">
                        {{ $account->hasAiAgent() ? __('Actif') : __('Inactif') }}
                    </span>
                </div>
                
                <form wire:submit.prevent="save">
                    {{-- Basic Account Configuration --}}
                    <div class="form-group mb-4">
                        <label for="sessionName" class="form-label">
                            <i class="la la-robot text-info"></i> {{ __('Nom de l\'agent IA') }}
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="sessionName" 
                               value="{{ $account->session_name }}"
                               readonly>
                        <small class="text-muted">
                            {{ __('Le nom de l\'agent IA ne peut pas être modifié après création.') }}
                        </small>
                    </div>

                    {{-- Enable/Disable AI Agent --}}
                    <div class="form-group mb-4">
                        <div class="custom-control custom-switch custom-control-lg">
                            <input type="checkbox" 
                                   class="custom-control-input" 
                                   id="aiAgentEnabled" 
                                   wire:model.live="ai_agent_enabled">
                            <label class="custom-control-label" for="aiAgentEnabled">
                                <strong>{{ __('Activer l\'agent IA pour ce compte WhatsApp') }}</strong>
                            </label>
                        </div>
                        <small class="text-muted">
                            {{ __('L\'agent IA répondra automatiquement aux messages selon la configuration définie.') }}
                        </small>
                    </div>

                    {{-- AI Model Selection --}}
                    <div class="form-group mb-4">
                        <label for="aiModel" class="form-label">
                            <i class="la la-brain text-primary"></i> {{ __('Modèle d\'IA') }} 
                            @if($ai_agent_enabled)<span class="text-danger">*</span>@endif
                        </label>
                        <select class="form-control @error('ai_model_id') is-invalid @enderror" 
                                id="aiModel" 
                                wire:model="ai_model_id">
                            <option value="">{{ __('Sélectionner un modèle...') }}</option>
                            @foreach($availableModels as $model)
                                <option value="{{ $model->id }}">
                                    {{ $model->name }} 
                                    @if($model->is_default) ({{ __('Par défaut') }}) @endif
                                    - {{ number_format($model->cost_per_1k_tokens, 6) }} USD/1k tokens
                                </option>
                            @endforeach
                        </select>
                        @error('ai_model_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        
                        @if($ai_model_id)
                            @php $selectedModel = $availableModels->find($ai_model_id) @endphp
                            @if($selectedModel)
                                <div class="mt-2">
                                    <span class="badge badge-{{ $selectedModel->getProviderBadgeColor() }} model-badge">
                                        <i class="la la-server"></i> {{ ucfirst($selectedModel->provider) }}
                                    </span>
                                    @if($selectedModel->requires_api_key && !$selectedModel->hasApiKey())
                                        <span class="badge badge-warning model-badge">
                                            <i class="la la-warning"></i> {{ __('Clé API requise') }}
                                        </span>
                                    @endif
                                    @if($selectedModel->isConfigured())
                                        <span class="badge badge-success model-badge">
                                            <i class="la la-check"></i> {{ __('Configuré') }}
                                        </span>
                                    @endif
                                </div>
                                <small class="text-muted">{{ $selectedModel->description }}</small>
                            @endif
                        @endif
                    </div>

                    {{-- AI Prompt --}}
                    <div class="form-group mb-4">
                        <label for="aiPrompt" class="form-label">
                            <i class="la la-comment-dots text-info"></i> {{ __('Prompt de l\'IA (Instructions)') }}
                        </label>
                        <textarea class="form-control @error('ai_prompt') is-invalid @enderror" 
                                  id="aiPrompt" 
                                  rows="4" 
                                  wire:model="ai_prompt"
                                  placeholder="{{ __('Ex: Tu es un assistant commercial pour notre entreprise. Réponds de manière professionnelle et amicale...') }}"></textarea>
                        @error('ai_prompt')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">
                            {{ __('Définit le comportement et le rôle de l\'IA. Laissez vide pour utiliser le prompt par défaut.') }}
                            ({{ strlen($ai_prompt) }}/1000 {{ __('caractères') }})
                        </small>
                    </div>

                    {{-- Trigger Words --}}
                    <div class="form-group mb-4">
                        <label for="aiTriggerWords" class="form-label">
                            <i class="la la-tags text-warning"></i> {{ __('Mots déclencheurs (optionnel)') }}
                        </label>
                        <input type="text" 
                               class="form-control @error('ai_trigger_words') is-invalid @enderror" 
                               id="aiTriggerWords" 
                               wire:model="ai_trigger_words"
                               placeholder="{{ __('Ex: help, aide, support, info') }}">
                        @error('ai_trigger_words')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">
                            {{ __('Mots séparés par des virgules. Si défini, l\'IA ne répondra qu\'aux messages contenant ces mots. Laissez vide pour répondre à tous les messages.') }}
                        </small>
                    </div>

                    {{-- Response Time --}}
                    <div class="form-group mb-4">
                        <label for="aiResponseTime" class="form-label">
                            <i class="la la-clock text-success"></i> {{ __('Délai de réponse') }}
                        </label>
                        <select class="form-control" 
                                id="aiResponseTime" 
                                wire:model="ai_response_time">
                            @foreach($responseTimeOptions as $option)
                                <option value="{{ $option['value'] }}">
                                    {{ $option['label'] }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">
                            {{ __('Temps d\'attente avant que l\'IA réponde pour paraître plus naturel.') }}
                        </small>
                    </div>

                    {{-- Save Button --}}
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="la la-save"></i> {{ __('Sauvegarder la configuration') }}
                        </button>
                        
                        @if($ai_model_id && $ai_agent_enabled)
                            <button type="button" 
                                    class="btn btn-outline-info btn-lg ms-2" 
                                    wire:click="toggleSimulation">
                                <i class="la la-flask"></i> 
                                {{ $showSimulation ? __('Masquer') : __('Tester') }} {{ __('la simulation') }}
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- Simulation & Info Panel (30%) --}}
        <div class="col-lg-4">
            @if($showSimulation && $ai_agent_enabled && $ai_model_id)
                <div class="simulation-panel mb-4">
                    <h5 class="mb-3">
                        <i class="la la-flask text-info"></i> {{ __('Simulation de réponse') }}
                    </h5>
                    
                    <div class="form-group mb-3">
                        <label for="simulationMessage" class="form-label">{{ __('Message de test') }}</label>
                        <textarea class="form-control" 
                                  id="simulationMessage" 
                                  rows="3" 
                                  wire:model="simulationMessage"
                                  placeholder="{{ __('Tapez un message pour tester la réponse de l\'IA...') }}"></textarea>
                    </div>

                    <button type="button" 
                            class="btn btn-success btn-block mb-3"
                            wire:click="simulateResponse"
                            wire:loading.attr="disabled"
                            wire:target="simulateResponse">
                        <span wire:loading.remove wire:target="simulateResponse">
                            <i class="la la-play"></i> {{ __('Simuler la réponse') }}
                        </span>
                        <span wire:loading wire:target="simulateResponse">
                            <i class="la la-spinner la-spin"></i> {{ __('Simulation...') }}
                        </span>
                    </button>

                    @if($simulationResponse)
                        <div class="alert alert-light border">
                            <strong>{{ __('Résultat de simulation:') }}</strong>
                            <hr>
                            <div style="white-space: pre-line; font-size: 0.9em;">{{ $simulationResponse }}</div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Account Information --}}
            <div class="account-info-panel">
                <h5 class="mb-3">
                    <i class="la la-info-circle text-success"></i> {{ __('Informations du compte') }}
                </h5>
                
                <div class="mb-3">
                    <strong>{{ __('Agent IA:') }}</strong><br>
                    <span class="text-muted">{{ $account->session_name }}</span>
                </div>
                
                <div class="mb-3">
                    <strong>{{ __('Téléphone:') }}</strong><br>
                    <span class="text-muted">{{ $account->phone_number ?: __('Non connecté') }}</span>
                </div>
                
                <div class="mb-3">
                    <strong>{{ __('Statut:') }}</strong><br>
                    <span class="badge badge-{{ $account->isConnected() ? 'success' : 'warning' }}">
                        {{ $account->status->label }}
                    </span>
                </div>
                
                @if($account->hasAiAgent())
                    <div class="mb-3">
                        <strong>{{ __('IA:') }}</strong><br>
                        <span class="badge badge-primary">{{ $account->getAiModel()?->name }}</span>
                    </div>
                @endif

                <div class="mb-0">
                    <strong>{{ __('Conversations:') }}</strong><br>
                    <small class="text-muted">
                        {{ $account->getTotalConversations() }} {{ __('total') }} | 
                        {{ $account->getActiveConversations() }} {{ __('actives') }}
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
