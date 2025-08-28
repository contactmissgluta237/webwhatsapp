@extends('modern.layouts.master')

@section('title', 'Détails - ' . $package->display_name)

@section('content')
    <div class="row mx-0 mt-1 mb-1">
        <div class="content-header-left col-md-8 col-12 mb-2">
            <h3 class="content-header-title">
                <i class="la la-gift text-whatsapp mr-2"></i>
                Détails du Package
            </h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.packages.index') }}">Packages</a></li>
                        <li class="breadcrumb-item active">{{ $package->display_name }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="content-header-right col-md-4 col-12 text-right">
            <a href="{{ route('admin.packages.edit', $package->id) }}" class="btn btn-whatsapp rounded btn-glow">
                <i class="la la-edit mr-1"></i> Modifier
            </a>
            <a href="{{ route('admin.packages.index') }}" class="btn btn-outline-whatsapp ml-2">
                <i class="la la-arrow-left mr-1"></i> Retour
            </a>
        </div>
    </div>

    <div class="content-body">
        <!-- Package Info Card -->
        <div class="row">
            <div class="col-lg-8 col-md-12">
                <div class="card shadow-none border-gray-light">
                    <div class="card-header bg-white border-bottom-0">
                        <h4 class="card-title">
                            <i class="la la-info-circle mr-2 text-whatsapp"></i>Informations du Package
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-whatsapp mb-2">{{ $package->display_name }}</h5>
                                <p class="text-muted">{{ $package->description ?: 'Aucune description' }}</p>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <tbody>
                                    <tr>
                                        <td class="fw-bold text-muted" style="width: 40%;">
                                            <i class="la la-tag text-whatsapp mr-2"></i>Nom technique
                                        </td>
                                        <td class="text-dark">{{ $package->name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted">
                                            <i class="la la-comment text-whatsapp mr-2"></i>Messages autorisés
                                        </td>
                                        <td class="text-dark">{{ number_format($package->messages_limit) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted">
                                            <i class="la la-whatsapp text-whatsapp mr-2"></i>Comptes WhatsApp
                                        </td>
                                        <td class="text-dark">{{ number_format($package->accounts_limit) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted">
                                            <i class="la la-shopping-bag text-whatsapp mr-2"></i>Produits
                                        </td>
                                        <td class="text-dark">{{ number_format($package->products_limit) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted">
                                            <i class="la la-file-text text-whatsapp mr-2"></i>Contextes
                                        </td>
                                        <td class="text-dark">{{ number_format($package->context_limit) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted">
                                            <i class="la la-calendar text-whatsapp mr-2"></i>Durée (jours)
                                        </td>
                                        <td class="text-dark">{{ $package->duration_days ?? 'Illimité' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted">
                                            <i class="la la-check-circle text-whatsapp mr-2"></i>Statut
                                        </td>
                                        <td>
                                            @if($package->is_active)
                                                <span class="badge badge-success">Actif</span>
                                            @else
                                                <span class="badge badge-secondary">Inactif</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted">
                                            <i class="la la-money text-whatsapp mr-2"></i>Prix normal
                                        </td>
                                        <td class="text-dark fw-bold">{{ number_format($package->price, 0, ',', ' ') }} {{ $package->currency }}</td>
                                    </tr>
                                    @if($package->promotional_price && $package->promotion_is_active)
                                        <tr>
                                            <td class="fw-bold text-muted">
                                                <i class="la la-percent text-whatsapp mr-2"></i>Prix promotionnel
                                            </td>
                                            <td>
                                                <span class="text-whatsapp fw-bold">{{ number_format($package->promotional_price, 0, ',', ' ') }} {{ $package->currency }}</span>
                                                <span class="badge badge-success ml-2">-{{ $package->getPromotionalDiscountPercentage() }}%</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">
                                                <i class="la la-clock text-whatsapp mr-2"></i>Période promotionnelle
                                            </td>
                                            <td class="text-dark">
                                                @if($package->promotion_starts_at && $package->promotion_ends_at)
                                                    Du {{ $package->promotion_starts_at->format('d/m/Y H:i') }} au {{ $package->promotion_ends_at->format('d/m/Y H:i') }}
                                                @elseif($package->promotion_starts_at)
                                                    À partir du {{ $package->promotion_starts_at->format('d/m/Y H:i') }}
                                                @elseif($package->promotion_ends_at)
                                                    Jusqu'au {{ $package->promotion_ends_at->format('d/m/Y H:i') }}
                                                @else
                                                    Promotion permanente
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td class="fw-bold text-muted">
                                            <i class="la la-sort text-whatsapp mr-2"></i>Ordre d'affichage
                                        </td>
                                        <td class="text-dark">{{ $package->sort_order }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Card -->
            <div class="col-lg-4 col-md-12">
                <div class="card shadow-none border-gray-light">
                    <div class="card-header bg-white border-bottom-0">
                        <h4 class="card-title">
                            <i class="la la-chart-bar mr-2 text-whatsapp"></i>Statistiques
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Souscriptions</h6>
                            <h3 class="text-whatsapp mb-0">{{ $package->subscriptions->count() }}</h3>
                        </div>
                        
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Souscriptions actives</h6>
                            <h3 class="text-success mb-0">{{ $package->subscriptions->where('status', 'active')->count() }}</h3>
                        </div>

                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Créé le</h6>
                            <p class="mb-0">{{ $package->created_at->format('d/m/Y H:i') }}</p>
                        </div>

                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Dernière modification</h6>
                            <p class="mb-0">{{ $package->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Subscriptions -->
                @if($package->subscriptions->count() > 0)
                    <div class="card shadow-none border-gray-light">
                        <div class="card-header bg-white border-bottom-0">
                            <h4 class="card-title">
                                <i class="la la-users mr-2 text-whatsapp"></i>Dernières Souscriptions
                            </h4>
                        </div>
                        <div class="card-body">
                            @foreach($package->subscriptions->take(5) as $subscription)
                                <div class="media mb-3">
                                    <div class="media-body">
                                        <h6 class="media-heading mb-1">
                                            <a href="{{ route('admin.customers.show', $subscription->user_id) }}" class="text-whatsapp">
                                                {{ $subscription->user->name }}
                                            </a>
                                        </h6>
                                        <p class="text-muted mb-0 small">
                                            <i class="la la-calendar mr-1"></i>
                                            {{ $subscription->created_at->format('d/m/Y H:i') }}
                                        </p>
                                        <span class="badge badge-{{ $subscription->status === 'active' ? 'success' : 'secondary' }} small">
                                            {{ ucfirst($subscription->status) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection