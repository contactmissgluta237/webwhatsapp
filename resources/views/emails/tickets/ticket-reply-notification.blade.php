<x-mail::message>
# New Reply on Your Ticket

Your ticket #{{ $ticketMessage->ticket->ticket_number }} has received a new reply.

**From:** {{ $ticketMessage->user->full_name }}
**Message:** {{ $ticketMessage->message }}

<x-mail::button :url="route('customer.tickets.show', $ticketMessage->ticket)">
View Ticket
</x-mail::button>

Thanks,
{{ config('app.name') }}
</x-mail::message>
