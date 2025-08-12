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
                                            {{-- Configurer/Modifier --}}
                                            <a href="{{ route('whatsapp.configure-ai', $session->id) }}"
                                                class="btn btn-sm btn-outline-whatsapp"
                                                title="Configurer l'agent IA">
                                                <i class="la la-cog mr-1"></i> {{ __('Configurer') }}
                                            </a>
                                            {{-- Activer/Désactiver --}}
                                            @if ($session->hasAiAgent())
                                                <button type="button" class="btn btn-sm btn-outline-warning"
                                                    onclick="toggleAiAgent({{ $session->id }}, false)"
                                                    data-toggle="tooltip"
                                                    title="{{ __('Désactiver l\'agent IA') }}">
                                                    <i class="la la-pause mr-1"></i> {{ __('Désactiver') }}
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-sm btn-outline-success"
                                                    onclick="toggleAiAgent({{ $session->id }}, true)"
                                                    data-toggle="tooltip"
                                                    title="{{ __('Activer l\'agent IA') }}">
                                                    <i class="la la-play mr-1"></i> {{ __('Activer') }}
                                                </button>
                                            @endif

                                            {{-- Supprimer --}}
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                onclick="deleteSession({{ $session->id }})"
                                                data-toggle="tooltip"
                                                title="{{ __('Supprimer la session') }}">
                                                <i class="la la-trash mr-1"></i> {{ __('Supprimer') }}
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

@section('page-script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        function toggleAiAgent(sessionId, enable) {
            const action = enable ? 'activer' : 'désactiver';
            const title = enable ? 'Activer l\'agent IA' : 'Désactiver l\'agent IA';

            Swal.fire({
                title: title,
                text: `Êtes-vous sûr de vouloir ${action} l\'agent IA pour cette session ?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: enable ? '#28a745' : '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: enable ? 'Oui, activer' : 'Oui, désactiver',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Send AJAX request to toggle AI agent
                    fetch(`/whatsapp/${sessionId}/toggle-ai`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content')
                            },
                            body: JSON.stringify({
                                enable: enable
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                toastr.success(data.message);
                                window.location.reload();
                            } else {
                                toastr.error(data.message || 'Une erreur est survenue');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            toastr.error('Une erreur est survenue lors de la requête');
                        });
                }
            });
        }

        function deleteSession(sessionId) {
            Swal.fire({
                title: 'Supprimer la session',
                text: 'Êtes-vous sûr de vouloir supprimer cette session WhatsApp ? Cette action est irréversible.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Oui, supprimer',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Send AJAX request to delete session
                    fetch(`/whatsapp/${sessionId}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content')
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                toastr.success(data.message);
                                window.location.reload();
                            } else {
                                toastr.error(data.message || 'Une erreur est survenue');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            toastr.error('Une erreur est survenue lors de la requête');
                        });
                }
            });
        }

        // Initialize tooltips
        $(document).ready(function() {
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
@endsection
