@extends('auth.layout')

@section('title', 'Inscription')

@section('content')
    <div class="bg-white p-8 rounded-xl shadow-2xl max-w-2xl w-full mx-auto">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Inscription</h2>
            <p class="mt-2 text-gray-600">Créez votre compte</p>
        </div>

        @livewire('auth.register-form')

        <div class="text-center mt-6">
            <p class="text-gray-600">
                Déjà un compte ?
                <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                    Se connecter
                </a>
            </p>
        </div>
    </div>
@endsection
