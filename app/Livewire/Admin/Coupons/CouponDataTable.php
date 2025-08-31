<?php

namespace App\Livewire\Admin\Coupons;

use App\Enums\CouponStatus;
use App\Enums\CouponType;
use App\Models\Coupon;
use HarroldWafo\LaravelCustomDatatable\DataTables\BaseDataTable;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class CouponDataTable extends BaseDataTable
{
    protected $model = Coupon::class;

    protected const DEFAULT_SORT_FIELD = 'created_at';
    protected const DEFAULT_SORT_DIRECTION = 'desc';

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setTableWrapperAttributes([
                'class' => 'table-responsive',
            ])
            ->setEmptyMessage('Aucun coupon trouvé');
    }

    protected function getExportFileName(): string
    {
        return 'coupons';
    }

    public function builder(): Builder
    {
        return Coupon::query()
            ->select('coupons.*')
            ->with('creator')
            ->orderBy(self::DEFAULT_SORT_FIELD, self::DEFAULT_SORT_DIRECTION);
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')
                ->sortable()
                ->deselected(),

            Column::make('Code', 'code')
                ->sortable()
                ->searchable()
                ->format(function (string $value): string {
                    return '<span class="badge bg-dark text-white" style="white-space: nowrap; font-family: monospace;">'.$value.'</span>';
                })
                ->html(),

            Column::make('Type', 'type')
                ->sortable()
                ->format(function (string $value, object $row): string {
                    $type = CouponType::make($value);

                    return '<span class="badge '.$type->badge().'">'.$type->label().'</span>';
                })
                ->html(),

            Column::make('Valeur', 'value')
                ->sortable()
                ->format(function (float $value, object $row): string {
                    if ($row->type->value === CouponType::PERCENTAGE()->value) {
                        return $value.'%';
                    }

                    return \App\Helpers\CurrencyHelper::formatUsd($value);
                }),

            Column::make('Utilisation', 'used_count')
                ->sortable()
                ->format(function (int $value, object $row): string {
                    $percentage = $row->usage_limit > 0 ? ($value / $row->usage_limit) * 100 : 0;

                    return '<div class="d-flex align-items-center">
                                <span class="me-2">'.$value.'/'.$row->usage_limit.'</span>
                                <div class="progress flex-grow-1" style="height: 6px; width: 100px;">
                                    <div class="progress-bar bg-success" style="width: '.$percentage.'%"></div>
                                </div>
                            </div>';
                })
                ->html(),

            Column::make('Statut', 'status')
                ->sortable()
                ->format(function (string $value): string {
                    $status = CouponStatus::make($value);

                    return '<span class="badge '.$status->badge().'">'.$status->label().'</span>';
                })
                ->html(),

            Column::make('Validité', 'valid_until')
                ->sortable()
                ->format(function (?string $value, object $row): string {
                    if (! $row->valid_from && ! $row->valid_until) {
                        return '<small class="text-muted">Illimitée</small>';
                    }
                    $html = '<small class="text-muted">';
                    if ($row->valid_from) {
                        $html .= 'Du '.$row->valid_from->format('d/m/Y').'<br>';
                    }
                    if ($row->valid_until) {
                        $html .= 'Au '.$row->valid_until->format('d/m/Y');
                    }
                    $html .= '</small>';

                    return $html;
                })
                ->html(),

            Column::make('Créé par', 'created_by')
                ->format(function (int $value, object $row): string {
                    return '<small class="text-muted">'.($row->creator->full_name ?? 'Système').'</small>';
                })
                ->html(),

            Column::make('Actions', 'id')
                ->format(function (int $value, object $row): string {
                    return view('admin.coupons.partials.actions', ['coupon' => $row])->render();
                })
                ->html(),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Type', 'type')
                ->options([
                    '' => 'Tous les types',
                    CouponType::PERCENTAGE()->value => CouponType::PERCENTAGE()->label(),
                    CouponType::FIXED_AMOUNT()->value => CouponType::FIXED_AMOUNT()->label(),
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value) {
                        $builder->where('type', $value);
                    }
                }),

            SelectFilter::make('Statut', 'status')
                ->options([
                    '' => 'Tous les statuts',
                    CouponStatus::ACTIVE()->value => CouponStatus::ACTIVE()->label(),
                    CouponStatus::EXPIRED()->value => CouponStatus::EXPIRED()->label(),
                    CouponStatus::USED()->value => CouponStatus::USED()->label(),
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value) {
                        $builder->where('status', $value);
                    }
                }),
        ];
    }
}
