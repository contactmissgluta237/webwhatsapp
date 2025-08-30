@extends('modern.layouts.master')

@section('title', $title)

@section('content')

<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="content-header-left col-md-6 col-12 mb-2">
        <h3 class="content-header-title text-whatsapp">{{ $title }}</h3>
        <div class="row breadcrumbs-top">
            <div class="breadcrumb-wrapper col-12">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.coupons.index') }}">Coupons</a></li>
                    <li class="breadcrumb-item active">Créer</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="content-header-right col-md-6 col-12 text-right">
        <div class="btn-group">
            <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary">
                <i class="la la-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>
</div>
<!-- Breadcrumb end -->

<div class="content-body">
    <section id="coupon-creation">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-none border border-gray-light">
                    <div class="card-body">
                        @livewire('admin.coupons.forms.create-coupon-form')
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection