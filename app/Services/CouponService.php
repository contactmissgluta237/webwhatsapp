<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CouponServiceInterface;
use App\Enums\CouponStatus;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\User;
use App\Models\UserSubscription;

final class CouponService implements CouponServiceInterface
{
    /**
     * Valider un code coupon pour un utilisateur et un montant
     */
    public function validateCoupon(string $couponCode, User $user, float $originalPrice): array
    {
        $coupon = Coupon::findByCode($couponCode);

        if (! $coupon) {
            return [
                'valid' => false,
                'message' => 'Code coupon invalide.',
                'coupon' => null,
            ];
        }

        if (! $coupon->isValid()) {
            return [
                'valid' => false,
                'message' => $this->getInvalidReason($coupon),
                'coupon' => $coupon,
            ];
        }

        if (! $coupon->canBeUsed()) {
            return [
                'valid' => false,
                'message' => 'Ce code coupon a atteint sa limite d\'utilisation.',
                'coupon' => $coupon,
            ];
        }

        // Vérifier si l'utilisateur a déjà utilisé ce coupon selon la limite par utilisateur
        if ($coupon->per_user_limit && $this->getUserCouponUsageCount($coupon, $user) >= $coupon->per_user_limit) {
            return [
                'valid' => false,
                'message' => 'Vous avez déjà utilisé ce code coupon.',
                'coupon' => $coupon,
            ];
        }

        $discountAmount = $coupon->calculateDiscount($originalPrice);
        $finalPrice = $coupon->applyDiscount($originalPrice);

        return [
            'valid' => true,
            'message' => 'Code coupon valide.',
            'coupon' => $coupon,
            'discount_amount' => $discountAmount,
            'final_price' => $finalPrice,
            'savings' => $discountAmount,
        ];
    }

    /**
     * Appliquer un coupon lors d'une souscription
     */
    public function applyCoupon(
        Coupon $coupon,
        User $user,
        UserSubscription $subscription,
        float $originalPrice
    ): CouponUsage {
        $discountAmount = $coupon->calculateDiscount($originalPrice);
        $finalPrice = $coupon->applyDiscount($originalPrice);

        // Enregistrer l'utilisation
        $usage = CouponUsage::recordUsage(
            $coupon,
            $user,
            $subscription,
            $originalPrice,
            $discountAmount,
            $finalPrice
        );

        // Marquer le coupon comme utilisé (incrémente used_count)
        $coupon->markAsUsed();

        return $usage;
    }

    /**
     * Compter le nombre d'utilisations d'un coupon par un utilisateur
     */
    private function getUserCouponUsageCount(Coupon $coupon, User $user): int
    {
        return CouponUsage::where('coupon_id', $coupon->id)
            ->where('user_id', $user->id)
            ->count();
    }

    /**
     * Obtenir la raison pour laquelle un coupon est invalide
     */
    private function getInvalidReason(Coupon $coupon): string
    {
        if ($coupon->status === CouponStatus::EXPIRED()) {
            return 'Ce code coupon a expiré.';
        }

        if ($coupon->status === CouponStatus::USED()) {
            return 'Ce code coupon a déjà été utilisé.';
        }

        if ($coupon->valid_from && now() < $coupon->valid_from) {
            return 'Ce code coupon n\'est pas encore actif.';
        }

        if ($coupon->valid_until && now() > $coupon->valid_until) {
            return 'Ce code coupon a expiré.';
        }

        if ($coupon->used_count >= $coupon->usage_limit) {
            return 'Ce code coupon a atteint sa limite d\'utilisation.';
        }

        return 'Code coupon invalide.';
    }
}
