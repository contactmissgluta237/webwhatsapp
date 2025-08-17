<div>
    {{-- Messages flash --}}
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session()->has('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            {{ session('info') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- Header avec bouton de connexion --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="la la-whatsapp text-success"></i>
                        {{ __('Mes comptes WhatsApp') }}
                    </h4>
                    <div class="heading-elements">
                        <button type="button" 
                                class="btn btn-success btn-sm"
                                wire:click="openConnectModal">
                            <i class="la la-plus"></i>
                            {{ __('Nouveau compte') }}
                        </button>
                    </div>
                </div>
                
                <div class="card-content">
                    <div class="card-body">
                        @if(count($accounts) > 0)
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Nom de session') }}</th>
                                            <th>{{ __('Numéro') }}</th>
                                            <th>{{ __('Statut') }}</th>
                                            <th>{{ __('Dernière activité') }}</th>
                                            <th>{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($accounts as $account)
                                            <tr>
                                                <td>
                                                    <strong>{{ $account['session_name'] }}</strong>
                                                </td>
                                                <td>
                                                    {{ $account['phone_number'] ?? __('Non défini') }}
                                                </td>
                                                <td>
                                                    @php
                                                        $status = $account['status'];
                                                        $badgeClass = match($status) {
                                                            'connected' => 'badge-success',
                                                            'disconnected' => 'badge-secondary',
                                                            'connecting' => 'badge-warning',
                                                            'error' => 'badge-danger',
                                                            default => 'badge-secondary'
                                                        };
                                                        $statusLabel = match($status) {
                                                            'connected' => 'Connecté',
                                                            'disconnected' => 'Déconnecté',
                                                            'connecting' => 'Connexion en cours',
                                                            'error' => 'Erreur',
                                                            default => ucfirst($status)
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $badgeClass }}">
                                                        {{ $statusLabel }}
                                                    </span>
                                                    
                                                    {{-- Informations de déconnexion/reconnexion --}}
                                                    @if($account['last_disconnected_at'])
                                                        <br><small class="text-muted">
                                                            Déconnecté le {{ \Carbon\Carbon::parse($account['last_disconnected_at'])->format('d/m/Y H:i') }}
                                                        </small>
                                                    @endif
                                                    @if($account['last_reconnected_at'])
                                                        <br><small class="text-success">
                                                            Reconnecté le {{ \Carbon\Carbon::parse($account['last_reconnected_at'])->format('d/m/Y H:i') }}
                                                        </small>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $account['last_seen_at'] ? \Carbon\Carbon::parse($account['last_seen_at'])->diffForHumans() : __('Jamais') }}
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        @if($status === 'connected')
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-danger"
                                                                    wire:click="disconnectAccount({{ $account['id'] }})"
                                                                    onclick="return confirm('Êtes-vous sûr de vouloir déconnecter ce compte ?')">
                                                                <i class="la la-times"></i>
                                                                {{ __('Déconnecter') }}
                                                            </button>
                                                        @elseif($status === 'disconnected')
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-success"
                                                                    wire:click="reconnectAccount({{ $account['id'] }})">
                                                                <i class="la la-refresh"></i>
                                                                {{ __('Reconnecter') }}
                                                            </button>
                                                        @endif
                                                        
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-primary"
                                                                onclick="window.location.href='{{ route('whatsapp.index') }}'">
                                                            <i class="la la-comments"></i>
                                                            {{ __('Conversations') }}
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="la la-whatsapp text-muted" style="font-size: 3rem;"></i>
                                <h5 class="text-muted mt-2">{{ __('Aucun compte WhatsApp configuré') }}</h5>
                                <p class="text-muted">{{ __('Cliquez sur "Nouveau compte" pour commencer') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de connexion --}}
    @if($showConnectModal)
        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="la la-whatsapp text-success"></i>
                            {{ __('Connecter un compte WhatsApp') }}
                        </h5>
                        <button type="button" class="close" wire:click="closeConnectModal">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        @livewire('whats-app.create-session')
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>