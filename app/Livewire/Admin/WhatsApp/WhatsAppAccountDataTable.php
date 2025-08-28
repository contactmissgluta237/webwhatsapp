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

    public function mount(): void
    {
        // No specific user filtering - show all accounts with user filter available
    }

    public function getEmptyMessage(): string
    {
        return 'No WhatsApp account found in the system.';
    }

    protected function getExportFileName(): string
    {
        return 'admin_all_whatsapp_accounts';
    }

    public function builder(): Builder
    {
        // Override parent builder() that filters by Auth::id() - we want ALL accounts for admin
        return WhatsAppAccount::query()
            ->with($this->getRelations())
            ->orderBy('created_at', 'desc');
    }

    protected function getRelations(): array
    {
        return [
            'aiModel',
            'conversations',
            'aiUsageLogs',
            'user:id,first_name,last_name,email',
        ];
    }

    protected function getBasicColumns(): array
    {
        $columns = [];

        // Always add user column since we're viewing all accounts
        $columns[] = Column::make(__('User'), 'user_id')
            ->format(function ($value, $row) {
                $user = \App\Models\User::find($row->user_id);

                if (! $user) {
                    return '<div class="d-flex flex-column" style="line-height: 1.2;">
                        <span class="fw-bold text-danger mb-0">
                            [Utilisateur supprimé]
                        </span>
                        <small class="text-muted">ID: '.$row->user_id.'</small>
                    </div>';
                }

                $fullName = $user->first_name.' '.$user->last_name;

                $userUrl = route('admin.customers.show', $user);

                return '<div class="d-flex flex-column" style="line-height: 1.2;">
                    <a href="'.$userUrl.'" class="fw-bold text-primary mb-0 text-decoration-none">
                        '.$fullName.'
                    </a>
                    <small class="text-muted">'.$user->email.'</small>
                </div>';
            })
            ->html();

        // Add core columns with translations
        $columns = array_merge($columns, [
            Column::make('ID', 'id')->sortable()->deselected(),

            Column::make(__('Session Name'), 'session_name')
                ->sortable()
                ->searchable()
                ->format(function ($value, $row) {
                    return '<div class="d-flex flex-column" style="line-height: 1.2;">
                        <span class="text-whatsapp fw-bold mb-0">
                            '.$value.'
                        </span>
                        <small class="text-muted">'.str($row->session_id)->limit(20).'</small>
                    </div>';
                })
                ->html(),

            Column::make(__('Phone'), 'phone_number')
                ->sortable()
                ->searchable()
                ->format(function ($value) {
                    return $value
                        ? '<span class="badge badge-whatsapp">'.$value.'</span>'
                        : '<span class="badge badge-secondary">'.__('Not connected').'</span>';
                })
                ->html(),

            Column::make(__('Status'), 'status')
                ->sortable()
                ->html()
                ->format(function ($value, $row) {
                    if ($row->isConnected()) {
                        return '<span class="badge badge-success"><i class="la la-check"></i> '.__('Connected').'</span>';
                    } elseif ($row->isConnecting()) {
                        return '<span class="badge badge-warning"><i class="la la-sync-alt"></i> '.__('Connecting...').'</span>';
                    } else {
                        return '<span class="badge badge-secondary"><i class="la la-times"></i> '.__('Disconnected').'</span>';
                    }
                }),

            Column::make(__('AI Agent'), 'agent_enabled')
                ->sortable()
                ->html()
                ->format(function ($value, $row) {
                    $freshAccount = \App\Models\WhatsAppAccount::find($row->id);

                    if ($freshAccount->agent_enabled && $freshAccount->ai_model_id) {
                        return '<span class="badge badge-success"><i class="la la-robot"></i> '.__('Active').'</span>';
                    }

                    return '<span class="badge badge-secondary"><i class="la la-robot"></i> '.__('Inactive').'</span>';
                }),

            Column::make(__('Conversations'), 'id')
                ->html()
                ->format(function ($value, $row) {
                    $count = $row->conversations->count();
                    if ($count === 0) {
                        return '<span class="text-muted">Aucune</span>';
                    }

                    return '<span class="badge badge-info">'.$count.' conversation'.($count > 1 ? 's' : '').'</span>';
                }),

            Column::make(__('Created At'), 'created_at')
                ->sortable()
                ->format(function ($value) {
                    return $value->locale(app()->getLocale())->isoFormat('D MMMM YYYY HH:mm:ss');
                }),
        ]);

        // Add stats columns
        $statsColumns = $this->getStatsColumns();
        $columns = array_merge($columns, $statsColumns);

        // Add actions column
        $columns[] = $this->getActionsColumn();

        return $columns;
    }

    protected function getStatsColumns(): array
    {
        return [
            Column::make(__('AI Cost'), 'id')
                ->format(function ($value, $row) {
                    $totalCostXAF = $row->aiUsageLogs->sum('total_cost_xaf');
                    $totalCostUSD = $row->aiUsageLogs->sum('total_cost_usd');
                    $requestCount = $row->aiUsageLogs->count();
                    $lastUsed = $row->aiUsageLogs->max('created_at');

                    return '<div class="text-center">
                        <div class="fw-bold text-danger">'.$totalCostXAF.' XAF</div>
                        <div class="fw-bold text-success">$'.$totalCostUSD.'</div>
                        <small class="text-muted">'.$requestCount.' AI req.</small>
                        '.($lastUsed ? '<br><small class="text-info">Dernier: '.$lastUsed->format('d/m H:i').'</small>' : '').'
                    </div>';
                })
                ->html(),

            Column::make(__('Tokens & Performance'), 'id')
                ->format(function ($value, $row) {
                    $totalTokens = $row->aiUsageLogs->sum('total_tokens');
                    $avgResponseTime = $row->aiUsageLogs->avg('response_time_ms');
                    $uniqueConversations = $row->aiUsageLogs->whereNotNull('whatsapp_conversation_id')->pluck('whatsapp_conversation_id')->unique()->count();

                    return '<div class="text-center">
                        <div><span class="badge badge-info">'.number_format($totalTokens).' tokens</span></div>
                        '.($avgResponseTime ? '<small class="text-muted">~'.round($avgResponseTime).'ms</small><br>' : '').'
                        <small class="text-success">'.$uniqueConversations.' conv. IA</small>
                    </div>';
                })
                ->html(),

            Column::make(__('Activity'), 'id')
                ->format(function ($value, $row) {
                    $last24h = $row->aiUsageLogs->where('created_at', '>', now()->subDay())->count();
                    $last7days = $row->aiUsageLogs->where('created_at', '>', now()->subWeek())->count();
                    $last30days = $row->aiUsageLogs->where('created_at', '>', now()->subMonth())->count();

                    return '<div class="text-center" style="font-size: 0.8rem;">
                        <div class="text-success">24h: '.$last24h.'</div>
                        <div class="text-warning">7j: '.$last7days.'</div>
                        <div class="text-info">30j: '.$last30days.'</div>
                    </div>';
                })
                ->html(),
        ];
    }

    protected function getActionsColumn(): Column
    {
        return Column::make(__('Actions'))
            ->label(fn (WhatsAppAccount $row) => view('partials.admin.whatsapp.account-actions', [
                'account' => $row,
            ])
            )
            ->html();
    }

    protected function getBasicFilters(): array
    {
        $filters = parent::getBasicFilters();

        // Always add user/customer filter
        array_unshift($filters,
            SelectFilter::make('Customer', 'user_id')
                ->options([
                    '' => 'Tous les utilisateurs',
                    'orphaned' => 'Comptes orphelins (utilisateur supprimé)',
                    ...\App\Models\User::whereHas('whatsappAccounts')
                        ->selectRaw("id, CONCAT(first_name, ' ', last_name) as display_name")
                        ->pluck('display_name', 'id')
                        ->toArray(),
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === '') {
                        return $builder;
                    } elseif ($value === 'orphaned') {
                        return $builder->whereDoesntHave('user');
                    } else {
                        return $builder->where('user_id', $value);
                    }
                })
        );

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
                        return $builder->whereIn('id', function ($subquery) {
                            $subquery->select('whatsapp_account_id')
                                ->from('ai_usage_logs')
                                ->groupBy('whatsapp_account_id')
                                ->havingRaw('SUM(total_cost_xaf) < 1000');
                        });
                    } elseif ($value === 'medium') {
                        return $builder->whereIn('id', function ($subquery) {
                            $subquery->select('whatsapp_account_id')
                                ->from('ai_usage_logs')
                                ->groupBy('whatsapp_account_id')
                                ->havingRaw('SUM(total_cost_xaf) BETWEEN 1000 AND 5000');
                        });
                    } elseif ($value === 'high') {
                        return $builder->whereIn('id', function ($subquery) {
                            $subquery->select('whatsapp_account_id')
                                ->from('ai_usage_logs')
                                ->groupBy('whatsapp_account_id')
                                ->havingRaw('SUM(total_cost_xaf) > 5000');
                        });
                    }

                    return $builder;
                }),
        ];
    }
}
