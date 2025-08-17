<?php

use App\Livewire\WhatsApp\ConversationSimulator;
use App\Models\WhatsAppAccount;
use Illuminate\Support\Facades\Log;

// Test du ConversationSimulator refactorisé
try {
    // Récupérer un compte WhatsApp pour tester
    $account = WhatsAppAccount::first();
    
    if (!$account) {
        Log::warning('🧪 Aucun compte WhatsApp trouvé pour le test');
        echo "⚠️  Aucun compte WhatsApp trouvé pour le test\n";
        return;
    }
    
    Log::info('🧪 Test ConversationSimulator refactorisé', [
        'account_id' => $account->id,
        'session_name' => $account->session_name,
    ]);
    
    // Créer le simulateur
    $simulator = new ConversationSimulator();
    $simulator->mount($account);
    
    // Tester les propriétés
    echo "✅ ConversationSimulator créé avec succès\n";
    echo "   - Account ID: {$account->id}\n";
    echo "   - Session Name: {$account->session_name}\n";
    echo "   - Current Prompt Length: " . strlen($simulator->currentPrompt) . " caractères\n";
    echo "   - Response Time: {$simulator->currentResponseTime}\n";
    
    Log::info('🧪 Test ConversationSimulator - Success', [
        'account_id' => $account->id,
        'current_prompt_length' => strlen($simulator->currentPrompt),
        'response_time' => $simulator->currentResponseTime,
    ]);
    
} catch (Exception $e) {
    Log::error('❌ Test ConversationSimulator échoué', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
