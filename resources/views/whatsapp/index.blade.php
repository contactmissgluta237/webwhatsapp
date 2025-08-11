@extends('modern.layouts.master')

@section('title', __('Liste des sessions WhatsApp'))

@section('page-style')
@endsection

@section('content')
<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="col-8 p-0">
        <h2 class="content-header-title mb-0">{{ __('Mes sessions WhatsApp') }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}"><i class="la la-dashboard"></i>{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('WhatsApp') }}</li>
            </ol>
        </div>
    </div>
    <div class="col-4 p-0 text-right">
        <a href="{{ route('whatsapp.create') }}" class="btn btn-primary">
            <i class="la la-plus"></i> {{ __('Créer un nouveau agent IA') }}
        </a>
    </div>
</div>
<!-- Breadcrumb end -->

    <div class="content-body">
        <section id="basic-examples">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">{{ __('Liste de vos sessions WhatsApp') }}</h4>
                            <a class="heading-elements-toggle"><i class="la la-ellipsis-v font-medium-3"></i></a>
                            <div class="heading-elements">
                                <ul class="list-inline mb-0">
                                    <li><a data-action="collapse"><i class="ft-minus"></i></a></li>
                                    <li><a data-action="reload"><i class="ft-rotate-cw"></i></a></li>
                                    <li><a data-action="expand"><i class="ft-maximize"></i></a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-content collapse show">
                            <div class="card-body">
                                @if($sessions->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Nom de session') }}</th>
                                                    <th>{{ __('Téléphone') }}</th>
                                                    <th>{{ __('Statut') }}</th>
                                                    <th>{{ __('Créé le') }}</th>
                                                    <th>{{ __('Actions') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($sessions as $session)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $session->session_name }}</strong>
                                                        </td>
                                                        <td>
                                                            @if($session->phone_number)
                                                                <span class="badge badge-success">{{ $session->phone_number }}</span>
                                                            @else
                                                                <span class="badge badge-secondary">{{ __('Non connecté') }}</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($session->isConnected())
                                                                <span class="badge badge-success">{{ __('Connecté') }}</span>
                                                            @elseif($session->isConnecting())
                                                                <span class="badge badge-warning">{{ __('Connexion...') }}</span>
                                                            @else
                                                                <span class="badge badge-secondary">{{ __('Déconnecté') }}</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $session->created_at->format('d/m/Y H:i') }}</td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <button type="button" class="btn btn-sm btn-primary">
                                                                    <i class="la la-eye"></i> {{ __('Voir') }}
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-warning">
                                                                    <i class="la la-edit"></i> {{ __('Modifier') }}
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-danger">
                                                                    <i class="la la-trash"></i> {{ __('Supprimer') }}
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    {{ $sessions->links() }}
                                @else
                                    <div class="text-center py-5">
                                        <i class="la la-whatsapp text-muted" style="font-size: 4rem;"></i>
                                        <h4 class="text-muted mt-3">{{ __('Aucune session WhatsApp') }}</h4>
                                        <p class="text-muted">{{ __('Vous n\'avez pas encore créé de session WhatsApp.') }}</p>
                                        <a href="{{ route('whatsapp.create') }}" class="btn btn-primary">
                                            <i class="la la-plus"></i> {{ __('Créer votre première session') }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('page-script')
@endsection
