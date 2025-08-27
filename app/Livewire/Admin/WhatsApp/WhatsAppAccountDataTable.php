<?php

declare(strict_types=1);

namespace App\Livewire\Admin\WhatsApp;

use App\Livewire\Customer\WhatsApp\WhatsAppAccountDataTable as CustomerAccountDataTable;
use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class WhatsAppAccountDataTable extends CustomerAccountDataTable
{
    protected ?User $targetUser = null;

    public function mount(?User $user = null): void
    {
        $this->targetUser = $user;
    }

    public function getEmptyMessage(): string
    {
        return $this->targetUser
            ? 'No WhatsApp account found for this user.'
            : 'No WhatsApp account found in the system.';
    }

    protected function getExportFileName(): string
    {
        return $this->targetUser
            ? 'admin_whatsapp_accounts_user_'.$this->targetUser->id
            : 'admin_all_whatsapp_accounts';
    }

    public function builder(): Builder
    {
        $query = WhatsAppAccount::query();

        if ($this->targetUser) {
            $query->where('user_id', $this->targetUser->id);
        }

        return $query->with($this->getRelations())
            ->orderBy('created_at', 'desc');
    }

    protected function getRelations(): array
    {
        return [
            'aiModel',
            'conversations',
            'aiUsageLogs',
            'user:id,name,email',
        ];
    }

    protected function getBasicColumns(): array
    {
        $columns = [];

        // Add user column if viewing all accounts
        if (! $this->targetUser) {
            $columns[] = Column::make('User', 'user.name')
                ->sortable()
                ->searchable()
                ->format(function ($value, $row) {
                    return '<div class="d-flex flex-column" style="line-height: 1.2;">
                        <span class="fw-bold text-primary mb-0">
                            '.$row->user->name.'
                        </span>
                        <small class="text-muted">'.$row->user->email.'</small>
                    </div>';
                })
                ->html();
        }

        // Get parent columns and merge
        $parentColumns = parent::getBasicColumns();

        // Remove last column (actions) to add stats columns before
        $actionsColumn = array_pop($parentColumns);

        // Add stats columns
        $statsColumns = $this->getStatsColumns();

        // Merge: user column + parent columns + stats columns + actions
        return array_merge($columns, $parentColumns, $statsColumns, [$actionsColumn]);
    }

    protected function getStatsColumns(): array
    {
        return [
            Column::make('AI Cost', 'id')
                ->format(function ($value, $row) {
                    $totalCost = $row->aiUsageLogs->sum('total_cost_xaf');
                    $requestCount = $row->aiUsageLogs->count();
                    $lastUsed = $row->aiUsageLogs->max('created_at');

                    return '<div class="text-center">
                        <div class="fw-bold text-danger">'.number_format($totalCost, 0).' XAF</div>
                        <small class="text-muted">'.$requestCount.' AI req.</small>
                        '.($lastUsed ? '<br><small class="text-info">Last: '.date('d/m H:i', strtotime($lastUsed)).'</small>' : '').'
                    </div>';
                })
                ->html(),

            Column::make('Tokens & Performance', 'id')
                ->format(function ($value, $row) {
                    $totalTokens = $row->aiUsageLogs->sum('total_tokens');
                    $avgResponseTime = $row->aiUsageLogs->avg('response_time_ms');
                    $uniqueConversations = $row->aiUsageLogs->whereNotNull('whatsapp_conversation_id')->pluck('whatsapp_conversation_id')->unique()->count();

                    return '<div class="text-center">
                        <div><span class="badge badge-info">'.number_format($totalTokens).' tokens</span></div>
                        '.($avgResponseTime ? '<small class="text-muted">~'.round($avgResponseTime).'ms</small><br>' : '').'
                        <small class="text-success">'.$uniqueConversations.' AI conv.</small>
                    </div>';
                })
                ->html(),

            Column::make('Activity', 'id')
                ->format(function ($value, $row) {
                    $last24h = $row->aiUsageLogs->where('created_at', '>', now()->subDay())->count();
                    $last7days = $row->aiUsageLogs->where('created_at', '>', now()->subWeek())->count();
                    $last30days = $row->aiUsageLogs->where('created_at', '>', now()->subMonth())->count();

                    return '<div class="text-center" style="font-size: 0.8rem;">
                        <div class="text-success">24h: '.$last24h.'</div>
                        <div class="text-warning">7d: '.$last7days.'</div>
                        <div class="text-info">30d: '.$last30days.'</div>
                    </div>';
                })
                ->html(),
        ];
    }

    protected function getActionsColumn(): Column
    {
        return Column::make('Actions')
            ->label(fn (WhatsAppAccount $row) => view('partials.admin.whatsapp.account-actions', [
                'account' => $row,
                'user' => $this->targetUser,
            ])
            )
            ->html();
    }

    protected function getBasicFilters(): array
    {
        $filters = parent::getBasicFilters();

        // Add user filter if viewing all accounts
        if (! $this->targetUser) {
            array_unshift($filters,
                SelectFilter::make('User', 'user_id')
                    ->options([
                        '' => 'All users',
                        ...\App\Models\User::whereHas('whatsappAccounts')
                            ->pluck('name', 'id')
                            ->toArray(),
                    ])
                    ->filter(fn (Builder $builder, string $value) => $value === '' ? $builder : $builder->where('user_id', $value)
                    )
            );
        }

        return array_merge($filters, $this->getStatsFilters());
    }

    protected function getStatsFilters(): array
    {
        return [
            SelectFilter::make('AI Activity', 'has_usage')
                ->options([
                    '' => 'All accounts',
                    '1' => 'With AI activity',
                    '0' => 'Without AI activity',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === '1') {
                        return $builder->has('aiUsageLogs');
                    } elseif ($value === '0') {
                        return $builder->doesntHave('aiUsageLogs');
                    }

                    return $builder;
                }),

            SelectFilter::make('Cost Range', 'cost_range')
                ->options([
                    '' => 'All costs',
                    'low' => 'Low (< 1000 XAF)',
                    'medium' => 'Medium (1000-5000 XAF)',
                    'high' => 'High (> 5000 XAF)',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === 'low') {
                        return $builder->whereHas('aiUsageLogs', function ($q) {
                            $q->havingRaw('SUM(total_cost_xaf) < 1000');
                        });
                    } elseif ($value === 'medium') {
                        return $builder->whereHas('aiUsageLogs', function ($q) {
                            $q->havingRaw('SUM(total_cost_xaf) BETWEEN 1000 AND 5000');
                        });
                    } elseif ($value === 'high') {
                        return $builder->whereHas('aiUsageLogs', function ($q) {
                            $q->havingRaw('SUM(total_cost_xaf) > 5000');
                        });
                    }

                    return $builder;
                }),
        ];
    }
}
