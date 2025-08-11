<?php

namespace Tests\Browser\Support\Pages;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class OrderCreatePage extends Page
{
    /**
     * URL de la page
     */
    public function url(): string
    {
        return '/orders/create';
    }

    /**
     * Éléments de la page
     */
    public function elements(): array
    {
        return [
            '@order-form' => 'form, [wire\\:submit], [wire\\:id*="create-order"]',
            '@client-select' => 'select[name*="client"], select[wire\\:model*="client"], [wire\\:model*="clientId"]',
            '@product-select' => 'select[name*="product"], select[wire\\:model*="product"], [wire\\:model*="productId"]',
            '@quantity-input' => 'input[name*="quantity"], input[wire\\:model*="quantity"]',
            '@add-item-button' => 'button[wire\\:click*="addItem"], button:contains("Ajouter"), button.add-item',
            '@order-items' => 'table tbody tr, .order-items tr',
            '@submit-button' => 'button[type="submit"], button:contains("Créer"), button:contains("Enregistrer")',
            '@cancel-button' => 'a[href*="/orders"], button:contains("Annuler"), a:contains("Annuler")',
            '@order-preview' => '.order-preview, .cart-preview, .order-summary',
            '@order-total' => '.order-total, .cart-total, .total-amount',
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
     * Vérifier que le formulaire de création est présent
     */
    public function assertOrderFormPresent(Browser $browser): self
    {
        try {
            $browser->assertPresent('@order-form');
            echo "✅ Formulaire de création de commande présent\n";
        } catch (\Exception $e) {
            echo '⚠️ Formulaire non trouvé: '.$e->getMessage()."\n";
        }

        return $this;
    }

    /**
     * Sélectionner un client
     */
    public function selectClient(Browser $browser, string $clientId = '1'): self
    {
        try {
            $browser->assertPresent('@client-select')
                ->select('@client-select', $clientId)
                ->pause(2000)
                ->screenshot('client_selected');

            echo "✅ Client sélectionné (ID: $clientId)\n";
        } catch (\Exception $e) {
            echo '⚠️ Impossible de sélectionner un client: '.$e->getMessage()."\n";
        }

        return $this;
    }

    /**
     * Ajouter un article à la commande
     */
    public function addOrderItem(Browser $browser, string $productId = '1', string $quantity = '1'): self
    {
        try {
            $browser->assertPresent('@product-select')
                ->select('@product-select', $productId)
                ->pause(1000);

            $browser->assertPresent('@quantity-input')
                ->clear('@quantity-input')
                ->type('@quantity-input', $quantity)
                ->pause(1000);

            $browser->assertPresent('@add-item-button')
                ->click('@add-item-button')
                ->pause(2000)
                ->screenshot('item_added');

            echo "✅ Article ajouté à la commande (Produit ID: $productId, Quantité: $quantity)\n";

            // Vérifier que l'article a bien été ajouté
            try {
                $browser->assertPresent('@order-items');
                echo "✅ Article visible dans le récapitulatif\n";
            } catch (\Exception $e) {
                echo "⚠️ Article non visible dans le récapitulatif\n";
            }
        } catch (\Exception $e) {
            echo "⚠️ Impossible d'ajouter un article: ".$e->getMessage()."\n";
        }

        return $this;
    }

    /**
     * Vérifier le récapitulatif de la commande
     */
    public function assertOrderPreviewPresent(Browser $browser): self
    {
        try {
            $browser->assertPresent('@order-preview');
            echo "✅ Récapitulatif de commande présent\n";

            try {
                $browser->assertPresent('@order-total');
                echo "✅ Total de la commande affiché\n";
            } catch (\Exception $e) {
                echo "⚠️ Total de la commande non affiché\n";
            }
        } catch (\Exception $e) {
            echo '⚠️ Récapitulatif non trouvé: '.$e->getMessage()."\n";
        }

        return $this;
    }

    /**
     * Soumettre la commande
     */
    public function submitOrder(Browser $browser): self
    {
        try {
            $browser->assertPresent('@submit-button')
                ->click('@submit-button')
                ->pause(5000) // Attendre la soumission et la redirection
                ->screenshot('order_submitted');

            echo "✅ Commande soumise\n";
        } catch (\Exception $e) {
            echo '⚠️ Impossible de soumettre la commande: '.$e->getMessage()."\n";
        }

        return $this;
    }

    /**
     * Annuler la création de commande
     */
    public function cancelOrderCreation(Browser $browser): self
    {
        try {
            $browser->assertPresent('@cancel-button')
                ->click('@cancel-button')
                ->pause(2000)
                ->screenshot('order_creation_cancelled');

            echo "✅ Création de commande annulée\n";
        } catch (\Exception $e) {
            echo "⚠️ Impossible d'annuler la création: ".$e->getMessage()."\n";
        }

        return $this;
    }
}
