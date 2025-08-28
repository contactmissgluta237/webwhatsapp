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
            ->setEmptyMessage('<div class="text-center py-4"><i class="mdi mdi-package-variant-closed mdi-3x text-muted mb-3 d-block"></i><p class="text-muted">Aucun package trouvé</p></div>');
    }

    protected function getExportFileName(): string
    {
        return 'packages';
    }

    public function builder(): Builder
    {
        return Package::query()
            ->select([
                'id',
                'name',
                'display_name',
                'description',
                'price',
                'promotional_price',
                'promotion_starts_at',
                'promotion_ends_at',
                'promotion_is_active',
                'currency',
                'messages_limit',
                'context_limit',
                'accounts_limit',
                'products_limit',
                'duration_days',
                'is_recurring',
                'one_time_only',
                'features',
                'is_active',
                'sort_order',
                'created_at',
            ])
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

                    $html = '<div>
                        <a href="'.$editUrl.'" class="text-decoration-none">
                            <span class="text-whatsapp fw-bold">
                                '.$value.'
                            </span>';

                    // Badge recommandé pour le package business
                    if ($row->name === 'business') {
                        $html .= ' <span class="badge badge-warning ml-1">Recommandé</span>';
                    }

                    $html .= '</a>
                        <br>
                        <small class="text-muted mt-1 d-block">'.($row->description ? str($row->description)->limit(50) : 'Aucune description').'</small>
                    </div>';

                    return $html;
                })
                ->html(),

            Column::make('Prix', 'price')
                ->sortable()
                ->format(function ($value, $row) {
                    if ($row->hasActivePromotion()) {
                        $originalPrice = $row->price == 0 ? 'GRATUIT' : number_format((float) $row->price, 0, ',', ' ').' XAF';
                        $promoPrice = $row->promotional_price == 0 ? 'GRATUIT' : number_format((float) $row->promotional_price, 0, ',', ' ').' XAF';

                        return '<div>
                            <span class="text-muted text-decoration-line-through small">'.$originalPrice.'</span><br>
                            <span class="text-whatsapp fw-bold">'.$promoPrice.'</span>
                            <span class="badge badge-success ml-1">-'.$row->getPromotionalDiscountPercentage().'%</span>
                        </div>';
                    }

                    if ($value == 0) {
                        return '<span class="text-success fw-bold">GRATUIT</span>';
                    }

                    return '<span class="text-dark fw-bold">'.number_format((float) $value, 0, ',', ' ').' XAF</span>';
                })
                ->html(),

            Column::make('Messages', 'messages_limit')
                ->sortable()
                ->format(function ($value) {
                    return '<span class="text-dark fw-bold">'.number_format($value).'</span>';
                })
                ->html(),

            Column::make('Contexte', 'context_limit')
                ->sortable()
                ->format(function ($value) {
                    return '<span class="text-dark fw-bold">'.number_format($value).'</span>';
                })
                ->html(),

            Column::make('Comptes', 'accounts_limit')
                ->sortable()
                ->format(function ($value) {
                    return '<span class="text-dark fw-bold">'.number_format($value).' compte'.($value > 1 ? 's' : '').'</span>';
                })
                ->html(),

            Column::make('Produits', 'products_limit')
                ->sortable()
                ->format(function ($value) {
                    return '<span class="text-dark fw-bold">'.number_format($value).' produit'.($value > 1 ? 's' : '').'</span>';
                })
                ->html(),

            Column::make('Durée', 'duration_days')
                ->html()
                ->format(function ($value, $row) {
                    if ($value === null) {
                        return '<span class="text-muted small">Illimitée</span>';
                    }

                    $durationText = $value == 1 ? '1 jour' : $value.' jour'.($value > 1 ? 's' : '');

                    $html = '<div class="small">
                        <div class="text-dark">'.$durationText.'</div>';

                    if ($row->is_recurring) {
                        $html .= '<span class="badge badge-success">Récurrent</span>';
                    } elseif ($row->one_time_only) {
                        $html .= '<span class="badge badge-warning">Une seule fois</span>';
                    }

                    $html .= '</div>';

                    return $html;
                }),

            Column::make('Fonctionnalités', 'features')
                ->html()
                ->format(function ($value, $row) {
                    if (empty($row->features)) {
                        return '<span class="text-muted small">Standard</span>';
                    }

                    $featureLabels = [
                        'weekly_reports' => 'Rapports hebdo.',
                        'priority_support' => 'Support prioritaire',
                        'advanced_analytics' => 'Analytics avancés',
                        'custom_branding' => 'Branding personnalisé',
                    ];

                    $html = '<div class="small">';
                    foreach ($row->features as $feature) {
                        $label = $featureLabels[$feature] ?? ucfirst(str_replace('_', ' ', $feature));
                        $html .= '<span class="badge badge-primary mr-1 mb-1">'.$label.'</span>';
                    }
                    $html .= '</div>';

                    return $html;
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
