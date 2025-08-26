<div class="text-center py-5">
    <i class="la la-whatsapp text-muted" style="font-size: 4rem;"></i>
    <h4 class="text-muted mt-3">{{ __('Aucune session WhatsApp') }}</h4>
    <p class="text-muted">{{ __('Vous n\'avez pas encore créé de session WhatsApp.') }}</p>
    <a href="{{ route('whatsapp.create') }}" class="btn btn-whatsapp rounded btn-glow">
        <i class="la la-plus mr-1"></i> {{ __('Créer votre première session') }}
    </a>
</div>