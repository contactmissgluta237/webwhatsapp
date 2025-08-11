<?php

namespace App\Providers;

use App\Events\AdminWithdrawalCreatedEvent;
use App\Events\CustomerCreatedEvent;
use App\Events\CustomerRechargeCreatedEvent;
use App\Events\ExternalTransactionApprovedEvent;
use App\Events\ExternalTransactionWebhookProcessedEvent;
use App\Events\RechargeCompletedByAdminEvent;
use App\Events\SystemAccountTransactionCreatedEvent;
use App\Events\TicketCreatedEvent;
use App\Events\TicketMessageSentEvent;
use App\Events\TicketStatusChangedEvent;
use App\Events\UserUpdatedEvent;
use App\Events\WithdrawalRequestedEvent;
use App\Listeners\AdminWithdrawalNotificationListener;
use App\Listeners\HandleExternalTransactionWebhookListener;
use App\Listeners\LogUserUpdatedListener;
use App\Listeners\NotifyAdminOfCustomerMessageListener;
use App\Listeners\NotifyAdminOfNewTicketListener;
use App\Listeners\NotifyAdminOfNewTicketSyncListener;
use App\Listeners\NotifyAdminsOfSystemAccountTransactionListener;
use App\Listeners\NotifyAdminsOfWithdrawalRequestListener;
use App\Listeners\NotifyCustomerOfStatusChangeListener;
use App\Listeners\NotifyCustomerOfTicketReplyListener;
use App\Listeners\NotifyReferrerListener;
use App\Listeners\SendApprovalNotificationListener;
use App\Listeners\SendRechargeNotificationToCustomerListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        UserUpdatedEvent::class => [
            LogUserUpdatedListener::class,
        ],
        CustomerCreatedEvent::class => [
            NotifyReferrerListener::class,
        ],
        AdminWithdrawalCreatedEvent::class => [
            AdminWithdrawalNotificationListener::class,
        ],
        CustomerRechargeCreatedEvent::class => [
        ],
        ExternalTransactionApprovedEvent::class => [
            SendApprovalNotificationListener::class,
        ],
        WithdrawalRequestedEvent::class => [
            NotifyAdminsOfWithdrawalRequestListener::class,
        ],
        RechargeCompletedByAdminEvent::class => [
            SendRechargeNotificationToCustomerListener::class,
        ],
        SystemAccountTransactionCreatedEvent::class => [
            NotifyAdminsOfSystemAccountTransactionListener::class,
        ],
        ExternalTransactionWebhookProcessedEvent::class => [
            HandleExternalTransactionWebhookListener::class,
        ],
        TicketCreatedEvent::class => [
            NotifyAdminOfNewTicketListener::class,
            NotifyAdminOfNewTicketSyncListener::class,
        ],
        TicketMessageSentEvent::class => [
            NotifyCustomerOfTicketReplyListener::class,
            NotifyAdminOfCustomerMessageListener::class,
        ],
        TicketStatusChangedEvent::class => [
            NotifyCustomerOfStatusChangeListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void {}
}
