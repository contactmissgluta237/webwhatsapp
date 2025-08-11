@component('mail::message')
# Nouvelle demande de retrait

Une nouvelle demande de retrait a été initiée par **{{ $transaction->wallet->user->full_name }}**.

**Détails de la transaction :**
- **Montant :** {{ number_format($transaction->amount, 0, ',', ' ') }} FCFA
- **Date :** {{ $transaction->created_at->format('d/m/Y H:i') }}

Vous pouvez approuver ou rejeter cette transaction depuis le panneau d'administration.

@component('mail::button', ['url' => route('admin.transactions.index')])
Voir les transactions
@endcomponent

Cordialement,<br>
L'équipe {{ config('app.name') }}
@endcomponent
