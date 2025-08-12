<div>
    @if($showModal)
        <div class="modal fade show" style="display: block;" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="assignModalLabel">Assign Ticket</h5>
                        <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="assignTicket">
                            <div class="mb-3">
                                <label for="adminSelect" class="form-label">Select Admin</label>
                                <select class="form-select" id="adminSelect" wire:model="selectedAdminId">
                                    <option value="">Choose an admin...</option>
                                    @foreach($this->admins as $admin)
                                        <option value="{{ $admin->id }}">{{ $admin->full_name }}</option>
                                    @endforeach
                                </select>
                                @error('selectedAdminId') 
                                    <div class="text-danger">{{ $message }}</div> 
                                @enderror
                            </div>
                            
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                                <button type="submit" class="btn btn-whatsapp" wire:loading.attr="disabled">
                                    <span wire:loading.remove>Assign Ticket</span>
                                    <span wire:loading>Assigning...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>