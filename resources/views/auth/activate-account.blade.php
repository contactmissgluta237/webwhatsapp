@extends('auth.layout')

@section('title', 'Activation du compte')

@section('content')
    @livewire('auth.activate-account-form', [
        'identifier' => $identifier ?? '',
    ])
@endsection
