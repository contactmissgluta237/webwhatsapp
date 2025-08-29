<?php

declare(strict_types=1);

namespace Tests\Unit\Requests\Customer\Ticket;

use App\Http\Requests\Customer\Ticket\ReplyTicketRequest;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

class ReplyTicketRequestTest extends BaseRequestTestCase
{
    protected function getRequestClass(): string
    {
        return ReplyTicketRequest::class;
    }

    protected function getValidData(): array
    {
        return [
            'message' => 'Merci pour votre réponse. Voici des informations supplémentaires.',
        ];
    }

    protected function getInvalidValidationCases(): array
    {
        return [
            'missing message' => [
                'message' => null,
                'expected_error_field' => 'message',
            ],
            'empty message' => [
                'message' => '',
                'expected_error_field' => 'message',
            ],
            'message not string' => [
                'message' => 123,
                'expected_error_field' => 'message',
            ],
            'message as array' => [
                'message' => ['not', 'a', 'string'],
                'expected_error_field' => 'message',
            ],
            'message as boolean' => [
                'message' => true,
                'expected_error_field' => 'message',
            ],
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'simple reply' => [
                'message' => 'Thank you for your help.',
            ],
            'reply with special characters' => [
                'message' => 'Merci beaucoup ! Problème résolu avec succès. ✅',
            ],
            'multiline reply' => [
                'message' => "First line of the reply.\nSecond line with more details.\nThird line with conclusion.",
            ],
            'very long reply' => [
                'message' => str_repeat('This is a very detailed reply with lots of information. ', 50),
            ],
            'reply with HTML-like content' => [
                'message' => 'Reply with <strong>bold</strong> and <em>italic</em> text that should be escaped.',
            ],
            'reply with code snippets' => [
                'message' => 'Here is the error: `console.log("test")` and some SQL: `SELECT * FROM users;`',
            ],
            'minimal reply' => [
                'message' => 'OK',
            ],
            'reply with numbers and symbols' => [
                'message' => 'Error code: 404. Price: $25.99. Email: test@example.com',
            ],
            'reply with quotes' => [
                'message' => 'As you said: "Please provide more details", here they are.',
            ],
            'reply with line breaks and spacing' => [
                'message' => "Problem:\n  - Issue 1\n  - Issue 2\n\nSolution:\n  1. Step one\n  2. Step two",
            ],
        ];
    }

    protected function getExpectedErrorMessages(): array
    {
        return [
            'message.required',
        ];
    }

    public function test_authorization_always_passes(): void
    {
        $request = new ReplyTicketRequest;

        $this->assertTrue($request->authorize());
    }

    public function test_attachments_validation_rules(): void
    {
        $request = new ReplyTicketRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('attachments.*', $rules);
        $attachmentRules = $rules['attachments.*'];

        $this->assertEquals('nullable|file|mimes:jpg,jpeg,png,pdf|max:2048', $attachmentRules);
    }

    public function test_custom_error_messages(): void
    {
        $request = new ReplyTicketRequest;
        $messages = $request->messages();

        $expectedMessages = [
            'message.required' => 'Le message est obligatoire.',
            'attachments.*.file' => 'Le fichier uploadé n\'est pas valide.',
            'attachments.*.mimes' => 'Les fichiers autorisés sont : jpg, jpeg, png, pdf.',
            'attachments.*.max' => 'La taille maximale d\'un fichier est de 2 Mo.',
        ];

        foreach ($expectedMessages as $key => $expectedMessage) {
            $this->assertArrayHasKey($key, $messages);
            $this->assertEquals($expectedMessage, $messages[$key]);
        }
    }

    public function test_message_accepts_very_long_strings(): void
    {
        $request = new ReplyTicketRequest;

        // Test with a very long message (no max length constraint)
        $veryLongMessage = str_repeat('This is a very long message. ', 200);
        $validData = array_merge($this->getValidData(), [
            'message' => $veryLongMessage,
        ]);

        $validator = $this->app['validator']->make($validData, $request->rules(), $request->messages());
        $this->assertTrue($validator->passes(), 'Very long messages should be accepted as there is no max constraint');
    }

    public function test_message_preserves_whitespace_and_formatting(): void
    {
        $request = new ReplyTicketRequest;

        // Test with message containing various whitespace and formatting
        $formattedMessage = "  Leading spaces\n\nMultiple line breaks\n\n\n  Indented text\t\tTabs\n  Trailing spaces  ";
        $validData = array_merge($this->getValidData(), [
            'message' => $formattedMessage,
        ]);

        $validator = $this->app['validator']->make($validData, $request->rules(), $request->messages());
        $this->assertTrue($validator->passes(), 'Messages with formatting should be preserved');
    }

    public function test_only_message_field_is_required(): void
    {
        $request = new ReplyTicketRequest;
        $rules = $request->rules();

        // Verify that only 'message' and 'attachments.*' are defined
        $this->assertCount(2, $rules);
        $this->assertArrayHasKey('message', $rules);
        $this->assertArrayHasKey('attachments.*', $rules);

        // Verify that message is required
        $this->assertStringContainsString('required', $rules['message']);

        // Verify that attachments are nullable
        $this->assertStringContainsString('nullable', $rules['attachments.*']);
    }

    public function test_minimal_valid_request(): void
    {
        $request = new ReplyTicketRequest;

        // Test with only the required field
        $minimalData = ['message' => 'Ok'];

        $validator = $this->app['validator']->make($minimalData, $request->rules(), $request->messages());
        $this->assertTrue($validator->passes(), 'Request with only message field should pass');
    }
}
