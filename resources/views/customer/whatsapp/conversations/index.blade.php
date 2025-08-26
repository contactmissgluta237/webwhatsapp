@extends('modern.layouts.master')

@section('title', __('Conversations') . ' - ' . $account->session_name)

@section('content')
    <div class="row mx-0 mt-1 mb-1">
        <div class="content-header-left col-md-6 col-12 mb-2">
            <h3 class="content-header-title">
                Conversations - {{ $account->session_name }}
            </h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('customer.whatsapp.index') }}">Agents WhatsApp</a></li>
                        <li class="breadcrumb-item active">Conversations</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="content-header-right col-md-6 col-12 text-right">
            <a href="{{ route('whatsapp.index') }}" class="btn btn-outline-whatsapp">
                <i class="la la-arrow-left"></i> {{ __('Retour aux comptes') }}
            </a>
        </div>
    </div>

    <div class="content-body">
        <section id="whatsapp-conversations-list">
            {{-- Informations du compte --}}
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card shadow-none border-gray-light">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="mr-3">
                                        @if($account->isConnected())
                                            <span class="badge badge-success badge-lg">
                                                <i class="la la-check"></i> Connecté
                                            </span>
                                        @else
                                            <span class="badge badge-secondary badge-lg">
                                                <i class="la la-times"></i> Déconnecté
                                            </span>
                                        @endif
                                    </div>
                                    <div>
                                        <h5 class="mb-1">{{ $account->session_name }}</h5>
                                        <p class="mb-0 text-muted">
                                            {{ $account->phone_number ?: 'Non connecté' }}
                                            @if($account->hasAiAgent())
                                                | <i class="la la-robot text-success"></i> IA Activée
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <small class="text-muted">
                                        {{ $account->getTotalConversations() }} conversation(s) au total<br>
                                        {{ $account->getActiveConversations() }} active(s) cette semaine
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- DataTable des conversations --}}
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-none border-gray-light">
                        <div class="card-body">
                            @livewire('customer.whats-app.conversation-data-table')
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection