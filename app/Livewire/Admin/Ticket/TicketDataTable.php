<?php

namespace App\Livewire\Admin\Ticket;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use HarroldWafo\LaravelCustomDatatable\DataTables\BaseDataTable;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class TicketDataTable extends BaseDataTable
{
    protected $model = Ticket::class;

    protected const DEFAULT_SORT_FIELD = 'created_at';
    protected const DEFAULT_SORT_DIRECTION = 'desc';

    public function configure(): void
    {
        parent::configure();
        $this->setDefaultSort(self::DEFAULT_SORT_FIELD, self::DEFAULT_SORT_DIRECTION);
    }

    protected function getExportFileName(): string
    {
        return 'admin_tickets';
    }

    public function builder(): Builder
    {
        return Ticket::query()
            ->select([
                'tickets.*',
            ])
            ->with(['user', 'assignedTo'])
            ->orderBy('created_at', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make('Ticket Number', 'ticket_number')
                ->sortable()
                ->searchable(),

            Column::make('Title', 'title')
                ->sortable()
                ->searchable(),

            Column::make('Created By')
                ->label(function (Ticket $row) {
                    /** @var \App\Models\User $user */
                    $user = $row->user;

                    return $user->full_name;
                })
                ->sortable(function (Builder $query, string $direction) {
                    return $query->leftJoin('users as creator_users', 'tickets.user_id', '=', 'creator_users.id')
                        ->orderBy('creator_users.first_name', $direction)
                        ->orderBy('creator_users.last_name', $direction)
                        ->select('tickets.*');
                }),

            Column::make('Status', 'status')
                ->sortable()
                ->html()
                ->format(function ($value) {
                    $status = TicketStatus::make($value);

                    return '<span class="badge text-outline-'.$status->badge().'">'.$status->label.'</span>';
                }),

            Column::make('Priority', 'priority')
                ->sortable()
                ->html()
                ->format(function ($value) {
                    $priority = TicketPriority::make($value);

                    return '<span class="badge text-outline-'.$priority->badge().'">'.$priority->label.'</span>';
                }),

            Column::make('Assigned To')
                ->label(function (Ticket $row) {
                    return $row->assignedTo?->full_name ?? 'N/A';
                })
                ->sortable(function (Builder $query, string $direction) {
                    return $query->leftJoin('users as assigned_users', 'tickets.assigned_to', '=', 'assigned_users.id')
                        ->orderBy('assigned_users.first_name', $direction)
                        ->orderBy('assigned_users.last_name', $direction)
                        ->select('tickets.*');
                }),

            Column::make('Created At', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('j F Y H:i:s')),

            Column::make('Actions')
                ->label(function (Ticket $row) {
                    return view('partials.admin.tickets.actions', ['ticket' => $row]);
                })
                ->html(),
        ];
    }

    public function filters(): array
    {
        $assignedToUsers = User::role('admin')->orderBy('first_name')->get()->mapWithKeys(function ($user) {
            return [$user->id => $user->full_name];
        })->toArray();

        return [
            SelectFilter::make('Status', 'status')
                ->options(
                    ['' => 'All Statuses'] +
                    collect(TicketStatus::values())
                        ->mapWithKeys(function ($value) {
                            $status = TicketStatus::make($value);

                            return [$value => $status->label];
                        })
                        ->toArray()
                )
                ->filter(function (Builder $builder, string $value) {
                    return $value === '' ? $builder : $builder->where('status', $value);
                }),

            SelectFilter::make('Priority', 'priority')
                ->options(
                    ['' => 'All Priorities'] +
                    collect(TicketPriority::values())
                        ->mapWithKeys(function ($value) {
                            $priority = TicketPriority::make($value);

                            return [$value => $priority->label];
                        })
                        ->toArray()
                )
                ->filter(function (Builder $builder, string $value) {
                    return $value === '' ? $builder : $builder->where('priority', $value);
                }),

            SelectFilter::make('Assigned To', 'assigned_to')
                ->options(
                    ['' => 'All Admins'] +
                    $assignedToUsers
                )
                ->filter(function (Builder $builder, string $value) {
                    return $value === '' ? $builder : $builder->where('assigned_to', $value);
                }),

            DateFilter::make('Created After', 'created_at')
                ->config([
                    'placeholder' => 'Min Creation Date',
                    'locale' => 'en',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->whereDate('created_at', '>=', $value);
                }),
        ];
    }
}
