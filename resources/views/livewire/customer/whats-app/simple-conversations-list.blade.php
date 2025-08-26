<div>
    @if($conversations->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Contact</th>
                        <th>Type</th>
                        <th>Messages non lus</th>
                        <th>Dernier message</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($conversations as $conversation)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($conversation->is_group)
                                        <i class="la la-users text-info mr-2"></i>
                                    @else
                                        <i class="la la-user text-primary mr-2"></i>
                                    @endif
                                    <div>
                                        <div class="fw-bold">{{ $conversation->getDisplayName() }}</div>
                                        @if($conversation->contact_name)
                                            <small class="text-muted">{{ $conversation->contact_phone }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($conversation->is_group)
                                    <span class="badge badge-info"><i class="la la-users"></i> Groupe</span>
                                @else
                                    <span class="badge badge-primary"><i class="la la-user"></i> Individuel</span>
                                @endif
                            </td>
                            <td>
                                @if($conversation->unread_count > 0)
                                    <span class="badge badge-danger">{{ $conversation->unread_count }} non lu{{ $conversation->unread_count > 1 ? 's' : '' }}</span>
                                @else
                                    <span class="badge badge-success">Lu</span>
                                @endif
                            </td>
                            <td>
                                @if($conversation->last_message_at)
                                    <div>
                                        <small class="text-muted">{{ $conversation->last_message_at->diffForHumans() }}</small>
                                        @php
                                            $lastMessage = $conversation->messages->first();
                                        @endphp
                                        @if($lastMessage)
                                            <br>
                                            <span class="text-dark">{{ str($lastMessage->content)->limit(50) }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">Aucun message</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('customer.whatsapp.conversations.show', ['account' => $account->id, 'conversation' => $conversation->id]) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="la la-eye"></i> Voir
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        {{ $conversations->links() }}
    @else
        <div class="text-center py-5">
            <i class="la la-comments text-muted" style="font-size: 4rem;"></i>
            <h4 class="text-muted mt-3">Aucune conversation</h4>
            <p class="text-muted">Cette session WhatsApp n'a pas encore de conversations.</p>
            <small class="text-muted">Les conversations apparaîtront ici dès que des messages seront échangés.</small>
        </div>
    @endif
</div>