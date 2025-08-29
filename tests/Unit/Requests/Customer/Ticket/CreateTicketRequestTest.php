<?php

declare(strict_types=1);

namespace Tests\Unit\Requests\Customer\Ticket;

use App\Http\Requests\Customer\Ticket\CreateTicketRequest;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

class CreateTicketRequestTest extends BaseRequestTestCase
{
    protected function getRequestClass(): string
    {
        return CreateTicketRequest::class;
    }

    protected function getValidData(): array
    {
        return [
            'title' => 'Problème avec mon compte',
            'description' => 'Je rencontre un problème avec mon compte, pouvez-vous m\'aider ?',
        ];
    }

    protected function getInvalidValidationCases(): array
    {
        return [
            'missing title' => [
                'title' => null,
                'expected_error_field' => 'title',
            ],
            'empty title' => [
                'title' => '',
                'expected_error_field' => 'title',
            ],
            'title too long' => [
                'title' => str_repeat('a', 256),
                'expected_error_field' => 'title',
            ],
            'title not string' => [
                'title' => 123,
                'expected_error_field' => 'title',
            ],
            'missing description' => [
                'description' => null,
                'expected_error_field' => 'description',
            ],
            'empty description' => [
                'description' => '',
                'expected_error_field' => 'description',
            ],
            'description not string' => [
                'description' => 456,
                'expected_error_field' => 'description',
            ],
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'basic ticket' => [
                'title' => 'Support Request',
                'description' => 'I need help with my account settings.',
            ],
            'ticket with special characters' => [
                'title' => 'Problème avec les caractères spéciaux é à ç',
                'description' => 'Description avec des caractères spéciaux: é, à, ç, ü, ñ',
            ],
            'maximum title length' => [
                'title' => str_repeat('a', 255),
                'description' => 'Ticket with maximum title length',
            ],
            'long description' => [
                'title' => 'Detailed Issue',
                'description' => str_repeat('This is a very detailed description of the issue. ', 20),
            ],
            'minimum required fields' => [
                'title' => 'Help',
                'description' => 'Need assistance.',
            ],
            'ticket with line breaks' => [
                'title' => 'Multi-line Issue',
                'description' => "First line of description.\nSecond line with more details.\nThird line with conclusion.",
            ],
            'ticket with html-like content' => [
                'title' => 'HTML Content Issue',
                'description' => 'Description with <script>alert("test")</script> content that should be escaped.',
            ],
        ];
    }

    protected function getExpectedErrorMessages(): array
    {
        return [
            'title.required',
            'title.max',
            'description.required',
        ];
    }

    public function test_authorization_always_passes(): void
    {
        $request = new CreateTicketRequest;

        $this->assertTrue($request->authorize());
    }

    public function test_attachments_validation_rules(): void
    {
        $request = new CreateTicketRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('attachments.*', $rules);
        $attachmentRules = $rules['attachments.*'];

        $this->assertEquals('nullable|file|mimes:jpg,jpeg,png,pdf|max:2048', $attachmentRules);
    }

    public function test_custom_error_messages(): void
    {
        $request = new CreateTicketRequest;
        $messages = $request->messages();

        $expectedMessages = [
            'title.required' => 'Le titre est obligatoire.',
            'title.max' => 'Le titre ne peut pas dépasser 255 caractères.',
            'description.required' => 'La description est obligatoire.',
            'attachments.*.file' => 'Le fichier uploadé n\'est pas valide.',
            'attachments.*.mimes' => 'Les fichiers autorisés sont : jpg, jpeg, png, pdf.',
            'attachments.*.max' => 'La taille maximale d\'un fichier est de 2 Mo.',
        ];

        foreach ($expectedMessages as $key => $expectedMessage) {
            $this->assertArrayHasKey($key, $messages);
            $this->assertEquals($expectedMessage, $messages[$key]);
        }
    }

    public function test_title_length_boundaries(): void
    {
        // Test exact boundary conditions
        $request = new CreateTicketRequest;

        // Test with exactly 255 characters (should pass)
        $validData = array_merge($this->getValidData(), [
            'title' => str_repeat('a', 255),
        ]);
        $validator = $this->app['validator']->make($validData, $request->rules(), $request->messages());
        $this->assertTrue($validator->passes());

        // Test with 256 characters (should fail)
        $invalidData = array_merge($this->getValidData(), [
            'title' => str_repeat('a', 256),
        ]);
        $validator = $this->app['validator']->make($invalidData, $request->rules(), $request->messages());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    public function test_description_string_validation(): void
    {
        $request = new CreateTicketRequest;

        // Test that description accepts very long strings
        $longDescription = str_repeat('Very long description with lots of details. ', 100);
        $validData = array_merge($this->getValidData(), [
            'description' => $longDescription,
        ]);

        $validator = $this->app['validator']->make($validData, $request->rules(), $request->messages());
        $this->assertTrue($validator->passes(), 'Long descriptions should be accepted');
    }
}
