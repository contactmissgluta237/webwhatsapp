@extends('modern.layouts.master')

@section('title', 'Tickets Management')

@section('content')
<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="content-header-left col-md-6 col-12 mb-2">
        <h3 class="content-header-title text-whatsapp">Gestion des Tickets</h3>
        <div class="row breadcrumbs-top">
            <div class="breadcrumb-wrapper col-12">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item active">Tickets</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- Breadcrumb end -->

<div class="card shadow-none border-gray-light">
    <div class="card-header bg-white border-bottom-0">
        <h4 class="card-title text-whatsapp">Liste des Tickets</h4>
    </div>
    <div class="card-body">
        <livewire:admin.ticket.ticket-data-table />
    </div>
</div>
@endsection