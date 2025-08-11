<?php

namespace App\Livewire\Admin\SystemAccounts\DataTables;

use App\Enums\ExternalTransactionType;
use App\Enums\PaymentMethod;
use App\Models\SystemAccountTransaction;
use HarroldWafo\LaravelCustomDatatable\DataTables\BaseDataTable;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class SystemAccountTransactionDataTable extends BaseDataTable
{
    protected $model = SystemAccountTransaction::class;
    protected const DEFAULT_SORT_FIELD = 'created_at';
    protected const DEFAULT_SORT_DIRECTION = 'desc';

    protected function getExportFileName(): string
    {
        return 'transactions_comptes_systeme';
    }

    public function builder(): Builder
    {
        return SystemAccountTransaction::query()
            ->with(['systemAccount', 'creator:id,first_name,last_name']);
    }

    public function columns(): array
    {
        return [
            $this->getIdColumn(),
            $this->getSystemAccountColumn(),
            $this->getTransactionTypeColumn(),
            $this->getAmountColumn(),
            $this->getSenderNameColumn(),
            $this->getSenderAccountColumn(),
            $this->getReceiverNameColumn(),
            $this->getReceiverAccountColumn(),
            $this->getDescriptionColumn(),
            $this->getCreatorColumn(),
            $this->getCreatedAtColumn(),
            $this->getActionsColumn(),
        ];
    }

    public function filters(): array
    {
        return [
            $this->getSystemAccountTypeFilter(),
            $this->getTransactionTypeFilter(),
            $this->getCreatedDateFilter(),
        ];
    }

    private function getIdColumn(): Column
    {
        return Column::make('ID', 'id')
            ->sortable()
            ->deselected();
    }

    private function getSystemAccountColumn(): Column
    {
        return Column::make('Compte Système', 'systemAccount.type')
            ->sortable()
            ->searchable()
            ->format(fn ($value) => PaymentMethod::make($value)->label);
    }

    private function getTransactionTypeColumn(): Column
    {
        return Column::make('Type', 'type')
            ->sortable()
            ->html()
            ->format(function ($value) {
                $type = ExternalTransactionType::make($value);
                $badgeClass = $this->isRechargeTransaction($type) ? 'success' : 'danger';

                return "<span class=\"badge text-outline-{$badgeClass}\">{$type->label}</span>";
            });
    }

    private function getAmountColumn(): Column
    {
        return Column::make('Montant', 'amount')
            ->sortable()
            ->format(fn ($value) => number_format($value, 0, ',', ' ').' FCFA');
    }

    private function getSenderNameColumn(): Column
    {
        return Column::make('Expéditeur', 'sender_name')
            ->sortable()
            ->searchable()
            ->format(fn ($value) => $value ?: '-');
    }

    private function getSenderAccountColumn(): Column
    {
        return Column::make('Compte Expéditeur', 'sender_account')
            ->sortable()
            ->searchable()
            ->format(fn ($value) => $value ?: '-');
    }

    private function getReceiverNameColumn(): Column
    {
        return Column::make('Destinataire', 'receiver_name')
            ->sortable()
            ->searchable()
            ->format(fn ($value) => $value ?: '-');
    }

    private function getReceiverAccountColumn(): Column
    {
        return Column::make('Compte Destinataire', 'receiver_account')
            ->sortable()
            ->searchable()
            ->format(fn ($value) => $value ?: '-');
    }

    private function getDescriptionColumn(): Column
    {
        return Column::make('Description', 'description')
            ->sortable()
            ->searchable()
            ->format(fn ($value) => $value ?: '-');
    }

    private function getCreatorColumn(): Column
    {
        return Column::make('Créé par', 'created_by')
            ->html()
            ->format(function ($value, SystemAccountTransaction $row) {
                return $row->creator ? $row->creator->full_name : '-';
            });
    }

    private function getActionsColumn(): Column
    {
        return Column::make('Actions')
            ->html()
            ->label(function (SystemAccountTransaction $row) {
                return view('partials.admin.system_accounts.transaction-actions', [
                    'transaction' => $row,
                ])->render();
            });
    }

    private function getCreatedAtColumn(): Column
    {
        return Column::make('Date de création', 'created_at')
            ->sortable()
            ->format(fn ($value) => $value->format('j F Y H:i'));
    }

    private function getSystemAccountTypeFilter(): SelectFilter
    {
        /** @var SelectFilter $filter */
        $filter = SelectFilter::make('Type de Compte', 'system_account.type')
            ->options($this->getPaymentMethodOptions())
            ->filter(function (Builder $builder, string $value) {
                return $builder->whereHas('systemAccount', function ($query) use ($value) {
                    $query->where('type', $value);
                });
            });

        return $filter;
    }

    private function getTransactionTypeFilter(): SelectFilter
    {
        /** @var SelectFilter $filter */
        $filter = SelectFilter::make('Type de Transaction', 'type')
            ->options($this->getTransactionTypeOptions())
            ->filter(function (Builder $builder, string $value) {
                return $value === '' ? $builder : $builder->where('type', $value);
            });

        return $filter;
    }

    private function getCreatedDateFilter(): DateFilter
    {
        /** @var DateFilter $filter */
        $filter = DateFilter::make('Créé après', 'created_at')
            ->config([
                'placeholder' => 'Date de création minimum',
                'locale' => 'fr',
            ])
            ->filter(function (Builder $builder, string $value) {
                return $builder->whereDate('created_at', '>=', $value);
            });

        return $filter;
    }

    private function getPaymentMethodOptions(): array
    {
        return $this->getEnumOptions(PaymentMethod::class, 'Tous les Comptes');
    }

    private function getTransactionTypeOptions(): array
    {
        return $this->getEnumOptions(ExternalTransactionType::class, 'Tous les Types');
    }

    /**
     * @param  class-string  $enumClass
     */
    private function getEnumOptions(string $enumClass, string $defaultLabel): array
    {
        $options = ['' => $defaultLabel];

        foreach ($enumClass::cases() as $case) {
            $options[$case->value] = $case->label;
        }

        return $options;
    }

    private function isRechargeTransaction(ExternalTransactionType $type): bool
    {
        return $type->value === ExternalTransactionType::RECHARGE()->value;
    }
}
