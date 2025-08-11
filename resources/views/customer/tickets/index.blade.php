@extends('modern.layouts.master')

@section('title', 'My Tickets')

@section('breadcrumb')
<div class="content-header-left col-md-8 col-12 mb-2">
    <div class="row breadcrumbs-top">
        <div class="col-12">
            <h2 class="content-header-title float-left mb-0">My Tickets</h2>
            <div class="breadcrumb-wrapper col-12">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Tickets</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-12 text-end mb-3">
        <a href="{{ route('customer.tickets.create') }}" class="btn btn-primary">{{ __('tickets.create_new_ticket') }}</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <livewire:customer.ticket.ticket-data-table />
    </div>
</div>
@endsection