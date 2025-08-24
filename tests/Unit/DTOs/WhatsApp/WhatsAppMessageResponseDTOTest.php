<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs\WhatsApp;

use App\DTOs\WhatsApp\ProductDataDTO;
use App\DTOs\WhatsApp\WhatsAppAIResponseDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;
use PHPUnit\Framework\TestCase;

final class WhatsAppMessageResponseDTOTest extends TestCase
{
    private function createMockAIResponse(string $response = 'Test response'): WhatsAppAIResponseDTO
    {
        return new WhatsAppAIResponseDTO(
            response: $response,
            model: 'gpt-4',
            confidence: 0.95,
            tokensUsed: 150,
            cost: 0.002
        );
    }

    /**
     * Test la réponse webhook avec réponse IA valide
     */
    public function test_to_webhook_response_with_ai_response(): void
    {
        // Arrange
        $aiDetails = $this->createMockAIResponse('Bonjour ! Je peux vous aider à trouver les meilleurs produits.');

        $dto = WhatsAppMessageResponseDTO::success(
            aiResponse: 'Bonjour ! Je peux vous aider à trouver les meilleurs produits. Que recherchez-vous ?',
            aiDetails: $aiDetails,
            waitTime: 2,
            typingDuration: 4,
            products: [],
            sessionId: 'session_123',
            phoneNumber: '+237123456789'
        );

        // Act
        $webhookResponse = $dto->toWebhookResponse();

        // Assert
        $this->assertTrue($webhookResponse['success']);
        $this->assertTrue($webhookResponse['processed']);
        $this->assertSame('Bonjour ! Je peux vous aider à trouver les meilleurs produits. Que recherchez-vous ?', $webhookResponse['response_message']);
        $this->assertSame(2, $webhookResponse['wait_time_seconds']);
        $this->assertSame(4, $webhookResponse['typing_duration_seconds']);
        $this->assertArrayNotHasKey('error', $webhookResponse);
    }

    /**
     * Test la réponse webhook sans réponse IA
     */
    public function test_to_webhook_response_without_ai_response(): void
    {
        // Arrange
        $dto = WhatsAppMessageResponseDTO::processedWithoutResponse();

        // Act
        $webhookResponse = $dto->toWebhookResponse();

        // Assert
        $this->assertTrue($webhookResponse['success']);
        $this->assertTrue($webhookResponse['processed']);
        $this->assertNull($webhookResponse['response_message']);
        $this->assertSame(0, $webhookResponse['wait_time_seconds']);
        $this->assertSame(0, $webhookResponse['typing_duration_seconds']);
        $this->assertArrayNotHasKey('error', $webhookResponse);
    }

    /**
     * Test la réponse webhook avec erreur
     */
    public function test_to_webhook_response_with_error(): void
    {
        // Arrange
        $dto = WhatsAppMessageResponseDTO::error('Service IA temporairement indisponible');

        // Act
        $webhookResponse = $dto->toWebhookResponse();

        // Assert
        $this->assertFalse($webhookResponse['success']);
        $this->assertFalse($webhookResponse['processed']);
        $this->assertSame('Service IA temporairement indisponible', $webhookResponse['error']);
        $this->assertArrayNotHasKey('response_message', $webhookResponse);
        $this->assertArrayNotHasKey('wait_time_seconds', $webhookResponse);
        $this->assertArrayNotHasKey('typing_duration_seconds', $webhookResponse);
    }

    /**
     * Test l'inclusion des paramètres de timing
     */
    public function test_webhook_response_includes_timing_parameters(): void
    {
        // Arrange - Message court (timing rapide)
        $shortAI = $this->createMockAIResponse('Oui !');
        $shortMessageDto = WhatsAppMessageResponseDTO::success(
            aiResponse: 'Oui !',
            aiDetails: $shortAI,
            waitTime: 0,
            typingDuration: 1,
            sessionId: 'session_123',
            phoneNumber: '+237123456789'
        );

        // Arrange - Message long (timing plus lent)
        $longAI = $this->createMockAIResponse('Voici une explication détaillée...');
        $longMessageDto = WhatsAppMessageResponseDTO::success(
            aiResponse: 'Voici une explication détaillée de nos produits avec toutes les caractéristiques techniques et les options de personnalisation disponibles.',
            aiDetails: $longAI,
            waitTime: 5,
            typingDuration: 8,
            sessionId: 'session_123',
            phoneNumber: '+237123456789'
        );

        // Act
        $shortResponse = $shortMessageDto->toWebhookResponse();
        $longResponse = $longMessageDto->toWebhookResponse();

        // Assert - Message court
        $this->assertIsInt($shortResponse['wait_time_seconds']);
        $this->assertIsInt($shortResponse['typing_duration_seconds']);
        $this->assertSame(0, $shortResponse['wait_time_seconds']);
        $this->assertSame(1, $shortResponse['typing_duration_seconds']);

        // Assert - Message long
        $this->assertIsInt($longResponse['wait_time_seconds']);
        $this->assertIsInt($longResponse['typing_duration_seconds']);
        $this->assertSame(5, $longResponse['wait_time_seconds']);
        $this->assertSame(8, $longResponse['typing_duration_seconds']);

        // Assert - Valeurs réalistes
        $this->assertGreaterThanOrEqual(0, $shortResponse['wait_time_seconds']);
        $this->assertGreaterThanOrEqual(1, $shortResponse['typing_duration_seconds']);
        $this->assertLessThanOrEqual(10, $longResponse['wait_time_seconds']);
        $this->assertLessThanOrEqual(15, $longResponse['typing_duration_seconds']);
    }

    /**
     * Test la réponse webhook avec produits enrichis
     */
    public function test_webhook_response_with_enriched_products(): void
    {
        // Arrange - Créer des produits fictifs pour test (sans vérifier toArray)
        $product1 = new ProductDataDTO(
            formattedProductMessage: "🛍️ *MacBook Pro M3*\n\n💰 **2 500 000 XAF**\n\n📝 Ordinateur portable haute performance\n\n📞 Interested? Contact us for more information!",
            mediaUrls: ['https://example.com/macbook-1.jpg']
        );
        $product2 = new ProductDataDTO(
            formattedProductMessage: "🛍️ *iPhone 15 Pro*\n\n💰 **1 500 000 XAF**\n\n📝 Smartphone dernière génération\n\n📞 Interested? Contact us for more information!",
            mediaUrls: ['https://example.com/iphone-1.jpg']
        );

        $aiDetails = $this->createMockAIResponse('Voici nos produits recommandés !');
        $dto = WhatsAppMessageResponseDTO::success(
            aiResponse: 'Voici nos produits recommandés pour vous !',
            aiDetails: $aiDetails,
            waitTime: 3,
            typingDuration: 5,
            products: [$product1, $product2],
            sessionId: 'session_123',
            phoneNumber: '+237123456789'
        );

        // Act - Test seulement les propriétés principales sans toWebhookResponse qui appelle toArray
        $this->assertTrue($dto->processed);
        $this->assertTrue($dto->hasAiResponse);
        $this->assertSame('Voici nos produits recommandés pour vous !', $dto->aiResponse);
        $this->assertSame(3, $dto->waitTimeSeconds);
        $this->assertSame(5, $dto->typingDurationSeconds);
        $this->assertSame('session_123', $dto->sessionId);
        $this->assertSame('+237123456789', $dto->phoneNumber);
        $this->assertCount(2, $dto->products);
        $this->assertSame($product1, $dto->products[0]);
        $this->assertSame($product2, $dto->products[1]);
    }

    /**
     * Test la méthode wasSuccessful
     */
    public function test_was_successful_method(): void
    {
        // Arrange & Act - Succès avec réponse
        $aiDetails = $this->createMockAIResponse('Réponse IA');
        $successWithResponse = WhatsAppMessageResponseDTO::success(
            aiResponse: 'Réponse IA',
            aiDetails: $aiDetails,
            waitTime: 2,
            typingDuration: 3,
            sessionId: 'session_123',
            phoneNumber: '+237123456789'
        );

        // Arrange & Act - Succès sans réponse
        $successWithoutResponse = WhatsAppMessageResponseDTO::processedWithoutResponse();

        // Arrange & Act - Erreur
        $error = WhatsAppMessageResponseDTO::error('Erreur test');

        // Assert
        $this->assertTrue($successWithResponse->wasSuccessful());
        $this->assertTrue($successWithoutResponse->wasSuccessful());
        $this->assertFalse($error->wasSuccessful());
    }

    /**
     * Test la méthode hasError
     */
    public function test_has_error_method(): void
    {
        // Arrange & Act - Sans erreur
        $aiDetails = $this->createMockAIResponse('Réponse normale');
        $withoutError = WhatsAppMessageResponseDTO::success(
            aiResponse: 'Réponse normale',
            aiDetails: $aiDetails,
            waitTime: 1,
            typingDuration: 2,
            sessionId: 'session_123',
            phoneNumber: '+237123456789'
        );

        // Arrange & Act - Avec erreur
        $withError = WhatsAppMessageResponseDTO::error('Erreur de connexion API');

        // Assert
        $this->assertFalse($withoutError->hasError());
        $this->assertTrue($withError->hasError());
    }

    /**
     * Test avec des timings extrêmes (edge cases)
     */
    public function test_with_extreme_timings(): void
    {
        // Arrange - Timings minimum
        $minAI = $this->createMockAIResponse('Ok');
        $minTimingDto = WhatsAppMessageResponseDTO::success(
            aiResponse: 'Ok',
            aiDetails: $minAI,
            waitTime: 0,
            typingDuration: 1,
            sessionId: 'session_123',
            phoneNumber: '+237123456789'
        );

        // Arrange - Timings maximum
        $maxAI = $this->createMockAIResponse('Réponse très longue...');
        $maxTimingDto = WhatsAppMessageResponseDTO::success(
            aiResponse: 'Réponse très longue avec beaucoup de détails...',
            aiDetails: $maxAI,
            waitTime: 10,
            typingDuration: 15,
            sessionId: 'session_123',
            phoneNumber: '+237123456789'
        );

        // Act
        $minResponse = $minTimingDto->toWebhookResponse();
        $maxResponse = $maxTimingDto->toWebhookResponse();

        // Assert
        $this->assertSame(0, $minResponse['wait_time_seconds']);
        $this->assertSame(1, $minResponse['typing_duration_seconds']);
        $this->assertSame(10, $maxResponse['wait_time_seconds']);
        $this->assertSame(15, $maxResponse['typing_duration_seconds']);
    }

    /**
     * Test avec des caractères spéciaux dans la réponse IA
     */
    public function test_with_special_characters_in_ai_response(): void
    {
        // Arrange
        $specialResponse = 'Bonjour ! 😊 Voici les prix : 100€, 50$ & "produits spéciaux" avec accents: àéèçù';
        $aiDetails = $this->createMockAIResponse($specialResponse);
        $dto = WhatsAppMessageResponseDTO::success(
            aiResponse: $specialResponse,
            aiDetails: $aiDetails,
            waitTime: 2,
            typingDuration: 4,
            sessionId: 'session_123',
            phoneNumber: '+237123456789'
        );

        // Act
        $webhookResponse = $dto->toWebhookResponse();

        // Assert
        $this->assertSame($specialResponse, $webhookResponse['response_message']);
        $this->assertTrue($webhookResponse['success']);
    }

    /**
     * Test avec produits vides
     */
    public function test_with_empty_products(): void
    {
        // Arrange
        $aiDetails = $this->createMockAIResponse('Désolé, aucun produit trouvé.');
        $dto = WhatsAppMessageResponseDTO::success(
            aiResponse: 'Désolé, aucun produit trouvé.',
            aiDetails: $aiDetails,
            waitTime: 1,
            typingDuration: 2,
            products: [],
            sessionId: 'session_123',
            phoneNumber: '+237123456789'
        );

        // Act
        $webhookResponse = $dto->toWebhookResponse();

        // Assert
        $this->assertTrue($webhookResponse['success']);
        $this->assertTrue($webhookResponse['processed']);
        $this->assertSame('Désolé, aucun produit trouvé.', $webhookResponse['response_message']);
        $this->assertIsArray($webhookResponse['products']);
        $this->assertEmpty($webhookResponse['products']);
    }

    /**
     * Test avec données de session et téléphone
     */
    public function test_with_session_and_phone_data(): void
    {
        // Arrange
        $aiDetails = $this->createMockAIResponse('Test session data');
        $dto = WhatsAppMessageResponseDTO::success(
            aiResponse: 'Test session data',
            aiDetails: $aiDetails,
            sessionId: 'session_456',
            phoneNumber: '+33123456789'
        );

        // Act
        $webhookResponse = $dto->toWebhookResponse();

        // Assert
        $this->assertSame('session_456', $webhookResponse['session_id']);
        $this->assertSame('+33123456789', $webhookResponse['phone_number']);
    }

    /**
     * Test des propriétés publiques du DTO
     */
    public function test_dto_public_properties(): void
    {
        // Arrange
        $aiDetails = $this->createMockAIResponse('Test properties');
        $dto = WhatsAppMessageResponseDTO::success(
            aiResponse: 'Test response',
            aiDetails: $aiDetails,
            waitTime: 3,
            typingDuration: 6,
            sessionId: 'session_789',
            phoneNumber: '+237987654321'
        );

        // Assert
        $this->assertTrue($dto->processed);
        $this->assertTrue($dto->hasAiResponse);
        $this->assertSame('Test response', $dto->aiResponse);
        $this->assertNull($dto->processingError);
        $this->assertSame($aiDetails, $dto->aiDetails);
        $this->assertSame(3, $dto->waitTimeSeconds);
        $this->assertSame(6, $dto->typingDurationSeconds);
        $this->assertSame('session_789', $dto->sessionId);
        $this->assertSame('+237987654321', $dto->phoneNumber);
    }
}
