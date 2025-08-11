@component('mail::message')
# Nouvelle Réponse Client

Le client **{{ $ticketMessage->user->full_name }}** a répondu au ticket #{{ $ticket->ticket_number }}.

**Message:**
{{ $ticketMessage->message }}

@component('mail::button', ['url' => $ticketUrl])
Voir le Ticket
@endcomponent

Merci,
{{ config('app.name') }}
@endcomponent