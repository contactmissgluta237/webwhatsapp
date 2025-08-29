<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Withdrawal;

use App\Http\Requests\Admin\Withdrawal\ManualWithdrawalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

final class ManualWithdrawalRequestTest extends BaseRequestTestCase
{
    use RefreshDatabase;

    protected function getRequestClass(): string
    {
        return ManualWithdrawalRequest::class;
    }

    protected function getValidData(): array
    {
        $user = User::factory()->create();

        return [
            'customer_id' => $user->id,
            'amount' => 50000,
            'payment_method' => 'mobile_money',
            'receiver_account' => '22612345678',
            'external_transaction_id' => 'EXT-'.uniqid(),
            'description' => 'Manual withdrawal processing for customer payout with detailed tracking',
            'sender_name' => 'Admin User',
            'sender_account' => 'admin@example.com',
            'receiver_name' => 'John Doe',
        ];
    }

    protected function getInvalidValidationCases(): array
    {
        return [
            'customer_id required' => [
                'customer_id' => '',
                'expected_error_field' => 'customer_id',
            ],
            'customer_id not integer' => [
                'customer_id' => 'not-integer',
                'expected_error_field' => 'customer_id',
            ],
            'customer_id does not exist' => [
                'customer_id' => 99999,
                'expected_error_field' => 'customer_id',
            ],
            'amount required' => [
                'amount' => '',
                'expected_error_field' => 'amount',
            ],
            'amount not integer' => [
                'amount' => 'not-integer',
                'expected_error_field' => 'amount',
            ],
            'amount too small' => [
                'amount' => 0,
                'expected_error_field' => 'amount',
            ],
            'payment_method required' => [
                'payment_method' => '',
                'expected_error_field' => 'payment_method',
            ],
            'payment_method invalid' => [
                'payment_method' => 'invalid_method',
                'expected_error_field' => 'payment_method',
            ],
            'receiver_account required' => [
                'receiver_account' => '',
                'expected_error_field' => 'receiver_account',
            ],
            'receiver_account too long' => [
                'receiver_account' => str_repeat('a', 256),
                'expected_error_field' => 'receiver_account',
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
                'description' => str_repeat('a', 501),
                'expected_error_field' => 'description',
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
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'orange money payment' => [
                'payment_method' => 'orange_money',
                'receiver_account' => '22798765432',
            ],
            'bank card payment' => [
                'payment_method' => 'bank_card',
                'receiver_account' => '4111111111111111',
            ],
            'cash payment' => [
                'payment_method' => 'cash',
                'receiver_account' => 'Cash Point 001',
            ],
            'large amount' => [
                'amount' => 1000000,
            ],
            'minimal amount' => [
                'amount' => 1,
            ],
            'max description length' => [
                'description' => str_repeat('a', 500),
            ],
            'max field lengths' => [
                'receiver_account' => str_repeat('a', 255),
                'external_transaction_id' => str_repeat('b', 255),
                'sender_name' => str_repeat('c', 255),
                'sender_account' => str_repeat('d', 255),
                'receiver_name' => str_repeat('e', 255),
            ],
        ];
    }

    protected function getExpectedErrorMessages(): array
    {
        return [
            'customer_id.required',
            'customer_id.exists',
            'amount.required',
            'amount.min',
            'payment_method.required',
            'payment_method.in',
            'receiver_account.required',
            'receiver_account.max',
            'external_transaction_id.required',
            'external_transaction_id.unique',
            'description.required',
            'description.max',
            'sender_name.required',
            'sender_name.max',
            'sender_account.required',
            'sender_account.max',
            'receiver_name.required',
            'receiver_name.max',
        ];
    }
}
