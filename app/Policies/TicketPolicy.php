<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class TicketPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::TICKETS_VIEW()->value);
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->can(PermissionEnum::TICKETS_VIEW()->value)) {
            return true;
        }

        return $ticket->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::TICKETS_CREATE()->value);
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->can(PermissionEnum::TICKETS_CHANGE_STATUS()->value)) {
            return true;
        }

        if ($user->can(PermissionEnum::TICKETS_ASSIGN()->value)) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->hasRole('super-admin');
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        if ($user->can(PermissionEnum::TICKETS_REPLY()->value)) {
            return true;
        }

        return $ticket->user_id === $user->id && ! $ticket->isClosed();
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->can(PermissionEnum::TICKETS_ASSIGN()->value);
    }

    public function changeStatus(User $user, Ticket $ticket): bool
    {
        return $user->can(PermissionEnum::TICKETS_CHANGE_STATUS()->value);
    }

    public function changePriority(User $user, Ticket $ticket): bool
    {
        return $user->can(PermissionEnum::TICKETS_CHANGE_STATUS()->value);
    }
}
