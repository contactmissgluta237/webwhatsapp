<x-mail::message>
# New Ticket Created

A new ticket has been created by {{ $ticket->user->full_name }}.

**Ticket Number:** {{ $ticket->ticket_number }}
**Title:** {{ $ticket->title }}
**Description:** {{ $ticket->description }}

<x-mail::button :url="route('admin.tickets.show', $ticket)">
View Ticket
</x-mail::button>

Thanks,
{{ config('app.name') }}
</x-mail::message>
