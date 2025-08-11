@extends('modern.layouts.master')

@section('title', $title)

@section('content')
<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="col-12 p-0">
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
</div>
<!-- Breadcrumb end -->

<div class="card">
    <div class="card-body">
        <livewire:admin.transactions.data-tables.internal-transaction-data-table />
    </div>
</div>
@endsection