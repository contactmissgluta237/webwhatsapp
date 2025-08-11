<form wire:submit.prevent="createTicket">
    <div class="mb-3">
        <label for="title" class="form-label">Title</label>
        <input type="text" class="form-control @error('title') is-invalid @enderror" 
               id="title" wire:model="title">
        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea class="form-control @error('description') is-invalid @enderror" 
                  id="description" rows="5" wire:model="description"></textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label for="attachments" class="form-label">Attachments (Optional)</label>
        <input type="file" class="form-control @error('attachments.*') is-invalid @enderror" 
               id="attachments" wire:model="attachments" multiple 
               accept=".jpg,.jpeg,.png,.pdf">
        <div class="form-text">Max 2MB per file. Formats: JPG, PNG, PDF</div>
        @error('attachments.*') <div class="invalid-feedback">{{ $message }}</div> @enderror
        
        {{-- Preview simple des fichiers sélectionnés --}}
        @if ($attachments)
            <div class="mt-2">
                <small class="text-muted">Selected files:</small>
                <ul class="list-unstyled">
                    @foreach($attachments as $attachment)
                        <li>
                            <small>📎 {{ $attachment->getClientOriginalName() }}</small>
                            @if ($attachment->temporaryUrl())
                                <div class="mt-1">
                                    @if (str_contains($attachment->getMimeType(), 'image'))
                                        <img src="{{ $attachment->temporaryUrl() }}" alt="Preview" class="img-thumbnail" style="max-width: 150px;">
                                    @elseif (str_contains($attachment->getMimeType(), 'pdf'))
                                        <embed src="{{ $attachment->temporaryUrl() }}" type="application/pdf" width="150" height="200">
                                    @endif
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($this->attachmentPreviewUrls)
            <div class="mt-2 d-flex flex-wrap gap-2">
                @foreach($this->attachmentPreviewUrls as $url)
                    <img src="{{ $url }}" class="img-thumbnail" style="max-width: 100px; max-height: 100px;">
                @endforeach
            </div>
        @endif
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-info" wire:loading.attr="disabled">
            <span wire:loading.remove>Submit Ticket</span>
            <span wire:loading>Submitting...</span>
        </button>
        <a href="{{ route('customer.tickets.index') }}" class="btn btn-danger">{{ __('tickets.cancel') }}</a>
    </div>
</form>
