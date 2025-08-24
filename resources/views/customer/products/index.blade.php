@extends('modern.layouts.master')

@section('title', __('Gestion des Produits'))

@section('content')
    <div class="row mx-0 mt-1 mb-1">
        <div class="content-header-left col-md-6 col-12 mb-2">
            <h3 class="content-header-title text-whatsapp">Mes produits</h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active">Produits</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="content-header-right col-md-6 col-12 text-right">
            <a href="{{ route('customer.products.create') }}" class="btn btn-whatsapp rounded btn-glow">
                <i class="la la-plus mr-1"></i> {{ __('Nouveau Produit') }}
            </a>
        </div>
    </div>

    <div class="content-body">
        <section id="products-list">
            @livewire('customer.product-data-table')
        </section>
    </div>
@endsection