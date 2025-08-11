<x-mail::message>
# New Message from Customer on Ticket

Ticket #{{ $ticketMessage->ticket->ticket_number }} has received a new message from the customer.

**From:** {{ $ticketMessage->user->full_name }}
**Message:** {{ $ticketMessage->message }}

<x-mail::button :url="route('admin.tickets.show', $ticketMessage->ticket)">
View Ticket
</x-mail::button>

Thanks,
{{ config('app.name') }}
</x-mail::message>
