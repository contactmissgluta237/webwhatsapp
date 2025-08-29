<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Packages;

use App\Http\Requests\Admin\Packages\UpdatePackageRequest;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

final class UpdatePackageRequestTest extends BaseRequestTestCase
{
    use RefreshDatabase;

    private Package $existingPackage;
    private Package $otherPackage;

    protected function getRequestClass(): string
    {
        return UpdatePackageRequest::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create packages for testing
        $this->existingPackage = Package::factory()->create(['name' => 'current-package']);
        $this->otherPackage = Package::factory()->create(['name' => 'other-package']);
    }

    protected function getValidData(): array
    {
        return [
            'name' => 'updated-package',
            'display_name' => 'Updated Package',
            'description' => 'An updated package for validation',
            'price' => 149.99,
            'currency' => 'EUR',
            'messages_limit' => 2000,
            'context_limit' => 100,
            'accounts_limit' => 10,
            'products_limit' => 200,
            'duration_days' => 60,
            'is_recurring' => false,
            'one_time_only' => true,
            'features' => ['updated-feature1', 'updated-feature2'],
            'is_active' => false,
            'sort_order' => 5,
            'promotional_price' => 129.99,
            'promotion_starts_at' => '2024-02-01',
            'promotion_ends_at' => '2024-02-28',
            'promotion_is_active' => false,
        ];
    }

    protected function getInvalidValidationCases(): array
    {
        return [
            'name required' => [
                'name' => '',
                'expected_error_field' => 'name',
            ],
            'name too long' => [
                'name' => str_repeat('a', 256),
                'expected_error_field' => 'name',
            ],
            'name not unique (other package)' => [
                'name' => 'other-package',
                'expected_error_field' => 'name',
            ],
            'display name required' => [
                'display_name' => '',
                'expected_error_field' => 'display_name',
            ],
            'display name too long' => [
                'display_name' => str_repeat('a', 256),
                'expected_error_field' => 'display_name',
            ],
            'description too long' => [
                'description' => str_repeat('a', 256),
                'expected_error_field' => 'description',
            ],
            'price required' => [
                'price' => null,
                'expected_error_field' => 'price',
            ],
            'price not numeric' => [
                'price' => 'not-numeric',
                'expected_error_field' => 'price',
            ],
            'price negative' => [
                'price' => -10,
                'expected_error_field' => 'price',
            ],
            'currency required' => [
                'currency' => '',
                'expected_error_field' => 'currency',
            ],
            'currency too long' => [
                'currency' => 'EURO',
                'expected_error_field' => 'currency',
            ],
            'messages limit required' => [
                'messages_limit' => null,
                'expected_error_field' => 'messages_limit',
            ],
            'messages limit not integer' => [
                'messages_limit' => 'not-integer',
                'expected_error_field' => 'messages_limit',
            ],
            'messages limit negative' => [
                'messages_limit' => -1,
                'expected_error_field' => 'messages_limit',
            ],
            'context limit required' => [
                'context_limit' => null,
                'expected_error_field' => 'context_limit',
            ],
            'context limit not integer' => [
                'context_limit' => 'not-integer',
                'expected_error_field' => 'context_limit',
            ],
            'context limit negative' => [
                'context_limit' => -1,
                'expected_error_field' => 'context_limit',
            ],
            'accounts limit required' => [
                'accounts_limit' => null,
                'expected_error_field' => 'accounts_limit',
            ],
            'accounts limit less than 1' => [
                'accounts_limit' => 0,
                'expected_error_field' => 'accounts_limit',
            ],
            'products limit required' => [
                'products_limit' => null,
                'expected_error_field' => 'products_limit',
            ],
            'products limit negative' => [
                'products_limit' => -1,
                'expected_error_field' => 'products_limit',
            ],
            'duration days less than 1' => [
                'duration_days' => 0,
                'expected_error_field' => 'duration_days',
            ],
            'sort order required' => [
                'sort_order' => null,
                'expected_error_field' => 'sort_order',
            ],
            'sort order negative' => [
                'sort_order' => -1,
                'expected_error_field' => 'sort_order',
            ],
            'promotional price not numeric' => [
                'promotional_price' => 'not-numeric',
                'expected_error_field' => 'promotional_price',
            ],
            'promotional price negative' => [
                'promotional_price' => -10,
                'expected_error_field' => 'promotional_price',
            ],
            'promotional price greater than price' => [
                'promotional_price' => 200.00,
                'price' => 150.00,
                'expected_error_field' => 'promotional_price',
            ],
            'promotion starts at invalid date' => [
                'promotion_starts_at' => 'invalid-date',
                'expected_error_field' => 'promotion_starts_at',
            ],
            'promotion ends at invalid date' => [
                'promotion_ends_at' => 'invalid-date',
                'expected_error_field' => 'promotion_ends_at',
            ],
            'promotion ends before start' => [
                'promotion_starts_at' => '2024-02-28',
                'promotion_ends_at' => '2024-02-01',
                'expected_error_field' => 'promotion_ends_at',
            ],
            'features not array' => [
                'features' => 'not-array',
                'expected_error_field' => 'features',
            ],
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'same name (should be allowed)' => [
                'name' => 'current-package', // Same as existing package
            ],
            'with zero values allowed' => [
                'price' => 0.0,
                'messages_limit' => 0,
                'context_limit' => 0,
                'products_limit' => 0,
                'sort_order' => 0,
                'promotional_price' => null, // Can't have promo price when price is 0
            ],
            'boolean values inverted' => [
                'is_recurring' => true,
                'one_time_only' => false,
                'is_active' => true,
                'promotion_is_active' => true,
            ],
            'maximum string lengths' => [
                'name' => str_repeat('a', 255),
                'display_name' => str_repeat('b', 255),
                'description' => str_repeat('c', 255),
                'currency' => 'USD',
            ],
            'with features array' => [
                'features' => ['Advanced Analytics', 'Priority Support', 'Custom Branding'],
            ],
            'null optional fields' => [
                'description' => null,
                'duration_days' => null,
                'features' => null,
                'promotional_price' => null,
                'promotion_starts_at' => null,
                'promotion_ends_at' => null,
            ],
        ];
    }

    protected function getExpectedErrorMessages(): array
    {
        return [
            'name.required',
            'name.unique',
            'display_name.required',
            'price.required',
            'price.min',
            'promotional_price.lt',
            'promotion_ends_at.after',
            'messages_limit.required',
            'accounts_limit.min',
        ];
    }

    // Override test methods to inject package for unique validation
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_passes_validation_with_valid_data(): void
    {
        $request = new ($this->getRequestClass())();
        $existingPackage = $this->existingPackage;
        $request->setRouteResolver(function () use ($existingPackage) {
            return new class($existingPackage)
            {
                private $package;

                public function __construct($package)
                {
                    $this->package = $package;
                }

                public function parameter($name)
                {
                    if ($name === 'package') {
                        return $this->package;
                    }

                    return null;
                }
            };
        });

        $validator = \Illuminate\Support\Facades\Validator::make($this->getValidData(), $request->rules(), $request->messages());
        $this->assertTrue($validator->passes(), 'Validation should pass with valid data');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidValidationCasesProvider')]
    public function it_fails_validation_with_invalid_data(string $caseName, array $caseData, string $expectedErrorField): void
    {
        $data = array_merge($this->getValidData(), $caseData);
        $request = new ($this->getRequestClass())();

        // Mock the route to return the existing package
        $existingPackage = $this->existingPackage;
        $request->setRouteResolver(function () use ($existingPackage) {
            return new class($existingPackage)
            {
                private $package;

                public function __construct($package)
                {
                    $this->package = $package;
                }

                public function parameter($name)
                {
                    if ($name === 'package') {
                        return $this->package;
                    }

                    return null;
                }
            };
        });

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

        // Mock the route to return the existing package
        $existingPackage = $this->existingPackage;
        $request->setRouteResolver(function () use ($existingPackage) {
            return new class($existingPackage)
            {
                private $package;

                public function __construct($package)
                {
                    $this->package = $package;
                }

                public function parameter($name)
                {
                    if ($name === 'package') {
                        return $this->package;
                    }

                    return null;
                }
            };
        });

        $validator = \Illuminate\Support\Facades\Validator::make($data, $request->rules(), $request->messages());
        $this->assertTrue($validator->passes(), "Validation should pass for case: {$caseName}");
    }
}
