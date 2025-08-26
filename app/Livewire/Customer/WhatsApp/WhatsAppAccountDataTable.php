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

final class WhatsAppAccountDataTable extends BaseDataTable
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
            ->setTableAttributes([
                'class' => 'table table-hover',
                'style' => 'vertical-align: middle;',
            ])
            ->setTdAttributes(function ($value, $row, $column) {
                return [
                    'style' => 'padding: 0.5rem 0.75rem; vertical-align: middle;',
                ];
            })
            ->setEmptyMessage('<div class="text-center py-5">
                <i class="la la-whatsapp text-muted" style="font-size: 4rem;"></i>
                <h4 class="text-muted mt-3">Aucune session WhatsApp</h4>
                <p class="text-muted">Vous n\'avez pas encore créé de session WhatsApp.</p>
                <a href="'.route('whatsapp.create').'" class="btn btn-whatsapp rounded btn-glow">
                    <i class="la la-plus mr-1"></i> Créer votre première session
                </a>
            </div>');
    }

    protected function getExportFileName(): string
    {
        return 'mes_comptes_whatsapp';
    }

    public function builder(): Builder
    {
        return WhatsAppAccount::query()
            ->where('user_id', Auth::id())
            ->with(['aiModel', 'conversations'])
            ->orderBy('created_at', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable()->deselected(),

            Column::make('Nom de session', 'session_name')
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

            Column::make('Téléphone', 'phone_number')
                ->sortable()
                ->searchable()
                ->format(function ($value) {
                    return $value
                        ? '<span class="badge badge-whatsapp">'.$value.'</span>'
                        : '<span class="badge badge-secondary">Non connecté</span>';
                })
                ->html(),

            Column::make('Statut', 'status')
                ->sortable()
                ->html()
                ->format(function ($value, $row) {
                    if ($row->isConnected()) {
                        return '<span class="badge badge-success"><i class="la la-check"></i> Connecté</span>';
                    } elseif ($row->isConnecting()) {
                        return '<span class="badge badge-warning"><i class="la la-sync-alt"></i> Connexion...</span>';
                    } else {
                        return '<span class="badge badge-secondary"><i class="la la-times"></i> Déconnecté</span>';
                    }
                }),

            Column::make('Agent IA', 'agent_enabled')
                ->sortable()
                ->html()
                ->format(function ($value, $row) {
                    if ($row->hasAiAgent()) {
                        $modelName = $row->aiModel?->name ? str($row->aiModel->name)->limit(15) : 'N/A';

                        return '<div class="d-flex flex-column align-items-start" style="line-height: 1.1;">
                            <span class="badge badge-success mb-1"><i class="la la-robot"></i> Actif</span>
                            <small class="text-muted" style="font-size: 0.7rem;">'.$modelName.'</small>
                        </div>';
                    }

                    return '<span class="badge badge-secondary"><i class="la la-robot"></i> Inactif</span>';
                }),

            Column::make('Conversations', 'id')
                ->html()
                ->format(function ($value, $row) {
                    $count = $row->conversations->count();
                    if ($count === 0) {
                        return '<span class="text-muted">Aucune</span>';
                    }

                    return '<span class="badge badge-info">'.$count.' conversation'.($count > 1 ? 's' : '').'</span>';
                }),

            Column::make('Date de création', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('j F Y H:i:s')),

            Column::make('Actions')
                ->label(fn (WhatsAppAccount $row) => view('partials.customer.whatsapp.actions', ['account' => $row]))
                ->html(),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Statut', 'status')
                ->options([
                    '' => 'Tous les statuts',
                    'connected' => 'Connecté',
                    'connecting' => 'Connexion...',
                    'disconnected' => 'Déconnecté',
                ])
                ->filter(fn (Builder $builder, string $value) => $value === '' ? $builder : $builder->where('status', $value)),

            SelectFilter::make('Agent IA', 'agent_enabled')
                ->options([
                    '' => 'Tous les agents',
                    '1' => 'Actif',
                    '0' => 'Inactif',
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

            DateFilter::make('Créé après', 'created_at')
                ->config(['placeholder' => 'Date de création minimum', 'locale' => 'fr'])
                ->filter(fn (Builder $builder, string $value) => $builder->whereDate('created_at', '>=', $value)),
        ];
    }
}
