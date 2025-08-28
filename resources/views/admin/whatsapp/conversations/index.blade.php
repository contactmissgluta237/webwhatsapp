@extends('modern.layouts.master')

@section('title', $pageTitle ?? 'Conversations WhatsApp')

@section('content')

<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="content-header-left col-md-6 col-12 mb-2">
        <h3 class="content-header-title text-whatsapp">{{ $pageTitle ?? 'Conversations WhatsApp' }}</h3>
        <div class="row breadcrumbs-top">
            <div class="breadcrumb-wrapper col-12">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.whatsapp.dashboard') }}">WhatsApp</a></li>
                    <li class="breadcrumb-item active">Conversations</li>
                </ol>
            </div>
        </div>
    </div>
    <div class="content-header-right col-md-6 col-12 text-right">
        @if(isset($account))
            <a href="{{ route('admin.whatsapp.accounts.show', $account) }}" class="btn btn-info rounded mr-1">
                <i class="la la-whatsapp mr-1"></i> Voir compte
            </a>
        @endif
        @if(isset($user))
            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-primary rounded mr-1">
                <i class="la la-user mr-1"></i> Voir utilisateur
            </a>
            <a href="{{ route('admin.whatsapp.accounts.index') }}?user_id={{ $user->id }}" class="btn btn-success rounded mr-1">
                <i class="la la-whatsapp mr-1"></i> Comptes WhatsApp
            </a>
        @endif
        <a href="{{ route('admin.whatsapp.dashboard') }}" class="btn btn-whatsapp rounded">
            <i class="la la-chart-bar mr-1"></i> Dashboard
        </a>
    </div>
</div>

<!-- Statistics if no specific filter -->
@if(!isset($user) && !isset($account))
<div class="row">
    <div class="col-lg-3 col-md-6 col-12">
        <div class="card border-left-primary">
            <div class="card-body">
                <div class="media d-flex align-items-center">
                    <div class="align-self-center">
                        <i class="la la-comments text-primary font-large-2 float-left"></i>
                    </div>
                    <div class="media-body text-right">
                        <h3 class="text-primary">{{ \App\Models\WhatsAppConversation::count() }}</h3>
                        <span>Total Conversations</span>
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
                        <i class="la la-robot text-success font-large-2 float-left"></i>
                    </div>
                    <div class="media-body text-right">
                        <h3 class="text-success">{{ \App\Models\WhatsAppConversation::where('is_ai_enabled', true)->count() }}</h3>
                        <span>IA Activée</span>
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
                        <i class="la la-envelope text-warning font-large-2 float-left"></i>
                    </div>
                    <div class="media-body text-right">
                        <h3 class="text-warning">{{ \App\Models\WhatsAppMessage::count() }}</h3>
                        <span>Messages Total</span>
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
                        <i class="la la-users text-info font-large-2 float-left"></i>
                    </div>
                    <div class="media-body text-right">
                        <h3 class="text-info">{{ \App\Models\WhatsAppConversation::distinct('contact_phone')->count('contact_phone') }}</h3>
                        <span>Contacts Uniques</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Conversations List -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-none border-gray-light">
            <div class="card-header bg-white border-bottom-0">
                <h4 class="card-title text-whatsapp">
                    <i class="la la-comments mr-1"></i>
                    @if(isset($user))
                        Conversations WhatsApp de {{ $user->full_name }}
                    @elseif(isset($account))
                        Conversations du compte {{ $account->session_name }}
                    @else
                        Liste des Conversations WhatsApp
                    @endif
                </h4>
            </div>
            <div class="card-body">
                <div class="app-scroll app-datatable-default">
                    @livewire('admin.whats-app.conversation-data-table', [
                        'user' => $user ?? null,
                        'account' => $account ?? null
                    ])
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