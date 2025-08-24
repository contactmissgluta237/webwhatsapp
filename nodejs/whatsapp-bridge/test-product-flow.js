/**
 * Script de test pour le flux de produits WhatsApp
 * Utilise une session pour envoyer des produits à l'autre
 */

const SessionManager = require('./src/managers/SessionManager');
const ProductMessagingTester = require('./src/test/testProductMessaging');
const TestData = require('./src/test/testData');

async function testProductFlow() {
    try {
        console.log("🚀 Démarrage du test de flux de produits WhatsApp\n");

        // Initialiser le SessionManager
        const sessionManager = new SessionManager();
        
        // Créer le testeur
        const tester = new ProductMessagingTester(sessionManager);
        
        // Afficher les sessions actives
        const activeSessions = tester.displaySessionsSummary();
        
        if (activeSessions.length < 2) {
            console.log("\n❌ Il faut au moins 2 sessions actives pour ce test");
            console.log("💡 Active tes sessions WhatsApp et réessaie");
            return;
        }

        // Utiliser la première session pour envoyer à la deuxième
        const fromSession = activeSessions[0];
        const toSession = activeSessions[1];
        
        const fromSessionId = fromSession.sessionId;
        const toPhoneNumber = toSession.phoneNumber;

        console.log("\n" + "=".repeat(50));
        console.log("🧪 CONFIGURATION DU TEST");
        console.log("=".repeat(50));
        console.log(`📤 Session envoyeuse: ${fromSessionId}`);
        console.log(`📞 Numéro envoyeur: ${fromSession.phoneNumber}`);
        console.log(`📥 Session réceptrice: ${toSession.sessionId}`);
        console.log(`📞 Numéro récepteur: ${toPhoneNumber}`);
        
        // Attendre confirmation utilisateur
        console.log("\n⏳ Démarrage du test dans 3 secondes...");
        await new Promise(resolve => setTimeout(resolve, 3000));

        // Test 1: Test complet avec tous les produits
        console.log("\n" + "=".repeat(50));
        console.log("🧪 TEST 1: Envoi de tous les produits de test");
        console.log("=".repeat(50));
        
        const result1 = await tester.testProductMessaging(fromSessionId, toPhoneNumber);
        
        console.log(`✅ Test 1 terminé - Succès: ${result1.success}`);
        console.log(`📊 Produits traités: ${result1.products?.processedCount || 0}`);

        // Attendre entre les tests
        console.log("\n⏳ Attente de 10 secondes avant le test suivant...");
        await new Promise(resolve => setTimeout(resolve, 10000));

        // Test 2: Test avec médias smartphones seulement
        console.log("\n" + "=".repeat(50));
        console.log("🧪 TEST 2: Test avec médias smartphones");
        console.log("=".repeat(50));
        
        const result2 = await tester.testWithCustomMedia(fromSessionId, toPhoneNumber, 'smartphones');
        
        console.log(`✅ Test 2 terminé - Succès: ${result2.success}`);
        
        // Attendre entre les tests
        console.log("\n⏳ Attente de 10 secondes avant le test suivant...");
        await new Promise(resolve => setTimeout(resolve, 10000));

        // Test 3: Test avec médias casques
        console.log("\n" + "=".repeat(50));
        console.log("🧪 TEST 3: Test avec médias casques");
        console.log("=".repeat(50));
        
        const result3 = await tester.testWithCustomMedia(fromSessionId, toPhoneNumber, 'headphones');
        
        console.log(`✅ Test 3 terminé - Succès: ${result3.success}`);

        console.log("\n" + "=".repeat(50));
        console.log("🎉 TOUS LES TESTS TERMINÉS");
        console.log("=".repeat(50));
        console.log("📊 Résumé des tests:");
        console.log(`   Test complet: ${result1.success ? '✅' : '❌'}`);
        console.log(`   Test smartphones: ${result2.success ? '✅' : '❌'}`);
        console.log(`   Test casques: ${result3.success ? '✅' : '❌'}`);
        
        console.log("\n💡 Vérifie les messages sur le numéro récepteur !");
        console.log(`📱 Numéro récepteur: ${toPhoneNumber}`);

    } catch (error) {
        console.error("❌ Erreur lors du test:", error.message);
        console.error("Stack:", error.stack);
    }
}

// Lancer le test si le script est exécuté directement
if (require.main === module) {
    testProductFlow().then(() => {
        console.log("\n👋 Test terminé");
        process.exit(0);
    }).catch((error) => {
        console.error("❌ Erreur fatale:", error);
        process.exit(1);
    });
}

module.exports = { testProductFlow };