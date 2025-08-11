@extends('auth.layout')

@section('title', __('Login'))

@section('content')
    <div class="card auth-card">
        <div class="card-body p-4">
            <div class="text-center mb-3">
                <h2 class="auth-header fw-bold mb-1">{{ __('Login') }}</h2>
                <p class="text-muted mb-0">{{ __('Access your account') }}</p>
            </div>

            @livewire('auth.login-form')

            <div class="text-center mt-3">
                <p class="text-muted mb-0 small">
                    {{ __('Don\'t have an account?') }}
                    <a href="{{ route('register') }}" class="auth-link">
                        {{ __('Create an account') }}
                    </a>
                </p>
            </div>
        </div>
    </div>
@endsection
