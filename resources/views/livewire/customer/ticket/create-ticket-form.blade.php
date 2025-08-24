<form wire:submit.prevent="createTicket">
    <div class="mb-4">
        <label for="title" class="form-label fw-semibold">{{ __('Sujet du ticket') }}</label>
        <input type="text" class="form-control @error('title') is-invalid @enderror" 
               id="title" wire:model="title" 
               placeholder="{{ __('Résumez votre demande en quelques mots') }}">
        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-4">
        <label for="description" class="form-label fw-semibold">{{ __('Description détaillée') }}</label>
        <textarea class="form-control @error('description') is-invalid @enderror" 
                  id="description" rows="6" wire:model="description"
                  placeholder="{{ __('Décrivez votre problème ou votre demande de manière détaillée...') }}"></textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-4">
        <label for="attachments" class="form-label fw-semibold">
            {{ __('Pièces jointes') }} <span class="text-muted small">({{ __('Optionnel') }})</span>
        </label>
        <div class="card border-gray-light shadow-none">
            <div class="card-body">
                <input type="file" class="form-control @error('attachments.*') is-invalid @enderror" 
                       id="attachments" wire:model="attachments" multiple 
                       accept=".jpg,.jpeg,.png,.pdf">
                <div class="form-text mt-2">
                    <i class="la la-info-circle me-1"></i>
                    {{ __('Taille max: 2MB par fichier. Formats acceptés: JPG, PNG, PDF') }}
                </div>
                @error('attachments.*') <div class="invalid-feedback">{{ $message }}</div> @enderror
                
                {{-- Preview simple des fichiers sélectionnés --}}
                @if ($attachments)
                    <div class="mt-3">
                        <small class="text-muted fw-semibold">{{ __('Fichiers sélectionnés:') }}</small>
                        <div class="mt-2">
                            @foreach($attachments as $attachment)
                                <div class="d-flex align-items-center mb-2 p-2 bg-light rounded">
                                    <i class="la la-file-o me-2 text-muted"></i>
                                    <span class="small">{{ $attachment->getClientOriginalName() }}</span>
                                    @if ($attachment->temporaryUrl())
                                        <div class="ms-auto">
                                            @if (str_contains($attachment->getMimeType(), 'image'))
                                                <img src="{{ $attachment->temporaryUrl() }}" alt="Preview" class="img-thumbnail" style="max-width: 50px; max-height: 50px;">
                                            @elseif (str_contains($attachment->getMimeType(), 'pdf'))
                                                <i class="la la-file-pdf-o fs-3 text-danger"></i>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($this->attachmentPreviewUrls)
                    <div class="mt-3 d-flex flex-wrap gap-2">
                        @foreach($this->attachmentPreviewUrls as $url)
                            <img src="{{ $url }}" class="img-thumbnail" style="max-width: 80px; max-height: 80px;">
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Boutons d'action -->
    <div class="d-flex flex-column flex-md-row gap-3 justify-content-between align-items-center mt-4">
        <a href="{{ route('customer.tickets.index') }}" class="btn btn-outline-secondary">
            <i class="la la-arrow-left me-2"></i>
            {{ __('Annuler') }}
        </a>
        
        <button type="submit" class="btn btn-whatsapp btn-lg px-4 py-2 fw-bold" wire:loading.attr="disabled">
            <span wire:loading.remove>
                <i class="la la-paper-plane me-2"></i>
                {{ __('Envoyer le ticket') }}
            </span>
            <span wire:loading>
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                {{ __('Envoi en cours...') }}
            </span>
        </button>
    </div>
</form>
