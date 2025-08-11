<!DOCTYPE html>
<html class="loading" lang="fr" data-textdirection="ltr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta name="description" content="Erreur - Generic SaaS">
    <meta name="author" content="Generic SaaS">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Erreur - Generic SaaS')</title>
    
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('modern/images/ico/favicon.ico') }}">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i%7CQuicksand:300,400,500,700" rel="stylesheet">

    @include('modern.layouts.css')
    @stack('styles')
</head>

<body class="bg-light">
    <div class="app-content">
        @yield('main-content')
    </div>

    @include('modern.layouts.scripts')
    @stack('scripts')
</body>
</html>