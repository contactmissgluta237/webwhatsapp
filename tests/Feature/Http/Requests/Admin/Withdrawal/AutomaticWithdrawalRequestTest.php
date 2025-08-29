<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Withdrawal;

use App\Http\Requests\Admin\Withdrawal\AutomaticWithdrawalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

final class AutomaticWithdrawalRequestTest extends BaseRequestTestCase
{
    use RefreshDatabase;

    protected function getRequestClass(): string
    {
        return AutomaticWithdrawalRequest::class;
    }

    protected function getValidData(): array
    {
        $user = User::factory()->create();

        return [
            'customer_id' => $user->id,
            'amount' => 25000,
            'payment_method' => 'mobile_money',
            'receiver_account' => '22612345678',
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
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'orange money' => [
                'payment_method' => 'orange_money',
                'receiver_account' => '22798765432',
            ],
            'bank card' => [
                'payment_method' => 'bank_card',
                'receiver_account' => '4111111111111111',
            ],
            'cash' => [
                'payment_method' => 'cash',
                'receiver_account' => 'Cash Point 001',
            ],
            'large amount' => [
                'amount' => 500000,
            ],
            'minimal amount' => [
                'amount' => 1,
            ],
            'max receiver account' => [
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
            'amount.min',
            'payment_method.required',
            'payment_method.in',
            'receiver_account.required',
            'receiver_account.max',
        ];
    }
}
