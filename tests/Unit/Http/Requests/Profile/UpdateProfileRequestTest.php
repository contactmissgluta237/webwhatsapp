<?php

namespace Tests\Unit\Http\Requests\Profile;

use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

class UpdateProfileRequestTest extends BaseRequestTestCase
{
    use RefreshDatabase;

    protected function getRequestClass(): string
    {
        return UpdateProfileRequest::class;
    }

    protected function getValidData(): array
    {
        return [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone_number' => '+237655332183',
        ];
    }

    protected function getInvalidValidationCases(): array
    {
        return [
            'first_name_is_missing' => [
                'first_name' => '',
                'expected_error_field' => 'first_name',
            ],
            'last_name_is_missing' => [
                'last_name' => '',
                'expected_error_field' => 'last_name',
            ],
            'email_is_missing' => [
                'email' => '',
                'expected_error_field' => 'email',
            ],
            'email_is_invalid' => [
                'email' => 'invalid-email',
                'expected_error_field' => 'email',
            ],
            'first_name_too_long' => [
                'first_name' => str_repeat('a', 256),
                'expected_error_field' => 'first_name',
            ],
            'last_name_too_long' => [
                'last_name' => str_repeat('b', 256),
                'expected_error_field' => 'last_name',
            ],
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'without_phone_number' => [
                'phone_number' => null,
            ],
            'with_different_email_format' => [
                'email' => 'test.user+tag@domain.co.uk',
            ],
            'with_longer_names' => [
                'first_name' => 'Jean-Baptiste',
                'last_name' => 'De La Fontaine',
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
            'first_name.max',
            'last_name.max',
        ];
    }
}
