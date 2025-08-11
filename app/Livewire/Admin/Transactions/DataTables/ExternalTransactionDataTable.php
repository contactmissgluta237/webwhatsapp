<?php

namespace App\Livewire\Admin\Transactions\DataTables;

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

    public function configure(): void
    {
        parent::configure();
        $this->setDefaultSort(self::DEFAULT_SORT_FIELD, self::DEFAULT_SORT_DIRECTION);
    }

    protected function getExportFileName(): string
    {
        return 'transactions_externes';
    }

    public function builder(): Builder
    {
        return ExternalTransaction::query()
            ->select('external_transactions.*')
            ->with(['wallet.user', 'creator', 'approver'])
            ->orderBy('created_at', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')
                ->sortable()
                ->deselected(),

            Column::make('Client')
                ->label(function ($row) {
                    return $row->wallet && $row->wallet->user
                        ? $row->wallet->user->full_name
                        : null;
                })
                ->sortable(
                    fn ($query, $direction) => $query->join('wallets', 'external_transactions.wallet_id', '=', 'wallets.id')
                        ->join('users', 'wallets.user_id', '=', 'users.id')
                        ->orderBy('users.first_name', $direction)
                        ->orderBy('users.last_name', $direction)
                )
                ->searchable(function (Builder $query, string $searchTerm) {
                    $query->whereHas('wallet.user', function ($q) use ($searchTerm) {
                        $q->where('first_name', 'like', "%$searchTerm%")
                            ->orWhere('last_name', 'like', "%$searchTerm%");
                    });
                }),

            Column::make('Type', 'transaction_type')
                ->sortable()
                ->html()
                ->format(function ($value) {
                    $type = ExternalTransactionType::make($value);

                    return '<span class="badge text-outline-'.$type->badge().'">'.$type->label.'</span>';
                }),

            Column::make('Montant', 'amount')
                ->sortable()
                ->format(function ($value) {
                    return number_format($value, 0, ',', ' ').' FCFA';
                }),

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
                    $method = PaymentMethod::make($value);

                    return $method->label;
                }),

            Column::make('ID Externe', 'external_transaction_id')
                ->sortable()
                ->searchable(),

            Column::make('Créé par')
                ->label(function ($row) {
                    return $row->creator ? $row->creator->full_name : '-';
                })
                ->sortable()
                ->searchable(function (Builder $query, string $searchTerm) {
                    $query->whereHas('creator', function ($q) use ($searchTerm) {
                        $q->where('first_name', 'like', "%$searchTerm%")
                            ->orWhere('last_name', 'like', "%$searchTerm%");
                    });
                }),

            Column::make('Approuvé par')
                ->label(function ($row) {
                    if (is_null($row->approved_by)) {
                        return '';
                    }

                    return $row->approver ? $row->approver->full_name : 'Utilisateur introuvable';
                })
                ->sortable(
                    fn ($query, $direction) => $query->leftJoin('users as approvers', 'external_transactions.approved_by', '=', 'approvers.id')
                        ->orderBy('approvers.first_name', $direction)
                        ->orderBy('approvers.last_name', $direction)
                ),

            Column::make('Date de création', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('j F Y H:i:s')),

            Column::make('Actions')
                ->label(function (ExternalTransaction $row) {
                    return view('partials.admin.transactions.external-actions', ['transaction' => $row]);
                })
                ->html(),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Type', 'transaction_type')
                ->options(
                    ['' => 'Tous les types'] +
                        collect(ExternalTransactionType::values())
                            ->mapWithKeys(function ($value, $key) {
                                $type = ExternalTransactionType::make($value);

                                return [$value => $type->label];
                            })
                            ->toArray()
                )
                ->filter(function (Builder $builder, string $value) {
                    return $value === '' ? $builder : $builder->where('transaction_type', $value);
                }),

            SelectFilter::make('Statut', 'status')
                ->options(
                    ['' => 'Tous les statuts'] +
                        collect(TransactionStatus::values())
                            ->mapWithKeys(function ($value, $key) {
                                $status = TransactionStatus::make($value);

                                return [$value => $status->label];
                            })
                            ->toArray()
                )
                ->filter(function (Builder $builder, string $value) {
                    return $value === '' ? $builder : $builder->where('status', $value);
                }),

            SelectFilter::make('Mode', 'mode')
                ->options(
                    ['' => 'Tous les modes'] +
                        collect(TransactionMode::values())
                            ->mapWithKeys(function ($value, $key) {
                                $mode = TransactionMode::make($value);

                                return [$value => $mode->label];
                            })
                            ->toArray()
                )
                ->filter(function (Builder $builder, string $value) {
                    return $value === '' ? $builder : $builder->where('mode', $value);
                }),

            SelectFilter::make('Méthode de paiement', 'payment_method')
                ->options(
                    ['' => 'Toutes les méthodes'] +
                        collect(PaymentMethod::values())
                            ->mapWithKeys(function ($value, $key) {
                                $method = PaymentMethod::make($value);

                                return [$value => $method->label];
                            })
                            ->toArray()
                )
                ->filter(function (Builder $builder, string $value) {
                    return $value === '' ? $builder : $builder->where('payment_method', $value);
                }),

            SelectFilter::make('Approbation requise', 'needs_approval')
                ->options([
                    '' => 'Toutes',
                    '1' => 'En attente d\'approbation',
                    '0' => 'Approuvées ou non requises',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === '') {
                        return $builder;
                    }

                    if ($value === '1') {
                        return $builder->where('transaction_type', ExternalTransactionType::WITHDRAWAL()->value)
                            ->where('status', TransactionStatus::PENDING()->value)
                            ->whereNull('approved_by');
                    }

                    return $builder->where(function ($q) {
                        $q->whereNotNull('approved_by')
                            ->orWhere('transaction_type', ExternalTransactionType::RECHARGE()->value);
                    });
                }),

            DateFilter::make('Créé après', 'created_at')
                ->config([
                    'placeholder' => 'Date de création minimum',
                    'locale' => 'fr',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->whereDate('created_at', '>=', $value);
                }),
        ];
    }
}
