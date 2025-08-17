<div class="product-manager-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>{{ __('Mes Produits') }}</h4>
        <button type="button" class="btn btn-primary" wire:click="openCreateForm">
            <i class="la la-plus"></i> {{ __('Ajouter un produit') }}
        </button>
    </div>

    @if($showForm)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    {{ $editingProduct ? __('Modifier le produit') : __('Nouveau produit') }}
                </h5>
            </div>
            <div class="card-body">
                <form wire:submit.prevent="save">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="title" class="form-label">{{ __('Titre') }} <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('title') is-invalid @enderror" 
                                       id="title" 
                                       wire:model="title"
                                       placeholder="{{ __('Nom du produit') }}">
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="price" class="form-label">{{ __('Prix (XAF)') }} <span class="text-danger">*</span></label>
                                <input type="number" 
                                       class="form-control @error('price') is-invalid @enderror" 
                                       id="price" 
                                       wire:model="price"
                                       min="0"
                                       step="0.01"
                                       placeholder="0">
                                @error('price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="description" class="form-label">{{ __('Description') }} <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  wire:model="description"
                                  rows="4"
                                  placeholder="{{ __('Description détaillée du produit') }}"></textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="images" class="form-label">{{ __('Images') }} ({{ __('max 5 images') }})</label>
                        <input type="file" 
                               class="form-control @error('images.*') is-invalid @enderror" 
                               id="images" 
                               wire:model="images"
                               multiple
                               accept="image/*">
                        @error('images.*')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">{{ __('Formats acceptés: JPG, PNG. Taille max: 2MB par image.') }}</small>
                    </div>

                    @if($editingProduct && $editingProduct->hasImages())
                        <div class="mb-3">
                            <label class="form-label">{{ __('Images actuelles') }}</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($editingProduct->getMedia('images') as $media)
                                    <div class="position-relative">
                                        <img src="{{ $media->getUrl() }}" 
                                             alt="{{ __('Image produit') }}" 
                                             class="img-thumbnail" 
                                             style="width: 100px; height: 100px; object-fit: cover;">
                                        <button type="button" 
                                                class="btn btn-sm btn-danger position-absolute top-0 end-0"
                                                style="transform: translate(25%, -25%); border-radius: 50%; width: 25px; height: 25px; padding: 0;"
                                                wire:click="removeImage({{ $editingProduct->id }}, {{ $media->id }})"
                                                title="{{ __('Supprimer cette image') }}">
                                            <i class="la la-times" style="font-size: 12px;"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="form-check mb-3">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="is_active" 
                               wire:model="is_active">
                        <label class="form-check-label" for="is_active">
                            {{ __('Produit actif') }}
                        </label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="la la-save"></i> {{ __('Enregistrer') }}
                        </button>
                        <button type="button" class="btn btn-secondary" wire:click="cancelEdit">
                            <i class="la la-times"></i> {{ __('Annuler') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="row">
        @forelse($this->products as $product)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    @if($product->hasImages())
                        <img src="{{ $product->getMainImageUrl() }}" 
                             class="card-img-top" 
                             alt="{{ $product->title }}"
                             style="height: 200px; object-fit: cover;">
                    @else
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                             style="height: 200px;">
                            <i class="la la-image text-muted" style="font-size: 3rem;"></i>
                        </div>
                    @endif
                    
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0">{{ $product->title }}</h5>
                            <span class="badge badge-{{ $product->is_active ? 'success' : 'secondary' }}">
                                {{ $product->is_active ? __('Actif') : __('Inactif') }}
                            </span>
                        </div>
                        
                        <p class="card-text text-muted small mb-2">{{ Str::limit($product->description, 100) }}</p>
                        
                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h6 text-primary mb-0">{{ $product->getFormattedPrice() }}</span>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" 
                                            class="btn btn-outline-primary" 
                                            wire:click="edit({{ $product->id }})"
                                            title="{{ __('Modifier') }}">
                                        <i class="la la-edit"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-outline-{{ $product->is_active ? 'warning' : 'success' }}" 
                                            wire:click="toggleStatus({{ $product->id }})"
                                            title="{{ $product->is_active ? __('Désactiver') : __('Activer') }}">
                                        <i class="la la-{{ $product->is_active ? 'eye-slash' : 'eye' }}"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-outline-danger" 
                                            wire:click="delete({{ $product->id }})"
                                            onclick="return confirm('{{ __('Êtes-vous sûr de vouloir supprimer ce produit ?') }}')"
                                            title="{{ __('Supprimer') }}">
                                        <i class="la la-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="la la-box text-muted" style="font-size: 4rem;"></i>
                    <h5 class="text-muted mt-3">{{ __('Aucun produit') }}</h5>
                    <p class="text-muted">{{ __('Commencez par créer votre premier produit') }}</p>
                    <button type="button" class="btn btn-primary" wire:click="openCreateForm">
                        <i class="la la-plus"></i> {{ __('Créer un produit') }}
                    </button>
                </div>
            </div>
        @endforelse
    </div>
</div>

@push('styles')
<style>
.product-manager-container .card-img-top {
    border-bottom: 1px solid #dee2e6;
}
</style>
@endpush