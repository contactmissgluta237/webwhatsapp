<div class="product-form-container">
    <form wire:submit.prevent="save">
        {{-- Ligne 1: Titre et Prix --}}
        <div class="row mb-3">
            <div class="col-md-8">
                <div class="form-group">
                    <label for="title" class="form-label">{{ __('Titre du produit') }} <span
                            class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" id="title"
                        wire:model="title" placeholder="{{ __('Nom du produit') }}">
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label for="price" class="form-label">{{ __('Prix (XAF)') }} <span
                            class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('price') is-invalid @enderror" id="price"
                        wire:model="price" min="0" step="0.01" placeholder="0">
                    @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Ligne 2: Description et Statut --}}
        <div class="row mb-3">
            <div class="col-md-8">
                <div class="form-group">
                    <label for="description" class="form-label">{{ __('Description') }} <span
                            class="text-danger">*</span></label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" wire:model="description"
                        rows="4" placeholder="{{ __('Décrivez votre produit...') }}"></textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_active" wire:model="is_active">
                        <label class="form-check-label" for="is_active">
                            {{ __('Produit actif') }}
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ligne 3: Médias --}}
        <div class="row mb-3">
            <div class="col-12">
                {{-- Champ upload réutilisable --}}
                <x-media-upload-field 
                    wire-model="mediaFiles"
                    :multiple="true"
                    :max-files="$maxFiles"
                    label="Médias du produit"
                    :help-text="'Maximum ' . $maxFiles . ' fichiers, 10MB par fichier. (' . $this->getMediaFilesCount() . '/' . $maxFiles . ')'"
                    error-bag="mediaFiles.*" />

                {{-- Preview avec le composant réutilisable --}}
                @if($this->hasMediaFiles())
                    <div class="mt-3">
                        {{-- Bouton tout supprimer --}}
                        <div class="mb-2 d-flex justify-content-end">
                            <button type="button" 
                                    class="btn btn-sm btn-outline-danger"
                                    wire:click="clearAllMediaFiles"
                                    title="Supprimer tous les fichiers">
                                <i class="fas fa-trash-alt me-1"></i>
                                Tout supprimer ({{ $this->getMediaFilesCount() }})
                            </button>
                        </div>

                        <div class="d-flex flex-wrap gap-3">
                            @foreach($allMediaFiles as $index => $file)
                                <x-media-preview 
                                    :file="$file"
                                    :index="$index"
                                    wire-method="removeMediaFile"
                                    size="md" />
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Boutons d'action --}}
        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('customer.products.index') }}" class="btn btn-outline-secondary">
                <i class="la la-times mr-1"></i> {{ __('Annuler') }}
            </a>
            <button type="submit" class="btn btn-whatsapp" wire:loading.attr="disabled">
                <span wire:loading.remove>
                    <i class="la la-save mr-1"></i> {{ __('Enregistrer') }}
                </span>
                <span wire:loading>
                    <i class="la la-spinner la-spin mr-1"></i> {{ __('Enregistrement...') }}
                </span>
            </button>
        </div>
    </form>
</div>
