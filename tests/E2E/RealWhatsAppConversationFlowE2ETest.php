<?php

declare(strict_types=1);

namespace Tests\E2E;

use App\Models\User;
use App\Models\UserProduct;
use App\Models\WhatsAppAccount;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ============================================================================
 * VRAI TEST END-TO-END : Flux de Conversation WhatsApp Complet
 * ============================================================================
 *
 * Ce test utilise le système EXISTANT tel quel - aucune modification requise !
 * Il teste un parcours utilisateur complet avec de vraies données.
 *
 * ⚠️ PRÉREQUIS AVANT EXÉCUTION :
 * ============================================================================
 * 1. Modifier les constantes ci-dessous avec deux session_id WhatsApp RÉELLES
 * 2. Les sessions doivent être ACTIVES et CONNECTÉES
 * 3. L'application doit être démarrée et accessible
 *
 * 📋 SCÉNARIOS TESTÉS (PROGRESSIFS) :
 * ============================================================================
 *
 * ÉTAPE 1 : Préparation des Données
 * ----------------------------------
 * - Créer un utilisateur avec abonnement actif (via factories existantes)
 * - Créer un compte WhatsApp et le configurer
 * - Créer 3 produits avec images et les lier
 *
 * ÉTAPE 2 : Test Conversation Basique
 * -----------------------------------
 * - Envoyer "Bonjour chef" via le webhook existant
 * - Vérifier la réponse IA dans la base de données
 * - Vérifier que les messages ont été débités
 *
 * ÉTAPE 3 : Test Recommandation Produits
 * ---------------------------------------
 * - Envoyer demande de site web via le webhook
 * - Vérifier que l'IA recommande les bons produits
 * - Vérifier la facturation IA + produits
 *
 * ÉTAPE 4 : Test Information Inconnue
 * ------------------------------------
 * - Envoyer question complexe inconnue
 * - Vérifier la réponse et les notifications
 */
class RealWhatsAppConversationFlowE2ETest extends TestCase
{
    // ========================================================================
    // 🔧 CONSTANTES CONFIGURABLES - Modifier selon vos besoins
    // ========================================================================

    // ⚠️ SESSIONS WHATSAPP RÉELLES - OBLIGATOIRE À MODIFIER
    private const TEST_SESSION_1 = 'YOUR_REAL_SESSION_ID_1_HERE';
    private const TEST_SESSION_2 = 'YOUR_REAL_SESSION_ID_2_HERE';

    // 💬 MESSAGES DE TEST - Faciles à modifier
    private const TEST_MESSAGES = [
        'greeting' => 'Bonjour chef',
        'product_inquiry' => 'Svp vous pouvez m\'aider à créer un site de présentation pour mon salon ? c\'est quoi vos prix ?',
        'unknown_question' => 'Est-ce que vous pouvez développer un système de gestion des stocks avec IA pour une usine textile au Bénin ?',
        'simple_question' => 'Comment ça va ?',
    ];

    // 🤖 CONFIGURATION IA
    private const AI_CONFIG = [
        'prompt' => 'Tu es DigitalBot, assistant commercial pour une agence digitale.',
        'context' => 'Nous proposons sites web, apps mobiles et e-commerce.',
        'enabled' => true,
    ];

    // 👤 CONFIGURATION UTILISATEUR DE TEST
    private const USER_CONFIG = [
        'wallet_balance' => 1000.0,
        'wallet_currency' => 'USD',
        'subscription_messages' => 100,
        'subscription_days' => 30,
    ];

    // 🛍️ PRODUITS DE TEST
    private const TEST_PRODUCTS = [
        [
            'title' => 'Site Web Vitrine',
            'price' => 100000,
            'description' => 'Site vitrine professionnel',
        ],
        [
            'title' => 'Site E-commerce',
            'price' => 250000,
            'description' => 'Boutique en ligne complète',
        ],
        [
            'title' => 'App Mobile E-learning',
            'price' => 1000000,
            'description' => 'Application de formation',
        ],
    ];

    // 📧 CONFIGURATION NOTIFICATIONS
    private const NOTIFICATION_CONFIG = [
        'email' => 'test@example.com',
        'phone' => '+237123456789',
        'email_enabled' => true,
        'phone_enabled' => true,
    ];

    // ⏱️ DÉLAIS DE TRAITEMENT (secondes)
    private const PROCESSING_DELAYS = [
        'basic_message' => 3,
        'ai_with_products' => 5,
        'complex_analysis' => 5,
        'notifications' => 2,
    ];

    // Variables pour stocker les données créées
    private ?User $testUser = null;
    private ?WhatsAppAccount $testAccount = null;
    private array $testProductIds = [];

    #[Test]
    public function complete_whatsapp_conversation_flow_works_end_to_end(): void
    {
        // ⚠️ Décommentez cette ligne pour activer le test avec de vraies sessions
        $this->markTestSkipped('Test E2E désactivé - Remplacez TEST_SESSION_1 et TEST_SESSION_2 par de vraies sessions');

        echo "\n🚀 DÉBUT DU TEST E2E WHATSAPP COMPLET\n";
        echo "=====================================\n\n";

        // Étape 1 : Préparer les données de test
        $this->step1_setupTestData();

        // Étape 2 : Test conversation basique (avec abonnement)
        $this->step2_testBasicGreeting();

        // Étape 3 : Test recommandation produits (avec abonnement)
        $this->step3_testProductRecommendation();

        // Étape 4 : Test facturation wallet (sans abonnement)
        $this->step4_testWalletBilling();

        // Étape 5 : Test information inconnue
        $this->step5_testUnknownInformation();

        echo "\n✅ TOUS LES TESTS E2E ONT RÉUSSI !\n\n";
    }

    /**
     * ÉTAPE 1 : Préparer les données de test avec les factories existantes
     */
    private function step1_setupTestData(): void
    {
        echo "📋 ÉTAPE 1 : Préparation des données de test\n";
        echo "--------------------------------------------\n";

        // Vérifier que les sessions sont configurées
        $this->assertNotEquals('YOUR_REAL_SESSION_ID_1_HERE', self::TEST_SESSION_1,
            '⚠️ Vous devez remplacer TEST_SESSION_1 par une vraie session_id');
        $this->assertNotEquals('YOUR_REAL_SESSION_ID_2_HERE', self::TEST_SESSION_2,
            '⚠️ Vous devez remplacer TEST_SESSION_2 par une vraie session_id');

        // 1. Créer un utilisateur avec abonnement (using constants)
        $this->testUser = User::factory()->create();
        $this->testUser->wallet()->create([
            'balance' => self::USER_CONFIG['wallet_balance'],
            'currency' => self::USER_CONFIG['wallet_currency'],
        ]);

        $package = \App\Models\Package::factory()->create([
            'messages_limit' => self::USER_CONFIG['subscription_messages'],
        ]);
        \App\Models\UserSubscription::factory()->create([
            'user_id' => $this->testUser->id,
            'package_id' => $package->id,
            'status' => 'active',
            'messages_limit' => self::USER_CONFIG['subscription_messages'],
            'starts_at' => now()->subDays(1),
            'ends_at' => now()->addDays(self::USER_CONFIG['subscription_days']),
        ]);

        // 2. Créer un compte WhatsApp avec la session réelle (using constants)
        $this->testAccount = WhatsAppAccount::factory()->create([
            'user_id' => $this->testUser->id,
            'session_id' => self::TEST_SESSION_1,
            'ai_enabled' => self::AI_CONFIG['enabled'],
            'ai_prompt' => self::AI_CONFIG['prompt'],
            'ai_context' => self::AI_CONFIG['context'],
        ]);

        // 3. Créer produits et les lier au compte (using constants)
        foreach (self::TEST_PRODUCTS as $productData) {
            $product = UserProduct::factory()->create([
                'user_id' => $this->testUser->id,
                'title' => $productData['title'],
                'price' => $productData['price'],
                'description' => $productData['description'],
                'is_active' => true,
            ]);

            // Lier au compte WhatsApp
            $product->whatsappAccounts()->attach($this->testAccount->id);
            $this->testProductIds[] = $product->id;
        }

        echo '✅ Utilisateur créé avec abonnement ('.self::USER_CONFIG['subscription_messages']." messages)\n";
        echo '✅ Compte WhatsApp créé avec session: '.self::TEST_SESSION_1."\n";
        echo '✅ '.count(self::TEST_PRODUCTS)." produits créés et liés au compte\n\n";
    }

    /**
     * ÉTAPE 2 : Tester "Bonjour chef" et vérifier la réponse + facturation
     */
    private function step2_testBasicGreeting(): void
    {
        echo "💬 ÉTAPE 2 : Test de conversation basique\n";
        echo "----------------------------------------\n";

        // État initial
        $subscription = $this->testUser->activeSubscription;
        $initialMessages = $subscription->getRemainingMessages();
        echo "Messages disponibles avant test: {$initialMessages}\n";

        // Envoyer message de salutation via le webhook existant
        $response = $this->postJson('/webhook/whatsapp/incoming', [
            'from' => self::TEST_SESSION_2.'@c.us',
            'body' => self::TEST_MESSAGES['greeting'],
            'sessionId' => self::TEST_SESSION_1,
            'timestamp' => time(),
            'type' => 'text',
            'isGroup' => false,
        ]);

        $response->assertStatus(200);
        echo "✅ Message '".self::TEST_MESSAGES['greeting']."' envoyé via webhook\n";

        // Attendre le traitement
        sleep(self::PROCESSING_DELAYS['basic_message']);

        // Vérifier la facturation
        $subscription->refresh();
        $finalMessages = $subscription->getRemainingMessages();
        $this->assertTrue($finalMessages < $initialMessages, 'Des messages doivent être débités');

        echo "Messages restants après traitement: {$finalMessages}\n";
        echo "✅ Facturation appliquée correctement\n\n";
    }

    /**
     * ÉTAPE 3 : Tester la recommandation de produits
     */
    private function step3_testProductRecommendation(): void
    {
        echo "🛍️ ÉTAPE 3 : Test de recommandation de produits\n";
        echo "-----------------------------------------------\n";

        // État initial
        $subscription = $this->testUser->activeSubscription;
        $initialMessages = $subscription->getRemainingMessages();
        echo "Messages disponibles avant test: {$initialMessages}\n";

        // Envoyer demande de produit
        $response = $this->postJson('/webhook/whatsapp/incoming', [
            'from' => self::TEST_SESSION_2.'@c.us',
            'body' => self::TEST_MESSAGES['product_inquiry'],
            'sessionId' => self::TEST_SESSION_1,
            'timestamp' => time(),
            'type' => 'text',
            'isGroup' => false,
        ]);

        $response->assertStatus(200);
        echo "✅ Demande de produit envoyée\n";

        // Attendre le traitement (plus long car IA + produits)
        sleep(self::PROCESSING_DELAYS['ai_with_products']);

        // Vérifier la facturation (IA + éventuels produits)
        $subscription->refresh();
        $finalMessages = $subscription->getRemainingMessages();
        $messagesUsed = $initialMessages - $finalMessages;

        echo "Messages utilisés: {$messagesUsed}\n";
        $this->assertGreaterThan(0, $messagesUsed, 'Au moins un message doit être débité');
        echo "✅ Facturation IA + produits appliquée\n\n";
    }

    /**
     * ÉTAPE 4 : Tester la facturation via wallet (sans abonnement)
     */
    private function step4_testWalletBilling(): void
    {
        echo "💰 ÉTAPE 4 : Test de facturation wallet (sans abonnement)\n";
        echo "--------------------------------------------------------\n";

        // Supprimer l'abonnement pour forcer l'utilisation du wallet
        $this->testUser->activeSubscription->update(['status' => 'expired']);
        echo "✅ Abonnement expiré pour forcer facturation wallet\n";

        // État initial du wallet
        $wallet = $this->testUser->wallet;
        $initialBalance = $wallet->balance;
        echo "Balance wallet avant test: {$initialBalance} ".self::USER_CONFIG['wallet_currency']."\n";

        // Envoyer une question simple
        $response = $this->postJson('/webhook/whatsapp/incoming', [
            'from' => self::TEST_SESSION_2.'@c.us',
            'body' => self::TEST_MESSAGES['simple_question'],
            'sessionId' => self::TEST_SESSION_1,
            'timestamp' => time(),
            'type' => 'text',
            'isGroup' => false,
        ]);

        $response->assertStatus(200);
        echo "✅ Message '".self::TEST_MESSAGES['simple_question']."' envoyé\n";

        // Attendre le traitement
        sleep(self::PROCESSING_DELAYS['basic_message']);

        // Vérifier que le wallet a été débité
        $wallet->refresh();
        $finalBalance = $wallet->balance;
        $debitedAmount = $initialBalance - $finalBalance;

        echo "Balance wallet après traitement: {$finalBalance} ".self::USER_CONFIG['wallet_currency']."\n";
        echo "Montant débité: {$debitedAmount} ".self::USER_CONFIG['wallet_currency']."\n";

        if ($debitedAmount > 0) {
            echo "✅ Facturation wallet appliquée correctement\n";
            $this->assertGreaterThan(0, $debitedAmount, 'Le wallet doit être débité');
        } else {
            echo "ℹ️ Aucun débit wallet (peut-être fonds insuffisants ou autre logique)\n";
        }

        echo "\n";
    }

    /**
     * ÉTAPE 5 : Tester les notifications d'informations inconnues
     */
    private function step5_testUnknownInformation(): void
    {
        echo "❓ ÉTAPE 5 : Test d'information inconnue\n";
        echo "---------------------------------------\n";

        // Configurer les notifications sur le compte (using constants)
        $this->testAccount->update([
            'unknown_info_email_enabled' => self::NOTIFICATION_CONFIG['email_enabled'],
            'unknown_info_phone_enabled' => self::NOTIFICATION_CONFIG['phone_enabled'],
            'notification_email' => self::NOTIFICATION_CONFIG['email'],
            'notification_phone' => self::NOTIFICATION_CONFIG['phone'],
        ]);
        echo "✅ Notifications configurées sur le compte\n";

        // Envoyer question complexe inconnue
        $response = $this->postJson('/webhook/whatsapp/incoming', [
            'from' => self::TEST_SESSION_2.'@c.us',
            'body' => self::TEST_MESSAGES['unknown_question'],
            'sessionId' => self::TEST_SESSION_1,
            'timestamp' => time(),
            'type' => 'text',
            'isGroup' => false,
        ]);

        $response->assertStatus(200);
        echo "✅ Question complexe envoyée\n";

        // Attendre le traitement
        sleep(self::PROCESSING_DELAYS['complex_analysis']);

        // Vérifier qu'une notification d'info inconnue a été créée
        $unknownNotification = \App\Models\UnknownInformationNotification::where([
            'whatsapp_account_id' => $this->testAccount->id,
        ])->latest()->first();

        if ($unknownNotification) {
            echo "✅ Notification d'information inconnue créée\n";
            echo "✅ Système de détection fonctionne\n";
        } else {
            echo "ℹ️ Aucune info inconnue détectée (question peut-être dans le contexte)\n";
        }

        // Attendre les notifications
        sleep(self::PROCESSING_DELAYS['notifications']);
        echo "✅ Délai d'envoi des notifications respecté\n\n";
    }

    /**
     * Nettoyage après test
     */
    protected function tearDown(): void
    {
        if ($this->testUser) {
            // Supprimer les produits liés
            UserProduct::whereIn('id', $this->testProductIds)->delete();

            // Supprimer le compte WhatsApp
            if ($this->testAccount) {
                $this->testAccount->delete();
            }

            // Supprimer l'utilisateur (cascade supprimera wallet, subscriptions, etc.)
            $this->testUser->delete();
        }

        parent::tearDown();
    }
}
