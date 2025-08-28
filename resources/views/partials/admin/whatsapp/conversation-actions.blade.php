<div class="dropdown" style="position: static;">
    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown" 
            aria-haspopup="true" aria-expanded="false" title="Actions"
            data-boundary="window">
        <i class="la la-ellipsis-v"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-right" style="position: absolute; z-index: 1050; min-width: 200px;">
        {{-- Voir détails --}}
        <a class="dropdown-item py-2" href="{{ route('admin.whatsapp.conversations.show', $conversation->id) }}">
            <i class="la la-eye text-info mr-2"></i>
            Voir détails
        </a>
        
        {{-- Voir statistiques --}}
        <a class="dropdown-item py-2" href="{{ route('admin.whatsapp.conversations.statistics', $conversation->id) }}">
            <i class="la la-chart-line text-primary mr-2"></i>
            Statistiques
        </a>
        
        {{-- Exporter --}}
        <a class="dropdown-item py-2" href="{{ route('admin.whatsapp.conversations.export', $conversation->id) }}">
            <i class="la la-download text-success mr-2"></i>
            Exporter
        </a>
        
        <div class="dropdown-divider"></div>
        
        {{-- Toggle AI --}}
        @if ($conversation->is_ai_enabled)
            <form method="POST" action="{{ route('admin.whatsapp.conversations.toggle-ai', $conversation->id) }}" style="display: inline; width: 100%;">
                @csrf
                <button type="submit" class="dropdown-item py-2 text-left border-0 bg-transparent w-100" 
                        onclick="return confirm('Désactiver l\'IA pour cette conversation ?')"
                        style="background: none !important;">
                    <i class="la la-pause text-warning mr-2"></i>
                    Désactiver l'IA
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.whatsapp.conversations.toggle-ai', $conversation->id) }}" style="display: inline; width: 100%;">
                @csrf
                <button type="submit" class="dropdown-item py-2 text-left border-0 bg-transparent w-100"
                        onclick="return confirm('Activer l\'IA pour cette conversation ?')"
                        style="background: none !important;">
                    <i class="la la-play text-success mr-2"></i>
                    Activer l'IA
                </button>
            </form>
        @endif
        
        {{-- Voir compte WhatsApp --}}
        <a class="dropdown-item py-2" href="{{ route('admin.whatsapp.accounts.show', $conversation->whatsapp_account_id) }}">
            <i class="la la-whatsapp text-secondary mr-2"></i>
            Voir compte WhatsApp
        </a>
        
        {{-- Voir utilisateur --}}
        @if(isset($user) || $conversation->whatsappAccount->user)
            <a class="dropdown-item py-2" href="{{ route('admin.users.show', $user ?? $conversation->whatsappAccount->user) }}">
                <i class="la la-user text-secondary mr-2"></i>
                Voir utilisateur
            </a>
        @endif
        
        <div class="dropdown-divider"></div>
        
        {{-- Supprimer --}}
        <form method="POST" action="{{ route('admin.whatsapp.conversations.destroy', $conversation->id) }}" style="display: inline; width: 100%;">
            @csrf
            @method('DELETE')
            <button type="submit" class="dropdown-item py-2 text-danger text-left border-0 bg-transparent w-100"
                    onclick="return confirm('Supprimer définitivement cette conversation ?')"
                    style="background: none !important;">
                <i class="la la-trash text-danger mr-2"></i>
                Supprimer
            </button>
        </form>
    </div>
</div>