@component('mail::message')
# Nouveau Ticket Client

Un nouveau ticket a été créé par **{{ $ticket->user->full_name }}**.

**Numéro du Ticket:** {{ $ticket->ticket_number }}
**Titre:** {{ $ticket->title }}

@component('mail::button', ['url' => $ticketUrl])
Voir le Ticket
@endcomponent

Merci,
{{ config('app.name') }}
@endcomponent