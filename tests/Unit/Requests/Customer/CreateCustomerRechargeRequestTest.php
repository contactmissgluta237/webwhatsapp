<?php

declare(strict_types=1);

namespace Tests\Unit\Requests\Customer;

use App\Constants\FinancialLimits;
use App\Constants\ValidationLimits;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Http\Requests\Customer\CreateCustomerRechargeRequest;
use App\Models\User;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

class CreateCustomerRechargeRequestTest extends BaseRequestTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create roles for testing
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    protected function getRequestClass(): string
    {
        return CreateCustomerRechargeRequest::class;
    }

    protected function getValidData(): array
    {
        return [
            'amount' => 1000,
            'payment_method' => PaymentMethod::MOBILE_MONEY()->value,
            'sender_account' => '+237670000000',
        ];
    }

    protected function getInvalidValidationCases(): array
    {
        return [
            'missing amount' => [
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
            'amount not in predefined amounts' => [
                'amount' => 999,
                'expected_error_field' => 'amount',
            ],
            'missing payment method' => [
                'payment_method' => null,
                'expected_error_field' => 'payment_method',
            ],
            'invalid payment method' => [
                'payment_method' => 'invalid_method',
                'expected_error_field' => 'payment_method',
            ],
            'missing sender account' => [
                'sender_account' => null,
                'expected_error_field' => 'sender_account',
            ],
            'sender account too long' => [
                'sender_account' => str_repeat('a', ValidationLimits::GENERAL_TEXT_MAX_LENGTH + 1),
                'expected_error_field' => 'sender_account',
            ],
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'mobile money with +237 prefix' => [
                'amount' => 2000,
                'payment_method' => PaymentMethod::MOBILE_MONEY()->value,
                'sender_account' => '+237670000000',
            ],
            'mobile money without prefix' => [
                'amount' => 5000,
                'payment_method' => PaymentMethod::MOBILE_MONEY()->value,
                'sender_account' => '670000000',
            ],
            'orange money with prefix' => [
                'amount' => 10000,
                'payment_method' => PaymentMethod::ORANGE_MONEY()->value,
                'sender_account' => '+237690000000',
            ],
            'orange money without prefix' => [
                'amount' => 25000,
                'payment_method' => PaymentMethod::ORANGE_MONEY()->value,
                'sender_account' => '690000000',
            ],
            'bank card format' => [
                'amount' => 50000,
                'payment_method' => PaymentMethod::BANK_CARD()->value,
                'sender_account' => '1234******567890',
            ],
            'minimum amount' => [
                'amount' => FinancialLimits::RECHARGE_MIN_AMOUNT,
                'payment_method' => PaymentMethod::MOBILE_MONEY()->value,
                'sender_account' => '+237670000000',
            ],
            'maximum amount' => [
                'amount' => FinancialLimits::RECHARGE_MAX_AMOUNT,
                'payment_method' => PaymentMethod::MOBILE_MONEY()->value,
                'sender_account' => '+237670000000',
            ],
        ];
    }

    protected function getExpectedErrorMessages(): array
    {
        return [
            'amount.required',
            'amount.integer',
            'amount.min',
            'amount.max',
            'amount.in',
            'payment_method.required',
            'payment_method.in',
            'sender_account.required',
            'sender_account.max',
        ];
    }

    protected function createAuthenticatedUser(): User
    {
        return User::factory()->create()->assignRole(UserRole::CUSTOMER()->value);
    }

    public function test_payment_method_specific_validation_mobile_money(): void
    {
        $user = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $invalidPhoneData = [
            'amount' => 1000,
            'payment_method' => PaymentMethod::MOBILE_MONEY()->value,
            'sender_account' => 'invalid-phone',
        ];

        // Create a mock request with the data to simulate form request behavior
        $request = CreateCustomerRechargeRequest::create('/test', 'POST', $invalidPhoneData);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $validator = $this->app['validator']->make($invalidPhoneData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('sender_account', $validator->errors()->toArray());
        $this->assertStringContainsString('Le format du numéro de téléphone n\'est pas valide', $validator->errors()->first('sender_account'));
    }

    public function test_payment_method_specific_validation_orange_money(): void
    {
        $user = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $invalidPhoneData = [
            'amount' => 1000,
            'payment_method' => PaymentMethod::ORANGE_MONEY()->value,
            'sender_account' => 'invalid-phone',
        ];

        // Create a mock request with the data to simulate form request behavior
        $request = CreateCustomerRechargeRequest::create('/test', 'POST', $invalidPhoneData);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $validator = $this->app['validator']->make($invalidPhoneData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('sender_account', $validator->errors()->toArray());
        $this->assertStringContainsString('Le format du numéro de téléphone n\'est pas valide', $validator->errors()->first('sender_account'));
    }

    public function test_payment_method_specific_validation_bank_card(): void
    {
        $user = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $invalidCardData = [
            'amount' => 1000,
            'payment_method' => PaymentMethod::BANK_CARD()->value,
            'sender_account' => 'invalid-card',
        ];

        // Create a mock request with the data to simulate form request behavior
        $request = CreateCustomerRechargeRequest::create('/test', 'POST', $invalidCardData);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $validator = $this->app['validator']->make($invalidCardData, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('sender_account', $validator->errors()->toArray());
        $this->assertStringContainsString('Les informations de carte ne sont pas valides', $validator->errors()->first('sender_account'));
    }

    public function test_authorization_passes_for_customer(): void
    {
        $user = User::factory()->create()->assignRole(UserRole::CUSTOMER()->value);
        $this->actingAs($user);

        $request = CreateCustomerRechargeRequest::create('/test', 'POST', $this->getValidData());
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $this->assertTrue($request->authorize());
    }

    public function test_authorization_fails_for_non_customer(): void
    {
        $user = User::factory()->create()->assignRole(UserRole::ADMIN()->value);
        $this->actingAs($user);

        $request = CreateCustomerRechargeRequest::create('/test', 'POST', $this->getValidData());
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $this->assertFalse($request->authorize());
    }
}
