@extends('modern.layouts.master')

@section('title', __('Liste des sessions WhatsApp'))

@section('content')
    <div class="row mx-0 mt-1 mb-1">
        <div class="content-header-left col-md-6 col-12 mb-2">
            <h3 class="content-header-title">Mes sessions WhatsApp</h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active">Agents WhatsApp</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="content-header-right col-md-6 col-12 text-right">
            <a href="{{ route('whatsapp.create') }}" class="btn btn-whatsapp rounded btn-glow">
                <i class="la la-plus mr-1"></i> {{ __('Créer un nouveau agent IA') }}
            </a>
        </div>
    </div>

    <div class="content-body">
        <section id="whatsapp-sessions-list">
            <div class="card shadow-none border-gray-light">
                <div class="card-body">
                    @livewire('customer.whats-app.whats-app-account-data-table')
                </div>
            </div>
        </section>
    </div>
@endsection

@push('styles')
<style>
/* Optimisation des DataTables WhatsApp */
#whatsapp-sessions-list .table {
    margin-bottom: 0;
}

#whatsapp-sessions-list .table td {
    border-top: 1px solid #e9ecef;
    vertical-align: middle !important;
    padding: 0.75rem 0.75rem !important;
}

#whatsapp-sessions-list .table tr {
    height: auto !important;
    min-height: 50px;
}

/* Optimisation des dropdowns dans les tableaux */
#whatsapp-sessions-list .dropdown {
    position: static !important;
}

#whatsapp-sessions-list .dropdown-menu {
    position: absolute !important;
    will-change: transform;
    z-index: 1050 !important;
    margin-top: 0.125rem !important;
}

/* Empêcher les débordements sur les petites colonnes */
#whatsapp-sessions-list .table .badge {
    font-size: 0.75rem;
    white-space: nowrap;
}

#whatsapp-sessions-list .table small {
    font-size: 0.7rem;
    line-height: 1.1;
}

/* Réduire la hauteur des lignes avec contenu multi-ligne */
#whatsapp-sessions-list .d-flex.flex-column {
    min-height: auto !important;
    gap: 0.1rem;
}

/* Style pour les boutons du dropdown */
#whatsapp-sessions-list .dropdown-item button {
    all: unset;
    width: 100%;
    text-align: left;
    padding: 0.375rem 1rem;
    display: block;
    cursor: pointer;
}

#whatsapp-sessions-list .dropdown-item button:focus {
    outline: none;
}
</style>
@endpush