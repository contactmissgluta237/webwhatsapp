@extends('modern.layouts.master')

@section('title', 'Gestion des Packages')

@section('content')
    <div class="row mx-0 mt-1 mb-1">
        <div class="content-header-left col-md-6 col-12 mb-2">
            <h3 class="content-header-title text-whatsapp">Gestion des Packages</h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active">Packages</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="content-header-right col-md-6 col-12 text-right">
            <a href="{{ route('admin.packages.create') }}" class="btn btn-whatsapp rounded btn-glow">
                <i class="la la-plus mr-1"></i> Nouveau Package
            </a>
        </div>
    </div>

    <div class="content-body">
        <section id="packages-list">
            @livewire('admin.packages-data-table')
        </section>
    </div>
@endsection