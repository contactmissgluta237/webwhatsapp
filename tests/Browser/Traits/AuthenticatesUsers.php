<?php

namespace Tests\Browser\Traits;

use Laravel\Dusk\Browser;

trait AuthenticatesUsers
{
    /**
     * Connecte l'utilisateur en tant qu'administrateur
     */
    protected function loginAsAdmin(Browser $browser): Browser
    {
        echo "🔑 Connexion admin en cours...\n";

        return $browser->visit('/login')
            ->waitFor('input[wire\\:model="identifier"]', 10)
            ->type('input[wire\\:model="identifier"]', env('ADMIN_EMAIL'))
            ->type('input[wire\\:model="password"]', env('ADMIN_PASSWORD'))
            ->press('Se connecter')
            ->pause(5000);
    }

    /**
     * Accède au dashboard
     */
    protected function visitDashboard(Browser $browser): Browser
    {
        return $browser->visit('/dashboard')->pause(3000);
    }

    /**
     * Se connecte et accède au dashboard
     */
    protected function loginAndVisitDashboard(Browser $browser): Browser
    {
        return $this->loginAsAdmin($browser)
            ->visit('/dashboard')
            ->pause(3000);
    }
}
