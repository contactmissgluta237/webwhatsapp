@extends('modern.layouts.master')
@section('title', 'Dashboard Client')

@push('styles')
    <!-- slick css -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/slick/slick.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/slick/slick-theme.css') }}">

    <!-- Data Table css-->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/datatable/jquery.dataTables.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/datatable/datatable2/buttons.dataTables.min.css') }}">
@endpush

@section('content')
    <div class="content-header-left col-md-6 col-12 mb-2">
        <h3 class="content-header-title">Tableau de bord Client</h3>
        <div class="row breadcrumbs-top">
            <div class="breadcrumb-wrapper col-12">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item active">Tableau de bord</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Push Notifications Settings -->
    <div class="row" id="push-notification-row" style="display: none;">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">🔔 Notifications Push</h4>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="closePushNotificationCard()" title="Fermer">
                        <i class="la la-times"></i>
                    </button>
                </div>
                <div class="card-content">
                    <div class="card-body">
                        <p class="card-text">Activez les notifications push pour recevoir des alertes en temps réel sur
                            votre appareil.</p>
                        @include('components.push-notification-button')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Dashboard Metrics Component -->
    <div class="row">
        <div class="col-12">
            @livewire('customer.customer-dashboard-metrics')
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row match-height">
        <div class="col-xl-3 col-lg-6 col-12">
            <div class="card pull-up border-gray-light shadow-none">
                <div class="card-content">
                    <div class="card-body text-center">
                        <a href="{{ route('customer.transactions.recharge') }}" class="d-block text-decoration-none">
                            <i class="la la-plus-circle font-large-2 mb-1 text-success"></i>
                            <h6 class="text-dark">Nouvelle Recharge</h6>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-12">
            <div class="card pull-up border-gray-light shadow-none">
                <div class="card-content">
                    <div class="card-body text-center">
                        <a href="{{ route('customer.transactions.withdrawal') }}" class="d-block text-decoration-none">
                            <i class="la la-minus-circle font-large-2 mb-1 text-warning"></i>
                            <h6 class="text-dark">Demander un Retrait</h6>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-12">
            <div class="card pull-up border-gray-light shadow-none">
                <div class="card-content">
                    <div class="card-body text-center">
                        <a href="{{ route('customer.transactions.index') }}" class="d-block text-decoration-none">
                            <i class="la la-list font-large-2 mb-1 text-info"></i>
                            <h6 class="text-dark">Mes Transactions</h6>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-12">
            <div class="card pull-up border-gray-light shadow-none">
                <div class="card-content">
                    <div class="card-body text-center">
                        <a href="{{ route('customer.referrals.index') }}" class="d-block text-decoration-none">
                            <i class="la la-users font-large-2 mb-1 text-primary"></i>
                            <h6 class="text-dark">Mes Filleuls</h6>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/vendor/slick/slick.min.js') }}"></script>
    <script src="{{ asset('assets/js/ticket.js') }}"></script>
    <script>
        // Push Notification Card Management
        function closePushNotificationCard() {
            const notificationRow = document.getElementById('push-notification-row');
            notificationRow.style.display = 'none';
            
            // Store in localStorage that user has closed the notification
            localStorage.setItem('pushNotificationCardClosed', 'true');
        }

        function shouldShowPushNotificationCard() {
            // Don't show if user has manually closed it
            if (localStorage.getItem('pushNotificationCardClosed') === 'true') {
                return false;
            }
            
            // Don't show if push notifications are already enabled
            return !isPushNotificationEnabled();
        }

        function isPushNotificationEnabled() {
            // Check if push notifications are currently enabled
            if (window.pushManager && typeof window.pushManager.getSubscriptionStatus === 'function') {
                return new Promise(async (resolve) => {
                    try {
                        const isSubscribed = await window.pushManager.getSubscriptionStatus();
                        resolve(isSubscribed);
                    } catch (error) {
                        resolve(false);
                    }
                });
            }
            return false;
        }

        // Initialize push notification card visibility
        document.addEventListener('DOMContentLoaded', function() {
            const notificationRow = document.getElementById('push-notification-row');
            
            // Wait a bit for push manager to be initialized
            setTimeout(async () => {
                try {
                    const shouldShow = await shouldShowPushNotificationCard();
                    
                    // If push manager is available, check current status
                    if (window.pushManager && typeof window.pushManager.getSubscriptionStatus === 'function') {
                        const isEnabled = await window.pushManager.getSubscriptionStatus();
                        
                        if (isEnabled) {
                            // Hide if notifications are already enabled
                            notificationRow.style.display = 'none';
                        } else if (!localStorage.getItem('pushNotificationCardClosed')) {
                            // Show only if not manually closed and notifications not enabled
                            notificationRow.style.display = 'block';
                        }
                    } else {
                        // Fallback: show if not manually closed
                        if (!localStorage.getItem('pushNotificationCardClosed')) {
                            notificationRow.style.display = 'block';
                        }
                    }
                } catch (error) {
                    // Fallback: show if not manually closed
                    if (!localStorage.getItem('pushNotificationCardClosed')) {
                        notificationRow.style.display = 'block';
                    }
                }
            }, 1000);
        });

        // Override the updateButtonsVisibility function from push-notification-button.blade.php
        // to also hide the entire card when notifications are enabled
        document.addEventListener('DOMContentLoaded', function() {
            // Wait for the original script to load
            setTimeout(() => {
                if (typeof updateButtonsVisibility === 'function') {
                    const originalUpdateButtonsVisibility = updateButtonsVisibility;
                    
                    window.updateButtonsVisibility = function(isSubscribed) {
                        originalUpdateButtonsVisibility(isSubscribed);
                        
                        // Hide the entire notification card when notifications are enabled
                        if (isSubscribed) {
                            document.getElementById('push-notification-row').style.display = 'none';
                        }
                    };
                }
            }, 1500);
        });
    </script>
@endpush