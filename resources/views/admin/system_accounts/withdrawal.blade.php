@extends('modern.layouts.master')

@section('title', $title)

@section('content')
<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="col-12 p-0">
        <h2 class="content-header-title mb-0">{{ $title }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}"><i class="la la-home"></i>{{ __('Home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.system-accounts.index') }}">{{ __('System Accounts') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Withdrawal') }}</li>
            </ol>
        </div>
    </div>
</div>
<!-- Breadcrumb end -->

<div class="card">
    <div class="card-body">
        <livewire:admin.system-accounts.forms.system-account-withdrawal-form />
    </div>
</div>
@endsection
