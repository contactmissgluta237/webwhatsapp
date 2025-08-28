<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Package;
use HarroldWafo\LaravelCustomDatatable\DataTables\BaseDataTable;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

final class PackagesDataTable extends BaseDataTable
{
    protected $model = Package::class;
    protected const DEFAULT_SORT_FIELD = 'sort_order';
    protected const DEFAULT_SORT_DIRECTION = 'asc';

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setTableWrapperAttributes([
                'class' => 'table-responsive',
            ])
            ->setEmptyMessage('<div class="text-center py-4"><i class="la la-gift la-3x text-muted mb-3 d-block"></i><p class="text-muted">Aucun package trouvé</p></div>');
    }

    protected function getExportFileName(): string
    {
        return 'packages';
    }

    public function builder(): Builder
    {
        return Package::query()
            ->with(['subscriptions'])
            ->orderBy('sort_order')
            ->orderBy('price');
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable()->deselected(),

            Column::make('Package', 'display_name')
                ->sortable()
                ->searchable()
                ->format(function ($value, $row) {
                    $editUrl = route('admin.packages.edit', $row->id);

                    return '<div>
                        <a href="'.$editUrl.'" class="text-decoration-none">
                            <span class="text-whatsapp fw-bold">
                                '.$value.'
                            </span>
                        </a>
                        <br>
                        <small class="text-muted mt-1 d-block">'.($row->description ? str($row->description)->limit(50) : 'Aucune description').'</small>
                    </div>';
                })
                ->html(),

            Column::make('Prix', 'price')
                ->sortable()
                ->format(function ($value, $row) {
                    if ($row->hasActivePromotion()) {
                        return '<div>
                            <span class="text-muted text-decoration-line-through small">'.number_format((float) $row->price, 0, ',', ' ').' XAF</span><br>
                            <span class="text-whatsapp fw-bold">'.number_format((float) $row->promotional_price, 0, ',', ' ').' XAF</span>
                            <span class="badge badge-success ml-1">-'.$row->getPromotionalDiscountPercentage().'%</span>
                        </div>';
                    }

                    return '<span class="text-dark fw-bold">'.number_format((float) $value, 0, ',', ' ').' XAF</span>';
                })
                ->html(),

            Column::make('Limites', 'id')
                ->html()
                ->format(function ($value, $row) {
                    // Debug: Let's see what we get
                    $debug = "ID:{$row->id} - M:{$row->messages_limit} - A:{$row->accounts_limit} - P:{$row->products_limit}";

                    return '<div class="small">
                        <div><i class="la la-comment text-whatsapp mr-1"></i> '.number_format((int) $row->messages_limit).' messages</div>
                        <div><i class="la la-whatsapp text-whatsapp mr-1"></i> '.number_format((int) $row->accounts_limit).' compte'.((int) $row->accounts_limit > 1 ? 's' : '').'</div>
                        <div><i class="la la-shopping-bag text-whatsapp mr-1"></i> '.number_format((int) $row->products_limit).' produit'.((int) $row->products_limit > 1 ? 's' : '').'</div>
                        <div class="text-muted small">'.$debug.'</div>
                    </div>';
                }),

            Column::make('Statut', 'is_active')
                ->sortable()
                ->html()
                ->format(function ($value) {
                    return $value
                        ? '<span class="badge badge-success">Actif</span>'
                        : '<span class="badge badge-secondary">Inactif</span>';
                }),

            Column::make('Souscriptions', 'id')
                ->html()
                ->format(function ($value, $row) {
                    $count = $row->subscriptions()->count();
                    if ($count === 0) {
                        return '<span class="text-muted">Aucune</span>';
                    }

                    return '<span class="badge badge-info">'.$count.' souscription'.($count > 1 ? 's' : '').'</span>';
                }),

            Column::make('Ordre', 'sort_order')
                ->sortable()
                ->format(fn ($value) => '<span class="badge badge-light">'.$value.'</span>')
                ->html(),

            Column::make('Créé le', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('j M Y H:i')),

            Column::make('Actions')
                ->label(fn (Package $row) => view('partials.admin.packages.actions', ['package' => $row])->render())
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

            SelectFilter::make('Type', 'name')
                ->options([
                    '' => 'Tous les types',
                    'trial' => 'Essai',
                    'starter' => 'Starter',
                    'business' => 'Business',
                    'pro' => 'Pro',
                ])
                ->filter(fn (Builder $builder, string $value) => $value === '' ? $builder : $builder->where('name', $value)),

            DateFilter::make('Créé après', 'created_at')
                ->config(['placeholder' => 'Date de création minimum', 'locale' => 'fr'])
                ->filter(fn (Builder $builder, string $value) => $builder->whereDate('created_at', '>=', $value)),
        ];
    }
}
