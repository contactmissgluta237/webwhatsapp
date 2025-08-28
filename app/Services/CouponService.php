<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CouponStatus;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\User;
use App\Models\UserSubscription;

final class CouponService
{
    /**
     * Valider un code coupon pour un utilisateur et un montant
     */
    public function validateCoupon(string $code, User $user, float $amount): array
    {
        $coupon = Coupon::findByCode($code);

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

        // Vérifier si l'utilisateur a déjà utilisé ce coupon (si limite = 1)
        if ($coupon->usage_limit === 1 && $this->hasUserUsedCoupon($coupon, $user)) {
            return [
                'valid' => false,
                'message' => 'Vous avez déjà utilisé ce code coupon.',
                'coupon' => $coupon,
            ];
        }

        $discountAmount = $coupon->calculateDiscount($amount);
        $finalPrice = $coupon->applyDiscount($amount);

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
     * Créer un nouveau coupon
     */
    public function createCoupon(array $data): Coupon
    {
        $data['code'] = strtoupper($data['code'] ?? Coupon::generateUniqueCode());
        $data['status'] = CouponStatus::ACTIVE();

        return Coupon::create($data);
    }

    /**
     * Vérifier si un utilisateur a déjà utilisé un coupon
     */
    public function hasUserUsedCoupon(Coupon $coupon, User $user): bool
    {
        return CouponUsage::where('coupon_id', $coupon->id)
            ->where('user_id', $user->id)
            ->exists();
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

    /**
     * Marquer les coupons expirés
     */
    public function markExpiredCoupons(): int
    {
        $count = Coupon::where('status', CouponStatus::ACTIVE())
            ->where('valid_until', '<', now())
            ->count();

        Coupon::where('status', CouponStatus::ACTIVE())
            ->where('valid_until', '<', now())
            ->update(['status' => CouponStatus::EXPIRED()]);

        return $count;
    }

    /**
     * Obtenir les statistiques d'utilisation d'un coupon
     */
    public function getCouponStats(Coupon $coupon): array
    {
        $usages = CouponUsage::where('coupon_id', $coupon->id)->get();

        return [
            'total_usages' => $usages->count(),
            'total_discount_given' => $usages->sum('discount_amount'),
            'average_original_price' => $usages->avg('original_price'),
            'unique_users' => $usages->pluck('user_id')->unique()->count(),
        ];
    }
}
