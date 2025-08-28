@extends('emails.layouts.master')

@section('title', __('emails.account_activation.subject'))

@section('header-title', __('emails.account_activation.header'))

@section('content')
    <div class="greeting">
        {{ __('emails.account_activation.welcome') }}
    </div>

    <div class="content">
        {!! __('emails.account_activation.instructions', ['identifier' => $maskedIdentifier]) !!}
    </div>

    <div class="otp-container">
        <div class="otp-label">{{ __('emails.account_activation.otp_label') }}</div>
        <div class="otp-code">{{ $otp }}</div>
        <div class="otp-validity">{{ __('emails.account_activation.validity') }}</div>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $activationUrl }}" class="button">{{ __('emails.account_activation.activate_button') }}</a>
    </div>

    <div class="divider"></div>

    <div class="content">
        <h3 style="color: #075E54; margin-bottom: 15px;">{{ __('emails.account_activation.features_title') }}</h3>
        <ul style="color: #4a5568; padding-left: 20px;">
            <li>{{ __('emails.account_activation.feature_1') }}</li>
            <li>{{ __('emails.account_activation.feature_2') }}</li>
            <li>{{ __('emails.account_activation.feature_3') }}</li>
        </ul>
    </div>

    <div class="security-notice">
        <h3>{{ __('emails.security.title') }}</h3>
        <p>{{ __('emails.account_activation.security_notice') }}</p>
    </div>
@endsection
