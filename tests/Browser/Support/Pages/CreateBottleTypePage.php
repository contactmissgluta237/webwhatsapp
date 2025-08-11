<?php

namespace Tests\Browser\Support\Pages;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class CreateBottleTypePage extends Page
{
    public function url(): string
    {
        return '/bottles/types/create';
    }

    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url())
            ->assertSee('Créer un Type de Bouteille'); // Assurez-vous que c'est le titre de la page
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
