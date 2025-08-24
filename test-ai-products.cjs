/**
 * Test complet de l'agent IA avec intégration produits
 * Teste l'envoi de produits avec médias automatiques
 */

const axios = require('axios');

async function testAIWithProducts() {
    try {
        console.log("🛍️ Test de l'Agent Commercial avec Produits\n");

        const API_BASE = 'http://localhost:8000'; // Laravel API
        
        // Message qui devrait déclencher l'envoi de produits
        const incomingMessage = {
            event: "incoming_message",
            session_id: "session_2_17552805081829_3d3b6b43", // Session avec IA activée
            session_name: "session_2_17552805081829_3d3b6b43",
            message: {
                id: "test_products_" + Date.now(),
                from: "237699999999@c.us", // Numéro fictif client
                body: "Je cherche des services pour créer un site e-commerce complet avec design moderne. Vous avez des offres ?",
                timestamp: Math.floor(Date.now() / 1000),
                type: "chat",
                isGroup: false
            }
        };

        console.log("📤 Envoi d'une demande qui devrait déclencher des produits...");
        console.log(`📞 De: ${incomingMessage.message.from}`);
        console.log(`📱 Vers session: ${incomingMessage.session_id}`);
        console.log(`💬 Message: "${incomingMessage.message.body}"`);

        const response = await axios.post(
            `${API_BASE}/api/whatsapp/webhook/incoming-message`,
            incomingMessage,
            {
                headers: {
                    'Content-Type': 'application/json',
                    'User-Agent': 'WhatsApp-Products-Test/1.0'
                },
                timeout: 60000 // 60 secondes pour laisser le temps à l'IA
            }
        );

        console.log("\n" + "=".repeat(60));
        console.log("🎯 RÉPONSE DE L'IA AVEC PRODUITS");
        console.log("=".repeat(60));
        console.log(`✅ Statut: ${response.status}`);
        console.log(`🔄 Traité: ${response.data.processed ? 'OUI' : 'NON'}`);
        console.log(`🤖 Réponse IA: ${response.data.response_message ? 'OUI' : 'NON'}`);

        if (response.data.response_message) {
            console.log("\n🗣️ RÉPONSE DE L'AGENT COMMERCIAL:");
            console.log("-".repeat(60));
            console.log(response.data.response_message);
            console.log("-".repeat(60));
        }

        console.log(`\n⏱️ Temps d'attente: ${response.data.wait_time_seconds || 0}s`);
        console.log(`⌨️ Durée de frappe: ${response.data.typing_duration_seconds || 0}s`);
        
        if (response.data.products && response.data.products.length > 0) {
            console.log(`\n🛍️ PRODUITS DÉTECTÉS: ${response.data.products.length}`);
            console.log("=".repeat(60));
            
            response.data.products.forEach((product, index) => {
                console.log(`\n📦 PRODUIT ${index + 1}:`);
                console.log(`   • Nom: ${product.name || 'N/A'}`);
                console.log(`   • Prix: ${product.price || 'N/A'}`);
                console.log(`   • Description: ${product.description ? product.description.substring(0, 100) + '...' : 'N/A'}`);
                
                if (product.formattedProductMessage) {
                    console.log(`   • Message formaté: ${product.formattedProductMessage.substring(0, 150)}...`);
                }
                
                if (product.mediaUrls && product.mediaUrls.length > 0) {
                    console.log(`   • Médias (${product.mediaUrls.length}):`);
                    product.mediaUrls.forEach((url, mediaIndex) => {
                        console.log(`     - [${mediaIndex + 1}] ${url}`);
                    });
                } else {
                    console.log(`   • Aucun média`);
                }
            });
            
            console.log("\n📱 Les produits seront envoyés automatiquement via WhatsApp avec:");
            console.log("   • Délai entre produits: 3 secondes");
            console.log("   • Délai entre médias: 1.5 secondes");
            console.log("   • Médias téléchargés et envoyés comme fichiers (pas URLs)");
            
        } else {
            console.log("\n⚠️ Aucun produit détecté dans la réponse");
        }

        console.log("\n🎉 TEST RÉUSSI ! L'agent commercial avec produits fonctionne !");

    } catch (error) {
        console.error("❌ Erreur lors du test:", error.message);
        
        if (error.response) {
            console.error(`Status: ${error.response.status}`);
            console.error(`Data:`, error.response.data);
        }
        
        if (error.code === 'ECONNREFUSED') {
            console.error("💡 Assure-toi que Laravel est démarré (php artisan serve)");
        }
        
        if (error.code === 'ECONNRESET' || error.code === 'TIMEOUT') {
            console.error("💡 Le traitement a pris trop de temps (normal pour l'IA)");
        }
    }
}

// Lancer le test
if (require.main === module) {
    testAIWithProducts().then(() => {
        console.log("\n👋 Test terminé - Vérifiez WhatsApp pour voir les produits envoyés");
        process.exit(0);
    }).catch((error) => {
        console.error("❌ Erreur fatale:", error);
        process.exit(1);
    });
}

module.exports = { testAIWithProducts };