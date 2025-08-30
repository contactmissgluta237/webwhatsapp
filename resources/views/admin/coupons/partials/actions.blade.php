<div class="dropdown" style="position: static;">
    <button class="btn btn-sm btn-whatsapp dropdown-toggle" type="button" data-toggle="dropdown" 
            aria-haspopup="true" aria-expanded="false" title="Actions"
            data-boundary="window">
        <i class="la la-ellipsis-v"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-right" style="position: absolute; z-index: 1050; min-width: 180px;">
        {{-- Copier le code --}}
        <a class="dropdown-item py-2" href="#" onclick="copyCoupon('{{ $coupon->code }}'); return false;">
            <i class="la la-copy text-info mr-2"></i>
            Copier le code
        </a>
        
        {{-- Modifier --}}
        <a class="dropdown-item py-2" href="{{ route('admin.coupons.edit', $coupon->id) }}">
            <i class="la la-edit text-warning mr-2"></i>
            Modifier
        </a>
        
        {{-- Toggle statut --}}
        <form method="POST" action="{{ route('admin.coupons.toggle-status', $coupon->id) }}" style="display: inline; width: 100%;">
            @csrf
            @if($coupon->status === \App\Enums\CouponStatus::ACTIVE())
                <input type="hidden" name="action" value="{{ \App\Enums\CouponAction::DEACTIVATE()->value }}">
                <button type="submit" class="dropdown-item py-2 text-left border-0 bg-transparent w-100" 
                        onclick="return confirm('Désactiver ce coupon ?')"
                        style="background: none !important;">
                    <i class="la la-pause text-warning mr-2"></i>
                    {{ \App\Enums\CouponAction::DEACTIVATE()->label() }}
                </button>
            @else
                <input type="hidden" name="action" value="{{ \App\Enums\CouponAction::ACTIVATE()->value }}">
                <button type="submit" class="dropdown-item py-2 text-left border-0 bg-transparent w-100" 
                        onclick="return confirm('Activer ce coupon ?')"
                        style="background: none !important;">
                    <i class="la la-play text-success mr-2"></i>
                    {{ \App\Enums\CouponAction::ACTIVATE()->label() }}
                </button>
            @endif
        </form>
        
        @if($coupon->used_count === 0)
            <div class="dropdown-divider"></div>
            
            {{-- Supprimer --}}
            <form method="POST" action="{{ route('admin.coupons.delete', $coupon->id) }}" style="display: inline; width: 100%;">
                @csrf
                @method('DELETE')
                <button type="submit" class="dropdown-item py-2 text-danger text-left border-0 bg-transparent w-100" 
                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer le coupon « {{ addslashes($coupon->code) }} » ? Cette action est irréversible.')"
                        style="background: none !important;">
                    <i class="la la-trash text-danger mr-2"></i>
                    Supprimer
                </button>
            </form>
        @endif
    </div>
</div>

<script>
function copyCoupon(code) {
    navigator.clipboard.writeText(code).then(() => {
        showToast('Code coupon copié : ' + code, 'success');
    }).catch(() => {
        // Fallback pour les anciens navigateurs
        const textArea = document.createElement('textarea');
        textArea.value = code;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showToast('Code coupon copié : ' + code, 'success');
    });
}

function showToast(message, type = 'success') {
    // Créer le toast
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed shadow-none border-${type}`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        <i class="la la-check-circle me-2"></i>
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    // Ajouter au DOM
    document.body.appendChild(toast);
    
    // Supprimer après 3 secondes
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 3000);
}
</script>