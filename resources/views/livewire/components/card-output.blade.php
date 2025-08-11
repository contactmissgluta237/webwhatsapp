<div>
    @if($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif
    
    <input type="text" 
           class="form-control" 
           placeholder="Numéro de la carte" 
           name="{{ $name }}" 
           value="{{ $value }}" 
           wire:model.live="value" 
           maxlength="19"
           id="{{ $name }}"
           {{ $required ? 'required' : '' }}>
</div>
