@extends('modern.layouts.master')

@section('title', __('Edit User'))


@section('content')
<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="col-8 p-0">
        <h2 class="content-header-title mb-0">{{ __('Edit User') }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}"><i class="la la-home"></i>{{ __('Home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">{{ __('Users') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Edit') }}</li>
            </ol>
        </div>
    </div>
    <div class="col-4 p-0">
        <div class="d-flex justify-content-end">
            <a href="{{ route('admin.users.index') }}" class="btn btn-info"><i class="la la-reply"></i>{{ __('Back to Users') }}</a>
        </div>
    </div>
</div>
<!-- Breadcrumb end -->

@livewire('admin.users.forms.edit-user-form', ['user' => $user])
@endsection
