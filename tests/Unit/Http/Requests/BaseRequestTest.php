<?php

namespace Tests\Unit\Http\Requests;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

abstract class BaseRequestTest extends TestCase
{
    abstract protected function getRequestClass(): string;

    abstract protected function getValidData(): array;

    abstract protected function getInvalidValidationCases(): array;

    protected function getValidValidationCases(): array
    {
        return [];
    }

    protected function getExpectedErrorMessages(): array
    {
        return [];
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_passes_validation_with_valid_data(): void
    {
        $request = new ($this->getRequestClass())();
        $validator = Validator::make($this->getValidData(), $request->rules(), $request->messages());

        $this->assertTrue($validator->passes(), 'Validation should pass with valid data');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_proper_error_messages(): void
    {
        $expectedMessages = $this->getExpectedErrorMessages();

        if (empty($expectedMessages)) {
            $this->markTestSkipped('No expected error messages defined');
        }

        $request = new ($this->getRequestClass())();
        $messages = $request->messages();

        foreach ($expectedMessages as $key) {
            $this->assertArrayHasKey($key, $messages, "Missing error message key: {$key}");
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidValidationCasesProvider')]
    public function it_fails_validation_with_invalid_data(string $caseName, array $caseData, string $expectedErrorField): void
    {
        $data = array_merge($this->getValidData(), $caseData);
        $request = new ($this->getRequestClass())();
        $validator = Validator::make($data, $request->rules(), $request->messages());

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
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes(), "Validation should pass for case: {$caseName}");
    }

    public static function invalidValidationCasesProvider(): array
    {
        $reflection = new \ReflectionClass(static::class);
        if ($reflection->isAbstract()) {
            return [];
        }

        $instance = $reflection->newInstanceWithoutConstructor();
        $cases = [];

        foreach ($instance->getInvalidValidationCases() as $caseName => $caseData) {
            $expectedErrorField = $caseData['expected_error_field'];
            unset($caseData['expected_error_field']);

            $cases[$caseName] = [$caseName, $caseData, $expectedErrorField];
        }

        return $cases;
    }

    public static function validValidationCasesProvider(): array
    {
        $reflection = new \ReflectionClass(static::class);
        if ($reflection->isAbstract()) {
            return [];
        }

        $instance = $reflection->newInstanceWithoutConstructor();
        $cases = [];

        foreach ($instance->getValidValidationCases() as $caseName => $caseData) {
            $cases[$caseName] = [$caseName, $caseData];
        }

        return $cases;
    }
}
