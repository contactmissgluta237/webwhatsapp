<?php

namespace Tests\Unit\Http\Requests\Auth;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LoginRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_passes_validation_with_valid_email_data(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'loginMethod' => 'email',
        ];

        $rules = LoginRequest::getRulesForMethod($data['loginMethod']);
        $messages = LoginRequest::getMessages();
        $validator = Validator::make($data, $rules, $messages);

        $this->assertTrue($validator->passes(), 'Validation should pass with valid email data. Errors: '.$validator->errors()->first());
    }

    /** @test */
    public function it_passes_validation_with_valid_phone_data(): void
    {
        $data = [
            'country_id' => 1,
            'phone_number_only' => '655332183',
            'phone_number' => '+237655332183',
            'password' => 'password123',
            'loginMethod' => 'phone',
        ];

        $this->seed('CountrySeeder');

        $rules = LoginRequest::getRulesForMethod($data['loginMethod']);
        $messages = LoginRequest::getMessages();
        $validator = Validator::make($data, $rules, $messages);

        $this->assertTrue($validator->passes(), 'Validation should pass with valid phone data. Errors: '.$validator->errors()->first());
    }

    /**
     * @test
     *
     * @dataProvider invalidEmailCasesProvider
     */
    public function it_fails_validation_with_invalid_email_data(string $caseName, array $invalidData, string $expectedErrorField): void
    {
        $data = array_merge([
            'email' => 'test@example.com',
            'password' => 'password123',
            'loginMethod' => 'email',
        ], $invalidData);

        $rules = LoginRequest::getRulesForMethod($data['loginMethod']);
        $messages = LoginRequest::getMessages();
        $validator = Validator::make($data, $rules, $messages);

        $this->assertTrue($validator->fails(), "Validation should fail for case: {$caseName}");
        $this->assertArrayHasKey($expectedErrorField, $validator->errors()->toArray(), "Expected error for field '{$expectedErrorField}' in case '{$caseName}'");
    }

    public static function invalidEmailCasesProvider(): array
    {
        return [
            'email_is_missing' => ['email is missing', ['email' => ''], 'email'],
            'password_is_missing' => ['password is missing', ['password' => ''], 'password'],
            'email_is_invalid' => ['email is invalid', ['email' => 'invalid-email'], 'email'],
        ];
    }
}
