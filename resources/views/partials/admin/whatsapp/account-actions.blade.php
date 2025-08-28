<div class="dropdown" style="position: static;">
    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown" 
            aria-haspopup="true" aria-expanded="false" title="Actions"
            data-boundary="window">
        <i class="la la-cog"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-right" style="position: absolute; z-index: 1050; min-width: 220px;">
        {{-- Éditer (ouvrir la page customer) --}}
        <a class="dropdown-item py-2" href="{{ route('whatsapp.configure-ai', $account->id) }}">
            <i class="la la-edit text-primary mr-2"></i>
            Éditer
        </a>
        
        {{-- Voir statistiques --}}
        <a class="dropdown-item py-2" href="{{ route('admin.whatsapp.accounts.statistics', $account->id) }}">
            <i class="la la-chart-bar text-info mr-2"></i>
            Statistiques
        </a>
        
        {{-- Voir conversations --}}
        <a class="dropdown-item py-2" href="{{ route('customer.whatsapp.conversations.index', $account->id) }}">
            <i class="la la-comments text-success mr-2"></i>
            Conversations
            <span class="badge badge-secondary ml-1">{{ $account->conversations()->count() }}</span>
        </a>
        
        <div class="dropdown-divider"></div>
        
        {{-- Toggle Agent IA --}}
        @php
            // Get fresh data to bypass cache issues
            $freshAccount = \App\Models\WhatsAppAccount::find($account->id);
            $isAgentActive = $freshAccount->agent_enabled && $freshAccount->ai_model_id;
        @endphp
        @if ($isAgentActive)
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
        @if($account->user)
            <a class="dropdown-item py-2" href="{{ route('admin.customers.show', $account->user) }}">
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

{{-- Style pour éviter les problèmes de scroll et améliorer l'ergonomie --}}
<style>
.dropdown-menu {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    border: 1px solid #dee2e6 !important;
}

.dropdown-item {
    cursor: pointer;
    transition: background-color 0.15s ease-in-out;
}

.dropdown-item:hover,
.dropdown-item:focus {
    background-color: #f8f9fa !important;
    color: #495057 !important;
}

.dropdown-item button {
    cursor: pointer;
}

.dropdown-item button:hover,
.dropdown-item button:focus {
    background-color: transparent !important;
}

/* Empêcher les problèmes de scroll avec les DataTables */
.dataTables_wrapper .dropdown {
    position: static !important;
}

.dataTables_wrapper .dropdown-menu {
    position: absolute !important;
    transform: none !important;
}
</style>