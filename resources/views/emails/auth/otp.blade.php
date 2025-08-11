@extends('emails.layouts.master')

@section('title', __('emails.otp.title'))

@section('header-title', __('emails.otp.header'))

@section('content')
    <div class="greeting">
        {{ __('emails.common.hello') }} {{ $userName ?? __('emails.common.user') }},
    </div>
    
    <div class="content">
        <p>{{ __('emails.otp.requested_for', ['identifier' => $maskedIdentifier]) }}</p>
        <p>{{ __('emails.otp.use_code_below') }}</p>
    </div>
    
    <div class="otp-container">
        <div class="otp-label">{{ __('emails.otp.verification_code') }}</div>
        <div class="otp-code">{{ $otp }}</div>
        <div class="otp-validity">{{ __('emails.otp.validity', ['minutes' => $validityMinutes ?? 10]) }}</div>
    </div>
    
    <div class="divider"></div>
    
    <div class="content">
        <p>{{ __('emails.otp.alternative_method') }}</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $resetUrl }}" class="button">
                @if(isset($verificationType) && $verificationType === 'register')
                    {{ __('emails.otp.verify_button') }}
                @else
                    {{ __('emails.otp.reset_button') }}
                @endif
            </a>
        </div>
    </div>
    
    <div class="security-notice">
        <h3>{{ __('emails.common.security_notice') }}</h3>
        <p>{{ __('emails.otp.security_warning') }}</p>
        <p>{{ __('emails.common.not_requested') }}</p>
    </div>
@endsection
