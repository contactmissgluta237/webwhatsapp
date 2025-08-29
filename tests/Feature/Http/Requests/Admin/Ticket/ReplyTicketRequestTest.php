<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Ticket;

use App\Http\Requests\Admin\Ticket\ReplyTicketRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\Unit\Http\Requests\BaseRequestTestCase;

final class ReplyTicketRequestTest extends BaseRequestTestCase
{
    use RefreshDatabase;

    protected function getRequestClass(): string
    {
        return ReplyTicketRequest::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    protected function getValidData(): array
    {
        return [
            'message' => 'This is an admin reply to the customer ticket with detailed information.',
            'isInternal' => false,
        ];
    }

    protected function getInvalidValidationCases(): array
    {
        return [
            'message required' => [
                'message' => '',
                'expected_error_field' => 'message',
            ],
            'message not string' => [
                'message' => 123,
                'expected_error_field' => 'message',
            ],
            'isInternal not boolean' => [
                'isInternal' => 'not-boolean',
                'expected_error_field' => 'isInternal',
            ],
        ];
    }

    protected function getValidValidationCases(): array
    {
        return [
            'minimal message only' => [
                'message' => 'Short reply',
            ],
            'with internal flag true' => [
                'isInternal' => true,
            ],
            'with internal flag false' => [
                'isInternal' => false,
            ],
            'long message' => [
                'message' => str_repeat('This is a detailed admin response to the customer inquiry. ', 20),
            ],
            'null attachments' => [
                'attachments' => null,
            ],
        ];
    }

    /** @test */
    public function it_validates_valid_file_attachments(): void
    {
        $validData = $this->getValidData();
        $request = new ($this->getRequestClass())();

        // Test valid JPG attachment
        $jpgData = array_merge($validData, [
            'attachments' => [UploadedFile::fake()->image('screenshot.jpg', 800, 600)->size(500)],
        ]);
        $validator = Validator::make($jpgData, $request->rules(), $request->messages());
        $this->assertTrue($validator->passes(), 'JPG attachment validation should pass');

        // Test valid PNG attachment
        $pngData = array_merge($validData, [
            'attachments' => [UploadedFile::fake()->image('image.png', 400, 300)->size(300)],
        ]);
        $validator = Validator::make($pngData, $request->rules(), $request->messages());
        $this->assertTrue($validator->passes(), 'PNG attachment validation should pass');

        // Test valid PDF attachment
        $pdfData = array_merge($validData, [
            'attachments' => [UploadedFile::fake()->create('document.pdf', 1000)],
        ]);
        $validator = Validator::make($pdfData, $request->rules(), $request->messages());
        $this->assertTrue($validator->passes(), 'PDF attachment validation should pass');

        // Test multiple valid attachments
        $multipleData = array_merge($validData, [
            'attachments' => [
                UploadedFile::fake()->image('image1.jpg', 800, 600)->size(500),
                UploadedFile::fake()->create('document.pdf', 800),
                UploadedFile::fake()->image('image2.png', 400, 300)->size(400),
            ],
        ]);
        $validator = Validator::make($multipleData, $request->rules(), $request->messages());
        $this->assertTrue($validator->passes(), 'Multiple attachments validation should pass');
    }

    /** @test */
    public function it_validates_invalid_file_attachments(): void
    {
        $validData = $this->getValidData();
        $request = new ($this->getRequestClass())();

        // Test invalid file type
        $invalidTypeData = array_merge($validData, [
            'attachments' => [UploadedFile::fake()->create('document.txt', 100)],
        ]);
        $validator = Validator::make($invalidTypeData, $request->rules(), $request->messages());
        $this->assertTrue($validator->fails(), 'Invalid file type validation should fail');
        $this->assertArrayHasKey('attachments.0', $validator->errors()->toArray(), 'Should have error for invalid file type');

        // Test file too large
        $largeFileData = array_merge($validData, [
            'attachments' => [UploadedFile::fake()->create('large.pdf', 3000)],
        ]);
        $validator = Validator::make($largeFileData, $request->rules(), $request->messages());
        $this->assertTrue($validator->fails(), 'Large file validation should fail');
        $this->assertArrayHasKey('attachments.0', $validator->errors()->toArray(), 'Should have error for large file');
    }

    protected function getExpectedErrorMessages(): array
    {
        // This Request doesn't have custom messages
        return [];
    }
}
