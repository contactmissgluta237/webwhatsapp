<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\CreateUserRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

final class CreateUserRequestTest extends BaseRequestTestCase
{
    use RefreshDatabase;

    protected function getRequestClass(): string
    {
        return CreateUserRequest::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create test roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'customer']);
        Role::create(['name' => 'manager']);

        // Create a test user for unique validation
        User::factory()->create([
            'email' => 'existing@example.com',
            'phone_number' => '+1234567890',
        ]);
    }

    protected function getValidData(): array
    {
        return [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone_number' => '+9876543210',
            'password' => 'password123',
            'is_active' => true,
            'selectedRoles' => ['customer'],
        ];
    }

    protected function getInvalidValidationCases(): array
    {
        return [
            'first name required' => [
                'first_name' => '',
                'expected_error_field' => 'first_name',
            ],
            'first name too long' => [
                'first_name' => str_repeat('a', 256),
                'expected_error_field' => 'first_name',
            ],
            'last name required' => [
                'last_name' => '',
                'expected_error_field' => 'last_name',
            ],
            'last name too long' => [
                'last_name' => str_repeat('b', 256),
                'expected_error_field' => 'last_name',
            ],
            'email required' => [
                'email' => '',
                'expected_error_field' => 'email',
            ],
            'email invalid format' => [
                'email' => 'invalid-email',
                'expected_error_field' => 'email',
            ],
            'email too long' => [
                'email' => str_repeat('a', 250).'@example.com',
                'expected_error_field' => 'email',
            ],
            'email not unique' => [
                'email' => 'existing@example.com',
                'expected_error_field' => 'email',
            ],
            'phone number too long' => [
                'phone_number' => str_repeat('1', 256),
                'expected_error_field' => 'phone_number',
            ],
            'phone number not unique' => [
                'phone_number' => '+1234567890',
                'expected_error_field' => 'phone_number',
            ],
            'password required' => [
                'password' => '',
                'expected_error_field' => 'password',
            ],
            'password too short' => [
                'password' => 'ab',
                'expected_error_field' => 'password',
            ],
            'is active required' => [
                'is_active' => null,
                'expected_error_field' => 'is_active',
            ],
            'selected roles required' => [
                'selectedRoles' => null,
                'expected_error_field' => 'selectedRoles',
            ],
            'selected roles empty array' => [
                'selectedRoles' => [],
                'expected_error_field' => 'selectedRoles',
            ],
            'selected roles invalid role' => [
                'selectedRoles' => ['invalid-role'],
                'expected_error_field' => 'selectedRoles.0',
            ],
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'minimum required fields' => [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane.smith@example.com',
                'password' => 'min',
                'is_active' => false,
                'selectedRoles' => ['admin'],
            ],
            'with optional phone number' => [
                'phone_number' => '+1111111111',
                'selectedRoles' => ['manager', 'customer'],
            ],
            'maximum length names' => [
                'first_name' => str_repeat('A', 255),
                'last_name' => str_repeat('B', 255),
                'email' => 'max.length@example.com',
                'phone_number' => str_repeat('1', 255),
                'password' => str_repeat('p', 100),
                'selectedRoles' => ['admin'],
            ],
            'null phone number' => [
                'phone_number' => null,
            ],
        ];
    }

    protected function getExpectedErrorMessages(): array
    {
        return [
            'selectedRoles.required',
            'selectedRoles.min',
        ];
    }
}
