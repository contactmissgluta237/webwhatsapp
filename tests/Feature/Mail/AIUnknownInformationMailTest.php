<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\Mail\AIUnknownInformationMail;
use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AIUnknownInformationMailTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private WhatsAppAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'locale' => 'fr',
        ]);

        $this->account = WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
            'agent_name' => 'Test Business',
        ]);
    }

    #[Test]
    public function it_uses_user_locale_for_email_subject(): void
    {
        // Définir l'application en anglais par défaut
        App::setLocale('en');

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'test_message_id',
            from: '33612345679@c.us',
            body: 'Quelle est votre politique de retour?',
            timestamp: now()->timestamp,
            type: 'text',
            isGroup: false
        );

        $mail = new AIUnknownInformationMail(
            $this->account,
            $messageRequest,
            'Je ne peux pas répondre à cette question pour le moment.',
            123
        );

        $envelope = $mail->envelope();

        // Vérifier que le sujet est en français
        $this->assertStringContainsString('Demande d\'Information IA', $envelope->subject);
        $this->assertStringContainsString('Test Business', $envelope->subject);
    }

    #[Test]
    public function it_uses_english_when_user_locale_is_english(): void
    {
        // Changer la locale de l'utilisateur
        $this->user->update(['locale' => 'en']);

        // Définir l'application en français par défaut
        App::setLocale('fr');

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'test_message_id',
            from: '33612345679@c.us',
            body: 'What is your return policy?',
            timestamp: now()->timestamp,
            type: 'text',
            isGroup: false
        );

        $mail = new AIUnknownInformationMail(
            $this->account,
            $messageRequest,
            'I cannot answer this question at the moment.',
            123
        );

        $envelope = $mail->envelope();

        // Vérifier que le sujet est en anglais
        $this->assertStringContainsString('AI Information Request', $envelope->subject);
        $this->assertStringContainsString('Test Business', $envelope->subject);
    }

    #[Test]
    public function it_preserves_app_locale_after_mail_creation(): void
    {
        // Définir l'application en anglais
        App::setLocale('en');
        $originalLocale = App::getLocale();

        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'test_message_id',
            from: '33612345679@c.us',
            body: 'Test question',
            timestamp: now()->timestamp,
            type: 'text',
            isGroup: false
        );

        $mail = new AIUnknownInformationMail(
            $this->account,
            $messageRequest,
            'Test AI response',
            123
        );

        // Exécuter les méthodes de mail
        $mail->envelope();
        $mail->content();

        // Vérifier que la locale de l'application n'a pas changé
        $this->assertEquals($originalLocale, App::getLocale());
    }

    #[Test]
    public function it_provides_correct_data_to_view(): void
    {
        $messageRequest = new WhatsAppMessageRequestDTO(
            id: 'test_message_id',
            from: '33612345679@c.us',
            body: 'Test question',
            timestamp: now()->timestamp,
            type: 'text',
            isGroup: false
        );

        $aiResponse = 'Test AI response for email';
        $conversationId = 123;

        $mail = new AIUnknownInformationMail(
            $this->account,
            $messageRequest,
            $aiResponse,
            $conversationId
        );

        $content = $mail->content();

        // Vérifier que la vue reçoit les bonnes données
        $this->assertEquals('emails.ai-unknown-information', $content->view);
        $this->assertArrayHasKey('account', $content->with);
        $this->assertArrayHasKey('incomingMessage', $content->with);
        $this->assertArrayHasKey('aiMessage', $content->with);
        $this->assertArrayHasKey('conversationId', $content->with);

        $this->assertEquals($this->account->id, $content->with['account']->id);
        $this->assertEquals($messageRequest->body, $content->with['incomingMessage']->body);
        $this->assertEquals($aiResponse, $content->with['aiMessage']);
        $this->assertEquals($conversationId, $content->with['conversationId']);
    }
}
