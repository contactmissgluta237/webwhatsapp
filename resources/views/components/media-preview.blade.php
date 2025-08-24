@php
    $cardSize = $getCardSize();
@endphp

<div class="media-preview-card border rounded position-relative" 
     style="width: {{ $cardSize['width'] }}; height: {{ $cardSize['height'] }};">
    
    {{-- Ligne 1: Nom centré verticalement --}}
    <div class="px-2 bg-whatsapp text-white d-flex align-items-center justify-content-center" 
         style="height: 5%; min-height: 20px; font-size: 10px;">
        <div class="text-truncate fw-bold" title="{{ $getFileName() }}">
            {{ Str::limit($getFileName(), 15) }}
        </div>
    </div>

    {{-- Ligne 2: Preview (90% de l'espace) --}}
    <div class="d-flex align-items-center justify-content-center bg-white" 
         style="height: 90%;">
        
        @if($isImageFile())
            <img src="{{ $getPreviewUrl() }}" 
                 class="img-fluid" 
                 style="max-width: 95%; max-height: 95%; object-fit: cover;"
                 alt="{{ $getFileName() }}">
        @elseif($isVideoFile())
            <div class="text-center">
                <i class="fas fa-video fa-2x text-info mb-1"></i>
                <div class="small">{{ $getFileExtension() }}</div>
            </div>
        @elseif($isPdfFile())
            <div class="text-center">
                <i class="fas fa-file-pdf fa-2x text-danger mb-1"></i>
                <div class="small">PDF</div>
            </div>
        @elseif($isDocumentFile())
            <div class="text-center">
                <i class="fas fa-file-word fa-2x text-primary mb-1"></i>
                <div class="small">{{ $getFileExtension() }}</div>
            </div>
        @else
            <div class="text-center">
                <i class="fas fa-file fa-2x text-secondary mb-1"></i>
                <div class="small">{{ $getFileExtension() }}</div>
            </div>
        @endif
    </div>

    {{-- Ligne 3: Taille à gauche, Extension à droite --}}
    <div class="px-2 bg-whatsapp text-white d-flex align-items-center justify-content-between" 
         style="height: 5%; min-height: 20px; font-size: 10px;">
        <small>{{ $getFileSize() }}</small>
        <small class="fw-bold">{{ $getFileExtension() }}</small>
    </div>

    {{-- Bouton supprimer - coin droit supérieur --}}
    @if($showDelete)
        <button type="button" 
                class="btn btn-danger position-absolute rounded-circle d-flex align-items-center justify-content-center shadow"
                style="width: 26px; height: 26px; top: -12px; right: -12px; z-index: 10; font-size: 14px; padding: 0; border: 3px solid white; background-color: #dc3545 !important;"
                wire:click="{{ $wireMethod }}({{ $index }})"
                title="Supprimer">
            <i class="fas fa-times text-white fw-bold" style="font-size: 14px; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);"></i>
        </button>
    @endif
</div>
