<?php

declare(strict_types=1);

namespace App\Livewire\Customer\WhatsApp;

use App\Models\WhatsAppAccount;
use HarroldWafo\LaravelCustomDatatable\DataTables\BaseDataTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class WhatsAppAccountDataTable extends BaseDataTable
{
    protected $model = WhatsAppAccount::class;
    protected const DEFAULT_SORT_FIELD = 'created_at';
    protected const DEFAULT_SORT_DIRECTION = 'desc';

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setTableWrapperAttributes([
                'class' => 'table-responsive',
            ])
            ->setEmptyMessage($this->getEmptyMessage());
    }

    public function getEmptyMessage(): string
    {
        return 'No WhatsApp session found.';
    }

    protected function getExportFileName(): string
    {
        return 'my_whatsapp_accounts';
    }

    public function builder(): Builder
    {
        return WhatsAppAccount::query()
            ->where('user_id', Auth::id())
            ->with($this->getRelations())
            ->orderBy('created_at', 'desc');
    }

    protected function getRelations(): array
    {
        return ['aiModel', 'conversations'];
    }

    public function columns(): array
    {
        return $this->getBasicColumns();
    }

    protected function getBasicColumns(): array
    {
        return [
            Column::make('ID', 'id')->sortable()->deselected(),

            Column::make('Session Name', 'session_name')
                ->sortable()
                ->searchable()
                ->format(function ($value, $row) {
                    return '<div class="d-flex flex-column" style="line-height: 1.2;">
                        <span class="text-whatsapp fw-bold mb-0">
                            '.$value.'
                        </span>
                        <small class="text-muted">'.str($row->session_id)->limit(20).'</small>
                    </div>';
                })
                ->html(),

            Column::make('Phone', 'phone_number')
                ->sortable()
                ->searchable()
                ->format(function ($value) {
                    return $value
                        ? '<span class="badge badge-whatsapp">'.$value.'</span>'
                        : '<span class="badge badge-secondary">Not connected</span>';
                })
                ->html(),

            Column::make('Status', 'status')
                ->sortable()
                ->html()
                ->format(function ($value, $row) {
                    if ($row->isConnected()) {
                        return '<span class="badge badge-success"><i class="la la-check"></i> Connected</span>';
                    } elseif ($row->isConnecting()) {
                        return '<span class="badge badge-warning"><i class="la la-sync-alt"></i> Connecting...</span>';
                    } else {
                        return '<span class="badge badge-secondary"><i class="la la-times"></i> Disconnected</span>';
                    }
                }),

            Column::make('AI Agent', 'agent_enabled')
                ->sortable()
                ->html()
                ->format(function ($value, $row) {
                    $freshAccount = \App\Models\WhatsAppAccount::find($row->id);

                    if ($freshAccount->agent_enabled && $freshAccount->ai_model_id) {
                        return '<span class="badge badge-success"><i class="la la-robot"></i> Active</span>';
                    }

                    return '<span class="badge badge-secondary"><i class="la la-robot"></i> Inactive</span>';
                }),

            Column::make('Conversations', 'id')
                ->html()
                ->format(function ($value, $row) {
                    $count = $row->conversations->count();
                    if ($count === 0) {
                        return '<span class="text-muted">None</span>';
                    }

                    return '<span class="badge badge-info">'.$count.' conversation'.($count > 1 ? 's' : '').'</span>';
                }),

            Column::make('Created At', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('j F Y H:i:s')),

            $this->getActionsColumn(),
        ];
    }

    protected function getActionsColumn(): Column
    {
        return Column::make('Actions')
            ->label(fn (WhatsAppAccount $row) => view('partials.customer.whatsapp.actions', ['account' => $row])
            )
            ->html();
    }

    public function filters(): array
    {
        return $this->getBasicFilters();
    }

    protected function getBasicFilters(): array
    {
        return [
            SelectFilter::make('Status', 'status')
                ->options([
                    '' => 'All statuses',
                    'connected' => 'Connected',
                    'connecting' => 'Connecting...',
                    'disconnected' => 'Disconnected',
                ])
                ->filter(fn (Builder $builder, string $value) => $value === '' ? $builder : $builder->where('status', $value)),

            SelectFilter::make('AI Agent', 'agent_enabled')
                ->options([
                    '' => 'All agents',
                    '1' => 'Active',
                    '0' => 'Inactive',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === '') {
                        return $builder;
                    }

                    if ($value === '1') {
                        return $builder->where('agent_enabled', true)->whereNotNull('ai_model_id');
                    }

                    return $builder->where('agent_enabled', false)->orWhereNull('ai_model_id');
                }),

            DateFilter::make('Created After', 'created_at')
                ->config(['placeholder' => 'Minimum creation date', 'locale' => 'en'])
                ->filter(fn (Builder $builder, string $value) => $builder->whereDate('created_at', '>=', $value)),
        ];
    }
}
