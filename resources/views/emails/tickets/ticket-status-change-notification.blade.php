<x-mail::message>
# Your Ticket Status Has Changed

Your ticket #{{ $ticket->ticket_number }} status has been updated to **{{ $ticket->status->label }}**.

**Title:** {{ $ticket->title }}

<x-mail::button :url="route('customer.tickets.show', $ticket)">
View Ticket
</x-mail::button>

Thanks,
{{ config('app.name') }}
</x-mail::message>
