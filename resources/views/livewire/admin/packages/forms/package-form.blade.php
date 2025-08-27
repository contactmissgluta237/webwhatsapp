<form wire:submit.prevent="save">
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="name" class="form-label">Nom technique <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                       wire:model="name" id="name" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">Identifiant unique (ex: starter, business, pro)</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="display_name" class="form-label">Nom d'affichage <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('display_name') is-invalid @enderror" 
                       wire:model="display_name" id="display_name" required>
                @error('display_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" 
                          wire:model="description" id="description" rows="2"></textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="mb-3">
                <label for="price" class="form-label">Prix normal <span class="text-danger">*</span></label>
                <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror" 
                       wire:model="price" id="price" required>
                @error('price')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-md-2">
            <div class="mb-3">
                <label for="currency" class="form-label">Devise <span class="text-danger">*</span></label>
                <select class="form-control @error('currency') is-invalid @enderror" wire:model="currency" id="currency" required>
                    <option value="XAF">XAF</option>
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                </select>
                @error('currency')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="mb-3">
                <label for="sort_order" class="form-label">Ordre d'affichage <span class="text-danger">*</span></label>
                <input type="number" class="form-control @error('sort_order') is-invalid @enderror" 
                       wire:model="sort_order" id="sort_order" required>
                @error('sort_order')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="mb-3">
                <label for="duration_days" class="form-label">Durée (jours)</label>
                <input type="number" class="form-control @error('duration_days') is-invalid @enderror" 
                       wire:model="duration_days" id="duration_days">
                @error('duration_days')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">Laissez vide si illimité</div>
            </div>
        </div>
    </div>

    <div class="card border-warning mb-4">
        <div class="card-header bg-warning bg-opacity-10">
            <h6 class="card-title mb-0">
                <i class="mdi mdi-tag-outline me-2"></i>Prix promotionnel
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="promotional_price" class="form-label">Prix promotionnel</label>
                        <input type="number" step="0.01" class="form-control @error('promotional_price') is-invalid @enderror" 
                               wire:model="promotional_price" id="promotional_price">
                        @error('promotional_price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Doit être inférieur au prix normal</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="promotion_starts_at" class="form-label">Début de promotion</label>
                        <input type="datetime-local" class="form-control @error('promotion_starts_at') is-invalid @enderror" 
                               wire:model="promotion_starts_at" id="promotion_starts_at">
                        @error('promotion_starts_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="promotion_ends_at" class="form-label">Fin de promotion</label>
                        <input type="datetime-local" class="form-control @error('promotion_ends_at') is-invalid @enderror" 
                               wire:model="promotion_ends_at" id="promotion_ends_at">
                        @error('promotion_ends_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-center">
                    <div class="form-check form-switch">
                        <input class="form-check-input @error('promotion_is_active') is-invalid @enderror" 
                               type="checkbox" role="switch" wire:model="promotion_is_active" id="promotion_is_active">
                        <label class="form-check-label" for="promotion_is_active">
                            Promotion active
                        </label>
                        @error('promotion_is_active')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            @php $preview = $this->getPromotionPreview(); @endphp
            @if($preview['show'])
                <div class="alert alert-info">
                    <strong>Aperçu :</strong> {{ $preview['text'] }}
                </div>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="mb-3">
                <label for="messages_limit" class="form-label">Limite messages <span class="text-danger">*</span></label>
                <input type="number" class="form-control @error('messages_limit') is-invalid @enderror" 
                       wire:model="messages_limit" id="messages_limit" required>
                @error('messages_limit')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="mb-3">
                <label for="context_limit" class="form-label">Limite contexte <span class="text-danger">*</span></label>
                <input type="number" class="form-control @error('context_limit') is-invalid @enderror" 
                       wire:model="context_limit" id="context_limit" required>
                @error('context_limit')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="mb-3">
                <label for="accounts_limit" class="form-label">Limite comptes <span class="text-danger">*</span></label>
                <input type="number" class="form-control @error('accounts_limit') is-invalid @enderror" 
                       wire:model="accounts_limit" id="accounts_limit" required>
                @error('accounts_limit')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-md-3">
            <div class="mb-3">
                <label for="products_limit" class="form-label">Limite produits <span class="text-danger">*</span></label>
                <input type="number" class="form-control @error('products_limit') is-invalid @enderror" 
                       wire:model="products_limit" id="products_limit" required>
                @error('products_limit')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="mb-3">
                <label class="form-label">Fonctionnalités avancées</label>
                <div class="row">
                    @foreach($availableFeatures as $key => $label)
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                       wire:model="features" value="{{ $key }}" id="feature_{{ $key }}">
                                <label class="form-check-label" for="feature_{{ $key }}">
                                    {{ $label }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="form-check form-switch mb-3">
                <input class="form-check-input @error('is_recurring') is-invalid @enderror" 
                       type="checkbox" role="switch" wire:model="is_recurring" id="is_recurring">
                <label class="form-check-label" for="is_recurring">
                    Package récurrent (mensuel)
                </label>
                @error('is_recurring')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-check form-switch mb-3">
                <input class="form-check-input @error('one_time_only') is-invalid @enderror" 
                       type="checkbox" role="switch" wire:model="one_time_only" id="one_time_only">
                <label class="form-check-label" for="one_time_only">
                    Une seule souscription autorisée
                </label>
                @error('one_time_only')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-check form-switch mb-3">
                <input class="form-check-input @error('is_active') is-invalid @enderror" 
                       type="checkbox" role="switch" wire:model="is_active" id="is_active">
                <label class="form-check-label" for="is_active">
                    Package actif
                </label>
                @error('is_active')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="hstack gap-2 justify-content-end">
                <a href="{{ route('admin.packages.index') }}" class="btn btn-light">Annuler</a>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ isset($package) ? 'Mettre à jour' : 'Créer le package' }}</span>
                    <span wire:loading>
                        <i class="spinner-border spinner-border-sm me-2" role="status"></i>
                        Traitement...
                    </span>
                </button>
            </div>
        </div>
    </div>
</form>