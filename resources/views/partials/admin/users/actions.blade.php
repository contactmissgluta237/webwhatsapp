<div class="dropdown" style="position: static;">
    <button class="btn btn-sm btn-whatsapp dropdown-toggle" type="button" data-toggle="dropdown" 
            aria-haspopup="true" aria-expanded="false" title="Actions"
            data-boundary="window">
        <i class="la la-ellipsis-v"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-right" style="position: absolute; z-index: 1050; min-width: 200px;">
        {{-- Voir détails client --}}
        @if($user->hasRole('customer'))
            <a href="{{ route('admin.customers.show', $user) }}" class="dropdown-item py-2">
                <i class="la la-eye text-info mr-2"></i>
                Détails client
            </a>
        @endif
        
        {{-- Voir comptes WhatsApp --}}
        @if($user->whatsappAccounts()->exists())
            <a href="{{ route('admin.whatsapp.accounts.index') }}?user_id={{ $user->id }}" class="dropdown-item py-2">
                <i class="la la-whatsapp text-success mr-2"></i>
                Comptes WhatsApp
                <span class="badge badge-secondary ml-1">{{ $user->whatsappAccounts()->count() }}</span>
            </a>
        @endif
        
        {{-- Voir conversations WhatsApp --}}
        @if($user->whatsappAccounts()->has('conversations')->exists())
            <a href="{{ route('admin.whatsapp.conversations.index') }}?filters[user_id]={{ $user->id }}" class="dropdown-item py-2">
                <i class="la la-comments text-primary mr-2"></i>
                Conversations
            </a>
        @endif
        
        <div class="dropdown-divider"></div>
        
        {{-- Éditer --}}
        <a href="{{ route('admin.users.edit', $user) }}" class="dropdown-item py-2">
            <i class="la la-edit text-warning mr-2"></i>
            Modifier
        </a>
        
        {{-- Supprimer --}}
        <button type="button" class="dropdown-item py-2 text-danger text-left border-0 bg-transparent w-100"
                onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')"
                style="background: none !important;">
            <i class="la la-trash text-danger mr-2"></i>
            Supprimer
        </button>
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

/* Empêcher les problèmes de scroll avec les DataTables */
.dataTables_wrapper .dropdown {
    position: static !important;
}

.dataTables_wrapper .dropdown-menu {
    position: absolute !important;
    transform: none !important;
}
</style>
