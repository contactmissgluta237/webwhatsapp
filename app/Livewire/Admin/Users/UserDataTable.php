<?php

namespace App\Livewire\Admin\Users;

use App\Models\Geography\Country;
use App\Models\User;
use HarroldWafo\LaravelCustomDatatable\DataTables\BaseDataTable;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Spatie\Permission\Models\Role;

class UserDataTable extends BaseDataTable
{
    protected $model = User::class;

    protected const DEFAULT_SORT_FIELD = 'created_at';
    protected const DEFAULT_SORT_DIRECTION = 'desc';

    public function configure(): void
    {
        parent::configure();
        $this->setDefaultSort(self::DEFAULT_SORT_FIELD, self::DEFAULT_SORT_DIRECTION);
    }

    protected function getExportFileName(): string
    {
        return 'utilisateurs';
    }

    public function builder(): Builder
    {
        return User::query()
            ->select('users.*')
            ->with('roles', 'country')
            ->orderBy('created_at', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')
                ->sortable()
                ->deselected(),

            Column::make('Nom Complet')
                ->label(function ($row) {
                    return $row->full_name;
                })
                ->sortable(function (Builder $query, string $direction) {
                    return $query->orderBy('first_name', $direction)
                        ->orderBy('last_name', $direction);
                })
                ->searchable(function (Builder $query, string $searchTerm) {
                    $query->where('first_name', 'like', "%{$searchTerm}%")
                        ->orWhere('last_name', 'like', "%{$searchTerm}%");
                }),

            Column::make('Email', 'email')
                ->sortable()
                ->searchable(),

            Column::make('Téléphone', 'phone_number')
                ->sortable()
                ->searchable(),

            Column::make('Statut', 'is_active')
                ->sortable()
                ->html()
                ->format(function ($value) {
                    $badgeClass = $value ? 'success' : 'danger';
                    $text = $value ? 'Actif' : 'Inactif';

                    return '<span class="badge text-outline-'.$badgeClass.'">'.$text.'</span>';
                }),

            Column::make('Rôles')
                ->label(function (User $row) {
                    return $row->roles->map(fn (\Spatie\Permission\Models\Role $role) => '<span class="badge text-outline-primary">'.$role->name.'</span>')->implode(' ');
                })
                ->html(),

            Column::make('Dernière connexion', 'last_login_at')
                ->sortable()
                ->format(fn ($value) => $value ? $value->format('j F Y H:i:s') : '-'),

            Column::make('Date de création', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('j F Y H:i:s')),

            Column::make('Actions')
                ->label(function (User $row) {
                    return view('partials.admin.users.actions', ['user' => $row]);
                })
                ->html(),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Statut', 'is_active')
                ->options([
                    '' => 'Tous les statuts',
                    '1' => 'Actif',
                    '0' => 'Inactif',
                ])
                ->filter(function (Builder $builder, string $value) {
                    return $value === '' ? $builder : $builder->where('is_active', (bool) $value);
                }),

            SelectFilter::make('Pays', 'country_id')
                ->options(
                    ['' => 'Tous les pays'] +
                    Country::orderBy('name')->pluck('name', 'id')->toArray()
                )
                ->filter(function (Builder $builder, string $value) {
                    return $value === '' ? $builder : $builder->where('country_id', $value);
                }),

            SelectFilter::make('Rôle', 'role')
                ->options(
                    ['' => 'Tous les rôles'] +
                    Role::orderBy('name')->pluck('name', 'name')->toArray()
                )
                ->filter(function (Builder $builder, string $value) {
                    return $value === '' ? $builder : $builder->whereHas('roles', fn ($query) => $query->where('name', 'value'));
                }),

            DateFilter::make('Créé après', 'created_at')
                ->config([
                    'placeholder' => 'Date de création minimum',
                    'locale' => 'fr',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->whereDate('created_at', '>=', $value);
                }),

            DateFilter::make('Dernière connexion après', 'last_login_at')
                ->config([
                    'placeholder' => 'Date de dernière connexion minimum',
                    'locale' => 'fr',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->whereDate('last_login_at', '>=', $value);
                }),
        ];
    }
}
