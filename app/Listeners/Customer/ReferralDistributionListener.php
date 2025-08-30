<?php

declare(strict_types=1);

namespace App\Listeners\Customer;

use App\Events\Customer\PackageSubscriptionCreatedEvent;
use App\Listeners\BaseListener;
use App\Services\ReferralService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener responsible for distributing referral earnings when a subscription is created
 *
 * This listener handles the distribution of referral commissions to referrers
 * and records system revenue when a user subscribes to a package.
 */
class ReferralDistributionListener extends BaseListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly ReferralService $referralService
    ) {}

    /**
     * Get unique identifiers for the event to prevent duplicate processing
     *
     * @param  PackageSubscriptionCreatedEvent  $event
     * @return array<string, mixed>
     */
    protected function getEventIdentifiers($event): array
    {
        return [
            'subscription_id' => $event->subscription->id,
            'user_id' => $event->user->id,
            'package_id' => $event->package->id,
            'amount_paid' => $event->amountPaid,
            'event_type' => 'referral_distribution',
        ];
    }

    /**
     * Handle the package subscription created event
     *
     * @param  PackageSubscriptionCreatedEvent  $event
     *
     * @throws \Exception
     */
    protected function handleEvent($event): void
    {
        try {

            // Distribute referral earnings and system revenue
            $this->referralService->distributeReferralEarnings(
                $event->subscription,
                $event->amountPaid
            );

            Log::info('Referral earnings distributed successfully', [
                'subscription_id' => $event->subscription->id,
                'user_id' => $event->user->id,
                'package_id' => $event->package->id,
                'amount_paid' => $event->amountPaid,
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to distribute referral earnings', [
                'subscription_id' => $event->subscription->id,
                'user_id' => $event->user->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            // Re-throw to trigger job retry
            throw $exception;
        }
    }
}
