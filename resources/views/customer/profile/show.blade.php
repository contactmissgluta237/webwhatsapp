@extends('modern.layouts.master')

@section('title', 'Mon profil')

@section('content')
<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="col-12 p-0">
        <h2 class="content-header-title mb-0">{{ __('profile.my_profile') }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}"><i class="la la-home"></i>{{__('Home') }}</a></li>
                <li class="breadcrumb-item active">{{ __('profile.my_profile') }}</li>
            </ol>
        </div>
    </div>
</div>
<!-- Breadcrumb end -->

<div class="row">
    <div class="col-12 p-0">
        @livewire('shared.profile-form', ['userType' => 'customer'])
    </div>
</div>

<div class="row mt-2">
    <div class="col-12">
        <div class="card shadow-none border-gray-light">
            <div class="card-header">
                <h4 class="card-title mb-0">
                    <i class="fas fa-bell me-2"></i>
                    Notifications Push
                </h4>
            </div>
            <div class="card-body">
                <p class="card-text mb-3">Activez les notifications push pour recevoir des alertes en temps réel sur votre appareil.</p>
                @include('components.push-notification-button')
                
                <!-- Bouton de diagnostic -->
                <div class="mt-3 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Problèmes avec les notifications ?
                        </small>
                        <button class="btn btn-outline-info btn-sm" onclick="showPushDiagnostic()" title="Diagnostic avancé">
                            <i class="fas fa-stethoscope me-1"></i>
                            Diagnostic
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Initialisation des notifications push pour la page de profil
    document.addEventListener('DOMContentLoaded', function() {
        // Attendre que le push manager soit chargé
        setTimeout(() => {
            if (window.checkPushNotificationStatus) {
                window.checkPushNotificationStatus();
            }
        }, 1000);
    });
    
    // Fonction pour ouvrir le diagnostic des notifications push
    function showPushDiagnostic() {
        // Créer une nouvelle fenêtre ou onglet pour le diagnostic
        const diagnosticWindow = window.open('', '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
        
        // Générer le contenu HTML du diagnostic
        const diagnosticContent = `
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Diagnostic Notifications Push</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
            </head>
            <body class="bg-light">
                <div class="container py-4">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="card shadow">
                                <div class="card-header bg-primary text-white">
                                    <h4 class="card-title mb-0">
                                        <i class="fas fa-stethoscope me-2"></i>
                                        Diagnostic Notifications Push
                                    </h4>
                                </div>
                                <div class="card-body" id="diagnostic-content">
                                    <div class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Chargement...</span>
                                        </div>
                                        <p class="mt-2">Génération du diagnostic...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <script>
                    // Générer le diagnostic
                    setTimeout(() => {
                        generateDiagnostic();
                    }, 1000);
                    
                    function generateDiagnostic() {
                        const content = document.getElementById('diagnostic-content');
                        
                        // Informations sur le navigateur
                        const userAgent = navigator.userAgent;
                        const isHTTPS = location.protocol === 'https:';
                        const hasServiceWorker = 'serviceWorker' in navigator;
                        const hasNotification = 'Notification' in window;
                        const hasPushManager = 'PushManager' in window;
                        const notificationPermission = Notification.permission;
                        
                        // Détection du navigateur
                        let browserInfo = 'Navigateur inconnu';
                        let browserSupport = 'Non testé';
                        
                        if (userAgent.includes('Chrome')) {
                            browserInfo = 'Google Chrome';
                            browserSupport = 'Excellente';
                        } else if (userAgent.includes('Firefox')) {
                            browserInfo = 'Mozilla Firefox';
                            browserSupport = 'Bonne';
                        } else if (userAgent.includes('Safari')) {
                            browserInfo = 'Safari';
                            browserSupport = 'Limitée';
                        } else if (userAgent.includes('Edge')) {
                            browserInfo = 'Microsoft Edge';
                            browserSupport = 'Bonne';
                        }
                        
                        // Générer le HTML du diagnostic
                        content.innerHTML = \`
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="text-primary">
                                        <i class="fas fa-browser me-1"></i>
                                        Informations Navigateur
                                    </h5>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Navigateur:</strong></td>
                                            <td>\${browserInfo}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Support Push:</strong></td>
                                            <td><span class="badge bg-info">\${browserSupport}</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Connexion:</strong></td>
                                            <td>
                                                <span class="badge \${isHTTPS ? 'bg-success' : 'bg-danger'}">
                                                    \${isHTTPS ? 'HTTPS \u2713' : 'HTTP (non sécurisé)'}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>User Agent:</strong></td>
                                            <td><small class="text-muted">\${userAgent}</small></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5 class="text-primary">
                                        <i class="fas fa-cog me-1"></i>
                                        Support Technique
                                    </h5>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Service Worker:</strong></td>
                                            <td>
                                                <span class="badge \${hasServiceWorker ? 'bg-success' : 'bg-danger'}">
                                                    \${hasServiceWorker ? 'Supporté \u2713' : 'Non supporté \u2717'}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>API Notification:</strong></td>
                                            <td>
                                                <span class="badge \${hasNotification ? 'bg-success' : 'bg-danger'}">
                                                    \${hasNotification ? 'Supporté \u2713' : 'Non supporté \u2717'}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>PushManager:</strong></td>
                                            <td>
                                                <span class="badge \${hasPushManager ? 'bg-success' : 'bg-danger'}">
                                                    \${hasPushManager ? 'Supporté \u2713' : 'Non supporté \u2717'}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Permissions:</strong></td>
                                            <td>
                                                <span class="badge \${notificationPermission === 'granted' ? 'bg-success' : notificationPermission === 'denied' ? 'bg-danger' : 'bg-warning'}">
                                                    \${notificationPermission === 'granted' ? 'Accordées \u2713' : notificationPermission === 'denied' ? 'Refusées \u2717' : 'En attente'}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h5 class="text-primary">
                                        <i class="fas fa-lightbulb me-1"></i>
                                        Recommandations
                                    </h5>
                                    <div class="alert \${getRecommendationLevel()} alert-dismissible">
                                        \${getRecommendations()}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-12 text-center">
                                    <button class="btn btn-primary" onclick="window.close()">
                                        <i class="fas fa-times me-1"></i>
                                        Fermer le diagnostic
                                    </button>
                                    <button class="btn btn-outline-secondary ms-2" onclick="window.print()">
                                        <i class="fas fa-print me-1"></i>
                                        Imprimer
                                    </button>
                                </div>
                            </div>
                        \`;
                    }
                    
                    function getRecommendationLevel() {
                        const isHTTPS = location.protocol === 'https:';
                        const hasServiceWorker = 'serviceWorker' in navigator;
                        const hasNotification = 'Notification' in window;
                        const hasPushManager = 'PushManager' in window;
                        const notificationPermission = Notification.permission;
                        
                        if (!isHTTPS || !hasServiceWorker || !hasNotification || !hasPushManager) {
                            return 'alert-danger';
                        }
                        
                        if (notificationPermission === 'denied') {
                            return 'alert-warning';
                        }
                        
                        return 'alert-success';
                    }
                    
                    function getRecommendations() {
                        const isHTTPS = location.protocol === 'https:';
                        const hasServiceWorker = 'serviceWorker' in navigator;
                        const hasNotification = 'Notification' in window;
                        const hasPushManager = 'PushManager' in window;
                        const notificationPermission = Notification.permission;
                        
                        let recommendations = [];
                        
                        if (!isHTTPS) {
                            recommendations.push('<strong>Problème HTTPS:</strong> Les notifications push nécessitent une connexion sécurisée HTTPS.');
                        }
                        
                        if (!hasServiceWorker) {
                            recommendations.push('<strong>Service Worker:</strong> Votre navigateur ne supporte pas les Service Workers.');
                        }
                        
                        if (!hasNotification) {
                            recommendations.push('<strong>API Notification:</strong> Votre navigateur ne supporte pas les notifications.');
                        }
                        
                        if (!hasPushManager) {
                            recommendations.push('<strong>PushManager:</strong> Votre navigateur ne supporte pas les notifications push.');
                        }
                        
                        if (notificationPermission === 'denied') {
                            recommendations.push('<strong>Permissions refusées:</strong> Vous devez autoriser les notifications dans les paramètres du navigateur.');
                        }
                        
                        if (recommendations.length === 0) {
                            return '<i class="fas fa-check-circle me-1"></i> <strong>Parfait !</strong> Votre configuration est optimale pour les notifications push.';
                        }
                        
                        return '<i class="fas fa-exclamation-triangle me-1"></i> ' + recommendations.join('<br><br>');
                    }
                </script>
            </body>
            </html>
        `;
        
        diagnosticWindow.document.open();
        diagnosticWindow.document.write(diagnosticContent);
        diagnosticWindow.document.close();
    }
</script>
@endpush
