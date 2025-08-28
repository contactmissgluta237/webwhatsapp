@extends('emails.layouts.master')

@section('title', __('emails.otp.subject'))

@section('header-title', __('emails.otp.header'))

@section('content')
    <div class="greeting">
        {{ __('emails.otp.greeting') }}
    </div>

    <div class="content">
        {!! __('emails.otp.instructions', ['identifier' => $maskedIdentifier]) !!}
    </div>

    <div class="otp-container">
        <div class="otp-label">{{ __('emails.otp.otp_label') }}</div>
        <div class="otp-code">{{ $otp }}</div>
        <div class="otp-validity">{{ __('emails.otp.validity') }}</div>
    </div>

    @if ($resetUrl)
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $resetUrl }}" class="button">{{ __('emails.otp.reset_button') }}</a>
        </div>
    @else
        <div class="content">
            {{ __('emails.otp.verification_instructions') }}
        </div>
    @endif

    <div class="security-notice">
        <h3>{{ __('emails.security.title') }}</h3>
        <p>{{ __('emails.otp.security_notice') }}</p>
    </div>
@endsection
