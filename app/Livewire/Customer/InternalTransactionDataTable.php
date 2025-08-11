<?php

namespace App\Livewire\Customer;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\InternalTransaction;
use HarroldWafo\LaravelCustomDatatable\DataTables\BaseDataTable;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class InternalTransactionDataTable extends BaseDataTable
{
    protected $model = InternalTransaction::class;
    protected const DEFAULT_SORT_FIELD = 'created_at';
    protected const DEFAULT_SORT_DIRECTION = 'desc';

    protected function getExportFileName(): string
    {
        return 'mouvements_de_compte';
    }

    public function builder(): Builder
    {
        $userWallet = auth()->user()->wallet;

        if (! $userWallet) {
            return InternalTransaction::query()->where('id', 0);
        }

        return InternalTransaction::query()
            ->where('wallet_id', $userWallet->id)
            ->with(['wallet.user', 'creator', 'recipient']);
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable()->deselected(),

            Column::make('Type', 'transaction_type')
                ->sortable()
                ->html()
                ->format(function ($value) {
                    $type = TransactionType::make($value);

                    return '<span class="badge text-outline-'.($type->value === TransactionType::CREDIT()->value ? 'success' : 'danger').'">'.$type->label.'</span>';
                }),

            Column::make('Montant', 'amount')
                ->sortable()
                ->format(fn ($value) => number_format($value, 0, ',', ' ').' FCFA'),

            Column::make('Statut', 'status')
                ->sortable()
                ->html()
                ->format(function ($value) {
                    $status = TransactionStatus::make($value);

                    return '<span class="badge text-outline-'.$status->badge().'">'.$status->label.'</span>';
                }),

            Column::make('Description', 'description')
                ->sortable()
                ->searchable()
                ->format(fn ($value) => $value ?: '-'),

            Column::make('Date de création', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('j F Y H:i')),

            Column::make('Actions')
                ->label(fn (InternalTransaction $row) => view('partials.customer.transactions.internal-actions', ['transaction' => $row])
                )
                ->html(),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Type', 'transaction_type')
                ->options($this->getEnumOptions(TransactionType::class, 'Tous les types'))
                ->filter(fn (Builder $builder, string $value) => $value === '' ? $builder : $builder->where('transaction_type', $value)
                ),

            SelectFilter::make('Statut', 'status')
                ->options($this->getEnumOptions(TransactionStatus::class, 'Tous les statuts'))
                ->filter(fn (Builder $builder, string $value) => $value === '' ? $builder : $builder->where('status', $value)
                ),

            DateFilter::make('Créé après', 'created_at')
                ->config(['placeholder' => 'Date de création minimum', 'locale' => 'fr'])
                ->filter(fn (Builder $builder, string $value) => $builder->whereDate('created_at', '>=', $value)
                ),
        ];
    }

    private function getEnumOptions(string $enumClass, string $defaultLabel): array
    {
        return ['' => $defaultLabel] +
            collect($enumClass::values())
                ->mapWithKeys(fn ($value, $key) => [$value => $enumClass::make($value)->label])
                ->toArray();
    }
}
