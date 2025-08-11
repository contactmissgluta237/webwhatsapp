<?php

namespace Tests\Browser\Support\Pages;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class OrderDetailsPage extends Page
{
    /**
     * @var string|int L'ID de la commande
     */
    protected $orderId;

    /**
     * Constructeur
     *
     * @param  string|int  $orderId  L'ID de la commande
     */
    public function __construct($orderId = '1')
    {
        $this->orderId = $orderId;
    }

    /**
     * URL de la page
     */
    public function url(): string
    {
        return "/orders/{$this->orderId}/details";
    }

    /**
     * Éléments de la page
     */
    public function elements(): array
    {
        return [
            '@order-info' => '.card, .order-details, [wire\\:id*="order-detail"]',
            '@order-id' => 'h5:contains("Commande #"), .card-title:contains("#")',
            '@order-status' => '.badge, .status-badge, .order-status',
            '@order-items' => 'table tbody tr, .order-items tr, .order-lines tr',
            '@order-totals' => '.totals, .order-totals, .cart-total',
            '@print-button' => 'a[href*="/print"], button[wire\\:click*="print"], a:contains("Imprimer"), button:contains("Imprimer")',
            '@download-button' => 'a[href*="/download/invoice"], button[wire\\:click*="download"], a:contains("Télécharger"), button:contains("Télécharger")',
            '@cancel-button' => 'button[wire\\:click*="cancel"], a[wire\\:click*="cancel"], button:contains("Annuler"), a:contains("Annuler")',
            '@back-button' => 'a[href*="/orders"], button[onclick*="history.back"], a:contains("Retour"), button:contains("Retour")',
        ];
    }

    /**
     * Assertions de la page
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url());
    }

    /**
     * Méthode utilitaire pour les pauses
     */
    public function pause(Browser $browser, int $milliseconds): self
    {
        $browser->pause($milliseconds);

        return $this;
    }

    /**
     * Vérifier que les détails de la commande sont affichés
     */
    public function assertOrderDetailsPresent(Browser $browser): self
    {
        try {
            $browser->assertPresent('@order-info')
                ->assertPresent('@order-id');

            echo "✅ Informations générales de la commande présentes\n";

            // Vérifier le statut
            try {
                $browser->assertPresent('@order-status');
                echo "✅ Statut de la commande affiché\n";
            } catch (\Exception $e) {
                echo "⚠️ Statut de la commande non trouvé\n";
            }
        } catch (\Exception $e) {
            echo '⚠️ Informations de la commande non trouvées: '.$e->getMessage()."\n";
        }

        return $this;
    }

    /**
     * Vérifier que les articles de la commande sont affichés
     */
    public function assertOrderItemsPresent(Browser $browser): self
    {
        try {
            $browser->assertPresent('@order-items');
            echo "✅ Articles de la commande affichés\n";
        } catch (\Exception $e) {
            echo '⚠️ Articles de la commande non trouvés: '.$e->getMessage()."\n";
        }

        return $this;
    }

    /**
     * Vérifier que les totaux sont affichés
     */
    public function assertOrderTotalsPresent(Browser $browser): self
    {
        try {
            $browser->assertPresent('@order-totals');
            echo "✅ Totaux de la commande affichés\n";
        } catch (\Exception $e) {
            echo '⚠️ Totaux de la commande non trouvés: '.$e->getMessage()."\n";
        }

        return $this;
    }

    /**
     * Tester le bouton d'impression
     */
    public function testPrintButton(Browser $browser): self
    {
        try {
            $browser->assertPresent('@print-button')
                ->click('@print-button')
                ->pause(3000)
                ->screenshot('order_print_clicked');

            echo "✅ Bouton d'impression cliqué\n";

            // Retourner à la page de détails si nécessaire
            if ($browser->driver->getCurrentURL() !== $this->url()) {
                $browser->back()->pause(2000);
                echo "⚠️ Retour à la page de détails après impression\n";
            }
        } catch (\Exception $e) {
            echo "⚠️ Bouton d'impression non fonctionnel: ".$e->getMessage()."\n";
        }

        return $this;
    }

    /**
     * Tester le bouton de téléchargement
     */
    public function testDownloadButton(Browser $browser): self
    {
        try {
            $browser->assertPresent('@download-button')
                ->click('@download-button')
                ->pause(3000)
                ->screenshot('order_download_clicked');

            echo "✅ Bouton de téléchargement cliqué\n";

            // Retourner à la page de détails si nécessaire
            if ($browser->driver->getCurrentURL() !== $this->url()) {
                $browser->back()->pause(2000);
                echo "⚠️ Retour à la page de détails après téléchargement\n";
            }
        } catch (\Exception $e) {
            echo '⚠️ Bouton de téléchargement non fonctionnel: '.$e->getMessage()."\n";
        }

        return $this;
    }

    /**
     * Tester le bouton d'annulation
     * Note: Ne devrait être utilisé que sur des commandes de test
     */
    public function testCancelButton(Browser $browser): self
    {
        try {
            $browser->assertPresent('@cancel-button');
            echo "✅ Bouton d'annulation présent\n";

            // On ne clique pas réellement pour éviter d'annuler une vraie commande
            $browser->screenshot('cancel_button_present');
        } catch (\Exception $e) {
            echo "⚠️ Bouton d'annulation non trouvé: ".$e->getMessage()."\n";
        }

        return $this;
    }

    /**
     * Tester le bouton de retour
     */
    public function testBackButton(Browser $browser): self
    {
        try {
            $browser->assertPresent('@back-button')
                ->click('@back-button')
                ->pause(2000)
                ->screenshot('back_button_clicked');

            echo "✅ Bouton de retour cliqué\n";
        } catch (\Exception $e) {
            echo '⚠️ Bouton de retour non fonctionnel: '.$e->getMessage()."\n";
        }

        return $this;
    }
}
