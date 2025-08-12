@extends('layout.error')

@section('main-content')
    <div class="container-fluid">
        <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
            <div class="col-md-8 text-center">
                <h1 class="display-1 fw-bold text-primary">401</h1>
                <h2 class="display-4 fw-bold">Non autorisé</h2>
                <p class="lead">Désolé, vous n'êtes pas autorisé à accéder à cette page.</p>
                <a href="{{ url('/') }}" class="btn btn-whatsapp mt-4">Retour à l'accueil</a>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        body {
            background-color: #f8f9fa;
            /* Light background for the error page */
        }

        .display-1 {
            font-size: 10rem;
        }

        .display-4 {
            font-size: 3rem;
        }
    </style>
@endpush
