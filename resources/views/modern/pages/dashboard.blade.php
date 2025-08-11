@extends('modern.layouts.master')

@section('title', 'Dashboard Admin - Modern')

@push('styles')
<!-- Custom dashboard styles -->
<style>
.crypto-card-3 {
    transition: all 0.3s ease;
}
.crypto-card-3:hover {
    transform: translateY(-5px);
}
.pull-up {
    box-shadow: 0 4px 8px rgba(0,0,0,0.12), 0 2px 4px rgba(0,0,0,0.08);
}
</style>
@endpush

@section('content')
<!-- Breadcrumb start -->
<div class="content-header-left col-md-8 col-12 mb-2">
    <div class="row breadcrumbs-top">
        <div class="col-12">
            <h2 class="content-header-title float-left mb-0">Dashboard Admin</h2>
            <div class="breadcrumb-wrapper col-12">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('modern.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- Breadcrumb end -->

<!-- Metrics Component with Modern Style -->
<div class="row">
    <div class="col-12">
        @livewire('admin.dashboard.dashboard-metrics')
    </div>
</div>

<!-- System Account Balances with Modern Cards -->
<div class="row match-height">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Soldes des Comptes Système</h4>
                <a class="heading-elements-toggle"><i class="la la-ellipsis-v font-medium-3"></i></a>
            </div>
            <div class="card-content">
                <div class="card-body">
                    @livewire('admin.system-accounts.management.system-account-balances')
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Account Management -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Gestion des Comptes Système</h4>
                <a class="heading-elements-toggle"><i class="la la-ellipsis-v font-medium-3"></i></a>
                <div class="heading-elements">
                    <ul class="list-inline mb-0">
                        <li><a data-action="collapse"><i class="ft-minus"></i></a></li>
                        <li><a data-action="reload"><i class="ft-rotate-cw"></i></a></li>
                        <li><a data-action="expand"><i class="ft-maximize"></i></a></li>
                    </ul>
                </div>
            </div>
            <div class="card-content collapse show">
                <div class="card-body">
                    @livewire('admin.system-account-management')
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Analytics Section -->
<div class="row">
    <div class="col-12 col-xl-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Activité Récente</h4>
                <a class="heading-elements-toggle"><i class="la la-ellipsis-v font-medium-3"></i></a>
                <div class="heading-elements">
                    <ul class="list-inline mb-0">
                        <li class="text-center mr-4">
                            <h6 class="text-muted">Transactions Aujourd'hui</h6>
                            <p class="text-bold-600 mb-0">{{ rand(50, 200) }}</p>
                        </li>
                        <li class="text-center mr-4">
                            <h6 class="text-muted">En Attente</h6>
                            <p class="text-bold-600 mb-0">{{ rand(5, 25) }}</p>
                        </li>
                        <li class="text-center">
                            <h6 class="text-muted">Terminées</h6>
                            <p class="text-bold-600 mb-0">{{ rand(180, 250) }}</p>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card-content collapse show">
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Utilisateur</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><i class="la la-arrow-up text-success"></i> Recharge</td>
                                    <td>John Doe</td>
                                    <td class="text-success">+25,000 FCFA</td>
                                    <td><span class="badge badge-success">Terminé</span></td>
                                    <td>{{ now()->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><i class="la la-arrow-down text-danger"></i> Retrait</td>
                                    <td>Jane Smith</td>
                                    <td class="text-danger">-15,000 FCFA</td>
                                    <td><span class="badge badge-warning">En attente</span></td>
                                    <td>{{ now()->subMinutes(30)->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><i class="la la-arrow-up text-success"></i> Recharge</td>
                                    <td>Bob Johnson</td>
                                    <td class="text-success">+50,000 FCFA</td>
                                    <td><span class="badge badge-success">Terminé</span></td>
                                    <td>{{ now()->subHours(1)->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><i class="la la-arrow-down text-danger"></i> Retrait</td>
                                    <td>Alice Brown</td>
                                    <td class="text-danger">-8,000 FCFA</td>
                                    <td><span class="badge badge-danger">Échoué</span></td>
                                    <td>{{ now()->subHours(2)->format('d/m/Y H:i') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Statistiques du Jour</h4>
                <a class="heading-elements-toggle"><i class="la la-ellipsis-v font-medium-3"></i></a>
            </div>
            <div class="card-content">
                <div class="card-body">
                    <div class="media-list">
                        <!-- Nouveaux utilisateurs -->
                        <div class="media">
                            <div class="media-left align-self-center">
                                <i class="la la-user-plus icon-bg-circle bg-cyan mr-2"></i>
                            </div>
                            <div class="media-body">
                                <h6 class="media-heading">Nouveaux Utilisateurs</h6>
                                <p class="notification-text font-small-3 text-muted">{{ rand(15, 45) }} inscriptions aujourd'hui</p>
                                <small class="text-success">+{{ rand(10, 30) }}% vs hier</small>
                            </div>
                        </div>
                        <!-- Volume transactions -->
                        <div class="media mt-1">
                            <div class="media-left align-self-center">
                                <i class="la la-exchange icon-bg-circle bg-red bg-darken-1 mr-2"></i>
                            </div>
                            <div class="media-body">
                                <h6 class="media-heading">Volume Transactions</h6>
                                <p class="notification-text font-small-3 text-muted">{{ number_format(rand(500000, 2000000), 0, ',', ' ') }} FCFA traités</p>
                                <small class="text-success">+{{ rand(5, 25) }}% vs hier</small>
                            </div>
                        </div>
                        <!-- Taux de réussite -->
                        <div class="media mt-1">
                            <div class="media-left align-self-center">
                                <i class="la la-check-circle icon-bg-circle bg-green mr-2"></i>
                            </div>
                            <div class="media-body">
                                <h6 class="media-heading">Taux de Réussite</h6>
                                <p class="notification-text font-small-3 text-muted">{{ rand(95, 99) }}% des transactions réussies</p>
                                <small class="text-success">Performance excellente</small>
                            </div>
                        </div>
                        <!-- Alerte système -->
                        <div class="media mt-1">
                            <div class="media-left align-self-center">
                                <i class="la la-exclamation-triangle icon-bg-circle bg-yellow bg-darken-3 mr-2"></i>
                            </div>
                            <div class="media-body">
                                <h6 class="media-heading">Système</h6>
                                <p class="notification-text font-small-3 text-muted">Tous les services opérationnels</p>
                                <small class="text-muted">Dernière vérification: {{ now()->format('H:i') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh des métriques toutes les 30 secondes
    setInterval(function() {
        Livewire.emit('refreshMetrics');
    }, 30000);
    
    // Animation des cartes au hover
    document.querySelectorAll('.crypto-card-3').forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>
@endpush