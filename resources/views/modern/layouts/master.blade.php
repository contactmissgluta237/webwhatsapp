<!DOCTYPE html>
<html class="loading" lang="fr" data-textdirection="ltr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta name="description" content="Modern admin template with clean design">
    <meta name="author" content="Generic SaaS">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
    @auth
        <meta name="user-id" content="{{ auth()->id() }}">
    @endauth
    
    <title>@yield('title', 'Modern Admin - Generic SaaS')</title>
    
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/icon-192x192.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('modern/images/ico/favicon.ico') }}">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i%7CQuicksand:300,400,500,700" rel="stylesheet">
    
    <!-- PWA Theme Color -->
    <meta name="theme-color" content="#0d6efd">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="SaaS App">

    @include('modern.layouts.css')

    @stack('styles')
</head>

<body class="vertical-layout vertical-menu 2-columns fixed-navbar" data-open="click" data-menu="vertical-menu" data-col="2-columns">

    <!-- BEGIN: Header-->
    @include('modern.layouts.header')
    <!-- END: Header-->

    <!-- BEGIN: Main Menu-->
    @include('modern.layouts.sidebar')
    <!-- END: Main Menu-->

    <!-- BEGIN: Content-->
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            <div class="content-body">
                @yield('content')
            </div>
        </div>
    </div>
    <!-- END: Content-->

    <!-- BEGIN: Footer-->
    @include('modern.layouts.footer')
    <!-- END: Footer-->

    <div class="sidenav-overlay"></div>
    <div class="drag-target"></div>

    <!-- BEGIN: Push Notification Float Component -->
    @auth
        <x-push-notification-float />
    @endauth
    <!-- END: Push Notification Float Component -->

    @include('modern.layouts.scripts')

    @stack('scripts')
    @auth
        <script src="{{ asset('js/push-notifications.js') }}"></script>
        <script src="{{ asset('js/app-pwa.js') }}"></script>
    @endauth
</body>
</html>