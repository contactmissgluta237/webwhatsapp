<?php

declare(strict_types=1);

namespace App\Livewire\Customer\WhatsApp;

use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use HarroldWafo\LaravelCustomDatatable\DataTables\BaseDataTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class ConversationDataTable extends BaseDataTable
{
    protected $model = WhatsAppConversation::class;
    protected const DEFAULT_SORT_FIELD = 'last_message_at';
    protected const DEFAULT_SORT_DIRECTION = 'desc';

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setTableWrapperAttributes([
                'class' => 'table-responsive',
            ])
            ->setEmptyMessage(view('partials.customer.whatsapp.conversations.empty-message')->render());
    }

    protected function getExportFileName(): string
    {
        return 'conversations';
    }

    public function builder(): Builder
    {
        // Laravel fait du route model binding - on reçoit directement l'objet WhatsAppAccount
        $account = request()->route('account');

        // Si c'est pas un objet WhatsAppAccount, on a un problème
        if (! $account instanceof WhatsAppAccount) {
            abort(404, 'Invalid account');
        }

        // Vérifier que le compte appartient à l'utilisateur connecté
        if ($account->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return WhatsAppConversation::query()
            ->where('whatsapp_account_id', $account->id)
            ->with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->withCount('messages')
            ->orderBy('last_message_at', 'desc');
    }

    public function columns(): array
    {
        return $this->getBasicColumns();
    }

    protected function getBasicColumns(): array
    {
        return [
            Column::make('ID', 'id')->sortable()->deselected(),

            Column::make('Nom du contact', 'contact_name')
                ->sortable()
                ->searchable()
                ->format(function ($value, $row) {
                    $isGroup = $row->is_group ? '<i class="la la-users text-info mr-1"></i>' : '<i class="la la-user text-primary mr-1"></i>';
                    $displayName = $value ?: 'Contact sans nom';

                    return $isGroup.'<span class="fw-bold">'.$displayName.'</span>';
                })
                ->html(),

            Column::make('Téléphone', 'contact_phone')
                ->sortable()
                ->searchable(),

            Column::make('Total messages', 'id')
                ->format(function ($value, $row) {
                    $count = $row->messages_count ?? 0;

                    return '<span class="badge badge-whatsapp">'.$count.' message'.($count > 1 ? 's' : '').'</span>';
                })
                ->html(),

            Column::make('Débit du wallet', 'id')
                ->format(function ($value, $row) {
                    $walletCost = $row->getWalletCost();
                    if ($walletCost <= 0) {
                        return '<span class="text-muted">0 XAF</span>';
                    }

                    return '<span class="badge badge-danger">'.number_format($walletCost, 0).' XAF</span>';
                })
                ->html(),

            Column::make('Dernier message à', 'last_message_at')
                ->sortable()
                ->format(function ($value) {
                    return $value ? $value->diffForHumans() : '<span class="text-muted">Jamais</span>';
                })
                ->html(),

            Column::make('Aperçu', 'id')
                ->format(function ($value, $row) {
                    $lastMessage = $row->messages->first();
                    if (! $lastMessage) {
                        return '<span class="text-muted">Aucun message</span>';
                    }

                    return '<span class="text-dark">'.e(str($lastMessage->content)->limit(60)).'</span>';
                })
                ->html(),

            Column::make('Actions')
                ->label(fn ($row) => view('partials.customer.whatsapp.conversations.actions', ['conversation' => $row]))
                ->html(),
        ];
    }

    public function filters(): array
    {
        return $this->getBasicFilters();
    }

    protected function getBasicFilters(): array
    {
        return [
            SelectFilter::make('Type', 'is_group')
                ->options([
                    '' => 'Tous les types',
                    '0' => 'Individuel',
                    '1' => 'Groupe',
                ])
                ->filter(fn (Builder $builder, string $value) => $value === '' ? $builder : $builder->where('is_group', (bool) $value)),

            DateFilter::make('Dernier message après', 'last_message_at')
                ->config(['placeholder' => 'Date minimum', 'locale' => 'fr'])
                ->filter(fn (Builder $builder, string $value) => $builder->whereDate('last_message_at', '>=', $value)),
        ];
    }
}
