@extends('modern.layouts.master')

@section('title', __('Créer un ticket'))

@section('content')
    <div class="row mx-0 mt-1 mb-1">
        <div class="content-header-left col-md-6 col-12 mb-2">
            <h3 class="content-header-title">{{ __('Créer un ticket') }}</h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('customer.tickets.index') }}">Support</a></li>
                        <li class="breadcrumb-item active">{{ __('Créer') }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="content-header-right col-md-6 col-12 text-right">
            <a href="{{ route('customer.tickets.index') }}" class="btn btn-outline-secondary rounded">
                <i class="la la-arrow-left mr-1"></i> {{ __('Retour') }}
            </a>
        </div>
    </div>

    <div class="content-body">
        <section id="ticket-create">
            <div class="card border-gray-light shadow-none">
                <div class="card-header border-bottom-0 pb-0">
                    <div class="d-flex align-items-center">
                        <div class="bg-whatsapp rounded-circle p-2 me-3">
                            <i class="la la-ticket text-white"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 text-whatsapp">{{ __('Nouveau ticket de support') }}</h5>
                            <small class="text-muted">{{ __('Décrivez votre problème ou votre demande') }}</small>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-4">
                    @livewire('customer.ticket.create-ticket-form')
                </div>
            </div>
        </section>
    </div>
@endsection