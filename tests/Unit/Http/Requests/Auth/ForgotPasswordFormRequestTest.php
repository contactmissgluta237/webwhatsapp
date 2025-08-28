<?php

namespace Tests\Unit\Http\Requests\Auth;

use App\Http\Requests\Auth\ForgotPasswordFormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

class ForgotPasswordFormRequestTest extends BaseRequestTestCase
{
    use RefreshDatabase;

    protected function getRequestClass(): string
    {
        return ForgotPasswordFormRequest::class;
    }

    protected function getValidData(): array
    {
        return [
            'email' => 'test@example.com',
            'resetMethod' => 'email',
        ];
    }

    protected function getInvalidValidationCases(): array
    {
        return [
            'email_is_missing' => [
                'email' => '',
                'expected_error_field' => 'email',
            ],
            'email_is_invalid' => [
                'email' => 'invalid-email',
                'expected_error_field' => 'email',
            ],
            'email_does_not_exist' => [
                'email' => 'nonexistent@example.com',
                'expected_error_field' => 'email',
            ],
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'valid_email_data' => [
                'email' => 'another@example.com',
                'resetMethod' => 'email',
            ],
        ];
    }

    protected function getExpectedErrorMessages(): array
    {
        return [
            'email.required',
            'email.email',
            'email.exists',
            'phoneNumber.required',
            'phoneNumber.exists',
            'country_id.required',
            'phone_number_only.required',
        ];
    }

    // Override des méthodes BaseRequestTestCase pour ForgotPasswordFormRequest spécifique
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_passes_validation_with_valid_data(): void
    {
        // Créer un utilisateur pour que l'email existe
        \App\Models\User::factory()->create(['email' => 'test@example.com']);

        $data = $this->getValidData();
        $rules = ForgotPasswordFormRequest::getRulesForMethod($data['resetMethod']);
        $messages = ForgotPasswordFormRequest::getMessages();
        $validator = Validator::make($data, $rules, $messages);

        $this->assertTrue($validator->passes(), 'Validation should pass with valid data');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidValidationCasesProvider')]
    public function it_fails_validation_with_invalid_data(string $caseName, array $caseData, string $expectedErrorField): void
    {
        // Créer un utilisateur pour que les tests exists fonctionnent
        \App\Models\User::factory()->create(['email' => 'test@example.com']);

        $data = array_merge($this->getValidData(), $caseData);
        $rules = ForgotPasswordFormRequest::getRulesForMethod($data['resetMethod']);
        $messages = ForgotPasswordFormRequest::getMessages();
        $validator = Validator::make($data, $rules, $messages);

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
        // Créer des utilisateurs pour que les emails existent
        \App\Models\User::factory()->create(['email' => 'test@example.com']);
        \App\Models\User::factory()->create(['email' => 'another@example.com']);

        $data = array_merge($this->getValidData(), $caseData);
        $rules = ForgotPasswordFormRequest::getRulesForMethod($data['resetMethod']);
        $messages = ForgotPasswordFormRequest::getMessages();
        $validator = Validator::make($data, $rules, $messages);

        $this->assertTrue($validator->passes(), "Validation should pass for case: {$caseName}");
    }

    // Test spécifique pour ForgotPasswordFormRequest - validation avec téléphone
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_passes_validation_with_valid_phone_data(): void
    {
        $this->seed('CountrySeeder');

        // Créer un utilisateur avec un numéro de téléphone
        \App\Models\User::factory()->create([
            'phone_number' => '+237655332183',
        ]);

        $data = [
            'country_id' => 1,
            'phone_number_only' => '655332183',
            'phoneNumber' => '+237655332183',
            'resetMethod' => 'phone',
        ];

        $rules = ForgotPasswordFormRequest::getRulesForMethod($data['resetMethod']);
        $messages = ForgotPasswordFormRequest::getMessages();
        $validator = Validator::make($data, $rules, $messages);

        $this->assertTrue($validator->passes(), 'Validation should pass with valid phone data. Errors: '.$validator->errors()->first());
    }
}
