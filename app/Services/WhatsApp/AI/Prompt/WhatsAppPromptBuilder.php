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
        $basePrompt = $this->buildBasePrompt($account);
        $contextualInfo = $this->buildContextualInfo($account);
        $conversationHistory = $this->buildConversationHistory($conversationContext);

        return $basePrompt.$contextualInfo.$conversationHistory.
               "Nouveau message du client: {$userMessage}\n\nRéponds de manière cohérente avec ton style précédent:";
    }

    private function buildBasePrompt(WhatsAppAccount $account): string
    {
        $basePrompt = $account->agent_prompt ?? 'Tu es un assistant WhatsApp utile et professionnel. Tu ne donnes jamais de fausses informations comme des coordonnées inventées (adresses, téléphones, emails, sites web). Si tu ne connais pas une information précise, tu le dis honnêtement.';

        $consistencyRules = "\n\nRÈGLES IMPORTANTES :\n";
        $consistencyRules .= "- MAINTIENS UN TON COHÉRENT ET PROFESSIONNEL tout au long de la conversation\n";
        $consistencyRules .= "- ÉVITE les changements brusques de personnalité ou de style\n";
        $consistencyRules .= "- RESTE DANS LE MÊME REGISTRE DE LANGAGE que tes précédentes réponses\n";
        $consistencyRules .= "- GARDE un style professionnel mais chaleureux\n";

        return $basePrompt.$consistencyRules;
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
