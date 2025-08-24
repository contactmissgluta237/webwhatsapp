<?php

declare(strict_types=1);

require_once __DIR__.'/BaseTestIncomingMessage.php';

use App\Models\UserProduct;

/**
 * Test du flow IncomingMessage avec des produits
 */
class TestIncomingFlowWithProducts extends BaseTestIncomingMessage
{
    private array $testProducts = [];

    public function __construct()
    {
        parent::__construct('Test Flow With Products');
    }

    /**
     * Message de test spécifique pour ce flow
     */
    protected function getTestMessage(): string
    {
        return 'Bonjour, quel produit à moins de 200 mille pouvez-vous me recommander ?';
    }

    /**
     * Création de produits de test
     */
    protected function setupTestSpecificData(): void
    {
        $this->log('📦 Création de produits de test...');

        // Produits de test avec différentes catégories
        $productsData = [
            [
                'title' => 'Smartphone Samsung Galaxy A54',
                'description' => 'Smartphone dernière génération avec écran AMOLED 6.4", 128GB de stockage, triple caméra 50MP. Parfait pour photos et vidéos de qualité professionnelle.',
                'price' => 350000,
                'category' => 'Électronique',
                'is_active' => true,
            ],
            [
                'title' => 'Ordinateur Portable HP Pavilion',
                'description' => 'PC portable 15.6" Intel Core i5, 8GB RAM, SSD 512GB. Idéal pour le travail, les études et le divertissement. Autonomie 8 heures.',
                'price' => 720000,
                'category' => 'Informatique',
                'is_active' => true,
            ],
            [
                'title' => 'Casque Audio Sony WH-1000XM4',
                'description' => 'Casque sans fil avec réduction de bruit active. Qualité audio exceptionnelle, autonomie 30h, compatible multipoints. Parfait pour voyages.',
                'price' => 180000,
                'category' => 'Audio',
                'is_active' => true,
            ],
            [
                'title' => 'Montre Connectée Apple Watch SE',
                'description' => 'Montre intelligente avec suivi santé, GPS intégré, étanche 50m. Monitore rythme cardiaque, sommeil et activités sportives.',
                'price' => 280000,
                'category' => 'Wearables',
                'is_active' => true,
            ],
            [
                'title' => 'Tablette iPad Air 10.9"',
                'description' => 'Tablette premium avec écran Liquid Retina, puce M1, 256GB. Compatible Apple Pencil et Magic Keyboard. Parfaite pour créativité.',
                'price' => 650000,
                'category' => 'Tablettes',
                'is_active' => true,
            ],
        ];

        // Création des produits et association au compte WhatsApp
        foreach ($productsData as $productData) {
            $product = UserProduct::create([
                'user_id' => $this->testAccount->user_id,
                'title' => $productData['title'],
                'description' => $productData['description'],
                'price' => $productData['price'],
                'category' => $productData['category'],
                'is_active' => $productData['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Association via la table pivot
            $this->testAccount->userProducts()->attach($product->id);

            // Ajouter 2-3 médias de test pour chaque produit
            $this->addTestMediaToProduct($product, $productData['category']);

            $this->testProducts[] = $product;

            $this->log("✅ Produit créé: {$product->title} ({$product->price} FCFA)");
        }

        // Vérifier l'association avec le compte WhatsApp
        $associatedProducts = $this->testAccount->userProducts()->where('is_active', true)->get();
        $this->log("✅ {$associatedProducts->count()} produits associés au compte WhatsApp");

        if ($associatedProducts->isEmpty()) {
            throw new Exception('Aucun produit associé au compte WhatsApp');
        }
    }

    /**
     * Validations spécifiques pour le test avec produits
     */
    protected function performTestSpecificValidations(array $response): void
    {
        // Vérifier la présence des champs attendus
        if (! isset($response['products'])) {
            throw new Exception('Champ "products" manquant dans la réponse');
        }

        if (! is_array($response['products'])) {
            throw new Exception('Le champ "products" doit être un tableau');
        }

        // Pour ce test spécifique : on demande des produits à moins de 200k
        // On s'attend à recevoir EXACTEMENT 1 produit (le casque audio à 180k)
        $returnedProducts = $response['products'];
        $expectedProductCount = 1;
        $actualProductCount = count($returnedProducts);

        if ($actualProductCount !== $expectedProductCount) {
            throw new Exception("ERREUR: L'IA a retourné {$actualProductCount} produit(s), mais on s'attendait à exactement {$expectedProductCount} produit à moins de 200k");
        }

        // Vérifier que le produit retourné a la bonne structure
        $returnedProduct = $returnedProducts[0];
        if (! isset($returnedProduct['formattedProductMessage']) || ! isset($returnedProduct['mediaUrls'])) {
            throw new Exception('Structure de produit incorrecte: '.json_encode($returnedProduct));
        }

        // Vérifier que c'est bien le casque audio (le seul produit à moins de 200k)
        $expectedProductName = 'Casque Audio Sony WH-1000XM4';
        $expectedPrice = '180 000';
        $productMessage = $returnedProduct['formattedProductMessage'];

        if (! str_contains($productMessage, $expectedProductName)) {
            throw new Exception("ERREUR: Le produit retourné n'est pas le casque audio attendu. Message: ".$productMessage);
        }

        if (! str_contains($productMessage, $expectedPrice)) {
            throw new Exception('ERREUR: Le prix du produit retourné ne correspond pas à 180k. Message: '.$productMessage);
        }

        // Vérifier la présence du message de réponse
        if (! isset($response['response_message']) || empty($response['response_message'])) {
            throw new Exception('Message de réponse manquant');
        }

        // Afficher les détails pour analyse
        $this->log('📋 Réponse complète: '.json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->log('📦 Produit retourné: 1 produit avec structure DTO');

        // Vérifier le format du message (devrait mentionner la contrainte de prix)
        $message = strtolower($response['response_message']);
        $hasPriceConstraint = (
            str_contains($message, '200') ||
            str_contains($message, 'moins') ||
            str_contains($message, 'budget') ||
            str_contains($message, '180')
        );

        if (! $hasPriceConstraint) {
            $this->log('⚠️ Le message ne semble pas mentionner la contrainte de prix');
        } else {
            $this->log('✅ Le message mentionne la contrainte de prix');
        }

        $this->log('✅ Validation spécifique: Exactement 1 produit retourné');
        $this->log('✅ Validation spécifique: Produit correct (Casque Audio 180k)');
        $this->log('✅ Validation spécifique: L\'IA a bien respecté la contrainte "moins de 200k"');
    }

    /**
     * Nettoyage des produits de test
     */
    protected function performTestSpecificCleanup(): void
    {
        $this->log('🧹 Suppression des produits de test...');

        // Supprimer les associations pivot d'abord
        if ($this->testAccount) {
            $this->testAccount->userProducts()->detach();
            $this->log('✅ Associations pivot supprimées');
        }

        // Puis supprimer les produits
        $deletedCount = 0;
        foreach ($this->testProducts as $product) {
            try {
                $product->delete();
                $deletedCount++;
            } catch (Exception $e) {
                $this->log("⚠️ Erreur suppression produit {$product->id}: ".$e->getMessage());
            }
        }

        if ($deletedCount > 0) {
            $this->log("✅ {$deletedCount} produits supprimés");
        }
    }

    /**
     * Ajoute des médias de test à un produit
     */
    private function addTestMediaToProduct(UserProduct $product, string $category): void
    {
        // Images de test basées sur la catégorie
        $testImages = $this->getTestImagesForCategory($category);

        // Créer 2-3 médias fictifs pour le test
        for ($i = 0; $i < min(3, count($testImages)); $i++) {
            try {
                // Simuler l'ajout d'un média avec Spatie Media Library
                // Note: Pour un vrai test, il faudrait de vraies images
                $media = $product->addMediaFromBase64($testImages[$i]['base64'])
                    ->usingName($testImages[$i]['name'])
                    ->usingFileName($testImages[$i]['filename'])
                    ->toMediaCollection('medias');

                $this->log("  ✅ Média ajouté: {$media->name}");
            } catch (Exception $e) {
                // Si l'ajout de média échoue, on continue (pas critique pour le test)
                $this->log("  ⚠️ Échec ajout média {$i}: ".$e->getMessage());
            }
        }
    }

    /**
     * Retourne des données d'images de test par catégorie
     */
    private function getTestImagesForCategory(string $category): array
    {
        // Image de test 1x1 pixel en base64 (transparent PNG)
        $transparentPixel = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==';

        return [
            [
                'name' => "{$category} - Image principale",
                'filename' => strtolower($category).'_main.png',
                'base64' => $transparentPixel,
            ],
            [
                'name' => "{$category} - Vue détail",
                'filename' => strtolower($category).'_detail.png',
                'base64' => $transparentPixel,
            ],
            [
                'name' => "{$category} - Packaging",
                'filename' => strtolower($category).'_package.png',
                'base64' => $transparentPixel,
            ],
        ];
    }
}

// Exécution du test
try {
    $tester = new TestIncomingFlowWithProducts;
    $tester->runTest();
} catch (Exception $e) {
    echo '❌ Erreur fatale: '.$e->getMessage().PHP_EOL;
    exit(1);
}
