<div class="dropdown">
    <button class="btn btn-sm btn-whatsapp dropdown-toggle" type="button" id="actions-menu-{{ $package->id }}" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="la la-ellipsis-v"></i>
    </button>
    <ul class="dropdown-menu" aria-labelledby="actions-menu-{{ $package->id }}">
        <li>
            <a class="dropdown-item" href="{{ route('admin.packages.edit', $package->id) }}">
                <i class="la la-edit"></i> Modifier
            </a>
        </li>
        <li>
            <form method="POST" action="{{ route('admin.packages.toggle-status', $package->id) }}" style="display: inline; width: 100%;">
                @csrf
                <input type="hidden" name="is_active" value="{{ $package->is_active ? '0' : '1' }}">
                <button type="submit" class="dropdown-item" onclick="return confirm('{{ $package->is_active ? 'Désactiver' : 'Activer' }} ce package ?')">
                    <i class="la la-{{ $package->is_active ? 'eye-slash' : 'eye' }}"></i> {{ $package->is_active ? 'Activer' : 'Désactiver' }}
                </button>
            </form>
        </li>
        <li>
            <a class="dropdown-item" href="{{ route('admin.packages.show', $package->id) }}">
                <i class="la la-eye"></i> Voir détails
            </a>
        </li>
        <li>
            <form method="POST" action="{{ route('admin.packages.delete', $package->id) }}" style="display: inline; width: 100%;">
                @csrf
                @method('DELETE')
                <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer le package « {{ addslashes($package->display_name) }} » ?')">
                    <i class="la la-trash"></i> Supprimer
                </button>
            </form>
        </li>
    </ul>
</div>