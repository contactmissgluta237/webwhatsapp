@extends('modern.layouts.master')

@section('title', 'Create New Ticket')

@section('content')
<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="col-8 p-0">
        <h2 class="content-header-title mb-0">Create New Ticket</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}"><i class="la la-home"></i>Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('customer.tickets.index') }}">Tickets</a></li>
                <li class="breadcrumb-item active">{{ __('tickets.create') }}</li>
            </ol>
        </div>
    </div>
    <div class="col-4 p-0">
        <div class="d-flex justify-content-end">
            <a href="{{ route('customer.tickets.index') }}" class="btn btn-info">
                <i class="la la-reply"></i>
                Retour
            </a>
        </div>
    </div>
</div>
<!-- Breadcrumb end -->

<div class="card">
    <div class="card-body">
        @livewire('customer.ticket.create-ticket-form')
    </div>
</div>
@endsection