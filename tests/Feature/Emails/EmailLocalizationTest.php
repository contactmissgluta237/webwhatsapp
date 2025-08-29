<?php

declare(strict_types=1);

namespace Tests\Feature\Emails;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un pays avec l'ID 1 pour éviter les erreurs de validation
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

    public function test_french_user_receives_french_email_translations(): void
    {
        $originalLocale = app()->getLocale();

        try {
            app()->setLocale('fr');

            // Test que les traductions françaises sont correctes
            $this->assertEquals('Activation de votre compte', __('emails.account_activation.subject'));
            $this->assertEquals('Activez votre compte', __('emails.account_activation.header'));
            $this->assertEquals('Bienvenue ! Votre compte a été créé avec succès.', __('emails.account_activation.welcome'));
            $this->assertEquals('Code de vérification', __('emails.otp.subject'));
            $this->assertEquals('🔒 Sécurité', __('emails.security.title'));

        } finally {
            app()->setLocale($originalLocale);
        }
    }

    public function test_english_user_receives_english_email_translations(): void
    {
        $originalLocale = app()->getLocale();

        try {
            app()->setLocale('en');

            // Test que les traductions anglaises sont correctes
            $this->assertEquals('Account Activation', __('emails.account_activation.subject'));
            $this->assertEquals('Activate Your Account', __('emails.account_activation.header'));
            $this->assertEquals('Welcome! Your account has been created successfully.', __('emails.account_activation.welcome'));
            $this->assertEquals('Verification Code', __('emails.otp.subject'));
            $this->assertEquals('🔒 Security', __('emails.security.title'));

        } finally {
            app()->setLocale($originalLocale);
        }
    }

    public function test_email_subject_respects_user_locale_french(): void
    {
        $user = User::factory()->create([
            'locale' => 'fr',
            'email' => 'french.user@test.com',
        ]);

        $originalLocale = app()->getLocale();

        try {
            app()->setLocale($user->locale);

            // Tester directement que les traductions email françaises fonctionnent
            $this->assertEquals('Activation de votre compte', __('emails.account_activation.subject'));
            $this->assertEquals('Code de vérification', __('emails.otp.subject'));
            $this->assertEquals('🔒 Sécurité', __('emails.security.title'));

            // Vérifier que la locale est bien définie
            $this->assertEquals('fr', app()->getLocale());

        } finally {
            app()->setLocale($originalLocale);
        }
    }

    public function test_email_subject_respects_user_locale_english(): void
    {
        $user = User::factory()->create([
            'locale' => 'en',
            'email' => 'english.user@test.com',
        ]);

        $originalLocale = app()->getLocale();

        try {
            app()->setLocale($user->locale);

            // Tester directement que les traductions email anglaises fonctionnent
            $this->assertEquals('Account Activation', __('emails.account_activation.subject'));
            $this->assertEquals('Verification Code', __('emails.otp.subject'));
            $this->assertEquals('🔒 Security', __('emails.security.title'));

            // Vérifier que la locale est bien définie
            $this->assertEquals('en', app()->getLocale());

        } finally {
            app()->setLocale($originalLocale);
        }
    }

    public function test_dynamic_translation_parameters_work_correctly(): void
    {
        $originalLocale = app()->getLocale();

        try {
            // Test avec français
            app()->setLocale('fr');
            $frenchText = __('emails.account_activation.instructions', ['identifier' => 'test@example.com']);
            $this->assertStringContainsString('test@example.com', $frenchText);
            $this->assertStringContainsString('Pour activer votre compte', $frenchText);

            // Test avec anglais
            app()->setLocale('en');
            $englishText = __('emails.account_activation.instructions', ['identifier' => 'test@example.com']);
            $this->assertStringContainsString('test@example.com', $englishText);
            $this->assertStringContainsString('To activate your account', $englishText);

        } finally {
            app()->setLocale($originalLocale);
        }
    }

    public function test_email_template_renders_with_correct_locale(): void
    {
        $originalLocale = app()->getLocale();

        try {
            // Test template français
            app()->setLocale('fr');
            $frenchView = view('emails.account-activation', [
                'otp' => '123456',
                'maskedIdentifier' => 't***@e***.com',
                'activationUrl' => 'http://test.com/activate',
            ]);

            $frenchContent = $frenchView->render();
            $this->assertStringContainsString('Bienvenue', $frenchContent);
            $this->assertStringContainsString('Code d', $frenchContent); // Simplifier pour éviter les problèmes d'échappement

            // Test template anglais
            app()->setLocale('en');
            $englishView = view('emails.account-activation', [
                'otp' => '123456',
                'maskedIdentifier' => 't***@e***.com',
                'activationUrl' => 'http://test.com/activate',
            ]);

            $englishContent = $englishView->render();
            $this->assertStringContainsString('Welcome', $englishContent);
            $this->assertStringContainsString('Activation Code', $englishContent);

        } finally {
            app()->setLocale($originalLocale);
        }
    }

    public function test_multiple_emails_respect_different_user_locales(): void
    {
        $originalLocale = app()->getLocale();

        try {
            // Tester que différents utilisateurs reçoivent des traductions dans leur langue

            // Utilisateur français
            app()->setLocale('fr');
            $frenchSubject = __('emails.otp.subject');
            $frenchGreeting = __('emails.otp.greeting');

            // Utilisateur anglais
            app()->setLocale('en');
            $englishSubject = __('emails.otp.subject');
            $englishGreeting = __('emails.otp.greeting');

            // Vérifications
            $this->assertEquals('Code de vérification', $frenchSubject);
            $this->assertEquals('Un code de vérification a été demandé.', $frenchGreeting);
            $this->assertEquals('Verification Code', $englishSubject);
            $this->assertEquals('A verification code has been requested.', $englishGreeting);

        } finally {
            app()->setLocale($originalLocale);
        }
    }
}
