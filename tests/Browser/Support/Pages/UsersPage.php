<?php

namespace Tests\Browser\Support\Pages;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class UsersPage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/users';
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url());
    }

    /**
     * Get the element shortcuts for the page.
     */
    public function elements(): array
    {
        return [
            '@create-user-button' => 'a[href="/users/create"]',
            '@user-table' => '#users-table',
            '@name-field' => 'input[name="name"]',
            '@email-field' => 'input[name="email"]',
            '@password-field' => 'input[name="password"]',
            '@password-confirmation-field' => 'input[name="password_confirmation"]',
            '@save-button' => 'button[type="submit"]',
            '@edit-button' => '.edit-user-button', // Assuming a class for edit buttons
            '@delete-button' => '.delete-user-button', // Assuming a class for delete buttons
            '@confirm-delete-button' => '#confirm-delete-button', // Assuming a confirmation button for delete
            '@success-message' => '.alert-success', // Assuming a success message class
            '@error-message' => '.alert-danger', // Assuming an error message class
        ];
    }

    /**
     * Navigate to the create user page.
     */
    public function navigateToCreateUser(Browser $browser): void
    {
        $browser->click('@create-user-button')
            ->assertPathIs('/users/create');
    }

    /**
     * Fill and submit the user form.
     */
    public function fillAndSubmitUserForm(Browser $browser, array $userData): void
    {
        if (isset($userData['name'])) {
            $browser->type('@name-field', $userData['name']);
        }
        if (isset($userData['email'])) {
            $browser->type('@email-field', $userData['email']);
        }
        if (isset($userData['password'])) {
            $browser->type('@password-field', $userData['password']);
        }
        if (isset($userData['password_confirmation'])) {
            $browser->type('@password-confirmation-field', $userData['password_confirmation']);
        }
        $browser->press('@save-button');
    }

    /**
     * Assert user is present in the table.
     */
    public function assertUserInTable(Browser $browser, string $email): void
    {
        $browser->assertSeeIn('@user-table', $email);
    }

    /**
     * Assert user is not present in the table.
     */
    public function assertUserNotInTable(Browser $browser, string $email): void
    {
        $browser->assertDontSeeIn('@user-table', $email);
    }

    /**
     * Click edit button for a specific user.
     */
    public function clickEditUser(Browser $browser, string $email): void
    {
        $browser->waitForText($email)
            ->click('//td[contains(text(), "'.$email.'")]/ancestor::tr//a[contains(@class, "edit-user-button")]');
    }

    /**
     * Click delete button for a specific user and confirm.
     */
    public function deleteUser(Browser $browser, string $email): void
    {
        $browser->waitForText($email)
            ->click('//td[contains(text(), "'.$email.'")]/ancestor::tr//button[contains(@class, "delete-user-button")]')
            ->waitFor('@confirm-delete-button')
            ->click('@confirm-delete-button');
    }
}
