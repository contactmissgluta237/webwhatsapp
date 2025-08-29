<?php

declare(strict_types=1);

namespace Tests\Unit\Requests\Customer;

use App\Http\Requests\Customer\UpdateProductRequest;
use App\Models\User;
use App\Models\UserProduct;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

class UpdateProductRequestTest extends BaseRequestTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create roles for testing
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    protected function getRequestClass(): string
    {
        return UpdateProductRequest::class;
    }

    protected function getValidData(): array
    {
        return [
            'title' => 'Produit Modifié',
            'description' => 'Description modifiée du produit',
            'price' => 35.99,
            'is_active' => false,
        ];
    }

    protected function getInvalidValidationCases(): array
    {
        return [
            'missing title' => [
                'title' => null,
                'expected_error_field' => 'title',
            ],
            'title too long' => [
                'title' => str_repeat('a', 256),
                'expected_error_field' => 'title',
            ],
            'missing description' => [
                'description' => null,
                'expected_error_field' => 'description',
            ],
            'description too long' => [
                'description' => str_repeat('a', 1001),
                'expected_error_field' => 'description',
            ],
            'missing price' => [
                'price' => null,
                'expected_error_field' => 'price',
            ],
            'price not numeric' => [
                'price' => 'invalid-price',
                'expected_error_field' => 'price',
            ],
            'negative price' => [
                'price' => -5.00,
                'expected_error_field' => 'price',
            ],
            'is_active not boolean' => [
                'is_active' => 'invalid-boolean',
                'expected_error_field' => 'is_active',
            ],
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'updated product' => [
                'title' => 'Updated Product Title',
                'description' => 'Updated product description with more details',
                'price' => 29.99,
                'is_active' => true,
            ],
            'zero price allowed for updates' => [
                'title' => 'Free Product',
                'description' => 'This product is now free',
                'price' => 0,
                'is_active' => false,
            ],
            'maximum title length' => [
                'title' => str_repeat('b', 255),
                'description' => 'Product with max title length for update',
                'price' => 150.00,
                'is_active' => true,
            ],
            'maximum description length' => [
                'title' => 'Product with long description update',
                'description' => str_repeat('c', 1000),
                'price' => 75.50,
                'is_active' => false,
            ],
            'decimal price update' => [
                'title' => 'Precise Pricing',
                'description' => 'Product with very precise pricing',
                'price' => 99.999,
                'is_active' => true,
            ],
            'deactivate product' => [
                'title' => 'Deactivated Product',
                'description' => 'This product has been deactivated',
                'price' => 45.00,
                'is_active' => false,
            ],
        ];
    }

    protected function getExpectedErrorMessages(): array
    {
        return [
            'title.required',
            'title.max',
            'description.required',
            'description.max',
            'price.required',
            'price.numeric',
            'price.min',
        ];
    }

    public function test_authorization_passes_for_product_owner(): void
    {
        $user = User::factory()->create();
        $product = UserProduct::factory()->create(['user_id' => $user->id]);

        $request = UpdateProductRequest::create('/test', 'PUT', $this->getValidData());
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Mock route parameter
        $request->setRouteResolver(function () use ($product) {
            return new class($product)
            {
                public function __construct(private UserProduct $product) {}

                public function parameter(string $name)
                {
                    return $name === 'product' ? $this->product : null;
                }
            };
        });

        $this->assertTrue($request->authorize());
    }

    public function test_authorization_fails_for_non_owner(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $product = UserProduct::factory()->create(['user_id' => $otherUser->id]);

        $request = UpdateProductRequest::create('/test', 'PUT', $this->getValidData());
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Mock route parameter
        $request->setRouteResolver(function () use ($product) {
            return new class($product)
            {
                public function __construct(private UserProduct $product) {}

                public function parameter(string $name)
                {
                    return $name === 'product' ? $this->product : null;
                }
            };
        });

        $this->assertFalse($request->authorize());
    }

    public function test_media_files_validation_rules(): void
    {
        $request = new UpdateProductRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('mediaFiles.*', $rules);
        $mediaRules = $rules['mediaFiles.*'];

        $this->assertContains('nullable', $mediaRules);
        $this->assertContains('file', $mediaRules);
        $this->assertContains('mimes:jpeg,jpg,png,gif,pdf,doc,docx', $mediaRules);
        $this->assertContains('max:10240', $mediaRules);
    }

    public function test_custom_error_messages(): void
    {
        $request = new UpdateProductRequest;
        $messages = $request->messages();

        $expectedMessages = [
            'title.required' => 'Le titre est obligatoire.',
            'title.max' => 'Le titre ne peut pas dépasser 255 caractères.',
            'description.required' => 'La description est obligatoire.',
            'description.max' => 'La description ne peut pas dépasser 1000 caractères.',
            'price.required' => 'Le prix est obligatoire.',
            'price.numeric' => 'Le prix doit être un nombre.',
            'price.min' => 'Le prix doit être positif.',
            'mediaFiles.*.file' => 'Le fichier uploadé n\'est pas valide.',
            'mediaFiles.*.mimes' => 'Les fichiers autorisés sont : jpeg, jpg, png, gif, pdf, doc, docx.',
            'mediaFiles.*.max' => 'La taille maximale d\'un fichier est de 10 Mo.',
        ];

        foreach ($expectedMessages as $key => $expectedMessage) {
            $this->assertArrayHasKey($key, $messages);
            $this->assertEquals($expectedMessage, $messages[$key]);
        }
    }

    public function test_price_difference_between_create_and_update(): void
    {
        // UpdateProductRequest allows price 0, CreateProductRequest requires min 0.01
        $updateRequest = new UpdateProductRequest;
        $updateRules = $updateRequest->rules();

        $this->assertEquals('required|numeric|min:0', $updateRules['price']);
    }
}
