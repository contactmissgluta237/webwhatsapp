@component('mail::message')
    # Transaction Approved

    Your transaction with ID: {{ $transaction->id }} has been approved.

    Thanks,<br>
    {{ config('app.name') }}
@endcomponent
