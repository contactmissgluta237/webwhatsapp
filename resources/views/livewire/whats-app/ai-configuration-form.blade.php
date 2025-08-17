@push('styles')
    <link href="{{ asset('css/ai-configuration.css') }}" rel="stylesheet">
@endpush

<div class="ai-configuration-container">
    <form wire:submit.prevent="save" class="ai-configuration-form">
        
        <!-- Navigation par onglets -->
        <div class="card-header p-0">
            <ul class="nav nav-tabs nav-top-border no-hover-bg" id="configTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" 
                            id="basic-tab" 
                            data-toggle="tab" 
                            data-target="#basic" 
                            type="button" 
                            role="tab" 
                            aria-controls="basic" 
                            aria-selected="true">
                        <i class="la la-info-circle"></i>
                        {{ __('Informations de base') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" 
                            id="contexts-tab" 
                            data-toggle="tab" 
                            data-target="#contexts" 
                            type="button" 
                            role="tab" 
                            aria-controls="contexts" 
                            aria-selected="false">
                        <i class="la la-file-text"></i>
                        {{ __('Contextes') }}
                        @if($account->getMedia('context_documents')->count() > 0)
                            <span class="badge badge-primary ml-1">{{ $account->getMedia('context_documents')->count() }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" 
                            id="advanced-tab" 
                            data-toggle="tab" 
                            data-target="#advanced" 
                            type="button" 
                            role="tab" 
                            aria-controls="advanced" 
                            aria-selected="false">
                        <i class="la la-cogs"></i>
                        {{ __('Configurations avancées') }}
                        @if($agent_enabled)
                            <span class="badge badge-success ml-1">{{ __('Actif') }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" 
                            id="products-tab" 
                            data-toggle="tab" 
                            data-target="#products" 
                            type="button" 
                            role="tab" 
                            aria-controls="products" 
                            aria-selected="false">
                        <i class="la la-box"></i>
                        {{ __('Produits') }}
                        @if($account->linkedProducts()->count() > 0)
                            <span class="badge badge-primary ml-1">{{ $account->linkedProducts()->count() }}</span>
                        @endif
                    </button>
                </li>
            </ul>
        </div>
        
        <div class="card-body">
            <div class="tab-content" id="configTabsContent">
                
                <!-- Onglet: Informations de base -->
                <div class="tab-pane fade show active" 
                     id="basic" 
                     role="tabpanel" 
                     aria-labelledby="basic-tab">
                    @include('livewire.whats-app.tabs.basic-information')
                </div>
                
                <!-- Onglet: Contextes -->
                <div class="tab-pane fade" 
                     id="contexts" 
                     role="tabpanel" 
                     aria-labelledby="contexts-tab">
                    @include('livewire.whats-app.tabs.contexts')
                </div>
                
                <!-- Onglet: Configurations avancées -->
                <div class="tab-pane fade" 
                     id="advanced" 
                     role="tabpanel" 
                     aria-labelledby="advanced-tab">
                    @include('livewire.whats-app.tabs.advanced-settings')
                </div>
                
                <!-- Onglet: Produits -->
                <div class="tab-pane fade" 
                     id="products" 
                     role="tabpanel" 
                     aria-labelledby="products-tab">
                    @include('livewire.whats-app.tabs.products')
                </div>
                
            </div>
        </div>
        
        <!-- Bouton de sauvegarde global -->
        <div class="card-footer">
            <div class="row align-items-center">
                <div class="col-12">
                    {{-- Text moved to parent view --}}
                </div>
                <div class="col-12 text-right">
                    <button type="submit" 
                            class="btn btn-whatsapp btn-lg" 
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
        </div>
    </form>
</div>
