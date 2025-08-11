<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self ADMIN()
 * @method static self CUSTOMER()
 * @method static self SUPER_ADMIN()
 */
class UserRole extends Enum
{
    /**
     * @return string[]
     */
    public static function labels(): array
    {
        return [
            'ADMIN' => 'Administrateur',
            'CUSTOMER' => 'Client',
            'SUPER_ADMIN' => 'Super Administrateur',
        ];
    }

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return [
            'ADMIN' => 'admin',
            'CUSTOMER' => 'customer',
            'SUPER_ADMIN' => 'super-admin',
        ];
    }

    /**
     * Get permissions for this role
     */
    public function permissions(): array
    {
        $allPermissions = PermissionEnum::values();

        return match ($this->value) {
            'admin' => array_merge($allPermissions, [
                PermissionEnum::TICKETS_VIEW()->value,
                PermissionEnum::TICKETS_REPLY()->value,
                PermissionEnum::TICKETS_CLOSE()->value,
                PermissionEnum::TICKETS_ASSIGN()->value,
                PermissionEnum::TICKETS_CHANGE_STATUS()->value,
            ]),
            'super-admin' => array_merge($allPermissions, [
                PermissionEnum::TICKETS_VIEW()->value,
                PermissionEnum::TICKETS_REPLY()->value,
                PermissionEnum::TICKETS_CLOSE()->value,
                PermissionEnum::TICKETS_ASSIGN()->value,
                PermissionEnum::TICKETS_CHANGE_STATUS()->value,
            ]),
            'customer' => [
                PermissionEnum::DASHBOARD_VIEW()->value,
                PermissionEnum::PROFILE_VIEW()->value,
                PermissionEnum::PROFILE_EDIT()->value,
                PermissionEnum::REFERRALS_VIEW()->value,
                PermissionEnum::WALLET_VIEW()->value,
                PermissionEnum::WALLET_RECHARGE()->value,
                PermissionEnum::WALLET_WITHDRAW()->value,
                PermissionEnum::TRANSACTIONS_VIEW_EXTERNAL()->value,
                PermissionEnum::TRANSACTIONS_VIEW_INTERNAL()->value,
                PermissionEnum::TRANSACTIONS_CREATE_CUSTOMER_RECHARGE()->value,
                PermissionEnum::TRANSACTIONS_CREATE_CUSTOMER_WITHDRAWAL()->value,
                PermissionEnum::ORDERS_VIEW()->value,
                PermissionEnum::TICKETS_VIEW()->value,
                PermissionEnum::TICKETS_CREATE()->value,
                PermissionEnum::TICKETS_REPLY()->value,
            ],
            default => [],
        };
    }
}
