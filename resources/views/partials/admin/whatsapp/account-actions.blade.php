<div class="dropdown" style="position: static;">
    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown" 
            aria-haspopup="true" aria-expanded="false" title="Actions"
            data-boundary="window">
        <i class="la la-ellipsis-v"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-right" style="position: absolute; z-index: 1050; min-width: 220px;">
        {{-- Voir détails du compte --}}
        <a class="dropdown-item py-2" href="{{ route('admin.whatsapp.accounts.show', $account->id) }}">
            <i class="la la-eye text-info mr-2"></i>
            Voir détails
        </a>
        
        {{-- Voir statistiques --}}
        <a class="dropdown-item py-2" href="{{ route('admin.whatsapp.accounts.statistics', $account->id) }}">
            <i class="la la-chart-bar text-primary mr-2"></i>
            Statistiques
        </a>
        
        {{-- Voir conversations --}}
        <a class="dropdown-item py-2" href="{{ route('admin.whatsapp.conversations.index') }}?filters[whatsapp_account_id]={{ $account->id }}">
            <i class="la la-comments text-success mr-2"></i>
            Conversations
            <span class="badge badge-secondary ml-1">{{ $account->conversations()->count() }}</span>
        </a>
        
        <div class="dropdown-divider"></div>
        
        {{-- Toggle Agent IA --}}
        @if ($account->agent_enabled)
            <form method="POST" action="{{ route('admin.whatsapp.accounts.toggle-ai', $account->id) }}" style="display: inline; width: 100%;">
                @csrf
                <input type="hidden" name="enable" value="0">
                <button type="submit" class="dropdown-item py-2 text-left border-0 bg-transparent w-100" 
                        onclick="return confirm('Désactiver l\'agent IA pour ce compte ?')"
                        style="background: none !important;">
                    <i class="la la-pause text-warning mr-2"></i>
                    Désactiver l'agent IA
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.whatsapp.accounts.toggle-ai', $account->id) }}" style="display: inline; width: 100%;">
                @csrf
                <input type="hidden" name="enable" value="1">
                <button type="submit" class="dropdown-item py-2 text-left border-0 bg-transparent w-100"
                        onclick="return confirm('Activer l\'agent IA pour ce compte ?')"
                        style="background: none !important;">
                    <i class="la la-play text-success mr-2"></i>
                    Activer l'agent IA
                </button>
            </form>
        @endif
        
        <div class="dropdown-divider"></div>
        
        {{-- Voir utilisateur --}}
        @if(isset($user) || $account->user)
            <a class="dropdown-item py-2" href="{{ route('admin.users.show', $user ?? $account->user) }}">
                <i class="la la-user text-secondary mr-2"></i>
                Voir utilisateur
            </a>
        @endif
        
        {{-- Supprimer --}}
        <form method="POST" action="{{ route('admin.whatsapp.accounts.destroy', $account->id) }}" style="display: inline; width: 100%;">
            @csrf
            @method('DELETE')
            <button type="submit" class="dropdown-item py-2 text-danger text-left border-0 bg-transparent w-100"
                    onclick="return confirm('Supprimer définitivement ce compte WhatsApp ?')"
                    style="background: none !important;">
                <i class="la la-trash text-danger mr-2"></i>
                Supprimer
            </button>
        </form>
    </div>
</div>