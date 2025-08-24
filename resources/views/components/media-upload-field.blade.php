<div class="form-group">
    <label class="form-label">{{ $label }}</label>
    
    <input type="file" 
           class="form-control @error($errorBag) is-invalid @enderror" 
           wire:model="{{ $wireModel }}" 
           {{ $multiple ? 'multiple' : '' }}
           accept="{{ $accept }}"
           max="{{ $maxFiles }}">
    
    <div class="form-text">{{ $helpText }}</div>

    @error($errorBag)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
