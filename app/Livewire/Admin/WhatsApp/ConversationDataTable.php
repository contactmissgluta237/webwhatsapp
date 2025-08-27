<?php

declare(strict_types=1);

namespace App\Livewire\Admin\WhatsApp;

use App\Livewire\Customer\WhatsApp\ConversationDataTable as CustomerConversationDataTable;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class ConversationDataTable extends CustomerConversationDataTable
{
    protected ?WhatsAppAccount $targetAccount = null;
    protected ?User $targetUser = null;

    public function mount(?WhatsAppAccount $account = null, ?User $user = null): void
    {
        $this->targetAccount = $account;
        $this->targetUser = $user;
    }

    public function configure(): void
    {
        $emptyMessage = $this->targetAccount
            ? 'No conversation found for this WhatsApp account.'
            : ($this->targetUser
                ? 'No conversation found for this user.'
                : 'No conversation found in the system.');

        $this->setPrimaryKey('id')
            ->setTableWrapperAttributes([
                'class' => 'table-responsive',
            ])
            ->setEmptyMessage($emptyMessage);
    }

    protected function getExportFileName(): string
    {
        if ($this->targetAccount) {
            return 'admin_conversations_account_'.$this->targetAccount->id;
        }

        return $this->targetUser
            ? 'admin_conversations_user_'.$this->targetUser->id
            : 'admin_all_conversations';
    }

    public function builder(): Builder
    {
        $query = WhatsAppConversation::query();

        if ($this->targetAccount) {
            $query->where('whatsapp_account_id', $this->targetAccount->id);
        } elseif ($this->targetUser) {
            $query->whereHas('whatsappAccount', function ($q) {
                $q->where('user_id', $this->targetUser->id);
            });
        }

        return $query->with($this->getRelations())
            ->withCount('messages')
            ->orderBy('last_message_at', 'desc');
    }

    protected function getRelations(): array
    {
        $relations = [
            'messages' => fn ($q) => $q->latest()->limit(1),
            'aiUsageLogs',
            'whatsappAccount:id,session_name,user_id',
        ];

        if (! $this->targetAccount) {
            $relations[] = 'whatsappAccount.user:id,name,email';
        }

        return $relations;
    }

    protected function getBasicColumns(): array
    {
        $columns = [];

        // Add user column if not viewing specific user/account
        if (! $this->targetUser && ! $this->targetAccount) {
            $columns[] = Column::make('User', 'whatsapp_account.user.name')
                ->sortable()
                ->searchable()
                ->format(function (string $value, WhatsAppConversation $row): string {
                    return '<div class="d-flex flex-column" style="line-height: 1.2;">
                        <span class="fw-bold text-primary mb-0">
                            '.$row->whatsappAccount->user->full_name.'
                        </span>
                        <small class="text-muted">'.$row->whatsappAccount->user->email.'</small>
                    </div>';
                })
                ->html();
        }

        // Get parent columns
        $parentColumns = parent::getBasicColumns();

        // Add account info if not viewing specific account
        if (! $this->targetAccount) {
            $accountColumn = Column::make('Account', 'whatsapp_account.session_name')
                ->sortable()
                ->searchable()
                ->format(function ($value, $row) {
                    return '<span class="badge badge-info">'.$value.'</span>';
                })
                ->html();

            // Insert account column after phone column
            array_splice($parentColumns, 3, 0, [$accountColumn]);
        }

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

                    if ($requestCount === 0) {
                        return '<span class="text-muted">No AI</span>';
                    }

                    return '<div class="text-center">
                        <div class="fw-bold text-danger">'.number_format($totalCost, 0).' XAF</div>
                        <small class="text-muted">'.$requestCount.' req.</small>
                        '.($lastUsed ? '<br><small class="text-info">'.date('d/m H:i', strtotime($lastUsed)).'</small>' : '').'
                    </div>';
                })
                ->html(),

            Column::make('Tokens & Performance', 'id')
                ->format(function ($value, $row) {
                    $totalTokens = $row->aiUsageLogs->sum('total_tokens');
                    $avgResponseTime = $row->aiUsageLogs->avg('response_time_ms');
                    $messagesWithAI = $row->aiUsageLogs->whereNotNull('whatsapp_message_id')->count();

                    if ($totalTokens === 0) {
                        return '<span class="text-muted">-</span>';
                    }

                    return '<div class="text-center">
                        <div><span class="badge badge-info">'.number_format($totalTokens).'</span></div>
                        '.($avgResponseTime ? '<small class="text-muted">~'.round($avgResponseTime).'ms</small><br>' : '').'
                        <small class="text-success">'.$messagesWithAI.' AI msg</small>
                    </div>';
                })
                ->html(),

            Column::make('Recent Activity', 'id')
                ->format(function ($value, $row) {
                    $last24h = $row->aiUsageLogs->where('created_at', '>', now()->subDay())->count();
                    $last7days = $row->aiUsageLogs->where('created_at', '>', now()->subWeek())->count();

                    if ($last24h === 0 && $last7days === 0) {
                        return '<span class="text-muted">Inactive</span>';
                    }

                    return '<div class="text-center" style="font-size: 0.8rem;">
                        '.($last24h > 0 ? '<div class="text-success">24h: '.$last24h.'</div>' : '').'
                        '.($last7days > 0 ? '<div class="text-warning">7d: '.$last7days.'</div>' : '').'
                    </div>';
                })
                ->html(),
        ];
    }

    protected function getActionsColumn(): Column
    {
        return Column::make('Actions')
            ->label(fn (WhatsAppConversation $row) => view('partials.admin.whatsapp.conversation-actions', [
                'conversation' => $row,
                'user' => $this->targetUser,
            ])
            )
            ->html();
    }

    protected function getBasicFilters(): array
    {
        $filters = parent::getBasicFilters();

        // Add user filter if not viewing specific user/account
        if (! $this->targetUser && ! $this->targetAccount) {
            array_unshift($filters,
                SelectFilter::make('User', 'user_id')
                    ->options([
                        '' => 'All users',
                        ...\App\Models\User::whereHas('whatsappAccounts.conversations')
                            ->pluck('name', 'id')
                            ->toArray(),
                    ])
                    ->filter(function (Builder $builder, string $value) {
                        if ($value === '') {
                            return $builder;
                        }

                        return $builder->whereHas('whatsappAccount', function ($q) use ($value) {
                            $q->where('user_id', $value);
                        });
                    })
            );
        }

        // Add account filter if not viewing specific account
        if (! $this->targetAccount) {
            $filters[] = SelectFilter::make('WhatsApp Account', 'whatsapp_account_id')
                ->options([
                    '' => 'All accounts',
                    ...WhatsAppAccount::with('user:id,name')
                        ->get()
                        ->pluck('session_name_with_user', 'id')
                        ->toArray(),
                ])
                ->filter(fn (Builder $builder, string $value) => $value === '' ? $builder : $builder->where('whatsapp_account_id', $value)
                );
        }

        return array_merge($filters, $this->getStatsFilters());
    }

    protected function getStatsFilters(): array
    {
        return [
            SelectFilter::make('AI Activity', 'has_ai_usage')
                ->options([
                    '' => 'All conversations',
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
                    'low' => 'Low (< 500 XAF)',
                    'medium' => 'Medium (500-2000 XAF)',
                    'high' => 'High (> 2000 XAF)',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === 'low') {
                        return $builder->whereHas('aiUsageLogs', function ($q) {
                            $q->selectRaw('whatsapp_conversation_id, SUM(total_cost_xaf) as total_cost')
                                ->groupBy('whatsapp_conversation_id')
                                ->havingRaw('SUM(total_cost_xaf) < 500');
                        });
                    } elseif ($value === 'medium') {
                        return $builder->whereHas('aiUsageLogs', function ($q) {
                            $q->selectRaw('whatsapp_conversation_id, SUM(total_cost_xaf) as total_cost')
                                ->groupBy('whatsapp_conversation_id')
                                ->havingRaw('SUM(total_cost_xaf) BETWEEN 500 AND 2000');
                        });
                    } elseif ($value === 'high') {
                        return $builder->whereHas('aiUsageLogs', function ($q) {
                            $q->selectRaw('whatsapp_conversation_id, SUM(total_cost_xaf) as total_cost')
                                ->groupBy('whatsapp_conversation_id')
                                ->havingRaw('SUM(total_cost_xaf) > 2000');
                        });
                    }

                    return $builder;
                }),

            SelectFilter::make('Activity Period', 'activity_period')
                ->options([
                    '' => 'All periods',
                    'today' => 'Today',
                    'week' => 'This week',
                    'month' => 'This month',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $date = match ($value) {
                        'today' => now()->startOfDay(),
                        'week' => now()->startOfWeek(),
                        'month' => now()->startOfMonth(),
                        default => null,
                    };

                    if ($date) {
                        return $builder->whereHas('aiUsageLogs', function ($q) use ($date) {
                            $q->where('created_at', '>=', $date);
                        });
                    }

                    return $builder;
                }),
        ];
    }
}
