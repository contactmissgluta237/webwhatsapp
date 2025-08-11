<div class="form-group">
    <div class="input-group">
        <input type="text" 
               class="form-control @if($errorMessage) is-invalid @elseif($isValid) is-valid @endif" 
               wire:model.live.debounce.300ms="sessionName"
               placeholder="ex: mon-whatsapp-principal"
               required>
        
        <div class="input-group-append">
            <button type="button"
                    class="btn btn-primary"
                    wire:click="generateQRCode"
                    @if(!$isValid || empty($sessionName) || $isGenerating) disabled @endif>
                @if($isGenerating)
                    <span class="d-flex align-items-center">
                        <i class="la la-spinner la-spin mr-1"></i> Génération en cours...
                    </span>
                @else
                    <span class="d-flex align-items-center">
                        <i class="la la-qrcode"></i> {{ __('Générer un nouveau QR Code') }}
                    </span>
                @endif
            </button>
        </div>
        
        @if($errorMessage)
            <div class="invalid-feedback d-block">{{ $errorMessage }}</div>
        @endif
    </div>
    
    {{-- Loading global seulement pendant la génération --}}
    @if($isGenerating)
        <div class="text-center mt-2">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="sr-only">Chargement...</span>
            </div>
            <small class="text-muted d-block">Génération du QR code en cours, cela peut prendre jusqu'à 30 secondes...</small>
        </div>
    @endif
    
    @if(!$isGenerating)
        <small class="form-text text-muted">
            Le nom est libre et peut être dupliqué. Seules les règles de format s'appliquent : 3-50 caractères, lettres, chiffres, tirets et underscores.
        </small>
    @endif
</div>
