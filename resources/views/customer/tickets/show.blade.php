@extends('modern.layouts.master')

@section('title', 'Ticket #' . $ticket->ticket_number)

@section('breadcrumb')
<div class="content-header-left col-md-8 col-12 mb-2">
    <div class="row breadcrumbs-top">
        <div class="col-12">
            <h2 class="content-header-title float-left mb-0">{{ __('tickets.ticket_number') }}{{ $ticket->ticket_number }}</h2>
            <div class="breadcrumb-wrapper col-12">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">{{ __('tickets.home') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('customer.tickets.index') }}">{{ __('tickets.tickets') }}</a></li>
                    <li class="breadcrumb-item active">{{ $ticket->ticket_number }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-12 text-end mb-3">
        <span class="badge bg-{{ $ticket->status->badge() }} me-2">{{ $ticket->status->label }}</span>
        <span class="badge bg-{{ $ticket->priority->badge() }}">{{ $ticket->priority->label }}</span>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ $ticket->title }}</h5>
            </div>
            <div class="card-body">
                <div class="ticket-messages">
                    @foreach($ticket->messages as $message)
                        <div class="message mb-4 p-3 {{ $message->sender_type === 'customer' ? 'bg-light-primary' : 'bg-light-secondary' }} rounded">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-3">
                                        <img src="{{ $message->user->avatar_url }}" alt="{{ $message->user->full_name }}" class="rounded-circle" style="width: 40px; height: 40px;">
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $message->user->full_name }}</h6>
                                        <small class="text-muted">{{ __('tickets.' . $message->sender_type) }}</small>
                                    </div>
                                </div>
                                
                                <small class="text-muted">{{ $message->created_at->format('d M Y, H:i') }}</small>
                            </div>
                            
                            <div class="message-content">
                                <p class="mb-2">{!! nl2br(e($message->message)) !!}</p>
                                
                                @if($message->getMedia('attachments')->count() > 0)
                                    <div class="attachments mt-3">
                                        <h6 class="mb-2">{{ __('tickets.attachments') }}</h6>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($message->getMedia('attachments') as $attachment)
                                                <a href="{{ $attachment->getUrl() }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-paperclip me-1"></i>{{ $attachment->name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                
                                @if($message->is_internal)
                                    <div class="mt-2">
                                        <span class="badge bg-warning">{{ __('tickets.internal_note') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($ticket->isOpen() || $ticket->isReplied())
                    <hr class="my-4">
                    <div class="reply-section">
                        <h5 class="mb-3">{{ __('tickets.send_reply') }}</h5>
                        @livewire('customer.ticket.reply-ticket-form', ['ticket' => $ticket])
                    </div>
                @else
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        {{ __('tickets.ticket_closed_cannot_reply', ['status' => strtolower($ticket->status->label)]) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('tickets.ticket_information') }}</h5>
            </div>
            <div class="card-body">
                <div class="ticket-info">
                    <div class="mb-3">
                        <strong>{{ __('tickets.status') }}</strong>
                        <span class="badge bg-{{ $ticket->status->badge() }} ms-2">{{ $ticket->status->label }}</span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>{{ __('tickets.priority') }}</strong>
                        <span class="badge bg-{{ $ticket->priority->badge() }} ms-2">{{ $ticket->priority->label }}</span>
                    </div>

                    <div class="mb-3">
                        <strong>{{ __('tickets.created_by') }}</strong>
                        <div class="d-flex align-items-center mt-1">
                            <img src="{{ $ticket->user->avatar_url }}" alt="{{ $ticket->user->full_name }}" class="rounded-circle me-2" style="width: 24px; height: 24px;">
                            <span>{{ $ticket->user->full_name }} {{ __('tickets.you') }}</span>
                        </div>
                    </div>

                    @if($ticket->assignedTo)
                        <div class="mb-3">
                            <strong>{{ __('tickets.assigned_to') }}</strong>
                            <div class="d-flex align-items-center mt-1">
                                <img src="{{ $ticket->assignedTo->avatar_url }}" alt="{{ $ticket->assignedTo->full_name }}" class="rounded-circle me-2" style="width: 24px; height: 24px;">
                                <span>{{ $ticket->assignedTo->full_name }}</span>
                            </div>
                        </div>
                    @else
                        <div class="mb-3">
                            <strong>{{ __('tickets.assigned_to') }}</strong>
                            <span class="text-muted">{{ __('tickets.awaiting_assignment') }}</span>
                        </div>
                    @endif

                    <div class="mb-3">
                        <strong>{{ __('tickets.created_at') }}</strong>
                        <div class="text-muted">{{ $ticket->created_at->format('d M Y, H:i') }}</div>
                    </div>

                    @if($ticket->closed_at)
                        <div class="mb-3">
                            <strong>{{ __('tickets.closed_at') }}</strong>
                            <div class="text-muted">{{ $ticket->closed_at->format('d M Y, H:i') }}</div>
                        </div>
                    @endif
                </div>

                @if($ticket->getMedia('attachments')->count() > 0)
                    <hr>
                    <div class="initial-attachments">
                        <h6 class="mb-2">{{ __('tickets.initial_attachments') }}</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($ticket->getMedia('attachments') as $attachment)
                                <a href="{{ $attachment->getUrl() }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-paperclip me-1"></i>{{ $attachment->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('tickets.ticket_progress') }}</h5>
            </div>
            <div class="card-body">
                <div class="progress mb-3" style="height: 6px;">
                    @php
                        $progress = match($ticket->status->value) {
                            'open' => 25,
                            'replied' => 50,
                            'resolved' => 75,
                            'closed' => 100,
                            default => 25
                        };
                    @endphp
                    <div class="progress-bar bg-{{ $ticket->status->badge() }}" role="progressbar" style="width: {{ $progress }}%"></div>
                </div>
                
                <div class="timeline">
                    <div class="timeline-item completed">
                        <i class="fas fa-plus-circle text-success"></i>
                        <span class="ms-2">{{ __('tickets.ticket_created') }}</span>
                        <small class="text-muted d-block ms-4">{{ $ticket->created_at->format('d M Y, H:i') }}</small>
                    </div>
                    
                    @if($ticket->assignedTo)
                        <div class="timeline-item completed mt-2">
                            <i class="fas fa-user-check text-info"></i>
                            <span class="ms-2">{{ __('tickets.assigned_to_support') }}</span>
                        </div>
                    @endif
                    
                    @if($ticket->isReplied())
                        <div class="timeline-item completed mt-2">
                            <i class="fas fa-reply text-success"></i>
                            <span class="ms-2">{{ __('tickets.under_review') }}</span>
                        </div>
                    @endif
                    
                    @if($ticket->isResolved() || $ticket->isClosed())
                        <div class="timeline-item completed mt-2">
                            <i class="fas fa-check-circle text-success"></i>
                            <span class="ms-2">{{ $ticket->isResolved() ? __('tickets.resolved') : __('tickets.closed') }}</span>
                            @if($ticket->closed_at)
                                <small class="text-muted d-block ms-4">{{ $ticket->closed_at->format('d M Y, H:i') }}</small>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if($ticket->isOpen() || $ticket->isReplied())
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('tickets.need_help') }}</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('customer.tickets.create') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-plus me-1"></i>{{ __('tickets.create_new_ticket') }}
                        </a>
                        <a href="{{ route('customer.tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-list me-1"></i>{{ __('tickets.view_all_tickets') }}
                        </a>
                        <a href="mailto:support@{{ request()->getHost() }}" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-envelope me-1"></i>{{ __('tickets.email_support') }}
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@push('styles')
<style>
.bg-light-primary {
    background-color: rgba(13, 110, 253, 0.1) !important;
    border-left: 3px solid #0d6efd;
}

.bg-light-secondary {
    background-color: rgba(108, 117, 125, 0.1) !important;
    border-left: 3px solid #6c757d;
}

.message {
    transition: all 0.3s ease;
}

.message:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.ticket-info strong {
    color: #495057;
    font-weight: 600;
}

.avatar-sm img {
    object-fit: cover;
}

.attachments a {
    text-decoration: none;
}

.attachments a:hover {
    transform: translateY(-1px);
}

.timeline-item {
    opacity: 0.5;
    transition: opacity 0.3s ease;
}

.timeline-item.completed {
    opacity: 1;
}

.timeline-item i {
    width: 16px;
    text-align: center;
}

.progress {
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar {
    transition: width 0.6s ease;
}
</style>
@endpush
@endsection
