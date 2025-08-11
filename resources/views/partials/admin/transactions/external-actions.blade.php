<div class="dropdown">
    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-ellipsis-v"></i>
    </button>
    <ul class="dropdown-menu">
        @if($transaction->needsApproval())
            <li>
                <form action="{{ route('admin.transactions.externals.approve', $transaction) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir approuver cette transaction ? Cette action est irréversible.');">
                    @csrf
                    <button type="submit" class="dropdown-item text-success" title="Approuver">
                        <i class="fas fa-check me-2"></i>Approuver
                    </button>
                </form>
            </li>
        @endif
        
        @if($transaction->isPending())
            <li>
                <button type="button" class="dropdown-item text-danger" title="Annuler">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
            </li>
        @endif
        
        <li>
            <button type="button" class="dropdown-item" title="Voir détails">
                <i class="fas fa-eye me-2"></i>Détails
            </button>
        </li>
        
        <li>
            <button type="button" class="dropdown-item" title="Modifier">
                <i class="fas fa-edit me-2"></i>Modifier
            </button>
        </li>
    </ul>
</div>
