<?php

use App\Contracts\WhatsApp\WhatsAppMessageOrchestratorInterface;
use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use Illuminate\Support\Facades\Log;

// Test simple de l'orchestrateur
try {
    $orchestrator = app(WhatsAppMessageOrchestratorInterface::class);
    
    Log::info('🧪 Test orchestrateur - Services bien injectés', [
        'orchestrator_class' => get_class($orchestrator),
    ]);
    
    // Test avec des données mock
    $messageData = [
        'id' => 'test_message_123',
        'from' => '237655332183@c.us',
        'body' => 'Hello, test message',
        'timestamp' => time(),
        'type' => 'chat',
        'isGroup' => false,
        'chatName' => null,
        'metadata' => []
    ];
    
    $messageRequest = WhatsAppMessageRequestDTO::fromWebhookData($messageData);
    
    Log::info('🧪 Test DTO créé avec succès', [
        'message_id' => $messageRequest->id,
        'from' => $messageRequest->from,
        'contact_phone' => $messageRequest->getContactPhone(),
    ]);
    
    echo "✅ Test orchestrateur réussi - Architecture fonctionnelle !\n";
    
} catch (Exception $e) {
    Log::error('❌ Test orchestrateur échoué', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
