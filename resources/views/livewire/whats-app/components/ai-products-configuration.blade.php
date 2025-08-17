<div class="ai-products-configuration">
    <div class="row">
        <!-- Section des produits liés -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="la la-link text-primary"></i>
                        {{ __('Produits liés à l\'agent') }}
                    </h6>
                    <span class="badge badge-primary">
                        {{ $this->linkedProducts->count() }}/10
                    </span>
                </div>
                <div class="card-body">
                    @if($this->linkedProducts->count() > 0)
                        <div class="linked-products-list">
                            @foreach($this->linkedProducts as $product)
                                <div class="product-item d-flex align-items-center justify-content-between mb-3 p-3 border rounded">
                                    <div class="d-flex align-items-center">
                                        @if($product->hasImages())
                                            <img src="{{ $product->getMainImageUrl() }}" 
                                                 alt="{{ $product->title }}"
                                                 class="rounded me-3" 
                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                        @else
                                            <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" 
                                                 style="width: 50px; height: 50px;">
                                                <i class="la la-image text-muted"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <h6 class="mb-1">{{ $product->title }}</h6>
                                            <small class="text-muted">{{ $product->getFormattedPrice() }}</small>
                                        </div>
                                    </div>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger"
                                            wire:click="removeProduct({{ $product->id }})"
                                            title="{{ __('Retirer ce produit') }}">
                                        <i class="la la-times"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="la la-box text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2 mb-0">{{ __('Aucun produit lié') }}</p>
                            <small class="text-muted">{{ __('Ajoutez des produits pour que l\'IA puisse les proposer') }}</small>
                        </div>
                    @endif

                    @if(!$this->canAddMoreProducts)
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="la la-exclamation-triangle"></i>
                            {{ __('Limite atteinte: 10 produits maximum par agent IA') }}
                        </div>
                    @else
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="la la-info-circle"></i>
                            {{ __('Vous pouvez encore ajouter :count produit(s)', ['count' => $this->remainingSlots]) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Section de recherche et ajout -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="la la-search text-success"></i>
                        {{ __('Ajouter des produits') }}
                    </h6>
                </div>
                <div class="card-body">
                    <!-- Barre de recherche -->
                    <div class="form-group mb-3">
                        <input type="text" 
                               class="form-control" 
                               wire:model.live.debounce.300ms="searchTerm"
                               placeholder="{{ __('Rechercher un produit...') }}">
                    </div>

                    <!-- Liste des produits disponibles -->
                    <div class="available-products-list" style="max-height: 400px; overflow-y: auto;">
                        @forelse($this->availableProducts as $product)
                            <div class="product-item d-flex align-items-center justify-content-between mb-3 p-3 border rounded">
                                <div class="d-flex align-items-center">
                                    @if($product->hasImages())
                                        <img src="{{ $product->getMainImageUrl() }}" 
                                             alt="{{ $product->title }}"
                                             class="rounded me-3" 
                                             style="width: 50px; height: 50px; object-fit: cover;">
                                    @else
                                        <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" 
                                             style="width: 50px; height: 50px;">
                                            <i class="la la-image text-muted"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <h6 class="mb-1">{{ $product->title }}</h6>
                                        <small class="text-muted">{{ $product->getFormattedPrice() }}</small>
                                        <br>
                                        <small class="text-muted">{{ Str::limit($product->description, 50) }}</small>
                                    </div>
                                </div>
                                
                                @if($this->linkedProducts->contains('id', $product->id))
                                    <span class="badge badge-success">
                                        <i class="la la-check"></i> {{ __('Lié') }}
                                    </span>
                                @elseif($this->canAddMoreProducts)
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-success"
                                            wire:click="addProduct({{ $product->id }})"
                                            title="{{ __('Ajouter ce produit') }}">
                                        <i class="la la-plus"></i>
                                    </button>
                                @else
                                    <span class="badge badge-secondary">
                                        {{ __('Limite atteinte') }}
                                    </span>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-4">
                                @if(empty($searchTerm))
                                    <i class="la la-box text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-2 mb-2">{{ __('Aucun produit disponible') }}</p>
                                    <a href="{{ route('customer.products.index') }}" class="btn btn-primary btn-sm">
                                        <i class="la la-plus"></i> {{ __('Créer un produit') }}
                                    </a>
                                @else
                                    <i class="la la-search text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-2 mb-0">{{ __('Aucun produit trouvé') }}</p>
                                    <small class="text-muted">{{ __('Essayez avec d\'autres mots-clés') }}</small>
                                @endif
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section d'aide -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h6 class="alert-heading">
                    <i class="la la-lightbulb"></i>
                    {{ __('Comment ça fonctionne ?') }}
                </h6>
                <ul class="mb-0">
                    <li>{{ __('Liez jusqu\'à 10 produits à votre agent IA') }}</li>
                    <li>{{ __('L\'IA pourra proposer ces produits aux clients lors des conversations') }}</li>
                    <li>{{ __('Les produits incluront automatiquement les images, prix et descriptions') }}</li>
                    <li>{{ __('Seuls les produits actifs peuvent être liés') }}</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.ai-products-configuration .product-item {
    transition: all 0.2s ease;
}

.ai-products-configuration .product-item:hover {
    background-color: #f8f9fa;
    border-color: #007bff !important;
}

.ai-products-configuration .available-products-list::-webkit-scrollbar {
    width: 6px;
}

.ai-products-configuration .available-products-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.ai-products-configuration .available-products-list::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.ai-products-configuration .available-products-list::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}
</style>
@endpush