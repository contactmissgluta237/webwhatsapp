<?php

declare(strict_types=1);

namespace App\Events\Customer;

use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PackageSubscriptionCreatedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly UserSubscription $subscription,
        public readonly User $user,
        public readonly Package $package,
        public readonly float $amountPaid,
        public readonly ?string $couponCode = null,
        public readonly ?float $couponDiscount = null
    ) {
        //
    }
}
