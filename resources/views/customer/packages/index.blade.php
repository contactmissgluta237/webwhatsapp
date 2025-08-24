@extends('modern.layouts.master')

@section('title', 'Packages disponibles')

@section('content')
    <div class="row mx-0 mt-1 mb-1">
        <div class="content-header-left col-md-6 col-12 mb-2">
            <h3 class="content-header-title text-whatsapp">Packages disponibles</h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active">Packages</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content-body">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Succès!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Erreur!</strong> {{ session('error') }}
            @if(session('recharge_needed'))
                <br><br>
                <a href="{{ route('customer.wallet.index') }}" class="btn btn-warning btn-sm">
                    <i class="la la-credit-card-plus"></i> Recharger mon wallet
                </a>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if($currentSubscription)
        <div class="alert alert-info" role="alert">
            <strong>Abonnement actuel:</strong> {{ $currentSubscription->package->display_name }} 
            - Expire le {{ $currentSubscription->ends_at->format('d/m/Y') }}
            - {{ $currentSubscription->getRemainingMessages() }} messages restants
        </div>
        @endif

        <section id="packages-list">
            <div class="row">
                @foreach($packages as $package)
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card pricing-card h-100 package-{{ $package->name }}">
                        
                        <div class="card-body text-center">
                            <div class="pricing-plan-title">
                                <h5 class="package-title-{{ $package->name }}">{{ $package->display_name }}</h5>
                                @if($package->isTrial())
                                    <div class="pricing-price">
                                        <span class="price-currency-{{ $package->name }}">GRATUIT</span>
                                        <div class="pricing-duration">7 jours</div>
                                    </div>
                                @else
                                    <div class="pricing-price">
                                        <span class="price-currency-{{ $package->name }}">{{ number_format($package->price) }}</span> <span class="price-currency-{{ $package->name }}">XAF</span>
                                        <div class="pricing-duration">/ mois</div>
                                    </div>
                                @endif
                            </div>

                            <div class="text-muted pricing-description">
                                {{ $package->description }}
                            </div>

                            <ul class="list-unstyled pricing-features mt-4">
                                <li><i class="la la-check text-success me-2"></i>{{ number_format($package->messages_limit) }} messages</li>
                                <li><i class="la la-check text-success me-2"></i>{{ number_format($package->context_limit) }} tokens de contexte</li>
                                <li><i class="la la-check text-success me-2"></i>{{ $package->accounts_limit }} compte{{ $package->accounts_limit > 1 ? 's' : '' }} WhatsApp</li>
                                
                                @if($package->products_limit > 0)
                                    <li><i class="la la-check text-success me-2"></i>{{ $package->products_limit }} produit{{ $package->products_limit > 1 ? 's' : '' }}</li>
                                @else
                                    <li><i class="la la-times text-muted me-2"></i>Pas de produits</li>
                                @endif

                                @if($package->hasWeeklyReports())
                                    <li><i class="la la-check text-success me-2"></i>Rapports hebdomadaires</li>
                                @endif

                                @if($package->hasPrioritySupport())
                                    <li><i class="la la-check text-success me-2"></i>Support prioritaire</li>
                                @endif
                            </ul>
                        </div>

                        <div class="card-footer text-center">
                            @if($currentSubscription)
                                @if($currentSubscription->package_id === $package->id)
                                    <button class="btn btn-package-{{ $package->name }} w-100" disabled>
                                        <i class="la la-check-circle"></i> Abonnement actuel
                                    </button>
                                @else
                                    <button class="btn btn-outline-secondary w-100" disabled>
                                        Abonnement en cours
                                    </button>
                                @endif
                            @else
                                @php
                                    $hasUsedTrial = auth()->user()->subscriptions()
                                        ->whereHas('package', fn($q) => $q->where('name', 'trial'))
                                        ->exists();
                                @endphp
                                @if($package->isTrial() && $hasUsedTrial)
                                    <button class="btn btn-outline-secondary w-100" disabled>
                                        Essai déjà utilisé
                                    </button>
                                @else
                                    <form method="POST" action="{{ route('customer.packages.subscribe', $package) }}" 
                                          onsubmit="return confirm('Êtes-vous sûr de vouloir souscrire à ce package ?')">
                                        @csrf
                                        <button type="submit" class="btn btn-package-{{ $package->name }} w-100">
                                            @if($package->isTrial())
                                                <i class="la la-gift"></i> Commencer l'essai
                                            @else
                                                <i class="la la-credit-card"></i> Souscrire
                                            @endif
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </section>
    </div>

<style>
.pricing-card {
    position: relative;
    border-radius: 8px;
    border: 1px solid transparent;
    transition: transform 0.2s;
}

.pricing-card:hover {
    transform: translateY(-2px);
}

/* Package Trial - Bleu léger */
.package-trial {
    border-color: #5DADE2;
}

.package-title-trial {
    color: #2E86C1 !important;
}

.price-currency-trial {
    color: #2E86C1;
}

.btn-package-trial {
    background-color: #5DADE2;
    border-color: #5DADE2;
    color: white;
}

.btn-package-trial:hover {
    background-color: #2E86C1;
    border-color: #2E86C1;
}

/* Package Starter - Rouge */
.package-starter {
    border-color: #E74C3C;
}

.package-title-starter {
    color: #C0392B !important;
}

.price-currency-starter {
    color: #C0392B;
}

.btn-package-starter {
    background-color: #E74C3C;
    border-color: #E74C3C;
    color: white;
}

.btn-package-starter:hover {
    background-color: #C0392B;
    border-color: #C0392B;
}

/* Package Pro - Vert foncé */
.package-pro {
    border-color: #1E8449;
}

.package-title-pro {
    color: #186A3B !important;
}

.price-currency-pro {
    color: #186A3B;
}

.btn-package-pro {
    background-color: #1E8449;
    border-color: #1E8449;
    color: white;
}

.btn-package-pro:hover {
    background-color: #186A3B;
    border-color: #186A3B;
}

/* Package Business - Vert WhatsApp */
.package-business {
    border-color: #25D366;
}

.package-title-business {
    color: #1DAA48 !important;
}

.price-currency-business {
    color: #1DAA48;
}

.btn-package-business {
    background-color: #25D366;
    border-color: #25D366;
    color: white;
}

.btn-package-business:hover {
    background-color: #1DAA48;
    border-color: #1DAA48;
}


.pricing-price {
    font-size: 2rem;
    font-weight: 700;
    margin: 20px 0 10px;
}

.pricing-duration {
    font-size: 0.9rem;
    color: #6c757d;
    font-weight: 400;
}

.pricing-features li {
    padding: 8px 0;
    border-bottom: 1px solid #f8f9fa;
}

.pricing-features li:last-child {
    border-bottom: none;
}
</style>
@endsection