@extends('modern.layouts.app')

@section('title', 'Gestion des Packages')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Gestion des Packages</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="#">Souscriptions</a></li>
                        <li class="breadcrumb-item active">Packages</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Liste des packages</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Package</th>
                                    <th>Prix</th>
                                    <th>Durée</th>
                                    <th>Messages</th>
                                    <th>Contexte</th>
                                    <th>Comptes</th>
                                    <th>Produits</th>
                                    <th>Fonctionnalités</th>
                                    <th>Souscriptions</th>
                                    <th>Statut</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($packages as $package)
                                <tr>
                                    <td>{{ $package->sort_order }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($package->name === 'business')
                                                <span class="badge bg-primary me-2">Recommandé</span>
                                            @endif
                                            <div>
                                                <strong>{{ $package->display_name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $package->description }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($package->price > 0)
                                            <strong>{{ number_format($package->price) }} XAF</strong>
                                        @else
                                            <span class="badge bg-success">GRATUIT</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $package->duration_days }} jour{{ $package->duration_days > 1 ? 's' : '' }}
                                        @if($package->is_recurring)
                                            <br><small class="text-primary">Récurrent</small>
                                        @endif
                                        @if($package->one_time_only)
                                            <br><small class="text-warning">Une seule fois</small>
                                        @endif
                                    </td>
                                    <td>{{ number_format($package->messages_limit) }}</td>
                                    <td>{{ number_format($package->context_limit) }}</td>
                                    <td>{{ $package->accounts_limit }}</td>
                                    <td>
                                        @if($package->products_limit > 0)
                                            {{ $package->products_limit }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($package->features && count($package->features) > 0)
                                            @foreach($package->features as $feature)
                                                <span class="badge bg-info me-1">
                                                    @if($feature === 'weekly_reports')
                                                        Rapports hebdo.
                                                    @elseif($feature === 'priority_support')
                                                        Support prioritaire
                                                    @else
                                                        {{ $feature }}
                                                    @endif
                                                </span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $package->subscriptions_count }}</span>
                                    </td>
                                    <td>
                                        @if($package->is_active)
                                            <span class="badge bg-success">Actif</span>
                                        @else
                                            <span class="badge bg-danger">Inactif</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.subscriptions.index', ['package_id' => $package->id]) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Voir les souscriptions">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="12" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="mdi mdi-package-variant-closed mdi-48px"></i>
                                            <p class="mt-2">Aucun package trouvé</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table td {
    vertical-align: middle;
    padding: 12px 8px;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.badge {
    font-size: 0.75rem;
}

.btn-group .btn {
    border-radius: 4px !important;
    margin: 0 2px;
}
</style>
@endsection