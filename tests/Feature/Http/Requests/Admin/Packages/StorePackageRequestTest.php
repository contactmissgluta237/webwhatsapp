<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Packages;

use App\Http\Requests\Admin\Packages\StorePackageRequest;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

final class StorePackageRequestTest extends BaseRequestTestCase
{
    use RefreshDatabase;

    protected function getRequestClass(): string
    {
        return StorePackageRequest::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create a package for unique validation testing
        Package::factory()->create(['name' => 'existing-package']);
    }

    protected function getValidData(): array
    {
        return [
            'name' => 'test-package',
            'display_name' => 'Test Package',
            'description' => 'A test package for validation',
            'price' => 99.99,
            'currency' => 'EUR',
            'messages_limit' => 1000,
            'context_limit' => 50,
            'accounts_limit' => 5,
            'products_limit' => 100,
            'duration_days' => 30,
            'is_recurring' => true,
            'one_time_only' => false,
            'features' => ['feature1', 'feature2'],
            'is_active' => true,
            'sort_order' => 1,
            'promotional_price' => 79.99,
            'promotion_starts_at' => '2024-01-01',
            'promotion_ends_at' => '2024-01-31',
            'promotion_is_active' => true,
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
            'name not unique' => [
                'name' => 'existing-package',
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
                'promotional_price' => 150.00,
                'price' => 100.00,
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
                'promotion_starts_at' => '2024-01-31',
                'promotion_ends_at' => '2024-01-01',
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
            'minimal required fields' => [
                'name' => 'minimal-package',
                'display_name' => 'Minimal Package',
                'description' => null,
                'price' => 0,
                'messages_limit' => 0,
                'context_limit' => 0,
                'products_limit' => 0,
                'duration_days' => null,
                'features' => null,
                'promotional_price' => null,
                'promotion_starts_at' => null,
                'promotion_ends_at' => null,
            ],
            'with zero values allowed' => [
                'price' => 0.0,
                'messages_limit' => 0,
                'context_limit' => 0,
                'products_limit' => 0,
                'sort_order' => 0,
                'promotional_price' => null, // Can't have promo price when price is 0
            ],
            'boolean values false' => [
                'is_recurring' => false,
                'one_time_only' => true,
                'is_active' => false,
                'promotion_is_active' => false,
            ],
            'maximum string lengths' => [
                'name' => str_repeat('a', 255),
                'display_name' => str_repeat('b', 255),
                'description' => str_repeat('c', 255),
                'currency' => 'USD',
            ],
            'with features array' => [
                'features' => ['AI Integration', 'Multi-language', 'Analytics'],
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
}
