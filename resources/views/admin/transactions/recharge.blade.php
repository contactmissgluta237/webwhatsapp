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
                    <li class="breadcrumb-item"><a href="{{ route('admin.transactions.index') }}">Transactions</a></li>
                    <li class="breadcrumb-item active">Recharge</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="content-header-right col-md-6 col-12 text-right">
        <div class="btn-group">
            <a href="{{ route('admin.transactions.index') }}" class="btn btn-outline-secondary">
                <i class="la la-arrow-left"></i> Retour
            </a>
        </div>
    </div>
</div>
<!-- Breadcrumb end -->

<div class="content-body">
    <section id="recharge-management">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-none border border-gray-light">
                    <div class="card-body">
                        @livewire('admin.transactions.forms.create-admin-recharge-form')
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
