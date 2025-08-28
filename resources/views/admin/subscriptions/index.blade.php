@extends('modern.layouts.master')

@section('title', 'Gestion des Souscriptions')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Gestion des Souscriptions</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="#">Souscriptions</a></li>
                        <li class="breadcrumb-item active">Toutes les souscriptions</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    @livewire('admin.subscriptions-data-table')
</div>
@endsection