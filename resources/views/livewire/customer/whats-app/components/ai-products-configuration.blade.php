<div class="products-configuration">
    <!-- Barre de recherche principale -->
    <div class="search-section mb-2">
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">                        
                        <div class="search-wrapper position-relative">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="la la-search text-muted"></i>
                                </span>
                                <input type="text" 
                                       class="form-control border-start-0 ps-0" 
                                       placeholder="Tapez le nom d'un produit pour le rechercher..."
                                       wire:model.live="searchTerm"
                                       style="font-size: 1.1rem;">
                                @if(count($this->selectedToAdd) > 0)
                                    <button type="button" 
                                            class="btn btn-whatsapp btn-lg"
                                            wire:click="addSelectedProducts">
                                        <i class="la la-plus"></i>
                                        Ajouter {{ count($this->selectedToAdd) }} produit(s)
                                    </button>
                                @endif
                            </div>
                            
                            <!-- Résultats de recherche -->
                            @if($this->searchResults->count() > 0)
                                <div class="search-results mt-3 border rounded bg-white shadow-sm" style="max-height: 300px; overflow-y: auto;">
                                    @foreach($this->searchResults as $product)
                                        <div class="search-result-item p-3 border-bottom" wire:key="search-{{ $product->id }}">
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       value="{{ $product->id }}"
                                                       wire:model.live="selectedToAdd"
                                                       id="product-{{ $product->id }}">
                                                <label class="form-check-label w-100" for="product-{{ $product->id }}">
                                                    <div class="d-flex align-items-center">
                                                        @if($product->getFirstMediaUrl('images'))
                                                            <img src="{{ $product->getFirstMediaUrl('images', 'thumb') }}" 
                                                                 alt="{{ $product->title }}"
                                                                 class="me-4 rounded"
                                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                                        @else
                                                            <div class="me-4 bg-light rounded d-flex align-items-center justify-content-center"
                                                                 style="width: 50px; height: 50px;">
                                                                <i class="la la-image text-muted"></i>
                                                            </div>
                                                        @endif
                                                        
                                                        <div class="flex-grow-1">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <h6 class="mb-1">{{ $product->title }}</h6>
                                                                <span class="badge bg-primary ms-2">{{ number_format($product->price, 0, ',', ' ') }} XAF</span>
                                                            </div>
                                                            <p class="mb-0 text-muted small">{{ Str::limit($product->description, 80) }}</p>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif(!empty($this->searchTerm))
                                <div class="text-center text-muted py-3 mt-3 border rounded bg-light">
                                    <i class="la la-search la-2x"></i>
                                    <p class="mt-2 mb-0">Aucun produit trouvé pour "{{ $this->searchTerm }}"</p>
                                </div>
                            @endif
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Produits liés (badges) -->
    <div class="linked-products-section">
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">
                                <i class="la la-check-circle text-success"></i>
                                Produits liés à cet agent IA
                            </h5>
                            <span class="badge bg-light text-dark fs-6">
                                {{ $this->linkedProducts->count() }}/10
                            </span>
                        </div>
                        
                        @if($this->linkedProducts->count() > 0)
                            <div class="products-badges-container">
                                @foreach($this->linkedProducts as $product)
                                    <div class="product-badge mb-1 border rounded bg-white" 
                                         wire:key="linked-{{ $product->id }}"
                                         style="padding: 10px; border: 1px solid #e2e7ef !important;">
                                        <div class="d-flex align-items-center">
                                            @if($product->getFirstMediaUrl('images'))
                                                <img src="{{ $product->getFirstMediaUrl('images', 'thumb') }}" 
                                                     alt="{{ $product->title }}"
                                                     class="me-4 rounded"
                                                     style="width: 60px; height: 60px; object-fit: cover;">
                                            @else
                                                <div class="me-4 bg-light rounded d-flex align-items-center justify-content-center"
                                                     style="width: 60px; height: 60px;">
                                                    <i class="la la-image text-muted la-lg"></i>
                                                </div>
                                            @endif
                                            
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <h6 class="mb-1">{{ $product->title }}</h6>
                                                    <span class="badge bg-success ms-2">{{ number_format($product->price, 0, ',', ' ') }} XAF</span>
                                                </div>
                                                <p class="mb-0 text-muted small">{{ Str::limit($product->description, 100) }}</p>
                                            </div>
                                            
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-sm ms-3"
                                                    wire:click="removeProduct({{ $product->id }})"
                                                    wire:confirm="Êtes-vous sûr de vouloir retirer ce produit ?">
                                                <i class="la la-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center text-muted py-5">
                                <i class="la la-shopping-bag la-3x"></i>
                                <h6 class="mt-3">Aucun produit lié</h6>
                                <p class="mb-0">Utilisez la barre de recherche ci-dessus pour ajouter des produits à cet agent IA</p>
                            </div>
                        @endif
                        
                        @if($this->linkedProducts->count() > 0)
                            <div class="mt-3 p-3 bg-whatsapp-light rounded">
                                <small class="text-muted">
                                    <i class="la la-info-circle"></i>
                                    Vous pouvez lier jusqu'à 10 produits par agent IA. 
                                    Il vous reste {{ 10 - $this->linkedProducts->count() }} emplacement(s).
                                </small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.search-result-item:hover {
    background-color: #f8f9fa;
}

.product-badge {
    transition: all 0.2s ease;
}

.product-badge:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.products-badges-container {
    display: grid;
    gap: 1rem;
}

@media (min-width: 768px) {
    .products-badges-container {
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    }
}

.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1000;
}
</style>
@endpush