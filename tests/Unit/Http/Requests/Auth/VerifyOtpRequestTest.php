<?php

namespace Tests\Unit\Http\Requests\Auth;

use App\Http\Requests\Auth\VerifyOtpRequest;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

class VerifyOtpRequestTest extends BaseRequestTestCase
{
    protected function getRequestClass(): string
    {
        return VerifyOtpRequest::class;
    }

    protected function getValidData(): array
    {
        return [
            'otpCode' => '123456',
        ];
    }

    protected function getInvalidValidationCases(): array
    {
        return [
            'otpCode_is_missing' => [
                'otpCode' => '',
                'expected_error_field' => 'otpCode',
            ],
            'otpCode_too_short' => [
                'otpCode' => '12',
                'expected_error_field' => 'otpCode',
            ],
            'otpCode_too_long' => [
                'otpCode' => '1234567890',
                'expected_error_field' => 'otpCode',
            ],
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'valid_4_digit_code' => [
                'otpCode' => '1234',
            ],
            'valid_5_digit_code' => [
                'otpCode' => '12345',
            ],
            'valid_6_digit_code' => [
                'otpCode' => '654321',
            ],
            'valid_alphanumeric_code' => [
                'otpCode' => 'ABC123',
            ],
        ];
    }

    protected function getExpectedErrorMessages(): array
    {
        return [
            'otpCode.required',
            'otpCode.min',
            'otpCode.max',
        ];
    }
}
