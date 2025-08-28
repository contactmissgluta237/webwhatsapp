<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use HarroldWafo\LaravelCustomDatatable\DataTables\BaseDataTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

final class SubscriptionsDataTable extends BaseDataTable
{
    protected $model = UserSubscription::class;
    protected const DEFAULT_SORT_FIELD = 'created_at';
    protected const DEFAULT_SORT_DIRECTION = 'desc';

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setTableWrapperAttributes([
                'class' => 'table-responsive',
            ])
            ->setEmptyMessage('<div class="text-center py-4 text-muted"><i class="la la-inbox la-3x d-block"></i><p class="mt-2">Aucune souscription trouvée</p></div>');
    }

    protected function getExportFileName(): string
    {
        return 'souscriptions';
    }

    public function builder(): Builder
    {
        return UserSubscription::query()
            ->with(['user', 'package'])
            ->orderBy('created_at', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable()->deselected(),

            Column::make('Utilisateur', 'user.first_name')
                ->sortable()
                ->searchable()
                ->format(function ($value, $row) {
                    if (! $row->user) {
                        return '<span class="text-muted">Utilisateur introuvable</span>';
                    }

                    $fullName = trim($row->user->first_name.' '.$row->user->last_name);

                    return '<div class="d-flex align-items-center">
                        <div class="avatar-sm me-3">
                            <div class="avatar-title bg-primary text-white rounded-circle">
                                '.strtoupper(substr($fullName, 0, 1)).'
                            </div>
                        </div>
                        <div>
                            <strong>'.$fullName.'</strong>
                            <br>
                            <small class="text-muted">'.$row->user->email.'</small>
                        </div>
                    </div>';
                })
                ->html(),

            Column::make('Package', 'package.display_name')
                ->sortable()
                ->format(function ($value, $row) {
                    if (! $row->package) {
                        return '<span class="text-muted">Package introuvable</span>';
                    }

                    $badge = '<span class="badge bg-primary">'.$value.'</span>';
                    $price = '<br><small class="text-muted">'.number_format((float) $row->package->price).' XAF</small>';

                    return $badge.$price;
                })
                ->html(),            Column::make('Statut', 'status')
                ->sortable()
                ->format(function ($value) {
                    $badges = [
                        'active' => '<span class="badge bg-success">Actif</span>',
                        'expired' => '<span class="badge bg-warning">Expiré</span>',
                        'cancelled' => '<span class="badge bg-danger">Annulé</span>',
                        'suspended' => '<span class="badge bg-secondary">Suspendu</span>',
                    ];

                    return $badges[$value] ?? '<span class="badge bg-light text-dark">'.ucfirst($value).'</span>';
                })
                ->html(),

            Column::make('Début', 'starts_at')
                ->sortable()
                ->format(function ($value) {
                    return '<strong>'.$value->format('d/m/Y').'</strong><br><small class="text-muted">'.$value->format('H:i').'</small>';
                })
                ->html(),

            Column::make('Fin', 'ends_at')
                ->sortable()
                ->format(function ($value) {
                    return '<strong>'.$value->format('d/m/Y').'</strong><br><small class="text-muted">'.$value->format('H:i').'</small>';
                })
                ->html(),

            Column::make('Montant', 'amount_paid')
                ->sortable()
                ->format(function ($value, $row) {
                    $amount = (float) $value;
                    if ($amount > 0) {
                        return '<strong>'.number_format($amount).' XAF</strong><br><small class="text-muted">'.($row->payment_method ?? 'wallet').'</small>';
                    }

                    return '<span class="badge bg-success">Gratuit</span>';
                })
                ->html(),

            Column::make('Usage', 'id')
                ->format(function ($value, $row) {
                    $totalUsed = $row->getTotalMessagesUsed();
                    $percentage = $row->messages_limit > 0 ? ($totalUsed / $row->messages_limit) * 100 : 0;

                    $progressClass = 'bg-success';
                    if ($percentage >= 90) {
                        $progressClass = 'bg-danger';
                    } elseif ($percentage >= 70) {
                        $progressClass = 'bg-warning';
                    }

                    return '<div class="d-flex align-items-center">
                        <div class="flex-grow-1 me-2">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar '.$progressClass.'" style="width: '.min(100, $percentage).'%"></div>
                            </div>
                        </div>
                        <small class="text-muted">'.$totalUsed.'/'.number_format((float) $row->messages_limit).'</small>
                    </div>';
                })
                ->html(),

            Column::make('Créé le', 'created_at')
                ->sortable()
                ->format(function ($value) {
                    return '<strong>'.$value->format('d/m/Y').'</strong><br><small class="text-muted">'.$value->diffForHumans().'</small>';
                })
                ->html(),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Package', 'package_id')
                ->options($this->getPackageOptions())
                ->filter(fn (Builder $builder, string $value) => $value === '' ? $builder : $builder->where('package_id', $value)),

            SelectFilter::make('Statut', 'status')
                ->options([
                    '' => 'Tous les statuts',
                    'active' => 'Actif',
                    'expired' => 'Expiré',
                    'cancelled' => 'Annulé',
                    'suspended' => 'Suspendu',
                ])
                ->filter(fn (Builder $builder, string $value) => $value === '' ? $builder : $builder->where('status', $value)),

            SelectFilter::make('Utilisateur', 'user_search')
                ->options($this->getUserOptions())
                ->filter(function (Builder $builder, string $value) {
                    if ($value === '') {
                        return $builder;
                    }

                    return $builder->whereHas('user', function ($query) use ($value) {
                        $query->where('first_name', 'like', '%'.$value.'%')
                            ->orWhere('last_name', 'like', '%'.$value.'%')
                            ->orWhere('email', 'like', '%'.$value.'%');
                    });
                }),

            DateFilter::make('Créé après', 'created_after')
                ->config(['placeholder' => 'Date minimum', 'locale' => 'fr'])
                ->filter(fn (Builder $builder, string $value) => $builder->whereDate('created_at', '>=', $value)),

            DateFilter::make('Créé avant', 'created_before')
                ->config(['placeholder' => 'Date maximum', 'locale' => 'fr'])
                ->filter(fn (Builder $builder, string $value) => $builder->whereDate('created_at', '<=', $value)),
        ];
    }

    private function getPackageOptions(): array
    {
        $packages = Package::orderBy('sort_order')->get(['id', 'display_name']);

        $options = ['' => 'Tous les packages'];
        foreach ($packages as $package) {
            $options[$package->id] = $package->display_name;
        }

        return $options;
    }

    private function getUserOptions(): array
    {
        /** @var Collection<User> $users */
        $users = User::whereHas('subscriptions')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(50)
            ->get(['id', 'first_name', 'last_name', 'email']);

        $options = ['' => 'Tous les utilisateurs'];
        foreach ($users as $user) {
            $fullName = trim($user->first_name.' '.$user->last_name);
            $options[$fullName] = $fullName.' ('.$user->email.')';
        }

        return $options;
    }
}
