@extends('modern.layouts.master')

@section('title', 'Paramètres système')

@section('content')

<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="col-8 p-0">
        <h2 class="content-header-title mb-0">Paramètres système</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin.dashboard') }}"><i class="ti ti-home"></i>Tableau de bord</a>
                </li>
                <li class="breadcrumb-item active">Paramètres</li>
            </ol>
        </div>
    </div>
</div>
<!-- Breadcrumb end -->

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">
                    <i class="ti ti-settings me-2"></i>
                    Configuration du système
                </h4>
                <p class="card-subtitle mb-0">
                    Gérer les paramètres globaux de l'application
                </p>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Credit System Settings -->
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 border-primary">
                            <div class="card-body text-center">
                                <div class="avatar avatar-lg bg-primary bg-opacity-10 text-primary rounded-circle mx-auto mb-3">
                                    <i class="ti ti-coins fs-2"></i>
                                </div>
                                <h5 class="card-title">Système de crédit</h5>
                                <p class="card-text text-muted">
                                    Configuration des coûts des messages IA et gestion des déductions automatiques
                                </p>
                                <a href="{{ route('admin.settings.credit-system.index') }}" class="btn btn-primary">
                                    <i class="ti ti-settings me-2"></i>
                                    Configurer
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Future settings sections can be added here -->
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 border-secondary">
                            <div class="card-body text-center">
                                <div class="avatar avatar-lg bg-secondary bg-opacity-10 text-secondary rounded-circle mx-auto mb-3">
                                    <i class="ti ti-mail fs-2"></i>
                                </div>
                                <h5 class="card-title">Notifications</h5>
                                <p class="card-text text-muted">
                                    Configuration des notifications email et système
                                </p>
                                <button class="btn btn-secondary" disabled>
                                    <i class="ti ti-settings me-2"></i>
                                    Bientôt disponible
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 border-secondary">
                            <div class="card-body text-center">
                                <div class="avatar avatar-lg bg-secondary bg-opacity-10 text-secondary rounded-circle mx-auto mb-3">
                                    <i class="ti ti-shield fs-2"></i>
                                </div>
                                <h5 class="card-title">Sécurité</h5>
                                <p class="card-text text-muted">
                                    Paramètres de sécurité et authentification
                                </p>
                                <button class="btn btn-secondary" disabled>
                                    <i class="ti ti-settings me-2"></i>
                                    Bientôt disponible
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection