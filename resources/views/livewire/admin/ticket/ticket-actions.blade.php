<div>
    @if($ticket->isOpen())
        <button type="button" class="btn btn-success btn-sm mb-2 w-100" 
                wire:click="changeStatus('resolved')" 
                wire:loading.attr="disabled">
            <i class="fas fa-check me-2"></i>Mark as Resolved
        </button>
        <button type="button" class="btn btn-danger btn-sm mb-2 w-100" 
                wire:click="changeStatus('closed')" 
                wire:loading.attr="disabled">
            <i class="fas fa-times me-2"></i>Mark as Closed
        </button>
    @elseif($ticket->isReplied())
        <button type="button" class="btn btn-success btn-sm mb-2 w-100" 
                wire:click="changeStatus('resolved')" 
                wire:loading.attr="disabled">
            <i class="fas fa-check me-2"></i>Mark as Resolved
        </button>
        <button type="button" class="btn btn-danger btn-sm mb-2 w-100" 
                wire:click="changeStatus('closed')" 
                wire:loading.attr="disabled">
            <i class="fas fa-times me-2"></i>Mark as Closed
        </button>
    @endif

    <div class="dropdown mb-2 w-100" x-data="{}" x-init="new bootstrap.Dropdown($el.querySelector('.dropdown-toggle'))">
        <button class="btn btn-outline-primary btn-sm dropdown-toggle w-100" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-exclamation-triangle me-2"></i>Change Priority
        </button>
        <ul class="dropdown-menu w-100" aria-labelledby="dropdownMenuButton">
            @foreach(\App\Enums\TicketPriority::cases() as $priority)
                <li>
                    <a class="dropdown-item {{ $ticket->priority->equals($priority) ? 'active' : '' }}" href="#"
                       wire:click="changePriority('{{ $priority->value }}')"
                       wire:loading.attr="disabled">
                        {{ $priority->label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    @if(!$ticket->isClosed() && !$ticket->isResolved() && !$ticket->assignedTo)
        <button type="button" class="btn btn-secondary btn-sm mb-2 w-100" 
                wire:click="assignToMe"
                wire:loading.attr="disabled">
            <i class="fas fa-user-plus me-2"></i>Assign to Me
        </button>
    @elseif(!$ticket->isClosed() && !$ticket->isResolved() && $ticket->assignedTo && $ticket->assignedTo->id !== auth()->id())
        <button type="button" class="btn btn-outline-secondary btn-sm mb-2 w-100" 
                wire:click="assignToMe"
                wire:loading.attr="disabled">
            <i class="fas fa-user-edit me-2"></i>Reassign to Me
        </button>
    @endif
</div>