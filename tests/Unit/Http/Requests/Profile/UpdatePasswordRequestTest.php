<?php

namespace Tests\Unit\Http\Requests\Profile;

use App\Http\Requests\Profile\UpdatePasswordRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

class UpdatePasswordRequestTest extends BaseRequestTestCase
{
    use RefreshDatabase;

    protected function getRequestClass(): string
    {
        return UpdatePasswordRequest::class;
    }

    protected function getValidData(): array
    {
        return [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];
    }

    protected function getInvalidValidationCases(): array
    {
        return [
            'current_password_is_missing' => [
                'current_password' => '',
                'expected_error_field' => 'current_password',
            ],
            'password_is_missing' => [
                'password' => '',
                'expected_error_field' => 'password',
            ],
            'password_too_short' => [
                'password' => '123',
                'password_confirmation' => '123',
                'expected_error_field' => 'password',
            ],
            'password_confirmation_does_not_match' => [
                'password_confirmation' => 'differentpassword',
                'expected_error_field' => 'password',
            ],
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'with_longer_password' => [
                'password' => 'verylongpassword123456789',
                'password_confirmation' => 'verylongpassword123456789',
            ],
            'with_special_characters' => [
                'password' => 'MyP@ssw0rd!',
                'password_confirmation' => 'MyP@ssw0rd!',
            ],
        ];
    }

    protected function getExpectedErrorMessages(): array
    {
        return [
            'current_password.required',
            'password.required',
            'password.min',
            'password.confirmed',
        ];
    }
}
