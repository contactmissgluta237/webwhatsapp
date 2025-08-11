<?php

namespace Tests\Browser\Support\Pages;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class EditBottleTypePage extends Page
{
    private $bottleTypeId;

    public function __construct(?int $bottleTypeId = null)
    {
        $this->bottleTypeId = $bottleTypeId;
    }

    public function url(): string
    {
        return '/bottles/types/edit/'.$this->bottleTypeId;
    }

    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url())
            ->assertSee('Modifier le Type de Bouteille'); // Assurez-vous que c'est le titre de la page
    }

    public function elements(): array
    {
        return [
            // Ajoutez des sélecteurs d'éléments spécifiques à cette page si nécessaire
            // Exemple pour un champ de formulaire:
            // '@name-input' => 'input[name="name"]',
            // '@submit-button' => 'button[type="submit"]',
        ];
    }
}
