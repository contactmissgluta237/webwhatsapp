@extends('auth.layout')

@section('title', 'Vérification du code')

@section('content')
    <div class="bg-white p-8 rounded-xl shadow-2xl max-w-md w-full mx-auto">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Vérification</h2>
            <p class="mt-2 text-gray-600">Saisissez le code reçu</p>
        </div>

        @livewire('auth.verify-otp-form', [
            'identifier' => $identifier ?? '',
            'resetType' => $resetType ?? 'email',
            'verificationType' => $verificationType ?? 'password_reset',
        ])
    </div>
@endsection
