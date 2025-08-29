<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\SystemAccounts;

use App\Enums\PaymentMethod;
use App\Http\Requests\Admin\SystemAccounts\WithdrawalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

final class WithdrawalRequestTest extends BaseRequestTestCase
{
    use RefreshDatabase;

    protected function getRequestClass(): string
    {
        return WithdrawalRequest::class;
    }

    protected function getValidData(): array
    {
        return [
            'paymentMethod' => PaymentMethod::ORANGE_MONEY(),
            'amount' => 25000,
            'receiverName' => 'Jane Smith',
            'receiverAccount' => '987654321',
            'description' => 'System account withdrawal for partner payments',
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
            'receiver name required' => [
                'receiverName' => '',
                'expected_error_field' => 'receiverName',
            ],
            'receiver name too long' => [
                'receiverName' => str_repeat('a', 256),
                'expected_error_field' => 'receiverName',
            ],
            'receiver account required' => [
                'receiverAccount' => '',
                'expected_error_field' => 'receiverAccount',
            ],
            'receiver account too long' => [
                'receiverAccount' => str_repeat('1', 256),
                'expected_error_field' => 'receiverAccount',
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
            'maximum receiver name length' => [
                'receiverName' => str_repeat('B', 255),
            ],
            'maximum receiver account length' => [
                'receiverAccount' => str_repeat('2', 255),
            ],
            'maximum description length' => [
                'description' => str_repeat('y', 500),
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
            'large amount' => [
                'amount' => 999999.99,
            ],
        ];
    }

    protected function getExpectedErrorMessages(): array
    {
        // This Request doesn't have custom messages, so skip the message test
        return [];
    }
}
