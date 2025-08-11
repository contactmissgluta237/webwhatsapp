@extends('modern.layouts.master')

@section('title', 'Reply to Ticket')

@section('breadcrumb')
<div class="content-header-left col-md-8 col-12 mb-2">
    <div class="row breadcrumbs-top">
        <div class="col-12">
            <h2 class="content-header-title float-left mb-0">Reply to Ticket: {{ $ticket->ticket_number }}</h2>
            <div class="breadcrumb-wrapper col-12">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.tickets.index') }}">Tickets</a></li>
                    <li class="breadcrumb-item active">{{ __('tickets.reply') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        @livewire('admin.ticket.reply-ticket-form', ['ticket' => $ticket])
    </div>
</div>
@endsection