<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

final class UpdateUserRequestTest extends BaseRequestTestCase
{
    use RefreshDatabase;

    private User $existingUser;
    private User $otherUser;

    protected function getRequestClass(): string
    {
        return UpdateUserRequest::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create test roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'customer']);
        Role::create(['name' => 'manager']);

        // Create users for testing
        $this->existingUser = User::factory()->create([
            'email' => 'user@example.com',
            'phone_number' => '+1234567890',
        ]);

        $this->otherUser = User::factory()->create([
            'email' => 'other@example.com',
            'phone_number' => '+9876543210',
        ]);
    }

    protected function getValidData(): array
    {
        return [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.updated@example.com',
            'phone_number' => '+1111111111',
            'password' => null, // Optional on update
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
            'email not unique (other user)' => [
                'email' => 'other@example.com',
                'expected_error_field' => 'email',
            ],
            'phone number too long' => [
                'phone_number' => str_repeat('1', 256),
                'expected_error_field' => 'phone_number',
            ],
            'phone number not unique (other user)' => [
                'phone_number' => '+9876543210',
                'expected_error_field' => 'phone_number',
            ],
            'password too short when provided' => [
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
            'same email (should be allowed)' => [
                'email' => 'user@example.com', // Same as existing user
            ],
            'same phone number (should be allowed)' => [
                'phone_number' => '+1234567890', // Same as existing user
            ],
            'null password (optional)' => [
                'password' => null,
            ],
            'null phone number' => [
                'phone_number' => null,
            ],
            'with password update' => [
                'password' => 'newpassword123',
            ],
            'multiple roles' => [
                'selectedRoles' => ['admin', 'customer'],
            ],
            'maximum length names' => [
                'first_name' => str_repeat('A', 255),
                'last_name' => str_repeat('B', 255),
                'email' => 'max.length@example.com',
                'phone_number' => str_repeat('1', 255),
                'password' => str_repeat('p', 100),
                'selectedRoles' => ['admin'],
            ],
        ];
    }

    protected function getExpectedErrorMessages(): array
    {
        return [
            'first_name.required',
            'first_name.max',
            'last_name.required',
            'last_name.max',
            'email.required',
            'email.email',
            'email.unique',
            'phone_number.unique',
            'password.min',
            'is_active.required',
            'selectedRoles.required',
            'selectedRoles.min',
            'selectedRoles.*.exists',
            'image.image',
            'image.max',
        ];
    }

    // Override test methods to inject user for unique validation
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_passes_validation_with_valid_data(): void
    {
        $request = new ($this->getRequestClass())();
        $request->setUser($this->existingUser);
        $validator = \Illuminate\Support\Facades\Validator::make($this->getValidData(), $request->rules(), $request->messages());

        $this->assertTrue($validator->passes(), 'Validation should pass with valid data');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidValidationCasesProvider')]
    public function it_fails_validation_with_invalid_data(string $caseName, array $caseData, string $expectedErrorField): void
    {
        $data = array_merge($this->getValidData(), $caseData);
        $request = new ($this->getRequestClass())();
        $request->setUser($this->existingUser);
        $validator = \Illuminate\Support\Facades\Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails(), "Validation should fail for case: {$caseName}");
        $this->assertArrayHasKey(
            $expectedErrorField,
            $validator->errors()->toArray(),
            "Expected error field '{$expectedErrorField}' not found for case: {$caseName}"
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('validValidationCasesProvider')]
    public function it_passes_validation_with_additional_valid_data(string $caseName, array $caseData): void
    {
        $data = array_merge($this->getValidData(), $caseData);
        $request = new ($this->getRequestClass())();
        $request->setUser($this->existingUser);
        $validator = \Illuminate\Support\Facades\Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes(), "Validation should pass for case: {$caseName}");
    }
}
