@props(['avatar', 'current_avatar_url'])

<div class="card mb-4 shadow-none border-gray-light">
    <div class="card-body text-center">
        <h5 class="card-title d-flex align-items-center justify-content-center gap-2 mb-4">
            <i class="ti ti-camera"></i>
            Photo de profil
        </h5>
        
        <div class="mb-4">
            @if($avatar)
                {{-- Si un fichier est sélectionné, vérifier s'il est prévisualisable --}}
                @try
                    <img src="{{ $avatar->temporaryUrl() }}" 
                         alt="Aperçu de la nouvelle photo" 
                         class="rounded-circle border border-primary" 
                         width="120" 
                         height="120"
                         style="object-fit: cover;">
                    <p class="text-primary mt-2 mb-0">
                        <i class="ti ti-eye me-1"></i>
                        Aperçu de la nouvelle photo
                    </p>
                @catch(Livewire\Features\SupportFileUploads\Exceptions\FileNotPreviewableException $e)
                    {{-- Si le fichier n'est pas prévisualisable, afficher un message --}}
                    <div class="rounded-circle border border-warning d-flex align-items-center justify-content-center" 
                         style="width: 120px; height: 120px; background-color: #fff3cd;">
                        <i class="ti ti-file-x text-warning" style="font-size: 2rem;"></i>
                    </div>
                    <p class="text-warning mt-2 mb-0">
                        <i class="ti ti-alert-triangle me-1"></i>
                        Type de fichier non supporté
                    </p>
                @endtry
            @else
                {{-- Sinon, afficher l'image actuelle --}}
                <img src="{{ $current_avatar_url }}" 
                     alt="Photo de profil" 
                     class="rounded-circle border" 
                     width="120" 
                     height="120"
                     style="object-fit: cover;">
            @endif
        </div>
        
        <form wire:submit.prevent="updateAvatar" enctype="multipart/form-data">
            <div class="row align-items-end mb-3">
                <div class="col-md-8">
                    <input type="file" 
                           class="form-control @error('avatar') is-invalid @enderror" 
                           wire:model="avatar" 
                           accept="image/*">
                    @error('avatar')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <button type="submit" 
                            class="btn btn-whatsapp w-100"
                            wire:loading.attr="disabled"
                            @if(!$avatar) disabled @endif>
                        <span wire:loading.remove wire:target="updateAvatar">
                            <i class="ti ti-upload me-1"></i>
                            Mettre à jour
                        </span>
                        <span wire:loading wire:target="updateAvatar">
                            <i class="ti ti-loader-2 me-1"></i>
                            Upload...
                        </span>
                    </button>
                </div>
            </div>
            
            @if(auth()->user()->hasAvatar())
                <div class="text-center">
                    <button type="button" 
                            class="btn btn-outline-danger"
                            wire:click="removeAvatar"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="removeAvatar">
                            <i class="ti ti-trash me-1"></i>
                            Supprimer la photo
                        </span>
                        <span wire:loading wire:target="removeAvatar">
                            <i class="ti ti-loader-2 me-1"></i>
                            Suppression...
                        </span>
                    </button>
                </div>
            @endif
        </form>
        
        <div class="mt-3">
            <small class="text-muted">
                <i class="ti ti-info-circle me-1"></i>
                {{ __('profile.accepted_formats_size') }}
            </small>
        </div>
    </div>
</div>