<?php

namespace Tests\Browser\Support\Pages;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class AccessoryPage extends Page
{
    public function url(): string
    {
        return '/accessories';
    }

    public function elements(): array
    {
        return [
            '@create-accessory-button' => 'a[href="/accessories/create"]',
            '@accessory-table' => 'table',
            '@accessory-name-field' => 'input[name="name"], input[wire:model="name"]',
            '@accessory-description-field' => 'textarea[name="description"], textarea[wire:model="description"]',
            '@accessory-price-field' => 'input[name="price"], input[wire:model="price"]',
            '@submit-button' => 'button[type="submit"]',
            '@edit-link' => 'a[href*="/accessories/edit/"]',
        ];
    }

    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function assertSeeAccessoryList(Browser $browser): self
    {
        $browser->assertSee('Liste des accessoires')
            ->assertPresent('@accessory-table');

        return $this;
    }

    public function assertSeeCreateForm(Browser $browser): self
    {
        $browser->assertSee('Créer un accessoire')
            ->assertPresent('@accessory-name-field')
            ->assertPresent('@submit-button');

        return $this;
    }

    public function assertSeeEditForm(Browser $browser, ?string $accessoryName = null): self
    {
        $browser->assertSee('Modifier l\'accessoire')
            ->assertPresent('@accessory-name-field')
            ->assertPresent('@submit-button');
        if ($accessoryName) {
            $browser->assertInputValue('@accessory-name-field', $accessoryName);
        }

        return $this;
    }
}
