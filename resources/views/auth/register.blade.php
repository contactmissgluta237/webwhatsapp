@extends('auth.layout')

@section('title', __('Register'))

@section('content')
    <div class="card auth-card" style="max-width: 600px;">
        <div class="card-body p-4">
            <div class="text-center mb-3">
                <h2 class="auth-header fw-bold mb-1">{{ __('Register') }}</h2>
                <p class="text-muted mb-0">{{ __('Create your account') }}</p>
            </div>

            @livewire('auth.register-form')

            <div class="text-center mt-3">
                <p class="text-muted mb-0 small">
                    {{ __('Already have an account?') }}
                    <a href="{{ route('login') }}" class="auth-link">
                        {{ __('Sign in') }}
                    </a>
                </p>
            </div>
        </div>
    </div>
@endsection
