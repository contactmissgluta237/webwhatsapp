@extends('modern.layouts.master')

@section('title', $title)

@section('content')

<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="col-8 p-0">
        <h2 class="content-header-title mb-0">{{ $title }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                @foreach($breadcrumbs as $breadcrumb)
                    @if($breadcrumb['url'])
                        <li class="breadcrumb-item">
                            <a href="{{ $breadcrumb['url'] }}"><i class="{{ $breadcrumb['icon'] }}"></i>{{ $breadcrumb['name'] }}</a>
                        </li>
                    @else
                        <li class="breadcrumb-item active">{{ $breadcrumb['name'] }}</li>
                    @endif
                @endforeach
            </ol>
        </div>
    </div>
    <div class="col-4 p-0">
        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('admin.transactions.recharge') }}" class="btn btn-success">
                <i class="ti ti-plus fs-4"></i>
                {{ __('New Recharge') }}
            </a>
            <a href="{{ route('admin.transactions.withdrawal') }}" class="btn btn-warning">
                <i class="ti ti-minus fs-4"></i>
                {{ __('New Withdrawal') }}
            </a>
        </div>
    </div>
</div>
<!-- Breadcrumb end -->

<div class="row">
    <div class="col-12">
        <x-session-alerts />
        <div class="card">
            <div class="card-body">
                @livewire('admin.transactions.data-tables.external-transaction-data-table')
            </div>
        </div>
    </div>
</div>
@endsection
