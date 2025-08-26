<?php

namespace Tests\Unit\Http\Requests\Auth;

use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\Http\Requests\BaseRequestTest;

class RegisterRequestTest extends BaseRequestTest
{
    use RefreshDatabase;

    protected function getRequestClass(): string
    {
        return RegisterRequest::class;
    }

    protected function getValidData(): array
    {
        return [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => true,
            'locale' => 'fr',
        ];
    }

    protected function getInvalidValidationCases(): array
    {
        return [
            'first_name_is_missing' => [
                'first_name' => '',
                'expected_error_field' => 'first_name',
            ],
            'email_is_invalid' => [
                'email' => 'invalid-email',
                'expected_error_field' => 'email',
            ],
            'password_confirmation_does_not_match' => [
                'password_confirmation' => 'different_password',
                'expected_error_field' => 'password',
            ],
            'terms_are_not_accepted' => [
                'terms' => false,
                'expected_error_field' => 'terms',
            ],
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'longer_names' => [
                'first_name' => 'Jean-Baptiste',
                'last_name' => 'De La Fontaine',
            ],
            'different_email_format' => [
                'email' => 'test.user+tag@domain.co.uk',
            ],
        ];
    }

    protected function getExpectedErrorMessages(): array
    {
        return [
            'first_name.required',
            'last_name.required',
            'email.required',
            'email.email',
            'password.required',
            'password.confirmed',
            'terms.accepted',
        ];
    }
}
