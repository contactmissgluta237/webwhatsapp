<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;

interface PackageSubscriptionServiceInterface
{
    /**
     * Subscribe a user directly to a package
     *
     * @throws \Exception
     */
    public function subscribeDirectly(User $user, Package $package, ?string $couponCode = null): UserSubscription;
}
