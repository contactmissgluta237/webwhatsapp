<div class="d-flex justify-content-center">
    <a href="{{ route('customer.whatsapp.conversations.show', [$conversation->whatsapp_account_id, $conversation->id]) }}" 
       class="btn btn-sm btn-outline-primary" 
       title="Voir la conversation">
        <i class="la la-eye"></i> Voir
    </a>
</div>