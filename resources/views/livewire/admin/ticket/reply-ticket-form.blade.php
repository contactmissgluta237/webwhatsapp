<form wire:submit.prevent="replyToTicket">
    <div class="mb-3">
        <label for="message" class="form-label">Message</label>
        <textarea class="form-control" id="message" rows="5" wire:model="message"></textarea>
        @error('message') <span class="text-danger">{{ $message }}</span> @enderror
    </div>

    <div class="mb-3">
        <label for="attachments" class="form-label">Attachments (Optional)</label>
        <input type="file" class="form-control" id="attachments" wire:model="attachments" multiple>
        @error('attachments.*') <span class="text-danger">{{ $message }}</span> @enderror
        @if ($this->attachmentPreviewUrls)
            <div class="mt-2 d-flex flex-wrap gap-2">
                @foreach($this->attachmentPreviewUrls as $url)
                    <img src="{{ $url }}" class="img-thumbnail" style="max-width: 100px; max-height: 100px;">
                @endforeach
            </div>
        @endif
    </div>

    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="isInternal" wire:model="isInternal">
        <label class="form-check-label" for="isInternal">Internal Note (Only visible to admins)</label>
    </div>

    <button type="submit" class="btn btn-whatsapp">{{ __('tickets.send_reply') }}</button>
</form>
