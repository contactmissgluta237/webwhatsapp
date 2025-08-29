<?php

declare(strict_types=1);

namespace Tests\Unit\Requests\Customer;

use App\Http\Requests\Customer\CreateProductRequest;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

class CreateProductRequestTest extends BaseRequestTestCase
{
    protected function getRequestClass(): string
    {
        return CreateProductRequest::class;
    }

    protected function getValidData(): array
    {
        return [
            'title' => 'Produit Test',
            'description' => 'Description du produit test',
            'price' => 25.99,
            'is_active' => true,
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
            'price too low' => [
                'price' => 0,
                'expected_error_field' => 'price',
            ],
            'negative price' => [
                'price' => -10.50,
                'expected_error_field' => 'price',
            ],
            'is_active not boolean' => [
                'is_active' => 'not-boolean',
                'expected_error_field' => 'is_active',
            ],
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'basic product' => [
                'title' => 'Simple Product',
                'description' => 'A simple product description',
                'price' => 10.00,
                'is_active' => true,
            ],
            'minimum price' => [
                'title' => 'Cheap Product',
                'description' => 'Very affordable product',
                'price' => 0.01,
                'is_active' => false,
            ],
            'maximum title length' => [
                'title' => str_repeat('a', 255),
                'description' => 'Product with max title length',
                'price' => 99.99,
                'is_active' => true,
            ],
            'maximum description length' => [
                'title' => 'Product with long description',
                'description' => str_repeat('a', 1000),
                'price' => 50.00,
                'is_active' => false,
            ],
            'decimal price' => [
                'title' => 'Decimal Price Product',
                'description' => 'Product with decimal pricing',
                'price' => 123.456,
                'is_active' => true,
            ],
            'inactive product' => [
                'title' => 'Inactive Product',
                'description' => 'This product is inactive',
                'price' => 25.00,
                'is_active' => false,
            ],
            'without is_active field' => [
                'title' => 'Product without active flag',
                'description' => 'Product where is_active is optional',
                'price' => 15.00,
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

    public function test_authorization_always_passes(): void
    {
        $request = new CreateProductRequest;

        $this->assertTrue($request->authorize());
    }

    public function test_media_files_validation_rules(): void
    {
        // Test that media files rules are properly set
        $request = new CreateProductRequest;
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
        $request = new CreateProductRequest;
        $messages = $request->messages();

        $expectedMessages = [
            'title.required' => 'Le titre est obligatoire.',
            'title.max' => 'Le titre ne peut pas dépasser 255 caractères.',
            'description.required' => 'La description est obligatoire.',
            'description.max' => 'La description ne peut pas dépasser 1000 caractères.',
            'price.required' => 'Le prix est obligatoire.',
            'price.numeric' => 'Le prix doit être un nombre.',
            'price.min' => 'Le prix doit être supérieur à 0.',
            'mediaFiles.*.file' => 'Le fichier uploadé n\'est pas valide.',
            'mediaFiles.*.mimes' => 'Les fichiers autorisés sont : jpeg, jpg, png, gif, pdf, doc, docx.',
            'mediaFiles.*.max' => 'La taille maximale d\'un fichier est de 10 Mo.',
        ];

        foreach ($expectedMessages as $key => $expectedMessage) {
            $this->assertArrayHasKey($key, $messages);
            $this->assertEquals($expectedMessage, $messages[$key]);
        }
    }
}
