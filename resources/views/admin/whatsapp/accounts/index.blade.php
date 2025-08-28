@extends('modern.layouts.master')

@section('title', $pageTitle ?? 'Comptes WhatsApp')

@section('content')

<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="content-header-left col-md-6 col-12 mb-2">
        <h3 class="content-header-title text-whatsapp">{{ $pageTitle ?? 'Comptes WhatsApp' }}</h3>
        <div class="row breadcrumbs-top">
            <div class="breadcrumb-wrapper col-12">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.whatsapp.dashboard') }}">WhatsApp</a></li>
                    <li class="breadcrumb-item active">Comptes</li>
                </ol>
            </div>
        </div>
    </div>
    <div class="content-header-right col-md-6 col-12 text-right">
        @if(isset($user))
            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-info rounded mr-1">
                <i class="la la-user mr-1"></i> Voir utilisateur
            </a>
        @endif
        <a href="{{ route('admin.whatsapp.dashboard') }}" class="btn btn-whatsapp rounded">
            <i class="la la-chart-bar mr-1"></i> Dashboard
        </a>
    </div>
</div>

<!-- Statistiques rapides -->
@if(!isset($user))
<div class="row">
    <div class="col-lg-3 col-md-6 col-12">
        <div class="card border-left-primary">
            <div class="card-body">
                <div class="media d-flex align-items-center">
                    <div class="align-self-center">
                        <i class="la la-whatsapp text-primary font-large-2 float-left"></i>
                    </div>
                    <div class="media-body text-right">
                        <h3 class="text-primary">{{ \App\Models\WhatsAppAccount::count() }}</h3>
                        <span>Total Comptes</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 col-12">
        <div class="card border-left-success">
            <div class="card-body">
                <div class="media d-flex align-items-center">
                    <div class="align-self-center">
                        <i class="la la-check-circle text-success font-large-2 float-left"></i>
                    </div>
                    <div class="media-body text-right">
                        <h3 class="text-success">{{ \App\Models\WhatsAppAccount::where('status', 'connected')->count() }}</h3>
                        <span>Connectés</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 col-12">
        <div class="card border-left-warning">
            <div class="card-body">
                <div class="media d-flex align-items-center">
                    <div class="align-self-center">
                        <i class="la la-robot text-warning font-large-2 float-left"></i>
                    </div>
                    <div class="media-body text-right">
                        <h3 class="text-warning">{{ \App\Models\WhatsAppAccount::where('agent_enabled', true)->count() }}</h3>
                        <span>Agents IA Actifs</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 col-12">
        <div class="card border-left-info">
            <div class="card-body">
                <div class="media d-flex align-items-center">
                    <div class="align-self-center">
                        <i class="la la-comments text-info font-large-2 float-left"></i>
                    </div>
                    <div class="media-body text-right">
                        <h3 class="text-info">{{ \App\Models\WhatsAppConversation::count() }}</h3>
                        <span>Conversations</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- WhatsApp Accounts List -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-none border-gray-light">
            <div class="card-header bg-white border-bottom-0">
                <h4 class="card-title text-whatsapp">
                    <i class="la la-whatsapp mr-1"></i>
                    {{ isset($user) ? 'Comptes WhatsApp de ' . $user->full_name : 'Liste des Comptes WhatsApp' }}
                </h4>
            </div>
            <div class="card-body">
                <div class="app-scroll app-datatable-default">
                    @livewire('admin.whats-app.whats-app-account-data-table', ['user' => $user ?? null])
                </div>
            </div>  
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.card.border-left-primary {
    border-left: 3px solid #007bff;
}
.card.border-left-success {
    border-left: 3px solid #28a745;
}
.card.border-left-warning {
    border-left: 3px solid #ffc107;
}
.card.border-left-info {
    border-left: 3px solid #17a2b8;
}
</style>
@endsection