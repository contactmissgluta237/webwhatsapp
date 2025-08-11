<div class="d-flex justify-content-between align-items-center">
    <button type="button" wire:click="resetForm" class="btn btn-outline-secondary">
        <i class="ti ti-refresh"></i>
        Réinitialiser
    </button>
    
    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
        <span wire:loading.remove>
            <i class="ti ti-check"></i>
            Créer le Retrait
        </span>
        <span wire:loading>
            <i class="ti ti-loader ti-spin"></i>
            Création en cours...
        </span>
    </button>
</div>