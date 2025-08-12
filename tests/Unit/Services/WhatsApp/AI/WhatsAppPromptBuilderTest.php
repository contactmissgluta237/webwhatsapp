<?php

declare(strict_types=1);

namespace Tests\Unit\Services\WhatsApp\AI;

use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\AI\Prompt\WhatsAppPromptBuilder;
use Tests\TestCase;

final class WhatsAppPromptBuilderTest extends TestCase
{
    private WhatsAppPromptBuilder $promptBuilder;
    private WhatsAppAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->promptBuilder = app(WhatsAppPromptBuilder::class);
        
        $this->account = new WhatsAppAccount([
            'agent_prompt' => 'Tu es un vendeur de téléphones. Reste professionnel.',
            'contextual_information' => 'Nos produits: Google Pixel 6 à 100k FCFA, iPhone XR d\'occasion à 90k FCFA. Garantie 1 an.',
            'response_time' => 'fast',
        ]);
    }

    public function test_it_builds_basic_prompt(): void
    {
        $prompt = $this->promptBuilder->buildPrompt(
            $this->account,
            'Bonjour',
            []
        );

        $this->assertIsString($prompt);
        $this->assertStringContainsString('vendeur de téléphones', $prompt);
        $this->assertStringContainsString('Reste professionnel', $prompt);
    }

    public function test_it_includes_contextual_information(): void
    {
        $prompt = $this->promptBuilder->buildPrompt(
            $this->account,
            'Quels téléphones vendez-vous ?',
            []
        );

        $this->assertStringContainsString('Google Pixel 6', $prompt);
        $this->assertStringContainsString('100k FCFA', $prompt);
        $this->assertStringContainsString('iPhone XR', $prompt);
        $this->assertStringContainsString('Garantie 1 an', $prompt);
    }

    public function test_it_handles_conversation_history(): void
    {
        $context = [
            ['role' => 'user', 'content' => 'Bonjour'],
            ['role' => 'assistant', 'content' => 'Bonjour ! Comment puis-je vous aider ?'],
            ['role' => 'user', 'content' => 'Avez-vous des iPhones ?'],
        ];

        $prompt = $this->promptBuilder->buildPrompt(
            $this->account,
            'C\'est quoi le prix ?',
            $context
        );

        // Vérifier que l'historique est inclus
        $this->assertStringContainsString('Bonjour', $prompt);
        $this->assertStringContainsString('Comment puis-je vous aider', $prompt);
        $this->assertStringContainsString('Avez-vous des iPhones', $prompt);
        $this->assertStringContainsString('C\'est quoi le prix', $prompt);
    }

    public function test_it_enforces_consistency_rules(): void
    {
        $prompt = $this->promptBuilder->buildPrompt(
            $this->account,
            'Salut mec !',
            []
        );

        // Vérifier les règles de cohérence
        $this->assertStringContainsString('TON COHÉRENT', $prompt);
        $this->assertStringContainsString('PROFESSIONNEL', $prompt);
        $this->assertStringContainsString('RÈGLES IMPORTANTES', $prompt);
    }

    public function test_it_handles_empty_contextual_information(): void
    {
        $this->account->contextual_information = null;
        
        $prompt = $this->promptBuilder->buildPrompt(
            $this->account,
            'Hello',
            []
        );

        $this->assertIsString($prompt);
        $this->assertStringContainsString('vendeur de téléphones', $prompt);
        // Ne devrait pas planter avec des infos contextuelles vides
    }

    public function test_it_truncates_long_conversation_history(): void
    {
        // Créer un long historique
        $longContext = [];
        for ($i = 0; $i < 50; $i++) {
            $longContext[] = ['role' => 'user', 'content' => "Message utilisateur numéro $i"];
            $longContext[] = ['role' => 'assistant', 'content' => "Réponse assistant numéro $i"];
        }

        $prompt = $this->promptBuilder->buildPrompt(
            $this->account,
            'Message final',
            $longContext
        );

        $this->assertIsString($prompt);
        // Le prompt ne devrait pas être trop long
        $this->assertLessThan(8000, strlen($prompt), 'Le prompt devrait être tronqué pour éviter de dépasser les limites');
    }

    public function test_it_maintains_prompt_structure(): void
    {
        $prompt = $this->promptBuilder->buildPrompt(
            $this->account,
            'Test message',
            []
        );

        // Vérifier la structure du prompt
        $this->assertStringContainsString('RÈGLES IMPORTANTES', $prompt);
        $this->assertStringContainsString('INFORMATIONS CONTEXTUELLES', $prompt);
        $this->assertStringContainsString('Nouveau message du client', $prompt);
    }
}
