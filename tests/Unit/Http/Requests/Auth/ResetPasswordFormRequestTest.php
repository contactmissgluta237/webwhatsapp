<?php

namespace Tests\Unit\Http\Requests\Auth;

use App\Http\Requests\Auth\ResetPasswordFormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

class ResetPasswordFormRequestTest extends BaseRequestTestCase
{
    use RefreshDatabase;

    protected function getRequestClass(): string
    {
        return ResetPasswordFormRequest::class;
    }

    protected function getValidData(): array
    {
        return [
            'identifier' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'token' => 'valid-reset-token',
            'resetType' => 'email',
        ];
    }

    protected function getInvalidValidationCases(): array
    {
        return [
            'identifier_is_missing' => [
                'identifier' => '',
                'expected_error_field' => 'identifier',
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
            'password_confirmation_mismatch' => [
                'password' => 'newpassword123',
                'password_confirmation' => 'differentpassword',
                'expected_error_field' => 'password',
            ],
            'token_is_missing' => [
                'token' => '',
                'expected_error_field' => 'token',
            ],
            'identifier_email_invalid' => [
                'identifier' => 'invalid-email',
                'expected_error_field' => 'identifier',
            ],
            'identifier_does_not_exist' => [
                'identifier' => 'nonexistent@example.com',
                'expected_error_field' => 'identifier',
            ],
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'valid_strong_password' => [
                'password' => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!',
            ],
            'valid_minimum_password' => [
                'password' => 'simple123',
                'password_confirmation' => 'simple123',
            ],
        ];
    }

    protected function getExpectedErrorMessages(): array
    {
        return [
            'identifier.required',
            'identifier.email',
            'identifier.exists',
            'identifier.string',
            'password.required',
            'password.confirmed',
            'token.required',
            'token.string',
        ];
    }

    // Override des méthodes BaseRequestTestCase pour ResetPasswordFormRequest spécifique
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_passes_validation_with_valid_data(): void
    {
        // Créer un utilisateur pour que l'email existe
        \App\Models\User::factory()->create(['email' => 'test@example.com']);

        $data = $this->getValidData();
        $rules = ResetPasswordFormRequest::getRulesForResetType($data['resetType']);
        $messages = ResetPasswordFormRequest::getMessages();
        $validator = Validator::make($data, $rules, $messages);

        $this->assertTrue($validator->passes(), 'Validation should pass with valid data. Errors: ' . $validator->errors()->first());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidValidationCasesProvider')]
    public function it_fails_validation_with_invalid_data(string $caseName, array $caseData, string $expectedErrorField): void
    {
        // Créer un utilisateur pour que les tests exists fonctionnent
        \App\Models\User::factory()->create(['email' => 'test@example.com']);

        $data = array_merge($this->getValidData(), $caseData);
        $rules = ResetPasswordFormRequest::getRulesForResetType($data['resetType']);
        $messages = ResetPasswordFormRequest::getMessages();
        $validator = Validator::make($data, $rules, $messages);

        $this->assertTrue($validator->fails(), "Validation should fail for case: {$caseName}");
        $this->assertArrayHasKey(
            $expectedErrorField,
            $validator->errors()->toArray(),
            "Expected error field '{$expectedErrorField}' not found for case: {$caseName}. Errors: " . $validator->errors()->first()
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('validValidationCasesProvider')]
    public function it_passes_validation_with_additional_valid_data(string $caseName, array $caseData): void
    {
        // Créer un utilisateur pour que l'email existe
        \App\Models\User::factory()->create(['email' => 'test@example.com']);

        $data = array_merge($this->getValidData(), $caseData);
        $rules = ResetPasswordFormRequest::getRulesForResetType($data['resetType']);
        $messages = ResetPasswordFormRequest::getMessages();
        $validator = Validator::make($data, $rules, $messages);

        $this->assertTrue($validator->passes(), "Validation should pass for case: {$caseName}. Errors: " . $validator->errors()->first());
    }

    // Test spécifique pour ResetPasswordFormRequest - validation avec téléphone
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_passes_validation_with_valid_phone_data(): void
    {
        // Créer un utilisateur avec un numéro de téléphone
        \App\Models\User::factory()->create([
            'phone_number' => '+237655332183',
        ]);

        $data = [
            'identifier' => '+237655332183',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'token' => 'valid-reset-token',
            'resetType' => 'phone',
        ];

        $rules = ResetPasswordFormRequest::getRulesForResetType($data['resetType']);
        $messages = ResetPasswordFormRequest::getMessages();
        $validator = Validator::make($data, $rules, $messages);

        $this->assertTrue($validator->passes(), 'Validation should pass with valid phone data. Errors: ' . $validator->errors()->first());
    }
}
