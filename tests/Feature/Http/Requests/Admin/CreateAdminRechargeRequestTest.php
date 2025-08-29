<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin;

use App\Constants\FinancialLimits;
use App\Constants\ValidationLimits;
use App\Enums\PaymentMethod;
use App\Http\Requests\Admin\CreateAdminRechargeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

final class CreateAdminRechargeRequestTest extends BaseRequestTestCase
{
    use RefreshDatabase;

    private $customerId;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'customer']);

        // Create a customer user
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $this->customerId = $customer->id;
    }

    protected function getRequestClass(): string
    {
        return CreateAdminRechargeRequest::class;
    }

    protected function getValidData(): array
    {
        return [
            'customer_id' => $this->customerId,
            'amount' => 5000,
            'external_transaction_id' => 'TEST-TXN-'.uniqid(),
            'description' => 'Recharge test par admin',
            'payment_method' => PaymentMethod::MOBILE_MONEY()->value,
            'sender_name' => 'Jean Dupont',
            'sender_account' => '+237670000000',
            'receiver_name' => 'Système AfrikSolutions',
            'receiver_account' => '+237650000000',
        ];
    }

    protected function getInvalidValidationCases(): array
    {
        return [
            'customer_id required' => [
                'customer_id' => null,
                'expected_error_field' => 'customer_id',
            ],
            'customer_id invalid' => [
                'customer_id' => 99999,
                'expected_error_field' => 'customer_id',
            ],
            'amount required' => [
                'amount' => null,
                'expected_error_field' => 'amount',
            ],
            'amount not integer' => [
                'amount' => 'invalid',
                'expected_error_field' => 'amount',
            ],
            'amount below minimum' => [
                'amount' => FinancialLimits::RECHARGE_MIN_AMOUNT - 1,
                'expected_error_field' => 'amount',
            ],
            'amount above maximum' => [
                'amount' => FinancialLimits::RECHARGE_MAX_AMOUNT + 1,
                'expected_error_field' => 'amount',
            ],
            'external_transaction_id required' => [
                'external_transaction_id' => '',
                'expected_error_field' => 'external_transaction_id',
            ],
            'external_transaction_id too long' => [
                'external_transaction_id' => str_repeat('a', 256),
                'expected_error_field' => 'external_transaction_id',
            ],
            'description required' => [
                'description' => '',
                'expected_error_field' => 'description',
            ],
            'description too long' => [
                'description' => str_repeat('a', ValidationLimits::DESCRIPTION_MAX_LENGTH + 1),
                'expected_error_field' => 'description',
            ],
            'payment_method required' => [
                'payment_method' => '',
                'expected_error_field' => 'payment_method',
            ],
            'payment_method invalid' => [
                'payment_method' => 'invalid_method',
                'expected_error_field' => 'payment_method',
            ],
            'sender_name required' => [
                'sender_name' => '',
                'expected_error_field' => 'sender_name',
            ],
            'sender_name too long' => [
                'sender_name' => str_repeat('a', 256),
                'expected_error_field' => 'sender_name',
            ],
            'sender_account required' => [
                'sender_account' => '',
                'expected_error_field' => 'sender_account',
            ],
            'sender_account too long' => [
                'sender_account' => str_repeat('a', 256),
                'expected_error_field' => 'sender_account',
            ],
            'receiver_name required' => [
                'receiver_name' => '',
                'expected_error_field' => 'receiver_name',
            ],
            'receiver_name too long' => [
                'receiver_name' => str_repeat('a', 256),
                'expected_error_field' => 'receiver_name',
            ],
            'receiver_account required' => [
                'receiver_account' => '',
                'expected_error_field' => 'receiver_account',
            ],
            'receiver_account too long' => [
                'receiver_account' => str_repeat('a', 256),
                'expected_error_field' => 'receiver_account',
            ],
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'minimum amount' => [
                'amount' => FinancialLimits::RECHARGE_MIN_AMOUNT,
                'external_transaction_id' => 'MIN-TXN-'.uniqid(),
                'description' => 'Recharge minimum',
                'payment_method' => PaymentMethod::MOBILE_MONEY()->value,
                'sender_name' => 'Test User',
                'sender_account' => '+237670000000',
                'receiver_name' => 'AfrikSolutions',
                'receiver_account' => '+237650000000',
            ],
            'maximum amount' => [
                'amount' => FinancialLimits::RECHARGE_MAX_AMOUNT,
                'external_transaction_id' => 'MAX-TXN-'.uniqid(),
                'description' => 'Recharge maximum',
                'payment_method' => PaymentMethod::ORANGE_MONEY()->value,
                'sender_name' => 'Test User Max',
                'sender_account' => '+237690000000',
                'receiver_name' => 'AfrikSolutions Max',
                'receiver_account' => '+237651000000',
            ],
            'maximum length fields' => [
                'amount' => 10000,
                'external_transaction_id' => str_repeat('a', 255),
                'description' => str_repeat('a', ValidationLimits::DESCRIPTION_MAX_LENGTH),
                'payment_method' => PaymentMethod::MOBILE_MONEY()->value,
                'sender_name' => str_repeat('a', 255),
                'sender_account' => str_repeat('a', 255),
                'receiver_name' => str_repeat('a', 255),
                'receiver_account' => str_repeat('a', 255),
            ],
        ];
    }

    protected function getExpectedErrorMessages(): array
    {
        return [
            'customer_id.required',
            'customer_id.exists',
            'amount.required',
            'amount.integer',
            'amount.min',
            'amount.max',
            'external_transaction_id.required',
            'external_transaction_id.unique',
            'description.required',
            'description.max',
            'payment_method.required',
            'payment_method.in',
            'sender_name.required',
            'sender_name.max',
            'sender_account.required',
            'sender_account.max',
            'receiver_name.required',
            'receiver_name.max',
            'receiver_account.required',
            'receiver_account.max',
        ];
    }
}
