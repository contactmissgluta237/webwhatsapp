<?php

declare(strict_types=1);

namespace App\Livewire\Customer;

use App\Models\UserSubscription;
use HarroldWafo\LaravelCustomDatatable\DataTables\BaseDataTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
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
            ->setEmptyMessage('No subscription found.');
    }

    protected function getExportFileName(): string
    {
        return 'my_subscriptions';
    }

    public function builder(): Builder
    {
        return UserSubscription::query()
            ->where('user_id', Auth::id())
            ->with(['package'])
            ->orderBy('created_at', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable()->deselected(),

            Column::make('Package', 'package.name')
                ->sortable()
                ->searchable()
                ->format(function ($value, $row) {
                    return '<div class="d-flex align-items-center">
                        <i class="la la-gift text-primary mr-2 la-lg"></i>
                        <div>
                            <div class="fw-bold">'.$value.'</div>
                            <small class="text-muted">'.number_format($row->amount_paid ?? 0, 0, ',', ' ').' XAF</small>
                        </div>
                    </div>';
                })
                ->html(),

            Column::make('Statut', 'status')
                ->sortable()
                ->format(function ($value, $row) {
                    $status = $row->getCurrentStatus();

                    return match ($status) {
                        'active' => '<span class="badge badge-success"><i class="la la-check"></i> Active</span>',
                        'expired' => '<span class="badge badge-secondary"><i class="la la-clock"></i> Expired</span>',
                        'cancelled' => '<span class="badge badge-danger"><i class="la la-times"></i> Cancelled</span>',
                        'suspended' => '<span class="badge badge-warning"><i class="la la-pause"></i> Suspended</span>',
                        default => '<span class="badge badge-light">'.ucfirst($status).'</span>'
                    };
                })
                ->html(),

            Column::make('Période', 'starts_at')
                ->sortable()
                ->format(function ($value, $row) {
                    return '<div class="text-center">
                        <div class="small"><strong>From:</strong> '.$row->starts_at->format('d/m/Y').'</div>
                        <div class="small"><strong>To:</strong> '.$row->ends_at->format('d/m/Y').'</div>
                    </div>';
                })
                ->html(),

            Column::make('Messages', 'id')
                ->format(function ($value, $row) {
                    $used = $row->getTotalMessagesUsed();
                    $total = $row->messages_limit;
                    $percentage = $total > 0 ? ($used / $total) * 100 : 0;

                    $progressClass = $percentage > 80 ? 'danger' : ($percentage > 60 ? 'warning' : 'success');

                    return '<div class="progress-container" style="min-width: 120px;">
                        <div class="progress mb-1" style="height: 6px;">
                            <div class="progress-bar bg-'.$progressClass.'" role="progressbar" 
                                 style="width: '.min(100, $percentage).'%"></div>
                        </div>
                        <small class="text-muted">'.number_format($used).' / '.number_format($total).'</small>
                    </div>';
                })
                ->html(),

            Column::make('Days remaining', 'ends_at')
                ->sortable()
                ->format(function ($value, $row) {
                    $days = $row->getRemainingDays();

                    if ($days <= 0) {
                        return '<span class="text-muted">Expired</span>';
                    }

                    $class = $days <= 7 ? 'text-danger' : ($days <= 30 ? 'text-warning' : 'text-success');

                    return '<span class="'.$class.' fw-bold">'.$days.' day'.($days > 1 ? 's' : '').'</span>';
                })
                ->html(),

            Column::make('Actions')
                ->label(fn (UserSubscription $row) => '
                    <a href="'.route('customer.subscriptions.show', $row->id).'" 
                       class="btn btn-sm btn-outline-primary" 
                       title="Voir les détails">
                        <i class="la la-eye"></i>
                    </a>
                ')
                ->html(),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Status', 'status')
                ->options([
                    '' => 'All statuses',
                    'active' => 'Active',
                    'expired' => 'Expired',
                    'cancelled' => 'Cancelled',
                    'suspended' => 'Suspended',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === '') {
                        return $builder;
                    }

                    if ($value === 'expired') {
                        return $builder->where('ends_at', '<=', now());
                    }

                    return $builder->where('status', $value);
                }),

            DateFilter::make('Subscribed after', 'starts_at')
                ->config(['placeholder' => 'Minimum subscription date', 'locale' => 'fr'])
                ->filter(fn (Builder $builder, string $value) => $builder->whereDate('starts_at', '>=', $value)),
        ];
    }
}
