<?php

declare(strict_types=1);

namespace App\Services\Customer;

use App\Constants\ApplicationLimits;
use App\Enums\PackageType;
use App\Enums\SubscriptionStatus;
use App\Events\Customer\PackageSubscriptionCreatedEvent;
use App\Http\Requests\Customer\Packages\SubscribePackageRequest;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\CouponService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PackageSubscriptionService
{
    public function __construct(
        private readonly CouponService $couponService
    ) {}

    /**
     * Direct subscription method for Livewire components
     *
     * @throws \Exception
     */
    public function subscribeDirectly(User $user, Package $package, ?string $couponCode = null): UserSubscription
    {
        $this->validateSubscriptionPreconditions($user, $package);

        $pricing = $this->calculatePricing($package, $couponCode, $user);

        return DB::transaction(function () use ($user, $package, $pricing, $couponCode) {
            // Handle existing active subscription
            $remainingLimits = $this->handleExistingActiveSubscription($user);

            $subscription = $this->createSubscription($user, $package, $pricing, $remainingLimits, $couponCode);

            if (! $package->isTrial()) {
                $this->processPayment($user, $package, $pricing, $subscription, $couponCode);
            }

            $this->applyCouponIfPresent($pricing['coupon_data'], $couponCode, $user, $subscription, $package);

            PackageSubscriptionCreatedEvent::dispatch(
                $subscription,
                $user,
                $package,
                $pricing['final_price'],
                $couponCode,
                $pricing['discount_amount']
            );

            Log::info('Package subscription created successfully', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'subscription_id' => $subscription->id,
                'amount_paid' => $pricing['final_price'],
                'coupon_code' => $couponCode,
            ]);

            return $subscription;
        });
    }

    /**
     * @throws \Exception
     */
    public function subscribe(SubscribePackageRequest $request): UserSubscription
    {
        $user = $request->user();
        if (! $user) {
            throw new \Exception('User not authenticated');
        }

        return $this->subscribeDirectly(
            $user,
            $request->getPackage(),
            $request->getCouponCode()
        );
    }

    /**
     * @throws \Exception
     */
    private function validateSubscriptionPreconditions(User $user, Package $package): void
    {
        if (! $package->is_active) {
            throw new \Exception(__('packages.errors.package_not_available'));
        }

        if ($package->isTrial() && $this->hasUsedTrial($user)) {
            throw new \Exception(__('packages.errors.trial_already_used'));
        }
    }

    private function hasUsedTrial(User $user): bool
    {
        return $user->subscriptions()
            ->whereHas('package', fn ($q) => $q->where('name', PackageType::TRIAL()))
            ->exists();
    }

    /**
     * @return array{original_price: float, final_price: float, discount_amount: float, coupon_data: ?array<string, mixed>}
     *
     * @throws \Exception
     */
    private function calculatePricing(Package $package, ?string $couponCode, User $user): array
    {
        $originalPrice = $package->getCurrentPrice();
        $finalPrice = $originalPrice;
        $discountAmount = 0.0;
        $couponData = null;

        if ($couponCode) {
            $couponValidation = $this->couponService->validateCoupon($couponCode, $user, $originalPrice);

            if (! $couponValidation['valid']) {
                throw new \Exception($couponValidation['message']);
            }

            $finalPrice = $couponValidation['final_price'];
            $discountAmount = $couponValidation['discount_amount'];
            $couponData = $couponValidation;
        }

        return [
            'original_price' => $originalPrice,
            'final_price' => $finalPrice,
            'discount_amount' => $discountAmount,
            'coupon_data' => $couponData,
        ];
    }

    /**
     * @throws \Exception
     */
    private function validateWalletBalance(User $user, float $amount): void
    {
        $wallet = $user->wallet;

        if (! $wallet || $wallet->balance < $amount) {
            $missingAmount = $amount - ($wallet->balance ?? 0);
            throw new \Exception(__('packages.errors.insufficient_balance', [
                'missing_amount' => number_format($missingAmount),
                'currency' => 'XAF',
            ]));
        }
    }

    /**
     * Handle existing active subscription by transferring remaining limits
     *
     * @return array{messages_limit: int, context_limit: int, accounts_limit: int, products_limit: int}
     */
    private function handleExistingActiveSubscription(User $user): array
    {
        /** @var UserSubscription|null $activeSubscription */
        $activeSubscription = $user->subscriptions()
            ->where('status', SubscriptionStatus::ACTIVE())
            ->where('ends_at', '>', now())
            ->first();

        $remainingLimits = [
            'messages_limit' => 0,
            'context_limit' => 0,
            'accounts_limit' => 0,
            'products_limit' => 0,
        ];

        if ($activeSubscription) {
            // Calculate remaining limits from active subscription
            // Note: Using full limits for now as *_used fields don't exist yet in the schema
            $remainingLimits = [
                'messages_limit' => (int) max(0, $activeSubscription->messages_limit),
                'context_limit' => (int) max(0, $activeSubscription->context_limit),
                'accounts_limit' => (int) max(0, $activeSubscription->accounts_limit),
                'products_limit' => (int) max(0, $activeSubscription->products_limit),
            ];

            // Mark old subscription as expired
            $activeSubscription->update([
                'status' => SubscriptionStatus::EXPIRED(),
                'ends_at' => now(),
            ]);

            Log::info('Transferred remaining limits from active subscription', [
                'user_id' => $user->id,
                'old_subscription_id' => $activeSubscription->id,
                'remaining_limits' => $remainingLimits,
            ]);
        }

        return $remainingLimits;
    }

    /**
     * @param  array{original_price: float, final_price: float, discount_amount: float, coupon_data: ?array<string, mixed>}  $pricing
     * @param  array{messages_limit: int, context_limit: int, accounts_limit: int, products_limit: int}  $remainingLimits
     */
    private function createSubscription(User $user, Package $package, array $pricing, array $remainingLimits = ['messages_limit' => 0, 'context_limit' => 0, 'accounts_limit' => 0, 'products_limit' => 0], ?string $couponCode = null): UserSubscription
    {
        // Add remaining limits from old subscription to new subscription limits
        $newMessagesLimit = $package->messages_limit + $remainingLimits['messages_limit'];
        $newContextLimit = $package->context_limit + $remainingLimits['context_limit'];
        $newAccountsLimit = $package->accounts_limit + $remainingLimits['accounts_limit'];
        $newProductsLimit = $package->products_limit + $remainingLimits['products_limit'];

        // Get coupon information for tracking
        $couponId = null;
        if ($couponCode && $pricing['coupon_data']) {
            $coupon = \App\Models\Coupon::findByCode($couponCode);
            $couponId = $coupon?->id;
        }

        return UserSubscription::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'starts_at' => now(),
            'ends_at' => now()->addDays($package->duration_days ?? ApplicationLimits::DEFAULT_PACKAGE_DURATION_DAYS),
            'status' => SubscriptionStatus::ACTIVE(),
            'messages_limit' => $newMessagesLimit,
            'context_limit' => $newContextLimit,
            'accounts_limit' => $newAccountsLimit,
            'products_limit' => $newProductsLimit,
            'amount_paid' => $pricing['final_price'],
            'original_price' => $pricing['original_price'],
            'promotional_price' => $package->hasActivePromotion() ? $package->promotional_price : null,
            'coupon_code' => $couponCode,
            'coupon_discount' => $pricing['discount_amount'],
            'coupon_id' => $couponId,
            'payment_method' => 'wallet',
            'activated_at' => now(),
        ]);
    }

    /**
     * @param  array{original_price: float, final_price: float, discount_amount: float, coupon_data: ?array<string, mixed>}  $pricing
     *
     * @throws \Exception
     */
    private function processPayment(User $user, Package $package, array $pricing, UserSubscription $subscription, ?string $couponCode): void
    {
        $this->validateWalletBalance($user, $pricing['final_price']);

        $description = $this->buildPaymentDescription($package, $pricing, $couponCode);

        if (! $user->wallet) {
            throw new \Exception(__('packages.errors.insufficient_balance', [
                'missing_amount' => number_format($pricing['final_price']),
                'currency' => 'XAF',
            ]));
        }

        // TODO: Utiliser InternalTransactionService une fois créé
        // Pour l'instant on utilise directement le modèle
        \App\Models\InternalTransaction::create([
            'wallet_id' => $user->wallet->id,
            'amount' => $pricing['final_price'],
            'transaction_type' => \App\Enums\TransactionType::DEBIT(),
            'status' => \App\Enums\TransactionStatus::COMPLETED(),
            'description' => $description,
            'related_type' => Package::class,
            'related_id' => $package->id,
            'created_by' => $user->id,
            'completed_at' => now(),
        ]);

        $user->wallet->decrement('balance', $pricing['final_price']);
    }

    /**
     * @param  array{original_price: float, final_price: float, discount_amount: float, coupon_data: ?array<string, mixed>}  $pricing
     */
    private function buildPaymentDescription(Package $package, array $pricing, ?string $couponCode): string
    {
        $description = __('packages.payment_description', ['package_name' => $package->display_name]);

        if ($package->hasActivePromotion()) {
            $description .= ' '.__('packages.promotion_applied', ['percentage' => $package->getPromotionalDiscountPercentage()]);
        }

        if ($couponCode && $pricing['discount_amount'] > 0) {
            $description .= ' '.__('packages.coupon_applied', ['discount' => number_format($pricing['discount_amount'])]);
        }

        return $description;
    }

    /**
     * @param  ?array<string, mixed>  $couponData
     */
    private function applyCouponIfPresent(?array $couponData, ?string $couponCode, User $user, UserSubscription $subscription, Package $package): void
    {
        if (! $couponData || ! $couponCode) {
            return;
        }

        $coupon = \App\Models\Coupon::findByCode($couponCode);
        if ($coupon) {
            $this->couponService->applyCoupon(
                $coupon,
                $user,
                $subscription,
                $package->getCurrentPrice()
            );
        }
    }
}
