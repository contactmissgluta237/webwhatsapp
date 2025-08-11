<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class ActivationPage extends Page
{
    protected string $identifier;

    public function __construct(string $identifier = 'test@example.com')
    {
        $this->identifier = $identifier;
    }

    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/account/activate/'.$this->identifier;
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathBeginsWith('/account/activate/');
    }

    /**
     * Get the element shortcuts for the page.
     */
    public function elements(): array
    {
        return [
            '@otpInput' => 'input[name="otp"], input[name="activation_code"]',
            '@activateButton' => 'button[type="submit"], button:contains("Activer")',
            '@resendButton' => 'button:contains("Renvoyer"), a:contains("Renvoyer")',
            '@errorMessage' => '.text-red-500, .alert-danger, [class*="error"]',
            '@successMessage' => '.text-green-500, .alert-success, [class*="success"]',
            '@loadingSpinner' => '.loading, .spinner, [wire\\:loading]',
        ];
    }

    /**
     * Activate account with OTP code
     */
    public function activateAccount(Browser $browser, string $otpCode): self
    {
        $browser->type('@otpInput', $otpCode)
            ->click('@activateButton');

        return $this;
    }

    /**
     * Resend activation code
     */
    public function resendActivationCode(Browser $browser): self
    {
        $browser->click('@resendButton');

        return $this;
    }

    /**
     * Wait for activation result
     */
    public function waitForActivationResult(Browser $browser): self
    {
        // Attendre soit une redirection soit un message d'erreur/succès
        $browser->waitUntil(
            'window.location.pathname !== window.location.pathname || document.querySelector(\'.text-red-500, .alert-danger, [class*="error"], .text-green-500, .alert-success, [class*="success"]\') !== null',
            10
        );

        return $this;
    }
}
