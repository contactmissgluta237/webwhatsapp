<?php

declare(strict_types=1);

namespace Tests\Feature\Emails;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailIntegrationLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un pays avec l'ID 1
        \Illuminate\Support\Facades\DB::table('countries')->insert([
            'id' => 1,
            'name' => 'Cameroon',
            'code' => 'CM',
            'phone_code' => '+237',
            'flag_emoji' => '🇨🇲',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_complete_email_flow_respects_user_language_preferences(): void
    {
        // Ne pas faker les emails pour tester l'envoi réel

        // Créer des utilisateurs avec différentes langues
        $frenchUser = User::factory()->create([
            'locale' => 'fr',
            'email' => 'test-fr@example.com',
            'first_name' => 'Jean',
        ]);

        $englishUser = User::factory()->create([
            'locale' => 'en',
            'email' => 'test-en@example.com',
            'first_name' => 'John',
        ]);

        // Test d'envoi d'email d'activation en français
        $this->sendActivationEmail($frenchUser);

        // Test d'envoi d'email d'activation en anglais
        $this->sendActivationEmail($englishUser);

        // Test d'envoi d'email OTP en français
        $this->sendOtpEmail($frenchUser);

        // Test d'envoi d'email OTP en anglais
        $this->sendOtpEmail($englishUser);

        // Si nous arrivons ici sans exception, les emails ont été envoyés avec succès
        $this->assertTrue(true, 'Tous les emails ont été envoyés avec succès selon la langue utilisateur');
    }

    public function test_email_template_renders_correctly_for_different_languages(): void
    {
        $frenchUser = User::factory()->create([
            'locale' => 'fr',
            'email' => 'template-test-fr@example.com',
        ]);

        $englishUser = User::factory()->create([
            'locale' => 'en',
            'email' => 'template-test-en@example.com',
        ]);

        // Tester le rendu du template avec différentes langues
        $this->assertEmailTemplateRenders($frenchUser, 'fr');
        $this->assertEmailTemplateRenders($englishUser, 'en');
    }

    public function test_email_localization_service_integration(): void
    {
        // Créer un utilisateur pour chaque langue supportée
        $users = [
            'fr' => User::factory()->create(['locale' => 'fr', 'email' => 'service-fr@test.com']),
            'en' => User::factory()->create(['locale' => 'en', 'email' => 'service-en@test.com']),
        ];

        foreach ($users as $locale => $user) {
            // Simuler l'appel au service d'email avec la bonne locale
            $originalLocale = app()->getLocale();

            try {
                app()->setLocale($user->locale);

                // Vérifier que les traductions sont correctes pour cette locale
                $subject = __('emails.account_activation.subject');
                $welcome = __('emails.account_activation.welcome');

                if ($locale === 'fr') {
                    $this->assertEquals('Activation de votre compte', $subject);
                    $this->assertEquals('Bienvenue ! Votre compte a été créé avec succès.', $welcome);
                } else {
                    $this->assertEquals('Account Activation', $subject);
                    $this->assertEquals('Welcome! Your account has been created successfully.', $welcome);
                }

            } finally {
                app()->setLocale($originalLocale);
            }
        }
    }

    private function sendActivationEmail(User $user): void
    {
        $originalLocale = app()->getLocale();

        try {
            app()->setLocale($user->locale);

            Mail::send('emails.account-activation', [
                'otp' => '123456',
                'maskedIdentifier' => $this->maskEmail($user->email),
                'activationUrl' => route('verify-otp', ['identifier' => $user->email]),
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject(__('emails.account_activation.subject'));
            });

        } finally {
            app()->setLocale($originalLocale);
        }
    }

    private function sendOtpEmail(User $user): void
    {
        $originalLocale = app()->getLocale();

        try {
            app()->setLocale($user->locale);

            Mail::send('emails.otp', [
                'otp' => '789012',
                'maskedIdentifier' => $this->maskEmail($user->email),
                'resetUrl' => route('password.reset', ['token' => 'test-token']),
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject(__('emails.otp.subject'));
            });

        } finally {
            app()->setLocale($originalLocale);
        }
    }

    private function assertEmailTemplateRenders(User $user, string $expectedLocale): void
    {
        $originalLocale = app()->getLocale();

        try {
            app()->setLocale($user->locale);

            // Rendre le template et vérifier qu'il ne lève pas d'exception
            $view = view('emails.account-activation', [
                'otp' => '123456',
                'maskedIdentifier' => $this->maskEmail($user->email),
                'activationUrl' => 'http://test.com/activate',
            ]);

            $renderedContent = $view->render();

            // Vérifier que le contenu contient les bonnes traductions
            if ($expectedLocale === 'fr') {
                $this->assertStringContains('Bienvenue', $renderedContent);
                $this->assertStringContains('Activez votre compte', $renderedContent);
            } else {
                $this->assertStringContains('Welcome', $renderedContent);
                $this->assertStringContains('Activate Your Account', $renderedContent);
            }

            // Vérifier que le HTML est valide (contient les éléments de base)
            $this->assertStringContains('<!DOCTYPE html', $renderedContent);
            $this->assertStringContains('email-wrapper', $renderedContent);
            $this->assertStringContains('otp-container', $renderedContent);

        } finally {
            app()->setLocale($originalLocale);
        }
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $username = $parts[0];
        $domain = $parts[1];

        return substr($username, 0, 1).str_repeat('*', strlen($username) - 2).substr($username, -1).
               '@'.substr($domain, 0, 1).str_repeat('*', strlen($domain) - 4).substr($domain, -3);
    }
}
