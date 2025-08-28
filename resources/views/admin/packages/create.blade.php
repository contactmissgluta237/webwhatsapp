@extends('modern.layouts.master')

@section('title', 'Créer un Package')

@section('content')
    <div class="row mx-0 mt-1 mb-1">
        <div class="content-header-left col-md-6 col-12 mb-2">
            <h3 class="content-header-title text-whatsapp">Créer un Package</h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.packages.index') }}">Packages</a></li>
                        <li class="breadcrumb-item active">Créer</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="content-header-right col-md-6 col-12 text-right">
            <a href="{{ route('admin.packages.index') }}" class="btn btn-outline-secondary rounded">
                <i class="la la-arrow-left mr-1"></i> Retour
            </a>
        </div>
    </div>

    <div class="content-body">
        <section id="package-create">
            <div class="card shadow-none border-gray-light">
                <div class="card-header bg-white border-bottom-0">
                    <h4 class="card-title">
                        <i class="la la-plus mr-2 text-whatsapp"></i>Nouveau Package
                    </h4>
                </div>
                <div class="card-body">
                    @livewire('admin.packages.forms.create-package-form')
                </div>
            </div>
        </section>
    </div>
@endsection