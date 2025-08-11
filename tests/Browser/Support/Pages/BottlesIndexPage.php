<?php

namespace Tests\Browser\Support\Pages;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class BottlesIndexPage extends Page
{
    public function url(): string
    {
        return '/bottles';
    }

    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url())
            ->assertSee('Liste des Bouteilles'); // Assurez-vous que c'est le titre de la page
    }

    public function elements(): array
    {
        return [
            // Ajoutez des sélecteurs d'éléments spécifiques à cette page si nécessaire
            // '@example-element' => '#example-id',
        ];
    }
}
