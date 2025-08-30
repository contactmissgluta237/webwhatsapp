@extends('emails.layouts.master')

@section('title', __('AI Information Request'))

@section('header-title', __('AI Information Request'))

@section('content')
    <div class="email-container">
        <h1>{{ __('AI Information Request') }} 🤖</h1>

        <p>{{ __('Hello!') }}</p>

        <p>{{ __('Your AI assistant received a question it couldn\'t answer from a customer.') }}</p>

        <div class="highlight-box">
            <h3>{{ __('Customer Details') }}:</h3>
            <ul>
                <li><strong>{{ __('Account') }}:</strong> {{ $account->agent_name ?? $account->session_name }}</li>
                <li><strong>{{ __('Customer Phone') }}:</strong> {{ $incomingMessage->getContactPhone() }}</li>
                <li><strong>{{ __('Customer Question') }}:</strong> {{ $incomingMessage->body }}</li>
            </ul>
        </div>

        <div class="highlight-box" style="background-color: #f8f9fa; border-left: 4px solid #6c757d;">
            <h3>{{ __('AI Response') }}:</h3>
            <p style="font-style: italic; color: #6c757d;">{{ $aiMessage }}</p>
        </div>

        <p>{{ __('Please review and provide the information to improve future responses.') }}</p>

        <div class="cta-section">
            <a href="{{ url('/customer/whatsapp/' . $account->id . ($conversationId ? '/conversations/' . $conversationId : '')) }}" class="cta-button">
                {{ __('View Conversation') }}
            </a>
        </div>

        <p>{{ __('Thanks') }},</p>
        <p>L'équipe {{ config('app.name') }}</p>
    </div>
@endsection