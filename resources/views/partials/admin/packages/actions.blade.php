<div class="dropdown" style="position: static;">
    <button class="btn btn-sm btn-whatsapp dropdown-toggle" type="button" data-toggle="dropdown" 
            aria-haspopup="true" aria-expanded="false" title="Actions"
            data-boundary="window">
        <i class="la la-ellipsis-v"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-right" style="position: absolute; z-index: 1050; min-width: 180px;">
        {{-- Modifier --}}
        <a class="dropdown-item py-2" href="{{ route('admin.packages.edit', $package->id) }}">
            <i class="la la-edit text-warning mr-2"></i>
            Modifier
        </a>
        
        {{-- Toggle statut --}}
        <form method="POST" action="{{ route('admin.packages.toggle-status', $package->id) }}" style="display: inline; width: 100%;">
            @csrf
            <input type="hidden" name="is_active" value="{{ $package->is_active ? '0' : '1' }}">
            <button type="submit" class="dropdown-item py-2 text-left border-0 bg-transparent w-100" 
                    onclick="return confirm('{{ $package->is_active ? 'Désactiver' : 'Activer' }} ce package ?')"
                    style="background: none !important;">
                <i class="la la-{{ $package->is_active ? 'eye-slash' : 'eye' }} mr-2"></i>
                {{ $package->is_active ? 'Désactiver' : 'Activer' }}
            </button>
        </form>
        
        {{-- Voir détails --}}
        <a class="dropdown-item py-2" href="{{ route('admin.packages.show', $package->id) }}">
            <i class="la la-eye text-info mr-2"></i>
            Voir détails
        </a>
        
        {{-- Voir les souscriptions --}}
        <a class="dropdown-item py-2" href="{{ route('admin.subscriptions.index', ['package_id' => $package->id]) }}">
            <i class="mdi mdi-eye text-primary mr-2"></i>
            Voir les souscriptions
        </a>
        
        <div class="dropdown-divider"></div>
        
        {{-- Supprimer --}}
        <form method="POST" action="{{ route('admin.packages.delete', $package->id) }}" style="display: inline; width: 100%;">
            @csrf
            @method('DELETE')
            <button type="submit" class="dropdown-item py-2 text-danger text-left border-0 bg-transparent w-100" 
                    onclick="return confirm('Êtes-vous sûr de vouloir supprimer le package « {{ addslashes($package->display_name) }} » ?')"
                    style="background: none !important;">
                <i class="la la-trash text-danger mr-2"></i>
                Supprimer
            </button>
        </form>
    </div>
</div>