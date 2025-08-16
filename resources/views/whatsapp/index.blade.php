@extends('modern.layouts.master')

@section('title', __('Liste des sessions WhatsApp'))

@section('page-style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('content')
    <div class="row mx-0 mt-1 mb-1">
        <div class="content-header-left col-md-6 col-12 mb-2">
            <h3 class="content-header-title">Mes sessions WhatsApp</h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active">Agents WhatsApp</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="content-header-right col-md-6 col-12 text-right">
            <a href="{{ route('whatsapp.create') }}" class="btn btn-whatsapp rounded btn-glow">
                <i class="la la-plus mr-1"></i> {{ __('Créer un nouveau agent IA') }}
            </a>
        </div>
    </div>

    {{-- Les messages flash seront gérés par toastr via le script --}}

    <div class="content-body">
        <section id="whatsapp-sessions-list">
            @if ($sessions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('Nom de session') }}</th>
                                <th>{{ __('Téléphone') }}</th>
                                <th>{{ __('Statut') }}</th>
                                <th>{{ __('Agent IA') }}</th>
                                <th>{{ __('Créé le') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sessions as $session)
                                <tr>
                                    <td>
                                        <strong>{{ $session->session_name }}</strong>
                                    </td>
                                    <td>
                                        @if ($session->phone_number)
                                            <span
                                                class="badge badge-success">{{ $session->phone_number }}</span>
                                        @else
                                            <span
                                                class="badge badge-secondary">{{ __('Non connecté') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($session->isConnected())
                                            <span
                                                class="badge badge-success">{{ __('Connecté') }}</span>
                                        @elseif($session->isConnecting())
                                            <span
                                                class="badge badge-warning">{{ __('Connexion...') }}</span>
                                        @else
                                            <span
                                                class="badge badge-secondary">{{ __('Déconnecté') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($session->hasAiAgent())
                                            <span class="badge badge-success">
                                                <i class="la la-robot"></i> {{ __('Actif') }}
                                            </span>
                                            <br>
                                            <small
                                                class="text-muted">{{ $session->getAiModel()?->name }}</small>
                                        @else
                                            <span class="badge badge-secondary">
                                                <i class="la la-robot"></i> {{ __('Inactif') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $session->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            {{-- Configurer --}}
                                            <a href="{{ route('whatsapp.configure-ai', $session->id) }}"
                                               class="btn btn-sm btn-outline-primary"
                                               title="Configurer l'agent IA">
                                                <i class="la la-cog"></i>
                                            </a>

                                            {{-- Toggle AI --}}
                                            @if ($session->hasAiAgent())
                                                <form method="POST" action="{{ route('whatsapp.toggle-ai', $session->id) }}" style="display: inline;">
                                                    @csrf
                                                    <input type="hidden" name="enable" value="0">
                                                    <button type="submit"
                                                            class="btn btn-sm btn-outline-warning"
                                                            title="Désactiver l'agent IA"
                                                            onclick="return confirm('Désactiver cet agent IA ?')">
                                                        <i class="la la-pause"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('whatsapp.toggle-ai', $session->id) }}" style="display: inline;">
                                                    @csrf
                                                    <input type="hidden" name="enable" value="1">
                                                    <button type="submit"
                                                            class="btn btn-sm btn-outline-success"
                                                            title="Activer l'agent IA"
                                                            onclick="return confirm('Activer cet agent IA ?')">
                                                        <i class="la la-play"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- SUPPRESSION SIMPLE ET DIRECTE --}}
                                            <form method="POST" action="{{ route('whatsapp.destroy', $session->id) }}" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Supprimer la session"
                                                        onclick="return confirm('Supprimer définitivement la session « {{ $session->session_name }} » ?')">
                                                    <i class="la la-trash"></i>
                                                </button>
                                            </form>
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
                    <p class="text-muted">{{ __('Vous n\'avez pas encore créé de session WhatsApp.') }}
                    </p>
                    <a href="{{ route('whatsapp.create') }}" class="btn btn-whatsapp rounded btn-glow">
                        <i class="la la-plus mr-1"></i> {{ __('Créer votre première session') }}
                    </a>
                </div>
            @endif
        </section>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
    $(document).ready(function() {
        // Affichage des messages flash avec toastr
        @if(session('success'))
            toastr.success('{{ session('success') }}');
        @endif
        @if(session('error'))
            toastr.error('{{ session('error') }}');
        @endif

        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();
    });
    </script>
@endpush