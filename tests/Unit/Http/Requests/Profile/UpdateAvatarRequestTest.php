<?php

namespace Tests\Unit\Http\Requests\Profile;

use App\Http\Requests\Profile\UpdateAvatarRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Unit\Http\Requests\BaseRequestTest;

class UpdateAvatarRequestTest extends BaseRequestTest
{
    use RefreshDatabase;

    protected function getRequestClass(): string
    {
        return UpdateAvatarRequest::class;
    }

    protected function getValidData(): array
    {
        return [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 300, 300),
        ];
    }

    protected function getInvalidValidationCases(): array
    {
        return [
            'avatar_is_missing' => [
                'avatar' => null,
                'expected_error_field' => 'avatar',
            ],
            'avatar_is_not_image' => [
                'avatar' => UploadedFile::fake()->create('document.pdf', 1000),
                'expected_error_field' => 'avatar',
            ],
            'avatar_too_large' => [
                'avatar' => UploadedFile::fake()->image('large-avatar.jpg')->size(3000), // 3MB
                'expected_error_field' => 'avatar',
            ],
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'jpg_image' => [
                'avatar' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
            ],
            'png_image' => [
                'avatar' => UploadedFile::fake()->image('avatar.png', 200, 200),
            ],
            'gif_image' => [
                'avatar' => UploadedFile::fake()->image('avatar.gif', 200, 200),
            ],
        ];
    }

    protected function getExpectedErrorMessages(): array
    {
        return [
            'avatar.required',
            'avatar.image',
            'avatar.max',
        ];
    }
}
