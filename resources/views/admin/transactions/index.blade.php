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
                    @foreach($breadcrumbs as $breadcrumb)
                        @if($breadcrumb['url'])
                            <li class="breadcrumb-item">
                                <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['name'] }}</a>
                            </li>
                        @else
                            <li class="breadcrumb-item active">{{ $breadcrumb['name'] }}</li>
                        @endif
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
    <div class="content-header-right col-md-6 col-12 text-right">
        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('admin.transactions.recharge') }}" class="btn btn-whatsapp rounded btn-glow">
                <i class="la la-plus mr-1"></i>
                {{ __('Nouvelle Recharge') }}
            </a>
            <a href="{{ route('admin.transactions.withdrawal') }}" class="btn btn-outline-whatsapp rounded btn-glow">
                <i class="la la-minus mr-1"></i>
                {{ __('Nouveau Retrait') }}
            </a>
        </div>
    </div>
</div>
<!-- Breadcrumb end -->

<div class="row">
    <div class="col-12">
        <x-session-alerts />
        <div class="card shadow-none border-gray-light">
            <div class="card-header bg-white border-bottom-0">
                <h4 class="card-title text-whatsapp">Liste des Transactions</h4>
            </div>
            <div class="card-body">
                @livewire('admin.transactions.data-tables.external-transaction-data-table')
            </div>
        </div>
    </div>
</div>
@endsection
