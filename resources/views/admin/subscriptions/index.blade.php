@extends('modern.layouts.master')

@section('title', 'Gestion des Souscriptions')

@section('content')

<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="content-header-left col-md-6 col-12 mb-2">
        <h3 class="content-header-title text-whatsapp">Gestion des Souscriptions</h3>
        <div class="row breadcrumbs-top">
            <div class="breadcrumb-wrapper col-12">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item active">Souscriptions</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Flash Messages -->
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-none border-success" role="alert">
        <i class="la la-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show shadow-none border-danger" role="alert">
        <i class="la la-exclamation-circle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Subscriptions List -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-none border-gray-light">
            <div class="card-header bg-white border-bottom-0">
                <h4 class="card-title text-whatsapp">
                    Liste des Souscriptions
                </h4>
            </div>
            <div class="card-body">
                <div class="app-scroll app-datatable-default">
                    @livewire('admin.subscriptions-data-table')
                </div>
            </div>  
        </div>
    </div>
</div>

@endsection