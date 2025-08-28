@extends('modern.layouts.master')

@section('title', 'Détails - ' . $subscription->package->name)

@section('content')
    <div class="row mx-0 mt-1 mb-1">
        <div class="content-header-left col-md-8 col-12 mb-2">
            <h3 class="content-header-title">
                <i class="la la-gift text-primary mr-2"></i>
                Détails de la souscription
            </h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('customer.subscriptions.index') }}">Mes Souscriptions</a></li>
                        <li class="breadcrumb-item active">{{ $subscription->package->name }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="content-header-right col-md-4 col-12 text-right">
            <a href="{{ route('customer.subscriptions.index') }}" class="btn btn-outline-whatsapp">
                <i class="la la-arrow-left mr-1"></i> Retour aux souscriptions
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
                                <h5 class="text-whatsapp mb-2">{{ $subscription->package->display_name }}</h5>
                                <p class="text-muted">{{ $subscription->package->description }}</p>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <tbody>
                                    <tr>
                                        <td class="fw-bold text-muted" style="width: 40%;">
                                            <i class="la la-comment text-whatsapp mr-2"></i>Messages autorisés
                                        </td>
                                        <td class="text-dark">{{ number_format($subscription->messages_limit) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted">
                                            <i class="la la-whatsapp text-whatsapp mr-2"></i>Comptes WhatsApp
                                        </td>
                                        <td class="text-dark">{{ number_format($subscription->accounts_limit) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted">
                                            <i class="la la-shopping-bag text-whatsapp mr-2"></i>Produits
                                        </td>
                                        <td class="text-dark">{{ number_format($subscription->products_limit) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted">
                                            <i class="la la-file-text text-whatsapp mr-2"></i>Contextes
                                        </td>
                                        <td class="text-dark">{{ number_format($subscription->context_limit) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted">
                                            <i class="la la-check-circle text-whatsapp mr-2"></i>Statut
                                        </td>
                                        <td>
                                            @php
                                                $status = $subscription->getCurrentStatus();
                                                $badgeClass = match($status) {
                                                    'active' => 'success',
                                                    'expired' => 'secondary',
                                                    'cancelled' => 'danger',
                                                    'suspended' => 'warning',
                                                    default => 'light'
                                                };
                                            @endphp
                                            <span class="badge badge-{{ $badgeClass }}">
                                                {{ ucfirst($status === 'active' ? 'Actif' : ($status === 'expired' ? 'Expiré' : $status)) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted">
                                            <i class="la la-money text-whatsapp mr-2"></i>Montant payé
                                        </td>
                                        <td class="text-whatsapp fw-bold">{{ number_format($subscription->amount_paid ?? 0, 0, ',', ' ') }} XAF</td>
                                    </tr>
                                    @if($subscription->payment_method)
                                        <tr>
                                            <td class="fw-bold text-muted">
                                                <i class="la la-credit-card text-whatsapp mr-2"></i>Méthode de paiement
                                            </td>
                                            <td class="text-dark">{{ ucfirst($subscription->payment_method) }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Usage Statistics -->
                <div class="card shadow-none border-gray-light">
                    <div class="card-header bg-white border-bottom-0">
                        <h4 class="card-title">
                            <i class="la la-chart-bar mr-2 text-whatsapp"></i>Utilisation des Messages
                        </h4>
                    </div>
                    <div class="card-body">
                        @php
                            $totalUsed = $subscription->getTotalMessagesUsed();
                            $totalLimit = $subscription->messages_limit;
                            $percentage = $totalLimit > 0 ? ($totalUsed / $totalLimit) * 100 : 0;
                            $remaining = $totalLimit - $totalUsed;
                        @endphp

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Messages utilisés</span>
                                <span class="text-dark font-weight-bold">{{ number_format($totalUsed) }} / {{ number_format($totalLimit) }}</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar {{ $percentage > 80 ? 'bg-danger' : ($percentage > 60 ? 'bg-warning' : 'bg-whatsapp') }}" 
                                     role="progressbar" 
                                     style="width: {{ min(100, $percentage) }}%"
                                     aria-valuenow="{{ $percentage }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                            <small class="text-muted">{{ number_format($remaining) }} messages restants</small>
                        </div>

                        @if($subscription->accountUsages->count() > 0)
                            <h6 class="mb-3">Détail par compte WhatsApp</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Compte</th>
                                            <th>Messages utilisés</th>
                                            <th>Messages dépassés</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($subscription->accountUsages as $usage)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="la la-whatsapp text-whatsapp mr-2"></i>
                                                        {{ $usage->whatsappAccount->session_name }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-primary">{{ number_format($usage->messages_used) }}</span>
                                                </td>
                                                <td>
                                                    @if($usage->overage_messages_used > 0)
                                                        <span class="badge badge-warning">{{ number_format($usage->overage_messages_used) }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Timeline Card -->
            <div class="col-lg-4 col-md-12">
                <div class="card shadow-none border-gray-light">
                    <div class="card-header bg-white border-bottom-0">
                        <h4 class="card-title">
                            <i class="la la-calendar mr-2 text-whatsapp"></i>Chronologie
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-point bg-whatsapp"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <h6 class="mb-0">Souscription créée</h6>
                                        <small class="text-muted">{{ $subscription->created_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                </div>
                            </div>

                            @if($subscription->activated_at)
                                <div class="timeline-item">
                                    <div class="timeline-point bg-whatsapp"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-header">
                                            <h6 class="mb-0">Package activé</h6>
                                            <small class="text-muted">{{ $subscription->activated_at->format('d/m/Y H:i') }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="timeline-item">
                                <div class="timeline-point {{ $subscription->isActive() ? 'bg-whatsapp' : 'bg-secondary' }}"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <h6 class="mb-0">Début de validité</h6>
                                        <small class="text-muted">{{ $subscription->starts_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                </div>
                            </div>

                            <div class="timeline-item">
                                <div class="timeline-point {{ $subscription->isExpired() ? 'bg-secondary' : 'bg-warning' }}"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <h6 class="mb-0">{{ $subscription->isExpired() ? 'Expiré le' : 'Expire le' }}</h6>
                                        <small class="text-muted">{{ $subscription->ends_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                    @if(!$subscription->isExpired())
                                        <p class="mb-0 text-whatsapp">
                                            <i class="la la-clock mr-1"></i>{{ $subscription->getRemainingDays() }} jour(s) restant(s)
                                        </p>
                                    @endif
                                </div>
                            </div>

                            @if($subscription->cancelled_at)
                                <div class="timeline-item">
                                    <div class="timeline-point bg-danger"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-header">
                                            <h6 class="mb-0">Annulé</h6>
                                            <small class="text-muted">{{ $subscription->cancelled_at->format('d/m/Y H:i') }}</small>
                                        </div>
                                        @if($subscription->cancellation_reason)
                                            <p class="mb-0 small">{{ $subscription->cancellation_reason }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e9ecef;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 30px;
    }

    .timeline-point {
        position: absolute;
        left: -22px;
        top: 5px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid white;
        z-index: 1;
    }

    .timeline-content {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        border-left: 3px solid #dee2e6;
    }

    .timeline-header h6 {
        color: #495057;
        font-weight: 600;
    }
    </style>
@endsection