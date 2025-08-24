<?php

namespace App\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        \App\Repositories\Contracts\TokenRepositoryInterface::class => \App\Repositories\Eloquent\TokenRepositoryEloquent::class,
        \App\Repositories\WhatsAppAccountRepositoryInterface::class => \App\Repositories\EloquentWhatsAppAccountRepository::class,
        \App\Repositories\WhatsAppMessageRepositoryInterface::class => \App\Repositories\EloquentWhatsAppMessageRepository::class,
    ];

    /**
     * @return array<class-string>
     */
    public function provides(): array
    {
        return array_keys($this->bindings);
    }
}
