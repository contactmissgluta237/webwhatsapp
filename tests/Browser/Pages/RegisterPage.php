<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class RegisterPage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/register';
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url())
            ->assertSee('Inscription');
    }

    /**
     * Get the element shortcuts for the page.
     */
    public function elements(): array
    {
        return [
            '@firstName' => 'input[name="first_name"]',
            '@lastName' => 'input[name="last_name"]',
            '@email' => 'input[name="email"]',
            '@phoneNumber' => 'input[name="phone_number"]',
            '@password' => 'input[name="password"]',
            '@passwordConfirmation' => 'input[name="password_confirmation"]',
            '@terms' => 'input[name="terms"]',
            '@submitButton' => 'button[type="submit"], button:contains("Créer mon compte")',
            '@errorMessage' => '.text-red-500, .alert-danger, [class*="error"]',
            '@loadingSpinner' => '.loading, .spinner, [wire\\:loading]',
        ];
    }

    /**
     * Fill the registration form with test data
     */
    public function fillRegistrationForm(Browser $browser, array $overrides = []): Browser
    {
        $data = array_merge([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone_number' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => true,
        ], $overrides);

        $browser->type('@firstName', $data['first_name'])
            ->type('@lastName', $data['last_name'])
            ->type('@email', $data['email']);

        if (! empty($data['phone_number'])) {
            $browser->type('@phoneNumber', $data['phone_number']);
        }

        $browser->type('@password', $data['password'])
            ->type('@passwordConfirmation', $data['password_confirmation']);

        if ($data['terms']) {
            $browser->check('@terms');
        }

        return $browser;
    }

    /**
     * Submit the registration form
     */
    public function submitForm(Browser $browser): Browser
    {
        $browser->click('@submitButton');

        return $browser;
    }

    /**
     * Wait for form submission result
     */
    public function waitForSubmissionResult(Browser $browser): Browser
    {
        // Attendre soit une redirection soit un message d'erreur
        $browser->waitUntil(
            'window.location.pathname !== "/register" || document.querySelector(\'.text-red-500, .alert-danger, [class*="error"]\') !== null',
            5
        );

        return $browser;
    }
}
