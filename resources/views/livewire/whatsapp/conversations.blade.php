<div>
    {{-- Header avec recherche --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="la la-comment text-primary"></i>
                        {{ __('Conversations WhatsApp') }}
                    </h4>
                    <a class="heading-elements-toggle"><i class="la la-ellipsis-h font-medium-3"></i></a>
                    <div class="heading-elements">
                        <div class="form-group mb-0">
                            <input type="text" 
                                   class="form-control form-control-sm" 
                                   wire:model.debounce.300ms="search"
                                   placeholder="{{ __('Rechercher une conversation...') }}"
                                   style="width: 250px;">
                        </div>
                    </div>
                </div>
                <div class="card-content">
                    <div class="card-body p-0">
                        @if($conversations->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>{{ __('Contact') }}</th>
                                            <th>{{ __('Compte WhatsApp') }}</th>
                                            <th>{{ __('Dernier message') }}</th>
                                            <th>{{ __('Date') }}</th>
                                            <th class="text-center">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($conversations as $conversation)
                                            <tr wire:click="selectConversation({{ $conversation->id }})" style="cursor: pointer;">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar bg-success mr-1">
                                                            <i class="la la-user text-white"></i>
                                                        </div>
                                                        <div>
                                                            <strong>{{ $conversation->contact_name ?? __('Contact anonyme') }}</strong><br>
                                                            <small class="text-muted">{{ $conversation->contact_phone }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-outline-info">
                                                        {{ $conversation->whatsappAccount->session_name }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($conversation->messages->count() > 0)
                                                        @php $lastMessage = $conversation->messages->first() @endphp
                                                        <div class="text-truncate" style="max-width: 200px;">
                                                            @if($lastMessage->direction->value === 'incoming')
                                                                <i class="la la-arrow-down text-success"></i>
                                                            @else
                                                                <i class="la la-arrow-up text-primary"></i>
                                                            @endif
                                                            {{ Str::limit($lastMessage->content, 50) }}
                                                        </div>
                                                    @else
                                                        <span class="text-muted">{{ __('Aucun message') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="text-muted">
                                                        {{ $conversation->updated_at->diffForHumans() }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="{{ route('whatsapp.conversation', $conversation) }}" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="la la-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Pagination --}}
                            <div class="card-footer">
                                {{ $conversations->links() }}
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="la la-comment-o text-muted" style="font-size: 4rem;"></i>
                                <h4 class="text-muted mt-2">{{ __('Aucune conversation trouvée') }}</h4>
                                @if($search)
                                    <p class="text-muted">{{ __('Aucun résultat pour') }} "{{ $search }}"</p>
                                    <button class="btn btn-outline-secondary" wire:click="$set('search', '')">
                                        {{ __('Effacer la recherche') }}
                                    </button>
                                @else
                                    <p class="text-muted">{{ __('Les conversations apparaîtront ici une fois que vous recevrez des messages') }}</p>
                                    <a href="{{ route('whatsapp.dashboard') }}" class="btn btn-primary">
                                        <i class="la la-whatsapp"></i> {{ __('Gérer mes comptes WhatsApp') }}
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de détail conversation (si sélectionnée) --}}
    @if($selectedConversation)
        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="la la-comment"></i>
                            {{ $selectedConversation->contact_name ?? $selectedConversation->contact_phone }}
                        </h5>
                        <button type="button" class="close" wire:click="$set('selectedConversation', null)">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>{{ __('Téléphone:') }}</strong> {{ $selectedConversation->contact_phone }}<br>
                                <strong>{{ __('Compte WhatsApp:') }}</strong> {{ $selectedConversation->whatsappAccount->session_name }}<br>
                                <strong>{{ __('Chat ID:') }}</strong> <code>{{ $selectedConversation->chat_id }}</code>
                            </div>
                            <div class="col-md-6">
                                <strong>{{ __('Créé le:') }}</strong> {{ $selectedConversation->created_at->format('d/m/Y H:i') }}<br>
                                <strong>{{ __('Dernière activité:') }}</strong> {{ $selectedConversation->updated_at->diffForHumans() }}<br>
                                <strong>{{ __('Nombre de messages:') }}</strong> {{ $selectedConversation->messages->count() }}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('selectedConversation', null)">
                            {{ __('Fermer') }}
                        </button>
                        <a href="{{ route('whatsapp.conversation', $selectedConversation) }}" class="btn btn-primary">
                            <i class="la la-external-link"></i> {{ __('Ouvrir la conversation') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
