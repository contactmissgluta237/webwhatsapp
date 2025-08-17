@extends('layout.modern')

@section('title', __('Mes Produits'))

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-body">
                @livewire('customer.product-manager')
            </div>
        </div>
    </div>
</div>
@endsection