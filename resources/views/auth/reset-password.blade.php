@extends('auth.layout')

@section('title', 'Réinitialiser le mot de passe')

@section('content')
    @livewire('auth.reset-password-form', ['token' => $token, 'identifier' => $identifier, 'resetType' => $resetType])
@endsection
