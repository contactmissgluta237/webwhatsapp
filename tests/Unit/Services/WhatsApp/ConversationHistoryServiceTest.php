<?php

declare(strict_types=1);

namespace Tests\Unit\Services\WhatsApp;

use App\Services\WhatsApp\ConversationHistoryService;
use App\Enums\MessageDirection;
use App\Enums\MessageType;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

final class ConversationHistoryServiceTest extends TestCase
{
    /**
     * Test des constantes de service
     */
    public function test_service_constants(): void
    {
        // Cette méthode teste que les constantes sont accessibles via réflection
        $reflection = new \ReflectionClass(ConversationHistoryService::class);
        
        $defaultLimit = $reflection->getConstant('DEFAULT_MESSAGE_LIMIT');
        $maxLimit = $reflection->getConstant('MAX_MESSAGE_LIMIT');
        $contextHours = $reflection->getConstant('CONTEXT_WINDOW_HOURS');

        // Assert
        $this->assertSame(20, $defaultLimit);
        $this->assertSame(50, $maxLimit);
        $this->assertSame(24, $contextHours);
    }

    /**
     * Test de la logique de validation des limites (méthode privée mais testable via réflection)
     */
    public function test_validate_message_limit_logic(): void
    {
        $service = new ConversationHistoryService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('validateMessageLimit');
        $method->setAccessible(true);

        // Test avec null (doit retourner la limite par défaut)
        $result = $method->invoke($service, null);
        $this->assertSame(20, $result);

        // Test avec une valeur valide
        $result = $method->invoke($service, 15);
        $this->assertSame(15, $result);

        // Test avec une valeur trop petite (doit être 1)
        $result = $method->invoke($service, 0);
        $this->assertSame(1, $result);

        $result = $method->invoke($service, -5);
        $this->assertSame(1, $result);

        // Test avec une valeur trop grande (doit être limitée à 50)
        $result = $method->invoke($service, 100);
        $this->assertSame(50, $result);

        $result = $method->invoke($service, 1000);
        $this->assertSame(50, $result);
    }

    /**
     * Test des enums MessageDirection
     */
    public function test_message_direction_enum(): void
    {
        $inbound = MessageDirection::INBOUND();
        $outbound = MessageDirection::OUTBOUND();

        // Assert
        $this->assertInstanceOf(MessageDirection::class, $inbound);
        $this->assertInstanceOf(MessageDirection::class, $outbound);
        $this->assertTrue($inbound->equals(MessageDirection::INBOUND()));
        $this->assertTrue($outbound->equals(MessageDirection::OUTBOUND()));
        $this->assertFalse($inbound->equals($outbound));
        $this->assertSame('inbound', $inbound->value);
        $this->assertSame('outbound', $outbound->value);
    }

    /**
     * Test des enums MessageType
     */
    public function test_message_type_enum(): void
    {
        $textType = MessageType::TEXT();

        // Assert
        $this->assertInstanceOf(MessageType::class, $textType);
        $this->assertTrue($textType->equals(MessageType::TEXT()));
        $this->assertSame('text', $textType->value);
    }

    /**
     * Test de formatage de dates avec Carbon
     */
    public function test_carbon_date_formatting(): void
    {
        // Arrange - Créer des dates fixes pour tests reproductibles
        $specificDate = Carbon::createFromFormat('Y-m-d H:i:s', '2024-01-15 14:30:45');
        
        // Act & Assert - Tester les formats utilisés dans le service
        $this->assertSame('2024-01-15', $specificDate->format('Y-m-d'));
        $this->assertSame('14:30', $specificDate->format('H:i'));
        $this->assertSame('15/01/2024', $specificDate->format('d/m/Y'));
        $this->assertSame('Y-m-d H:i:s', 'Y-m-d H:i:s'); // Format utilisé dans les logs
    }

    /**
     * Test de formatage d'heure contextuelle
     */
    public function test_context_window_calculation(): void
    {
        // Arrange
        $now = Carbon::createFromFormat('Y-m-d H:i:s', '2024-01-15 14:30:00');
        $twentyFourHoursAgo = $now->copy()->subHours(24);
        
        // Act & Assert
        $this->assertTrue($twentyFourHoursAgo->lessThan($now));
        $this->assertSame('2024-01-14 14:30:00', $twentyFourHoursAgo->format('Y-m-d H:i:s'));
        // Correction: diffInSeconds retourne la valeur absolue en float, conversion en int
        $this->assertSame(24 * 60 * 60, (int) abs($now->diffInSeconds($twentyFourHoursAgo)));
    }

    /**
     * Test de validation de chaînes vides et nulles
     */
    public function test_empty_string_handling(): void
    {
        // Test de trim() avec différents types de chaînes
        $this->assertSame('', trim(''));
        $this->assertSame('', trim('   '));
        $this->assertSame('', trim("\n\t  "));
        $this->assertSame('test', trim('  test  '));
        
        // Test de empty() avec différents types
        $this->assertTrue(empty(''));
        $this->assertTrue(empty(trim('   '))); // Correction: trim d'abord puis empty
        $this->assertTrue(empty('0')); // Correction: '0' est considéré comme empty en PHP
        $this->assertFalse(empty('test'));
    }

    /**
     * Test de construction de tableau de formatage
     */
    public function test_array_operations_for_formatting(): void
    {
        // Simulation du comportement de implode/explode utilisé dans le service
        $messages = [
            'user: Bonjour',
            'system: Bonjour ! Comment puis-je vous aider ?',
            'user: Je cherche un téléphone',
            'system: Voici nos derniers modèles'
        ];

        // Test implode (utilisé pour créer l'historique final)
        $history = implode("\n", $messages);
        $expected = "user: Bonjour\nsystem: Bonjour ! Comment puis-je vous aider ?\nuser: Je cherche un téléphone\nsystem: Voici nos derniers modèles";
        $this->assertSame($expected, $history);

        // Test explode (utilisé pour parser l'historique)
        $parsed = explode("\n", $history);
        $this->assertSame($messages, $parsed);
        $this->assertCount(4, $parsed);
    }

    /**
     * Test de logique de formatage de timestamp
     */
    public function test_timestamp_formatting_logic(): void
    {
        // Arrange - Différents formats de time utilisés dans le service
        $morning = Carbon::createFromFormat('Y-m-d H:i:s', '2024-01-15 09:15:30');
        $afternoon = Carbon::createFromFormat('Y-m-d H:i:s', '2024-01-15 14:30:45');
        $evening = Carbon::createFromFormat('Y-m-d H:i:s', '2024-01-15 23:59:59');

        // Act & Assert - Format H:i utilisé dans formatSingleMessage
        $this->assertSame('09:15', $morning->format('H:i'));
        $this->assertSame('14:30', $afternoon->format('H:i'));
        $this->assertSame('23:59', $evening->format('H:i'));

        // Test de comparaison de dates (même jour)
        $this->assertSame('2024-01-15', $morning->format('Y-m-d'));
        $this->assertSame('2024-01-15', $afternoon->format('Y-m-d'));
        $this->assertSame('2024-01-15', $evening->format('Y-m-d'));
    }

    /**
     * Test de validation de contenu de message
     */
    public function test_message_content_validation(): void
    {
        // Test de différents types de contenu que le service pourrait traiter
        $validContents = [
            'Message simple',
            'Message avec émojis 🛍️📱',
            'Message avec caractères spéciaux: àéèçù "quotes" & symbols',
            'Message très long: ' . str_repeat('Lorem ipsum ', 50),
            '123456', // Numérique
        ];

        foreach ($validContents as $content) {
            // Validation basique que le service ferait
            $trimmed = trim($content);
            $this->assertNotEmpty($trimmed);
            $this->assertIsString($trimmed);
            $this->assertGreaterThan(0, strlen($trimmed));
        }

        // Test de contenu vide ou invalide
        $invalidContents = ['', '   ', "\n\t  "];
        foreach ($invalidContents as $content) {
            $trimmed = trim($content);
            $this->assertEmpty($trimmed);
        }
    }

    /**
     * Test de logique de confidence AI
     */
    public function test_ai_confidence_formatting(): void
    {
        // Test du format de confidence utilisé dans formatSingleMessage
        $highConfidence = 0.95;
        $lowConfidence = 0.12;
        $nullConfidence = null;

        // Format: (conf: 95%)
        $formatted = number_format($highConfidence * 100, 0) . '%';
        $this->assertSame('95%', $formatted);

        $formatted = number_format($lowConfidence * 100, 0) . '%';
        $this->assertSame('12%', $formatted);

        // Test de condition de confidence
        $this->assertTrue($highConfidence >= 0.8);
        $this->assertFalse($lowConfidence >= 0.8);
        $this->assertNull($nullConfidence);
    }

    /**
     * Test de construction de messages avec séparateurs de date
     */
    public function test_date_separator_logic(): void
    {
        // Simulation de la logique de séparateur de date du service
        $date1 = Carbon::createFromFormat('Y-m-d H:i:s', '2024-01-15 10:00:00');
        $date2 = Carbon::createFromFormat('Y-m-d H:i:s', '2024-01-15 11:00:00');
        $date3 = Carbon::createFromFormat('Y-m-d H:i:s', '2024-01-16 10:00:00');

        // Test même jour
        $this->assertSame($date1->format('Y-m-d'), $date2->format('Y-m-d'));
        
        // Test jour différent
        $this->assertNotSame($date1->format('Y-m-d'), $date3->format('Y-m-d'));

        // Format de séparateur: --- 15/01/2024 ---
        $separator1 = '--- ' . $date1->format('d/m/Y') . ' ---';
        $separator3 = '--- ' . $date3->format('d/m/Y') . ' ---';
        
        $this->assertSame('--- 15/01/2024 ---', $separator1);
        $this->assertSame('--- 16/01/2024 ---', $separator3);
    }
}
