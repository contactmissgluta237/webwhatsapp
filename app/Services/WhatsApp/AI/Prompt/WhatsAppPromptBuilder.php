<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\AI\Prompt;

use App\Models\WhatsAppAccount;

final class WhatsAppPromptBuilder
{
    public function buildPrompt(
        WhatsAppAccount $account,
        string $userMessage,
        array $conversationContext = []
    ): string {
        $basePrompt = $account->agent_prompt;
        $contextualInfo = $this->buildContextualInfo($account);
        $conversationHistory = $this->buildConversationHistory($conversationContext);

        return $basePrompt.$contextualInfo.$conversationHistory.
               "Nouveau message du client: {$userMessage}\n\nRéponds de manière cohérente avec ton style précédent:";
    }

    private function buildContextualInfo(WhatsAppAccount $account): string
    {
        if (empty($account->contextual_information)) {
            return '';
        }

        return "\n\n=== INFORMATIONS CONTEXTUELLES ===\n".
               $account->contextual_information.
               "\n=== FIN DES INFORMATIONS CONTEXTUELLES ===\n\n".
               "Utilise ces informations contextuelles pour personnaliser tes réponses selon l'activité et les services de l'entreprise.";
    }

    private function buildConversationHistory(array $conversationContext): string
    {
        if (empty($conversationContext)) {
            return '';
        }

        $history = "\n\n=== HISTORIQUE DE CONVERSATION ===\n";
        foreach ($conversationContext as $msg) {
            $role = $msg['role'] === 'user' ? 'Client' : 'Assistant';
            $history .= "{$role}: {$msg['content']}\n";
        }
        $history .= "=== FIN HISTORIQUE ===\n\n";
        $history .= "IMPORTANT: Analyse le ton et le style de tes précédentes réponses dans l'historique et RESTE COHÉRENT avec ce style.\n\n";

        return $history;
    }
}
