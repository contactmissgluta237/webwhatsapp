<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\SystemAccounts;

use App\Enums\PaymentMethod;
use App\Http\Requests\Admin\SystemAccounts\RechargeRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

final class RechargeRequestTest extends BaseRequestTestCase
{
    use RefreshDatabase;

    protected function getRequestClass(): string
    {
        return RechargeRequest::class;
    }

    protected function getValidData(): array
    {
        return [
            'paymentMethod' => PaymentMethod::ORANGE_MONEY(),
            'amount' => 50000,
            'senderName' => 'John Doe',
            'senderAccount' => '123456789',
            'description' => 'System account recharge for operations',
        ];
    }

    protected function getInvalidValidationCases(): array
    {
        return [
            'payment method required' => [
                'paymentMethod' => null,
                'expected_error_field' => 'paymentMethod',
            ],
            'payment method invalid' => [
                'paymentMethod' => 'INVALID_METHOD',
                'expected_error_field' => 'paymentMethod',
            ],
            'amount required' => [
                'amount' => null,
                'expected_error_field' => 'amount',
            ],
            'amount not numeric' => [
                'amount' => 'not-numeric',
                'expected_error_field' => 'amount',
            ],
            'amount zero' => [
                'amount' => 0,
                'expected_error_field' => 'amount',
            ],
            'amount negative' => [
                'amount' => -100,
                'expected_error_field' => 'amount',
            ],
            'sender name required' => [
                'senderName' => '',
                'expected_error_field' => 'senderName',
            ],
            'sender name too long' => [
                'senderName' => str_repeat('a', 256),
                'expected_error_field' => 'senderName',
            ],
            'sender account required' => [
                'senderAccount' => '',
                'expected_error_field' => 'senderAccount',
            ],
            'sender account too long' => [
                'senderAccount' => str_repeat('1', 256),
                'expected_error_field' => 'senderAccount',
            ],
            'description too long' => [
                'description' => str_repeat('a', 501),
                'expected_error_field' => 'description',
            ],
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'minimum amount' => [
                'amount' => 1,
            ],
            'maximum sender name length' => [
                'senderName' => str_repeat('A', 255),
            ],
            'maximum sender account length' => [
                'senderAccount' => str_repeat('1', 255),
            ],
            'maximum description length' => [
                'description' => str_repeat('x', 500),
            ],
            'null description' => [
                'description' => null,
            ],
            'mobile money payment' => [
                'paymentMethod' => PaymentMethod::MOBILE_MONEY(),
            ],
            'bank card payment' => [
                'paymentMethod' => PaymentMethod::BANK_CARD(),
            ],
            'cash payment' => [
                'paymentMethod' => PaymentMethod::CASH(),
            ],
            'decimal amount' => [
                'amount' => 999.99,
            ],
        ];
    }

    protected function getExpectedErrorMessages(): array
    {
        return [
            'paymentMethod.required',
            'paymentMethod.in',
            'amount.required',
            'amount.numeric',
            'amount.min',
            'senderName.required',
            'senderName.max',
            'senderAccount.required',
            'senderAccount.max',
            'description.max',
        ];
    }
}
