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
        $this->setPrimaryKey('id')
            ->setTableWrapperAttributes([
                'class' => 'table-responsive',
            ])
            ->setEmptyMessage('<div class="text-center py-4"><i class="la la-users la-3x text-muted mb-3 d-block"></i><p class="text-muted">Aucun utilisateur trouvé</p></div>');
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
            ->orderBy(self::DEFAULT_SORT_FIELD, self::DEFAULT_SORT_DIRECTION);
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')
                ->sortable()
                ->deselected(),

            Column::make('Nom Complet', 'first_name')
                ->sortable()
                ->searchable()
                ->format(function ($value, $row) {
                    $editUrl = route('admin.users.edit', $row->id);

                    return '<div>
                        <a href="'.$editUrl.'" class="text-decoration-none">
                            <span class="text-whatsapp fw-bold">
                                '.$row->full_name.'
                            </span>
                        </a>
                        <br>
                        <small class="text-muted mt-1 d-block">'.$row->email.'</small>
                    </div>';
                })
                ->html(),

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
                    return $value
                        ? '<span class="badge badge-success">Actif</span>'
                        : '<span class="badge badge-secondary">Inactif</span>';
                }),

            Column::make('Rôles')
                ->label(function (User $row) {
                    return $row->roles->map(fn (\Spatie\Permission\Models\Role $role) => '<span class="badge badge-primary">'.$role->name.'</span>')->implode(' ');
                })
                ->html(),

            Column::make('Dernière connexion', 'last_login_at')
                ->sortable()
                ->format(fn ($value) => $value ? $value->format('j M Y H:i') : '<span class="text-muted">Jamais</span>')
                ->html(),

            Column::make('Créé le', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('j M Y H:i')),

            Column::make('Actions')
                ->label(fn (User $row) => view('partials.admin.users.actions', ['user' => $row])->render())
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
                ->filter(fn (Builder $builder, string $value) => $value === '' ? $builder : $builder->where('is_active', $value)),

            SelectFilter::make('Pays', 'country_id')
                ->options(
                    ['' => 'Tous les pays'] +
                    Country::orderBy('name')->pluck('name', 'id')->toArray()
                )
                ->filter(fn (Builder $builder, string $value) => $value === '' ? $builder : $builder->where('country_id', $value)),

            SelectFilter::make('Rôle', 'role')
                ->options(
                    ['' => 'Tous les rôles'] +
                    Role::orderBy('name')->pluck('name', 'name')->toArray()
                )
                ->filter(function (Builder $builder, string $value) {
                    return $value === '' ? $builder : $builder->whereHas('roles', fn ($query) => $query->where('name', $value));
                }),

            DateFilter::make('Créé après', 'created_at')
                ->config(['placeholder' => 'Date de création minimum', 'locale' => 'fr'])
                ->filter(fn (Builder $builder, string $value) => $builder->whereDate('created_at', '>=', $value)),

            DateFilter::make('Dernière connexion après', 'last_login_at')
                ->config(['placeholder' => 'Date de dernière connexion minimum', 'locale' => 'fr'])
                ->filter(fn (Builder $builder, string $value) => $builder->whereDate('last_login_at', '>=', $value)),
        ];
    }
}
