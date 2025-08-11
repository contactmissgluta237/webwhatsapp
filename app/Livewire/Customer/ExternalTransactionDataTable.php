<?php

namespace App\Livewire\Customer;

use App\Enums\ExternalTransactionType;
use App\Enums\PaymentMethod;
use App\Enums\TransactionMode;
use App\Enums\TransactionStatus;
use App\Models\ExternalTransaction;
use HarroldWafo\LaravelCustomDatatable\DataTables\BaseDataTable;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class ExternalTransactionDataTable extends BaseDataTable
{
    protected $model = ExternalTransaction::class;
    protected const DEFAULT_SORT_FIELD = 'created_at';
    protected const DEFAULT_SORT_DIRECTION = 'desc';

    protected function getExportFileName(): string
    {
        return 'mes_transactions';
    }

    public function builder(): Builder
    {
        $userWallet = auth()->user()->wallet;

        if (! $userWallet) {
            return ExternalTransaction::query()->where('id', 0);
        }

        return ExternalTransaction::query()
            ->where('wallet_id', $userWallet->id)
            ->with(['wallet.user', 'creator', 'approver'])
            ->orderBy('created_at', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable()->deselected(),

            Column::make('Type', 'transaction_type')
                ->sortable()
                ->html()
                ->format(function ($value) {
                    $type = ExternalTransactionType::make($value);

                    return '<span class="badge text-outline-'.$type->badge().'">'.$type->label.'</span>';
                }),

            Column::make('Montant', 'amount')
                ->sortable()
                ->format(fn ($value) => number_format($value, 0, ',', ' ').' FCFA'),

            Column::make('Mode', 'mode')
                ->sortable()
                ->html()
                ->format(function ($value) {
                    $mode = TransactionMode::make($value);

                    return '<span class="badge text-outline-'.$mode->badge().'">'.$mode->label.'</span>';
                }),

            Column::make('Statut', 'status')
                ->sortable()
                ->html()
                ->format(function ($value) {
                    $status = TransactionStatus::make($value);

                    return '<span class="badge text-outline-'.$status->badge().'">'.$status->label.'</span>';
                }),

            Column::make('Méthode de paiement', 'payment_method')
                ->sortable()
                ->format(function ($value) {
                    if (! $value) {
                        return '-';
                    }

                    return PaymentMethod::make($value)->label;
                }),

            Column::make('ID Externe', 'external_transaction_id')
                ->sortable()
                ->searchable(),

            Column::make('Description', 'description')
                ->sortable()
                ->searchable()
                ->format(fn ($value) => $value ?: '-'),

            Column::make('Date de création', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('j F Y H:i:s')),

            Column::make('Actions')
                ->label(fn (ExternalTransaction $row) => view('partials.customer.transactions.external-actions', ['transaction' => $row])
                )
                ->html(),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Type', 'transaction_type')
                ->options($this->getEnumOptions(ExternalTransactionType::class, 'Tous les types'))
                ->filter(fn (Builder $builder, string $value) => $value === '' ? $builder : $builder->where('transaction_type', $value)
                ),

            SelectFilter::make('Statut', 'status')
                ->options($this->getEnumOptions(TransactionStatus::class, 'Tous les statuts'))
                ->filter(fn (Builder $builder, string $value) => $value === '' ? $builder : $builder->where('status', $value)
                ),

            SelectFilter::make('Mode', 'mode')
                ->options($this->getEnumOptions(TransactionMode::class, 'Tous les modes'))
                ->filter(fn (Builder $builder, string $value) => $value === '' ? $builder : $builder->where('mode', $value)
                ),

            SelectFilter::make('Méthode de paiement', 'payment_method')
                ->options($this->getEnumOptions(PaymentMethod::class, 'Toutes les méthodes'))
                ->filter(fn (Builder $builder, string $value) => $value === '' ? $builder : $builder->where('payment_method', $value)
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
