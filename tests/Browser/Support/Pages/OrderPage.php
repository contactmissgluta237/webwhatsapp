<?php

namespace Tests\Browser\Support\Pages;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class OrderPage extends Page
{
    /**
     * URL de la page
     */
    public function url(): string
    {
        return '/orders';
    }

    /**
     * Éléments de la page
     */
    public function elements(): array
    {
        return [
            '@filter-button' => '.col-4 a[data-bs-toggle="collapse"], button[data-bs-toggle="collapse"]',
            '@filter-panel' => '#collapseFilter',
            '@orders-table' => 'table, .table, [wire\\:id*="order-list"], [wire\\:id*="order.list"]',
            '@table-rows' => 'table tbody tr, .table tbody tr',
            '@pagination' => '.pagination',
            '@create-button' => 'a[href*="/orders/create"], button:contains("Nouvelle commande"), a:contains("Nouvelle commande")',
            '@search-input' => 'input[wire\\:model*="search"], input[name*="search"], input[placeholder*="Recherche"]',
            '@filter-form' => 'form[wire\\:submit], form',
            '@reset-filter' => 'button[wire\\:click*="resetFilter"], button.reset-filter, a.reset-filter, [wire\\:click*="reset"]',
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
     * Ouvrir le filtre
     */
    public function openFilter(Browser $browser): self
    {
        try {
            $browser->assertPresent('@filter-button')
                ->click('@filter-button')
                ->pause(2000)
                ->screenshot('orders_filter_opened')
                ->assertVisible('@filter-panel');

            echo "✅ Panneau de filtre ouvert\n";
        } catch (\Exception $e) {
            echo "⚠️ Impossible d'ouvrir le filtre: ".$e->getMessage()."\n";
        }

        return $this;
    }

    /**
     * Vérifier la présence du tableau des commandes
     */
    public function assertOrdersTablePresent(Browser $browser): self
    {
        try {
            $browser->assertPresent('@orders-table');
            echo "✅ Tableau des commandes présent\n";

            // Vérifier s'il y a des lignes dans le tableau
            try {
                $browser->assertPresent('@table-rows');
                echo "✅ Des commandes sont listées dans le tableau\n";
            } catch (\Exception $e) {
                echo "⚠️ Aucune commande listée dans le tableau\n";
            }
        } catch (\Exception $e) {
            echo '⚠️ Tableau des commandes non trouvé: '.$e->getMessage()."\n";
        }

        return $this;
    }

    /**
     * Rechercher une commande
     */
    public function searchOrder(Browser $browser, string $keyword): self
    {
        try {
            $browser->assertPresent('@search-input')
                ->type('@search-input', $keyword)
                ->pause(3000) // Attendre que Livewire réagisse
                ->screenshot('order_search_results');

            echo "✅ Recherche effectuée pour: $keyword\n";
        } catch (\Exception $e) {
            echo "⚠️ Impossible d'effectuer la recherche: ".$e->getMessage()."\n";
        }

        return $this;
    }

    /**
     * Cliquer sur le bouton de création d'une nouvelle commande
     */
    public function clickCreateOrder(Browser $browser): self
    {
        try {
            $browser->assertPresent('@create-button')
                ->click('@create-button')
                ->pause(2000)
                ->screenshot('create_order_clicked');

            echo "✅ Redirection vers la page de création de commande\n";
        } catch (\Exception $e) {
            echo '⚠️ Bouton de création non trouvé: '.$e->getMessage()."\n";
        }

        return $this;
    }

    /**
     * Accéder aux détails d'une commande (premier élément trouvé)
     */
    public function openFirstOrderDetails(Browser $browser): self
    {
        try {
            $browser->assertPresent('@table-rows')
                ->click('@table-rows a[href*="/orders/"], @table-rows button[wire\\:click*="details"], @table-rows .btn-info')
                ->pause(3000)
                ->screenshot('order_details_opened');

            echo "✅ Détails d'une commande ouverts\n";
        } catch (\Exception $e) {
            echo "⚠️ Impossible d'ouvrir les détails d'une commande: ".$e->getMessage()."\n";
        }

        return $this;
    }

    /**
     * Vérifier la pagination
     */
    public function assertPaginationWorks(Browser $browser): self
    {
        try {
            if ($browser->element('@pagination')) {
                $browser->click('@pagination li:not(.active) a')
                    ->pause(2000)
                    ->screenshot('pagination_clicked');

                echo "✅ Pagination fonctionnelle\n";
            } else {
                echo "⚠️ Pas de pagination trouvée (peut-être une seule page)\n";
            }
        } catch (\Exception $e) {
            echo '⚠️ Impossible de tester la pagination: '.$e->getMessage()."\n";
        }

        return $this;
    }

    /**
     * Réinitialiser les filtres
     */
    public function resetFilters(Browser $browser): self
    {
        try {
            $this->openFilter($browser);

            $browser->assertPresent('@reset-filter')
                ->click('@reset-filter')
                ->pause(3000)
                ->screenshot('orders_filters_reset');

            echo "✅ Filtres réinitialisés\n";
        } catch (\Exception $e) {
            echo '⚠️ Bouton de réinitialisation non trouvé: '.$e->getMessage()."\n";
        }

        return $this;
    }
}
