@extends('emails.layouts.master')

@section('title', __('AI Information Request'))

@section('header-title', __('AI Information Request'))

@section('content')
    <div class="email-container">
        <div class="greeting">
            <p>{{ __('Hello!') }}</p>
        </div>

        <div class="content">
            <p>{{ __('Your AI assistant received a question it couldn\'t fully answer from a customer. Please review the details below and update your knowledge base to improve future responses.') }}</p>
        </div>

        <div class="highlight-box" style="background-color: #f8f9fa; padding: 25px; border-radius: 8px; margin: 30px 0; border-left: 4px solid #075E54;">
            <h3 style="color: #054A42; font-size: 18px; margin-bottom: 15px;">{{ __('Customer Request Details') }}</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; font-weight: 600; color: #2d3748; width: 120px;">{{ __('Account') }}:</td>
                    <td style="padding: 8px 0; color: #4a5568;">{{ $account->agent_name ?? $account->session_name }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: 600; color: #2d3748;">{{ __('Customer') }}:</td>
                    <td style="padding: 8px 0; color: #4a5568;">{{ $incomingMessage->getContactPhone() }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: 600; color: #2d3748; vertical-align: top;">{{ __('Question') }}:</td>
                    <td style="padding: 8px 0; color: #4a5568; line-height: 1.5;">{{ $incomingMessage->body }}</td>
                </tr>
            </table>
        </div>

        <div class="highlight-box" style="background-color: #fdf2f8; padding: 25px; border-radius: 8px; margin: 30px 0; border-left: 4px solid #f56565;">
            <h3 style="color: #c53030; font-size: 18px; margin-bottom: 15px;">{{ __('AI Response Provided') }}</h3>
            <p style="font-style: italic; color: #718096; line-height: 1.6; background-color: #ffffff; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0;">{{ $aiMessage }}</p>
        </div>

        <div class="content">
            <p>{{ __('This information request requires your attention to ensure customers receive accurate and complete responses in the future.') }}</p>
        </div>

        <div class="cta-section" style="text-align: center; margin: 40px 0;">
            <a href="{{ url('/customer/whatsapp/' . $account->id . ($conversationId ? '/conversations/' . $conversationId : '')) }}" 
               class="button"
               style="display: inline-block; background: linear-gradient(135deg, #075E54 0%, #054A42 100%); color: white; padding: 15px 30px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 16px;">
                {{ __('View Conversation') }}
            </a>
        </div>

        <div class="divider"></div>

        <div class="content">
            <p>{{ __('Best regards,') }}</p>
            <p><strong>{{ __('The :app_name Team', ['app_name' => config('app.name')]) }}</strong></p>
        </div>
    </div>
@endsection