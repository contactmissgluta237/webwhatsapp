<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\SubscriptionStatus;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use Exception;
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
            ->setEmptyMessage(__('subscriptions.no_subscriptions_found'));
    }

    protected function getExportFileName(): string
    {
        return __('subscriptions.export_filename');
    }

    public function builder(): Builder
    {
        return UserSubscription::query()
            ->select('user_subscriptions.*')
            ->with(['user', 'package'])
            ->orderBy('created_at', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make(__('subscriptions.id'), 'id')->sortable()->deselected(),

            Column::make(__('subscriptions.user'), 'user.first_name')
                ->sortable()
                ->searchable()
                ->format(function ($value, $row) {
                    if (! $row->user) {
                        return '<span class="text-muted">'.__('subscriptions.user_not_found').'</span>';
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

            Column::make(__('subscriptions.package'), 'package.display_name')
                ->sortable()
                ->format(function ($value, $row) {
                    if (! $row->package) {
                        return '<span class="text-muted">'.__('subscriptions.package_not_found').'</span>';
                    }

                    $badge = '<span class="badge bg-primary">'.$value.'</span>';
                    $price = '<br><small class="text-muted">'.number_format((float) $row->package->price).' XAF</small>';

                    return $badge.$price;
                })
                ->html(),            Column::make(__('subscriptions.status'), 'status')
                ->sortable()
                ->format(function ($value) {
                    try {
                        $status = SubscriptionStatus::make($value);
                        $badgeClass = $status->getBadgeClass();
                        $label = $status->label;

                        return '<span class="badge '.$badgeClass.'">'.$label.'</span>';
                    } catch (Exception $e) {
                        return '<span class="badge bg-light text-dark">'.ucfirst($value).'</span>';
                    }
                })
                ->html(),

            Column::make(__('subscriptions.starts_at'), 'starts_at')
                ->sortable()
                ->format(function ($value) {
                    return '<strong>'.$value->format('d/m/Y').'</strong><br><small class="text-muted">'.$value->format('H:i').'</small>';
                })
                ->html(),

            Column::make(__('subscriptions.ends_at'), 'ends_at')
                ->sortable()
                ->format(function ($value) {
                    return '<strong>'.$value->format('d/m/Y').'</strong><br><small class="text-muted">'.$value->format('H:i').'</small>';
                })
                ->html(),

            Column::make(__('subscriptions.amount_paid'), 'amount_paid')
                ->sortable()
                ->format(function ($value, $row) {
                    $amount = (float) $value;
                    if ($amount > 0) {
                        return '<strong>'.number_format($amount).' XAF</strong><br><small class="text-muted">'.($row->payment_method ?? 'wallet').'</small>';
                    }

                    return '<span class="badge bg-success">'.__('subscriptions.free').'</span>';
                })
                ->html(),

            Column::make(__('subscriptions.usage'), 'id')
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

            Column::make(__('subscriptions.created_at'), 'created_at')
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
            SelectFilter::make(__('subscriptions.package'), 'package_id')
                ->options($this->getPackageOptions())
                ->filter(fn (Builder $builder, string $value) => $value === '' ? $builder : $builder->where('package_id', $value)),

            SelectFilter::make(__('subscriptions.status'), 'status')
                ->options($this->getStatusOptions())
                ->filter(fn (Builder $builder, string $value) => $value === '' ? $builder : $builder->where('status', $value)),

            SelectFilter::make(__('subscriptions.user'), 'user_search')
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

            DateFilter::make(__('subscriptions.created_after'), 'created_after')
                ->config(['placeholder' => __('subscriptions.min_date_placeholder'), 'locale' => app()->getLocale()])
                ->filter(fn (Builder $builder, string $value) => $builder->whereDate('created_at', '>=', $value)),

            DateFilter::make(__('subscriptions.created_before'), 'created_before')
                ->config(['placeholder' => __('subscriptions.max_date_placeholder'), 'locale' => app()->getLocale()])
                ->filter(fn (Builder $builder, string $value) => $builder->whereDate('created_at', '<=', $value)),
        ];
    }

    private function getPackageOptions(): array
    {
        $packages = Package::orderBy('sort_order')->get(['id', 'display_name']);

        $options = ['' => __('subscriptions.all_packages')];
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

        $options = ['' => __('subscriptions.all_users')];
        foreach ($users as $user) {
            $fullName = trim($user->first_name.' '.$user->last_name);
            $options[$fullName] = $fullName.' ('.$user->email.')';
        }

        return $options;
    }

    private function getStatusOptions(): array
    {
        $options = ['' => __('subscriptions.all_statuses')];
        $enumValues = SubscriptionStatus::toArray();

        foreach ($enumValues as $value => $enumKey) {
            try {
                $status = SubscriptionStatus::make($value);
                $translationKey = 'subscriptions.status_'.$value;
                $label = __($translationKey);
                $options[$value] = $label;
            } catch (Exception $e) {
                $options[$value] = ucfirst($value);
            }
        }

        $options['suspended'] = __('subscriptions.status_suspended');

        return $options;
    }
}
