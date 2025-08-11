<div class="dropdown">
    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-ellipsis-v"></i>
    </button>
    <ul class="dropdown-menu">
        @if($user->hasRole('customer'))
            <li>
                <a href="{{ route('admin.customers.show', $user) }}" class="dropdown-item" title="{{ __('View details') }}">
                    <i class="fas fa-eye me-2"></i>{{ __('Details') }}
                </a>
            </li>
        @endif
        <li>
            <a href="{{ route('admin.users.edit', $user) }}" class="dropdown-item" title="{{ __('Edit') }}">
                <i class="fas fa-edit me-2"></i>{{ __('Edit') }}
            </a>
        </li>
        <li>
            <button type="button" class="dropdown-item text-danger" title="{{ __('Delete') }}">
                <i class="fas fa-trash me-2"></i>{{ __('Delete') }}
            </button>
        </li>
    </ul>
</div>
