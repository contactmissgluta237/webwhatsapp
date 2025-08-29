<?php

declare(strict_types=1);

namespace App\Listeners\Customer;

use App\Events\Customer\PackageSubscriptionCreatedEvent;
use App\Services\ReferralService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ReferralDistributionListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly ReferralService $referralService
    ) {}

    public function handle(PackageSubscriptionCreatedEvent $event): void
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
