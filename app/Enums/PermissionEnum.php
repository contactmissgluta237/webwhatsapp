<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self USERS_VIEW()
 * @method static self USERS_CREATE()
 * @method static self USERS_EDIT()
 * @method static self USERS_DELETE()
 * @method static self USERS_MANAGE_ROLES()
 * @method static self CUSTOMERS_VIEW()
 * @method static self CUSTOMERS_EDIT()
 * @method static self CUSTOMERS_DELETE()
 * @method static self TRANSACTIONS_VIEW_EXTERNAL()
 * @method static self TRANSACTIONS_VIEW_INTERNAL()
 * @method static self TRANSACTIONS_CREATE_RECHARGE()
 * @method static self TRANSACTIONS_CREATE_WITHDRAWAL()
 * @method static self TRANSACTIONS_APPROVE_WITHDRAWAL()
 * @method static self TRANSACTIONS_REJECT_WITHDRAWAL()
 * @method static self SYSTEM_ACCOUNTS_VIEW_BALANCES()
 * @method static self SYSTEM_ACCOUNTS_VIEW_TRANSACTIONS()
 * @method static self SYSTEM_ACCOUNTS_RECHARGE()
 * @method static self SYSTEM_ACCOUNTS_WITHDRAW()
 * @method static self SYSTEM_ACCOUNTS_MANAGE()
 * @method static self DASHBOARD_VIEW()
 * @method static self PROFILE_VIEW()
 * @method static self PROFILE_EDIT()
 * @method static self SETTINGS_VIEW()
 * @method static self SETTINGS_EDIT()
 * @method static self REFERRALS_VIEW()
 * @method static self REFERRALS_MANAGE()
 * @method static self WALLET_VIEW()
 * @method static self WALLET_RECHARGE()
 * @method static self WALLET_WITHDRAW()
 * @method static self TRANSACTIONS_CREATE_CUSTOMER_RECHARGE()
 * @method static self TRANSACTIONS_CREATE_CUSTOMER_WITHDRAWAL()
 * @method static self ORDERS_VIEW()
 * @method static self TICKETS_VIEW()
 * @method static self TICKETS_CREATE()
 * @method static self TICKETS_REPLY()
 * @method static self TICKETS_CLOSE()
 * @method static self TICKETS_ASSIGN()
 * @method static self TICKETS_CHANGE_STATUS()
 */
class PermissionEnum extends Enum
{
    public static function values(): array
    {
        return [
            // USER MANAGEMENT
            'USERS_VIEW' => 'users.view',
            'USERS_CREATE' => 'users.create',
            'USERS_EDIT' => 'users.edit',
            'USERS_DELETE' => 'users.delete',
            'USERS_MANAGE_ROLES' => 'users.manage_roles',

            // CUSTOMER MANAGEMENT
            'CUSTOMERS_VIEW' => 'customers.view',
            'CUSTOMERS_EDIT' => 'customers.edit',
            'CUSTOMERS_DELETE' => 'customers.delete',

            // TRANSACTION MANAGEMENT
            'TRANSACTIONS_VIEW_EXTERNAL' => 'transactions.view_external',
            'TRANSACTIONS_VIEW_INTERNAL' => 'transactions.view_internal',
            'TRANSACTIONS_CREATE_RECHARGE' => 'transactions.create_recharge',
            'TRANSACTIONS_CREATE_WITHDRAWAL' => 'transactions.create_withdrawal',
            'TRANSACTIONS_APPROVE_WITHDRAWAL' => 'transactions.approve_withdrawal',
            'TRANSACTIONS_REJECT_WITHDRAWAL' => 'transactions.reject_withdrawal',
            'TRANSACTIONS_CREATE_CUSTOMER_RECHARGE' => 'transactions.create_customer_recharge',
            'TRANSACTIONS_CREATE_CUSTOMER_WITHDRAWAL' => 'transactions.create_customer_withdrawal',

            // SYSTEM ACCOUNT MANAGEMENT
            'SYSTEM_ACCOUNTS_VIEW_BALANCES' => 'system_accounts.view_balances',
            'SYSTEM_ACCOUNTS_VIEW_TRANSACTIONS' => 'system_accounts.view_transactions',
            'SYSTEM_ACCOUNTS_RECHARGE' => 'system_accounts.recharge',
            'SYSTEM_ACCOUNTS_WITHDRAW' => 'system_accounts.withdraw',
            'SYSTEM_ACCOUNTS_MANAGE' => 'system_accounts.manage',

            // DASHBOARD ACCESS
            'DASHBOARD_VIEW' => 'dashboard.view',

            // PROFILE MANAGEMENT
            'PROFILE_VIEW' => 'profile.view',
            'PROFILE_EDIT' => 'profile.edit',

            // SETTINGS MANAGEMENT
            'SETTINGS_VIEW' => 'settings.view',
            'SETTINGS_EDIT' => 'settings.edit',

            // REFERRAL MANAGEMENT
            'REFERRALS_VIEW' => 'referrals.view',
            'REFERRALS_MANAGE' => 'referrals.manage',

            // WALLET MANAGEMENT
            'WALLET_VIEW' => 'wallet.view',
            'WALLET_RECHARGE' => 'wallet.recharge',
            'WALLET_WITHDRAW' => 'wallet.withdraw',

            // ORDER MANAGEMENT
            'ORDERS_VIEW' => 'orders.view',

            // TICKET MANAGEMENT
            'TICKETS_VIEW' => 'tickets.view',
            'TICKETS_CREATE' => 'tickets.create',
            'TICKETS_REPLY' => 'tickets.reply',
            'TICKETS_CLOSE' => 'tickets.close',
            'TICKETS_ASSIGN' => 'tickets.assign',
            'TICKETS_CHANGE_STATUS' => 'tickets.change_status',
        ];
    }
}
