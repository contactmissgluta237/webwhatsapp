<div class="dropdown">
    <button class="btn btn-sm btn-whatsapp dropdown-toggle" type="button" id="actions-menu-{{ $product->id }}" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="la la-ellipsis-v"></i>
    </button>
    <ul class="dropdown-menu" aria-labelledby="actions-menu-{{ $product->id }}">
        <li>
            <a class="dropdown-item" href="{{ route('customer.products.edit', $product->id) }}">
                <i class="la la-edit"></i> Modifier
            </a>
        </li>
        <li>
            <form method="POST" action="{{ route('customer.products.toggle-status', $product->id) }}" style="display: inline; width: 100%;">
                @csrf
                <input type="hidden" name="is_active" value="{{ $product->is_active ? '0' : '1' }}">
                <button type="submit" class="dropdown-item" onclick="return confirm('{{ $product->is_active ? 'Désactiver' : 'Activer' }} ce produit ?')">
                    <i class="la la-{{ $product->is_active ? 'eye-slash' : 'eye' }}"></i> {{ $product->is_active ? 'Désactiver' : 'Activer' }}
                </button>
            </form>
        </li>
        <li>
            <form method="POST" action="{{ route('customer.products.delete', $product->id) }}" style="display: inline; width: 100%;">
                @csrf
                @method('DELETE')
                <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer le produit « {{ addslashes($product->title) }} » ?')">
                    <i class="la la-trash"></i> Supprimer
                </button>
            </form>
        </li>
    </ul>
</div>
