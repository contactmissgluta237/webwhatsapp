@extends('modern.layouts.master')

@section('title', $title)

@section('content')
<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="col-8 p-0">
        <h2 class="content-header-title mb-0">{{ $title }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}"><i class="la la-home"></i>{{ __('Home') }}</a></li>
                <li class="breadcrumb-item active"><a href="{{ route('admin.transactions.index') }}">{{ __('Transactions') }}</a>
                <li class="breadcrumb-item active">{{ __('Recharge') }}</li>
            </ol>
        </div>
    </div>
    <div class="col-4 p-0">
        <div class="d-flex justify-content-end">
            <a href="{{ route('admin.transactions.index') }}" class="btn btn-info">
                <i class="la la-reply"></i>
                {{ __('Back') }}
            </a>
        </div>
    </div>
</div>
<!-- Breadcrumb end -->

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                @livewire('admin.transactions.forms.create-admin-recharge-form')
            </div>
        </div>
    </div>
</div>
@endsection
