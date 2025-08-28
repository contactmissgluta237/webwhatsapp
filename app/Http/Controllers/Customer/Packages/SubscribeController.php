<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\Packages;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\InternalTransaction;
use App\Models\Package;
use App\Models\UserSubscription;
use App\Services\CouponService;
use App\Services\ReferralService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscribeController extends Controller
{
    public function __construct(
        private readonly CouponService $couponService,
        private readonly ReferralService $referralService
    ) {}

    public function __invoke(Request $request, Package $package): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasActiveSubscription()) {
            return redirect()
                ->route('customer.packages.index')
                ->with('error', 'Vous avez déjà un abonnement actif. Veuillez attendre son expiration ou le résilier avant de souscrire à un nouveau package.');
        }

        if ($package->isTrial()) {
            $hasUsedTrial = $user->subscriptions()
                ->whereHas('package', fn (Builder $q): Builder => $q->where('name', 'trial'))
                ->exists();

            if ($hasUsedTrial) {
                return redirect()
                    ->route('customer.packages.index')
                    ->with('error', 'Vous avez déjà utilisé votre essai gratuit.');
            }
        }

        if (! $package->is_active) {
            return redirect()
                ->route('customer.packages.index')
                ->with('error', 'Ce package n\'est plus disponible.');
        }

        if ($package->isTrial()) {
            return $this->createSubscription($user, $package);
        }

        // Calculer le prix avec coupon éventuel
        $currentPrice = $package->getCurrentPrice();
        $couponCode = $request->input('coupon_code');
        $couponData = null;

        if ($couponCode) {
            $couponValidation = $this->couponService->validateCoupon($couponCode, $user, $currentPrice);
            if ($couponValidation['valid']) {
                $currentPrice = $couponValidation['final_price'];
                $couponData = $couponValidation;
            } else {
                return redirect()
                    ->route('customer.packages.index')
                    ->with('error', $couponValidation['message']);
            }
        }

        $wallet = $user->wallet;
        if (! $wallet || $wallet->balance < $currentPrice) {
            $missingAmount = $currentPrice - ($wallet ? $wallet->balance : 0);

            return redirect()
                ->route('customer.packages.index')
                ->with('error', "Solde insuffisant. Il vous manque {$missingAmount} XAF.")
                ->with('recharge_needed', true)
                ->with('missing_amount', $missingAmount);
        }

        return $this->createSubscription($user, $package, $couponData);
    }

    private function createSubscription($user, Package $package, ?array $couponData = null): RedirectResponse
    {
        try {
            $subscription = null;

            DB::transaction(function () use ($user, $package, $couponData, &$subscription) {
                $originalPrice = $package->getCurrentPrice();
                $finalPrice = $couponData ? $couponData['final_price'] : $originalPrice;

                if (! $package->isTrial()) {
                    $description = "Souscription au package {$package->display_name}";
                    if ($package->hasActivePromotion()) {
                        $description .= " (promotion -{$package->getPromotionalDiscountPercentage()}%)";
                    }
                    if ($couponData) {
                        $description .= " (coupon -{$couponData['savings']} XAF)";
                    }

                    InternalTransaction::create([
                        'wallet_id' => $user->wallet->id,
                        'amount' => $finalPrice,
                        'transaction_type' => TransactionType::DEBIT(),
                        'status' => TransactionStatus::COMPLETED(),
                        'description' => $description,
                        'related_type' => Package::class,
                        'related_id' => $package->id,
                        'created_by' => $user->id,
                        'completed_at' => now(),
                    ]);

                    $user->wallet->decrement('balance', $finalPrice);
                }

                $subscription = UserSubscription::create([
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                    'starts_at' => now(),
                    'ends_at' => now()->addDays($package->duration_days ?? 30),
                    'status' => 'active',
                    'messages_limit' => $package->messages_limit,
                    'context_limit' => $package->context_limit,
                    'accounts_limit' => $package->accounts_limit,
                    'products_limit' => $package->products_limit,
                    'amount_paid' => $finalPrice,
                    'payment_method' => 'wallet',
                    'activated_at' => now(),
                ]);

                // Appliquer le coupon si présent
                if ($couponData) {
                    $this->couponService->applyCoupon(
                        $couponData['coupon'],
                        $user,
                        $subscription,
                        $originalPrice
                    );
                }

                // Distribuer les gains de parrainage (seulement si pas gratuit)
                if (! $package->isTrial() && $finalPrice > 0) {
                    $this->referralService->distributeReferralEarnings($subscription, $finalPrice);
                }
            });

            $message = $package->isTrial()
                ? 'Votre essai gratuit de 7 jours a été activé avec succès !'
                : "Votre abonnement au package {$package->display_name} a été activé avec succès !";

            if ($couponData) {
                $message .= " Vous avez économisé {$couponData['savings']} XAF grâce à votre code promo.";
            }

            return redirect()
                ->route('customer.packages.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()
                ->route('customer.packages.index')
                ->with('error', 'Une erreur est survenue lors de la souscription. Veuillez réessayer.');
        }
    }
}
