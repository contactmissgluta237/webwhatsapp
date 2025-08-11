@extends('customer.layouts.main')

@section('title', 'Mon Profil')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Mon Profil</h4>
            <p class="text-muted mb-0">Gérez vos informations personnelles et vos paramètres</p>
        </div>
    </div>

    @livewire('shared.profile-form', ['userType' => 'customer'])
@endsection