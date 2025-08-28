@extends('modern.layouts.master')

@section('title', 'Gestion des Coupons')

@section('content')
    <div class="row mx-0 mt-1 mb-1">
        <div class="content-header-left col-md-6 col-12 mb-2">
            <h3 class="content-header-title text-whatsapp">Gestion des Coupons</h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active">Coupons</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="content-header-right col-md-6 col-12 text-right">
            <div class="btn-group">
                <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="la la-chart-bar"></i> Statistiques
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#" onclick="showCouponStats()">
                        <i class="la la-eye"></i> Voir les stats
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="content-body">
        <section id="coupons-management">
            @livewire('admin.coupons.coupon-manager')
        </section>
    </div>

    <!-- Stats Modal -->
    <div class="modal fade" id="statsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="la la-chart-bar"></i> Statistiques des Coupons
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="statsContent">
                    <div class="text-center">
                        <i class="la la-spinner la-spin la-3x"></i>
                        <p class="mt-2">Chargement des statistiques...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
function showCouponStats() {
    new bootstrap.Modal(document.getElementById('statsModal')).show();
    
    // Simuler des stats (tu peux créer une vraie API plus tard)
    setTimeout(() => {
        document.getElementById('statsContent').innerHTML = `
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <i class="la la-ticket la-2x mb-2"></i>
                            <h4 class="mb-0">${Math.floor(Math.random() * 50) + 10}</h4>
                            <small>Coupons actifs</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <i class="la la-check-circle la-2x mb-2"></i>
                            <h4 class="mb-0">${Math.floor(Math.random() * 200) + 50}</h4>
                            <small>Utilisations</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <i class="la la-money la-2x mb-2"></i>
                            <h4 class="mb-0">${(Math.random() * 500000).toFixed(0)}</h4>
                            <small>Économies clients (XAF)</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <i class="la la-clock la-2x mb-2"></i>
                            <h4 class="mb-0">${Math.floor(Math.random() * 10) + 2}</h4>
                            <small>Expirant bientôt</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <h6>Coupons les plus populaires</h6>
                <div class="list-group">
                    <div class="list-group-item d-flex justify-content-between">
                        <span><code>WELCOME50</code> - 50% de réduction</span>
                        <span class="badge bg-primary">${Math.floor(Math.random() * 50) + 20} utilisations</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><code>SAVE20</code> - 20% de réduction</span>
                        <span class="badge bg-primary">${Math.floor(Math.random() * 30) + 10} utilisations</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><code>FIRST5000</code> - 5000 XAF de réduction</span>
                        <span class="badge bg-primary">${Math.floor(Math.random() * 20) + 5} utilisations</span>
                    </div>
                </div>
            </div>
        `;
    }, 1000);
}
</script>
@endsection