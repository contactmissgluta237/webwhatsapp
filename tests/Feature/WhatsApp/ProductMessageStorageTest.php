<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\DTOs\WhatsApp\ProductDataDTO;
use App\DTOs\WhatsApp\WhatsAppAIResponseDTO;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use App\Enums\MessageDirection;
use App\Enums\MessageSubtype;
use App\Enums\MessageType;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Repositories\WhatsApp\Contracts\WhatsAppMessageRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductMessageStorageTest extends TestCase
{
    use RefreshDatabase;

    private WhatsAppMessageRepositoryInterface $repository;
    private WhatsAppAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(WhatsAppMessageRepositoryInterface::class);

        // Create test user and WhatsApp account
        $user = User::factory()->create();
        $this->account = WhatsAppAccount::factory()->create(['user_id' => $user->id]);
    }

    public function test_can_store_message_exchange_without_products(): void
    {
        // Arrange
        $incomingMessage = new WhatsAppMessageRequestDTO(
            id: 'test_msg_123',
            from: '237123456789@c.us',
            body: 'Hello, do you have products?',
            timestamp: time(),
            type: 'chat',
            isGroup: false,
            contactName: 'Test Customer'
        );

        $aiDetails = new WhatsAppAIResponseDTO(
            response: 'Hello! Yes, we have products available.',
            model: 'gpt-4',
            confidence: 0.95
        );

        $aiResponse = WhatsAppMessageResponseDTO::success(
            aiResponse: 'Hello! Yes, we have products available.',
            aiDetails: $aiDetails,
            products: []
        );

        // Act
        $result = $this->repository->storeMessageExchange(
            $this->account,
            $incomingMessage,
            $aiResponse
        );

        // Assert
        $this->assertInstanceOf(\App\DTOs\WhatsApp\MessageExchangeResult::class, $result);
        $this->assertInstanceOf(WhatsAppConversation::class, $result->conversation);
        $this->assertInstanceOf(WhatsAppMessage::class, $result->incomingMessage);
        $this->assertInstanceOf(WhatsAppMessage::class, $result->outgoingMessage);

        // Verify the content
        $this->assertEquals('Hello, do you have products?', $result->incomingMessage->content);
        $this->assertEquals('Hello! Yes, we have products available.', $result->outgoingMessage->content);
        $this->assertTrue($result->outgoingMessage->is_ai_generated);
    }

    public function test_can_store_message_exchange_with_products(): void
    {
        // Arrange
        $products = [
            new ProductDataDTO(
                formattedProductMessage: '🛍️ *iPhone 15*\n\n💰 **850,000 USD**\n\n📝 Latest iPhone model',
                mediaUrls: [
                    'https://example.com/iphone1.jpg',
                    'https://example.com/iphone2.jpg',
                ]
            ),
            new ProductDataDTO(
                formattedProductMessage: '🛍️ *MacBook Air*\n\n💰 **1,200,000 USD**\n\n📝 Ultra-light laptop',
                mediaUrls: [
                    'https://example.com/macbook.mp4',
                    'https://example.com/macbook.jpg',
                ]
            ),
        ];

        $incomingMessage = new WhatsAppMessageRequestDTO(
            id: 'test_msg_456',
            from: '237987654321@c.us',
            body: 'Show me your products',
            timestamp: time(),
            type: 'chat',
            isGroup: false,
            contactName: 'Another Customer'
        );

        $aiDetails = new WhatsAppAIResponseDTO(
            response: 'Here are our available products:',
            model: 'gpt-4',
            confidence: 0.90
        );

        $aiResponse = WhatsAppMessageResponseDTO::success(
            aiResponse: 'Here are our available products:',
            aiDetails: $aiDetails,
            products: $products
        );

        // Act
        $result = $this->repository->storeMessageExchange(
            $this->account,
            $incomingMessage,
            $aiResponse
        );

        // Assert
        $this->assertInstanceOf(\App\DTOs\WhatsApp\MessageExchangeResult::class, $result);
        $this->assertInstanceOf(WhatsAppConversation::class, $result->conversation);
        $this->assertInstanceOf(WhatsAppMessage::class, $result->incomingMessage);
        $this->assertInstanceOf(WhatsAppMessage::class, $result->outgoingMessage);

        // Verify the content
        $this->assertEquals('Show me your products', $result->incomingMessage->content);
        $this->assertEquals('Here are our available products:', $result->outgoingMessage->content);
        $this->assertTrue($result->outgoingMessage->is_ai_generated);

        // Verify product messages were stored (check the conversation for product messages)
        $productMessages = $result->conversation->messages()
            ->where('message_subtype', MessageSubtype::PRODUCT())
            ->get();

        $this->assertCount(2, $productMessages);

        foreach ($productMessages as $productMessage) {
            $this->assertTrue($productMessage->direction->equals(MessageDirection::OUTBOUND()));
            $this->assertTrue($productMessage->message_type->equals(MessageType::TEXT()));
            $this->assertTrue($productMessage->message_subtype->equals(MessageSubtype::PRODUCT()));
            $this->assertTrue($productMessage->is_ai_generated);
            $this->assertNotNull($productMessage->media_urls);
            $this->assertIsArray($productMessage->media_urls);
        }

        // Verify first product message
        $firstProduct = $productMessages->first();
        $this->assertStringContainsString('iPhone 15', $firstProduct->content);
        $this->assertCount(2, $firstProduct->media_urls);
        $this->assertEquals('https://example.com/iphone1.jpg', $firstProduct->media_urls[0]);

        // Verify second product message
        $secondProduct = $productMessages->last();
        $this->assertStringContainsString('MacBook Air', $secondProduct->content);
        $this->assertCount(2, $secondProduct->media_urls);
        $this->assertEquals('https://example.com/macbook.mp4', $secondProduct->media_urls[0]);
    }
}
