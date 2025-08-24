@extends('modern.layouts.master')

@section('title', __('Modifier le produit'))

@section('content')
    <div class="row mx-0 mt-1 mb-1">
        <div class="content-header-left col-md-6 col-12 mb-2">
            <h3 class="content-header-title">Modifier le produit</h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('customer.products.index') }}">Produits</a></li>
                        <li class="breadcrumb-item active">Modifier</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="content-header-right col-md-6 col-12 text-right">
            <a href="{{ route('customer.products.index') }}" class="btn btn-outline-secondary rounded">
                <i class="la la-arrow-left mr-1"></i> {{ __('Retour') }}
            </a>
        </div>
    </div>

    <div class="content-body">
        <section id="product-edit">
            <div class="card">
                <div class="card-body">
                    @livewire('customer.products.forms.edit-product-form', ['product' => $product])
                </div>
            </div>
        </section>
    </div>
@endsection
