<?php

namespace App\Livewire\Customer;

use App\Models\User;
use HarroldWafo\LaravelCustomDatatable\DataTables\BaseDataTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;

class ReferralDataTable extends BaseDataTable
{
    protected $model = User::class;
    protected const DEFAULT_SORT_FIELD = 'created_at';
    protected const DEFAULT_SORT_DIRECTION = 'desc';

    protected function getExportFileName(): string
    {
        return 'mes_filleuls';
    }

    public function builder(): Builder
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = User::query()
            ->select(['users.*'])
            ->where('referrer_id', $user->id)
            ->whereHas('customer')
            ->with(['customer']);

        return $query;
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')
                ->sortable()
                ->deselected(),

            Column::make('Nom complet')
                ->label(function (User $row): string {
                    return $row->full_name ?? 'N/A';
                })
                ->sortable(
                    fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('first_name', $direction)
                        ->orderBy('last_name', $direction)
                )
                ->searchable(function (Builder $query, string $searchTerm): Builder {
                    return $query->where(function (Builder $subQuery) use ($searchTerm): void {
                        $subQuery->where('first_name', 'like', '%'.$searchTerm.'%')
                            ->orWhere('last_name', 'like', '%'.$searchTerm.'%');
                    });
                }),

            Column::make('Email', 'email')
                ->sortable()
                ->searchable(),

            Column::make('Téléphone', 'phone_number')
                ->sortable()
                ->searchable(),

            Column::make('Date d\'inscription', 'created_at')
                ->format(fn ($value) => $value->format('d/m/Y H:i'))
                ->sortable(),

            Column::make('Statut')
                ->label(function (User $row): string {
                    return $row->is_active
                        ? '<span class="badge bg-success">Actif</span>'
                        : '<span class="badge bg-danger">Inactif</span>';
                })
                ->html(),
        ];
    }

    public function filters(): array
    {
        return [
            DateFilter::make('Date d\'inscription')
                ->config([
                    'min' => now()->subYear()->format('Y-m-d'),
                    'max' => now()->format('Y-m-d'),
                ])
                ->filter(function (Builder $builder, string $value): Builder {
                    return $builder->whereDate('created_at', $value);
                }),
        ];
    }
}
