<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SimpleLoginTest extends DuskTestCase
{
    /**
     * Test simple - juste voir la page
     */
    public function test_just_visit_login_page()
    {
        $this->browse(function (Browser $browser) {
            echo "🚀 Début du test...\n";

            $browser->visit('/login')
                ->pause(5000) // 5 secondes pour voir
                ->screenshot('simple_login_page');

            echo "✅ Page visitée et capture prise\n";
        });
    }

    /**
     * Test - vérifier les éléments un par un
     */
    public function test_check_elements_one_by_one()
    {
        $this->browse(function (Browser $browser) {
            echo "🔍 Recherche des éléments...\n";

            $browser->visit('/login')
                ->pause(3000)
                ->screenshot('before_element_check');

            // Vérifier si le body existe
            try {
                $browser->assertPresent('body');
                echo "✅ Body trouvé\n";
            } catch (\Exception $e) {
                echo '❌ Body non trouvé: '.$e->getMessage()."\n";
            }

            // Vérifier si le formulaire existe
            try {
                $browser->assertPresent('form');
                echo "✅ Form trouvé\n";
            } catch (\Exception $e) {
                echo '❌ Form non trouvé: '.$e->getMessage()."\n";
            }

            // Vérifier les inputs avec différents sélecteurs
            $selectors = [
                'input[wire\\:model="identifier"]',
                'input[wire\\:model\\:live="identifier"]',
                'input[wire\\:model\\.live="identifier"]',
                'input[type="text"]',
                'input.form-control',
            ];

            foreach ($selectors as $selector) {
                try {
                    $browser->assertPresent($selector);
                    echo "✅ Sélecteur trouvé: {$selector}\n";
                    break;
                } catch (\Exception $e) {
                    echo "❌ Sélecteur non trouvé: {$selector}\n";
                }
            }

            $browser->pause(5000)
                ->screenshot('after_element_check');
        });
    }
}
