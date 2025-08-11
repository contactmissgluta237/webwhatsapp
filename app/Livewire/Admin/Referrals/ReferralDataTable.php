<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referrals;

use App\Models\User;
use Carbon\Carbon;
use HarroldWafo\LaravelCustomDatatable\DataTables\BaseDataTable;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class ReferralDataTable extends BaseDataTable
{
    protected $model = User::class;
    protected const DEFAULT_SORT_FIELD = 'created_at';
    protected const DEFAULT_SORT_DIRECTION = 'desc';

    protected function getExportFileName(): string
    {
        return 'filleuls_plateforme';
    }

    public function builder(): Builder
    {
        return User::query()
            ->select([
                'users.*',
            ])
            ->whereNotNull('users.referrer_id')
            ->whereHas('customer')
            ->with([
                'referrer:id,first_name,last_name,email,affiliation_code',
                'customer:id,user_id',
            ]);
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')
                ->sortable()
                ->deselected(),

            Column::make('Filleul')
                ->label(fn (User $row): string => $row->full_name)
                ->sortable(
                    fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('first_name', $direction)
                        ->orderBy('last_name', $direction)
                )
                ->searchable(function (Builder $query, string $searchTerm): Builder {
                    return $query->where(function (Builder $subQuery) use ($searchTerm): void {
                        $subQuery->where('first_name', 'like', "%{$searchTerm}%")
                            ->orWhere('last_name', 'like', "%{$searchTerm}%");
                    });
                }),

            Column::make('Email du filleul', 'email')
                ->sortable()
                ->searchable(),

            Column::make('Parrain')
                ->label(fn (User $row): string => $row->referrer?->full_name ?? 'Aucun parrain')
                ->sortable(
                    fn (Builder $query, string $direction): Builder => $query
                        ->join('users as referrers', 'users.referrer_id', '=', 'referrers.id')
                        ->orderBy('referrers.first_name', $direction)
                        ->orderBy('referrers.last_name', $direction)
                )
                ->searchable(function (Builder $query, string $searchTerm): Builder {
                    return $query->join('users as referrers', 'users.referrer_id', '=', 'referrers.id')
                        ->where(function (Builder $subQuery) use ($searchTerm): void {
                            $subQuery->where('referrers.first_name', 'like', "%{$searchTerm}%")
                                ->orWhere('referrers.last_name', 'like', "%{$searchTerm}%");
                        });
                }),

            Column::make('Code d\'affiliation parrain')
                ->label(fn (User $row): string => $row->referrer?->affiliation_code ?? 'Aucun code')
                ->searchable(function (Builder $query, string $searchTerm): Builder {
                    return $query->join('users as referrers', 'users.referrer_id', '=', 'referrers.id')
                        ->where('referrers.affiliation_code', 'like', "%{$searchTerm}%");
                }),

            Column::make('Gain généré', 'id')
                ->format(function (int $value, User $row): string {
                    $gain = $this->calculateReferralEarnings($row);

                    return number_format($gain, 0, ',', ' ').' FCFA';
                })
                ->html(),

            Column::make('Date de parrainage', 'created_at')
                ->sortable()
                ->format(fn (?Carbon $value): string => $value?->format('d/m/Y H:i') ?? '-'),
        ];
    }

    public function filters(): array
    {
        $referrers = User::whereHas('referrals', function (Builder $query): void {
            $query->whereHas('customer');
        })
            ->with('customer')
            ->get()
            ->mapWithKeys(fn (User $user): array => [
                $user->id => $user->full_name,
            ])
            ->toArray();

        return [
            SelectFilter::make('Parrain', 'referrer_id')
                ->options(['' => 'Tous les parrains'] + $referrers)
                ->filter(function (Builder $query, string $value): Builder {
                    return $query->where('referrer_id', $value);
                }),

            DateFilter::make('Date de parrainage depuis', 'created_at_from')
                ->filter(function (Builder $query, string $value): Builder {
                    return $query->whereDate('created_at', '>=', $value);
                }),

            DateFilter::make('Date de parrainage jusqu\'à', 'created_at_to')
                ->filter(function (Builder $query, string $value): Builder {
                    return $query->whereDate('created_at', '<=', $value);
                }),
        ];
    }

    private function calculateReferralEarnings(User $user): float
    {
        return 0.0;
    }
}
