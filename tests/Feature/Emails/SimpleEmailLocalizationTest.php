<?php

declare(strict_types=1);

namespace Tests\Feature\Emails;

use Tests\TestCase;

class SimpleEmailLocalizationTest extends TestCase
{
    public function test_french_translations_are_correctly_loaded(): void
    {
        app()->setLocale('fr');

        $translations = [
            'emails.account_activation.subject' => 'Activation de votre compte',
            'emails.account_activation.welcome' => 'Bienvenue ! Votre compte a été créé avec succès.',
            'emails.otp.subject' => 'Code de vérification',
            'emails.security.title' => '🔒 Sécurité',
        ];

        foreach ($translations as $key => $expected) {
            $this->assertEquals($expected, __($key), "La traduction française pour '{$key}' est incorrecte");
        }
    }

    public function test_english_translations_are_correctly_loaded(): void
    {
        app()->setLocale('en');

        $translations = [
            'emails.account_activation.subject' => 'Account Activation',
            'emails.account_activation.welcome' => 'Welcome! Your account has been created successfully.',
            'emails.otp.subject' => 'Verification Code',
            'emails.security.title' => '🔒 Security',
        ];

        foreach ($translations as $key => $expected) {
            $this->assertEquals($expected, __($key), "La traduction anglaise pour '{$key}' est incorrecte");
        }
    }

    public function test_locale_switching_works_correctly_for_emails(): void
    {
        // Test français
        app()->setLocale('fr');
        $frenchSubject = __('emails.account_activation.subject');
        $frenchWelcome = __('emails.account_activation.welcome');

        // Test anglais
        app()->setLocale('en');
        $englishSubject = __('emails.account_activation.subject');
        $englishWelcome = __('emails.account_activation.welcome');

        // Vérifications
        $this->assertEquals('Activation de votre compte', $frenchSubject);
        $this->assertEquals('Bienvenue ! Votre compte a été créé avec succès.', $frenchWelcome);
        $this->assertEquals('Account Activation', $englishSubject);
        $this->assertEquals('Welcome! Your account has been created successfully.', $englishWelcome);

        // S'assurer que les traductions sont différentes
        $this->assertNotEquals($frenchSubject, $englishSubject);
        $this->assertNotEquals($frenchWelcome, $englishWelcome);
    }

    public function test_dynamic_parameters_work_in_translations(): void
    {
        // Test français
        app()->setLocale('fr');
        $frenchText = __('emails.account_activation.instructions', ['identifier' => 'test@example.com']);
        $this->assertStringContainsString('test@example.com', $frenchText);
        $this->assertStringContainsString('Pour activer', $frenchText);

        // Test anglais
        app()->setLocale('en');
        $englishText = __('emails.account_activation.instructions', ['identifier' => 'test@example.com']);
        $this->assertStringContainsString('test@example.com', $englishText);
        $this->assertStringContainsString('To activate', $englishText);
    }

    public function test_email_templates_render_without_errors(): void
    {
        $testData = [
            'otp' => '123456',
            'maskedIdentifier' => 't***@e***.com',
            'activationUrl' => 'http://test.com/activate',
        ];

        // Test rendu français
        app()->setLocale('fr');
        $frenchView = view('emails.account-activation', $testData);
        $frenchContent = $frenchView->render();
        $this->assertStringContainsString('<!DOCTYPE html', $frenchContent);
        $this->assertStringContainsString('Bienvenue', $frenchContent);

        // Test rendu anglais
        app()->setLocale('en');
        $englishView = view('emails.account-activation', $testData);
        $englishContent = $englishView->render();
        $this->assertStringContainsString('<!DOCTYPE html', $englishContent);
        $this->assertStringContainsString('Welcome', $englishContent);

        // S'assurer que les contenus sont différents
        $this->assertNotEquals($frenchContent, $englishContent);
    }
}
