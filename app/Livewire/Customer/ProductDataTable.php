<?php

declare(strict_types=1);

namespace App\Livewire\Customer;

use App\Models\UserProduct;
use App\Models\WhatsAppAccount;
use App\Services\CurrencyService;
use HarroldWafo\LaravelCustomDatatable\DataTables\BaseDataTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\NumberRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

final class ProductDataTable extends BaseDataTable
{
    protected $model = UserProduct::class;
    protected const DEFAULT_SORT_FIELD = 'created_at';
    protected const DEFAULT_SORT_DIRECTION = 'desc';

    protected CurrencyService $currencyService;

    public function boot(): void
    {
        $this->currencyService = app(CurrencyService::class);
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setTableWrapperAttributes([
                'class' => 'table-responsive',
            ])
            ->setEmptyMessage(view('partials.customer.products.empty-message')->render());
    }

    protected function getExportFileName(): string
    {
        return 'mes_produits';
    }

    public function builder(): Builder
    {
        return UserProduct::query()
            ->where('user_id', Auth::id())
            ->with(['whatsappAccounts'])
            ->orderBy('created_at', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable()->deselected(),

            Column::make('Titre', 'title')
                ->sortable()
                ->searchable()
                ->format(function ($value, $row) {
                    $editUrl = route('customer.products.edit', $row->id);

                    return '<div>
                        <a href="'.$editUrl.'" class="text-decoration-none">
                            <span class="text-whatsapp fw-bold">
                                '.$value.'
                            </span>
                        </a>
                        <br>
                        <small class="text-muted mt-1 d-block">'.str($row->description)->limit(50).'</small>
                    </div>';
                })
                ->html(),

            Column::make('Prix', 'price')
                ->sortable()
                ->format(fn ($value, $row) => $this->currencyService->formatPrice((float) $value, $this->currencyService->getUserCurrency(auth()->user()))),

            Column::make('Statut', 'is_active')
                ->sortable()
                ->html()
                ->format(function ($value) {
                    return $value
                        ? '<span class="badge badge-success">Actif</span>'
                        : '<span class="badge badge-secondary">Inactif</span>';
                }),

            Column::make('Comptes WhatsApp', 'id')
                ->html()
                ->format(function ($value, $row) {
                    $count = $row->whatsappAccounts->count();
                    if ($count === 0) {
                        return '<span class="text-muted">Aucun</span>';
                    }

                    return '<span class="badge badge-info">'.$count.' compte'.($count > 1 ? 's' : '').'</span>';
                }),

            Column::make('Date de création', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('j F Y H:i:s')),

            Column::make('Actions')
                ->label(fn (UserProduct $row) => view('partials.customer.products.actions', ['product' => $row]))
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

            NumberRangeFilter::make('Prix (XAF)', 'price')
                ->config(['placeholder' => ['Prix minimum', 'Prix maximum']])
                ->filter(function (Builder $builder, array $values) {
                    if ($values['min'] !== null) {
                        $builder->where('price', '>=', $values['min']);
                    }
                    if ($values['max'] !== null) {
                        $builder->where('price', '<=', $values['max']);
                    }

                    return $builder;
                }),

            SelectFilter::make('Compte WhatsApp', 'whatsapp_account')
                ->options($this->getWhatsAppAccountOptions())
                ->filter(function (Builder $builder, string $value) {
                    if ($value === '') {
                        return $builder;
                    }

                    return $builder->whereHas('whatsappAccounts', function ($query) use ($value) {
                        $query->where('whatsapp_accounts.id', $value);
                    });
                }),

            DateFilter::make('Créé après', 'created_at')
                ->config(['placeholder' => 'Date de création minimum', 'locale' => 'fr'])
                ->filter(fn (Builder $builder, string $value) => $builder->whereDate('created_at', '>=', $value)),
        ];
    }

    private function getWhatsAppAccountOptions(): array
    {
        $accounts = WhatsAppAccount::where('user_id', Auth::id())
            ->orderBy('session_name')
            ->get(['id', 'session_name']);

        $options = ['' => 'Tous les comptes'];

        foreach ($accounts as $account) {
            $options[$account->id] = $account->session_name;
        }

        return $options;
    }
}
