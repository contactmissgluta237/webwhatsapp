@extends('modern.layouts.master')

@section('title', 'Mouvements de compte')

@section('breadcrumb')
<div class="content-header-left col-md-8 col-12 mb-2">
    <div class="row breadcrumbs-top">
        <div class="col-12">
            <h2 class="content-header-title float-left mb-0">Mouvements de compte</h2>
            <div class="breadcrumb-wrapper col-12">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item active">Mouvements de compte</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                @livewire('customer.internal-transaction-data-table')
            </div>
        </div>
    </div>
</div>
@endsection
