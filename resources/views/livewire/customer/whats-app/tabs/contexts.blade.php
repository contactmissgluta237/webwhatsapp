<div class="tab-content-section">
    <!-- Contexte textuel -->
    <div class="form-group">
        <label for="contextual_information" class="form-label">
            <i class="la la-file-text"></i> {{ __('Informations contextuelles') }}
            <span class="text-muted">(<span id="char-count">{{ strlen($contextual_information ?? '') }}</span>/{{ number_format($this->userContextLimit) }})</span>
        </label>
        <textarea wire:model.defer="contextual_information" 
                  id="contextual_information" 
                  class="form-control @error('contextual_information') is-invalid @enderror" 
                  rows="8" 
                  maxlength="{{ $this->userContextLimit }}"
                  placeholder="{{ __('Ex: AFRIK SOLUTIONS est une entreprise spécialisée dans le développement web, mobile et les solutions digitales. Nos services incluent...') }}"
                  oninput="updateCharCount(this)"></textarea>
        @error('contextual_information')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="form-text text-muted">
            <i class="la la-info-circle"></i> {{ __('Ajoutez des informations sur votre entreprise, vos services, vos tarifs que l\'IA pourra utiliser dans ses réponses. Maximum :limit caractères.', ['limit' => number_format($this->userContextLimit)]) }}
        </small>
    </div>

    <!-- Documents de contexte - MASQUÉ TEMPORAIREMENT -->
    <div class="form-group d-none">
        <label class="form-label">
            <i class="la la-file-pdf"></i> {{ __('Documents de référence') }}
            <span class="text-muted">({{ __('1 document maximum - 20 000 caractères max') }})</span>
        </label>
        
        <!-- Zone de téléchargement -->
        <div class="upload-zone">
            <div class="upload-area border border-dashed border-secondary rounded p-4 text-center"
                 x-data="{ 
                     isDragging: false,
                     handleFiles(files) {
                         // Logique de téléchargement à implémenter
                     }
                 }"
                 x-on:dragover.prevent="isDragging = true"
                 x-on:dragleave.prevent="isDragging = false"
                 x-on:drop.prevent="isDragging = false; handleFiles($event.dataTransfer.files)"
                 :class="{ 'border-primary bg-light': isDragging }">
                
                <div class="upload-content">
                    <i class="la la-cloud-upload la-3x text-muted mb-3"></i>
                    <h6 class="text-muted">{{ __('Glissez-déposez vos documents PDF ici') }}</h6>
                    <p class="text-sm text-muted mb-3">{{ __('ou') }}</p>
                    
                    <input type="file" 
                           id="context_documents" 
                           wire:model="contextDocuments"
                           multiple 
                           accept=".pdf"
                           class="d-none">
                    
                    <label for="context_documents" class="btn btn-outline-primary">
                        <i class="la la-plus"></i> {{ __('Choisir des fichiers') }}
                    </label>
                    
                    <div wire:loading wire:target="contextDocuments" class="mt-2">
                        <i class="la la-spinner la-spin"></i> {{ __('Téléchargement en cours...') }}
                    </div>
                </div>
            </div>
            
            <small class="form-text text-muted mt-2">
                <i class="la la-exclamation-triangle text-warning"></i> 
                {{ __('Formats acceptés: PDF uniquement. Taille max: 10MB par fichier') }}
            </small>
        </div>

        <!-- Liste des documents existants -->
        @if($account->getMedia('context_documents')->count() > 0)
            <div class="uploaded-documents mt-4">
                <h6 class="text-muted mb-3">
                    <i class="la la-folder"></i> {{ __('Documents ajoutés') }}
                </h6>
                
                <div class="documents-list">
                    @foreach($account->getMedia('context_documents') as $document)
                        <div class="document-item d-flex align-items-center justify-content-between p-3 border rounded mb-2">
                            <div class="document-info d-flex align-items-center">
                                <i class="la la-file-pdf la-2x text-danger mr-3"></i>
                                <div>
                                    <h6 class="mb-1">{{ $document->name }}</h6>
                                    <small class="text-muted">
                                        {{ $document->human_readable_size }} • 
                                        {{ $document->created_at->format('d/m/Y H:i') }}
                                    </small>
                                </div>
                            </div>
                            
                            <div class="document-actions">
                                <a href="{{ $document->getUrl() }}" 
                                   target="_blank"
                                   class="btn btn-sm btn-outline-info mr-2"
                                   title="{{ __('Voir le document') }}">
                                    <i class="la la-eye"></i>
                                </a>
                                
                                <button type="button" 
                                        wire:click="removeDocument({{ $document->id }})"
                                        wire:confirm="{{ __('Êtes-vous sûr de vouloir supprimer ce document ?') }}"
                                        class="btn btn-sm btn-outline-danger"
                                        title="{{ __('Supprimer') }}">
                                    <i class="la la-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Aide contextuelle -->
    <div class="context-help bg-whatsapp-light rounded p-3 mt-4">
        <h6 class="text-primary mb-2">
            <i class="la la-question-circle"></i> {{ __('Comment utiliser le contexte ?') }}
        </h6>
        <ul class="mb-0 text-sm text-muted">
            <li>{{ __('Les informations textuelles seront directement intégrées aux instructions de l\'IA') }}</li>
            <li>{{ __('Soyez précis : décrivez vos produits, services, tarifs, conditions...') }}</li>
            <li>{{ __('L\'IA utilisera ces informations pour personnaliser ses réponses selon votre activité') }}</li>
            <li>{{ __('Exemple : "Nous vendons des smartphones Samsung et Google Pixel de 100k à 950k FCFA"') }}</li>
        </ul>
    </div>
</div>

<script>
function updateCharCount(textarea) {
    const charCount = textarea.value.length;
    const charCountElement = document.getElementById('char-count');
    if (charCountElement) {
        charCountElement.textContent = charCount;
        
        // Changer la couleur selon la limite
        const label = textarea.closest('.form-group').querySelector('.form-label');
        const maxLength = parseInt(textarea.getAttribute('maxlength'));
        const warningThreshold = maxLength * 0.9; // 90%
        const dangerThreshold = maxLength * 0.95; // 95%
        
        if (charCount > dangerThreshold) {
            label.style.color = '#dc3545'; // Rouge
        } else if (charCount > warningThreshold) {
            label.style.color = '#ffc107'; // Orange
        } else {
            label.style.color = ''; // Normal
        }
    }
}
</script>
