/**
 * Script de test pour le flux de produits WhatsApp via API HTTP
 * Simule une réponse Laravel avec produits et teste l'envoi
 */

const axios = require('axios');
const TestData = require('./src/test/testData');

const API_BASE = 'http://localhost:3000';

async function testProductFlowViaAPI() {
    try {
        console.log("🚀 Test de flux de produits via API HTTP\n");

        // 1. Vérifier les sessions actives
        console.log("=".repeat(50));
        console.log("📱 Vérification des sessions actives");
        console.log("=".repeat(50));
        
        const sessionsResponse = await axios.get(`${API_BASE}/api/sessions`);
        const sessionsData = sessionsResponse.data;
        const sessions = sessionsData.sessions || [];
        
        console.log(`Total sessions: ${sessions.length}`);
        
        const activeSessions = sessions.filter(s => s.status === 'connected');
        console.log(`Sessions actives: ${activeSessions.length}`);
        
        if (activeSessions.length === 0) {
            console.log("❌ Aucune session active trouvée");
            return;
        }
        
        activeSessions.forEach((session, index) => {
            console.log(`${index + 1}. ${session.sessionId} (${session.phoneNumber})`);
        });

        if (activeSessions.length < 2) {
            console.log("\n⚠️ Il faut 2 sessions pour tester entre elles");
            console.log("💡 On va tester en envoyant à la première session");
        }

        // 2. Choisir session source et destination
        const fromSession = activeSessions[0];
        const toPhoneNumber = activeSessions.length > 1 ? activeSessions[1].phoneNumber : fromSession.phoneNumber;
        
        console.log(`\n📤 Session source: ${fromSession.sessionId} (${fromSession.phoneNumber})`);
        console.log(`📥 Destination: ${toPhoneNumber}`);

        // 3. Simuler un message entrant avec une réponse Laravel contenant des produits
        console.log("\n" + "=".repeat(50));
        console.log("🧪 Test d'envoi de produits");
        console.log("=".repeat(50));

        // Créer payload avec produits de test
        const mockLaravelResponse = {
            success: true,
            processed: true,
            session_id: fromSession.sessionId,
            phone_number: fromSession.phoneNumber,
            response_message: "Voici quelques produits qui pourraient vous intéresser :",
            wait_time_seconds: 1,
            typing_duration_seconds: 2,
            products: TestData.sampleProducts
        };

        console.log(`📦 Produits à envoyer: ${mockLaravelResponse.products.length}`);
        console.log(`⏱️ Délais configurés: 3s entre produits, 1.5s entre médias`);

        // Simuler l'envoi direct des éléments de réponse
        console.log("\n⏳ Envoi du message de réponse IA...");
        
        const aiResponse = await axios.post(`${API_BASE}/api/bridge/send-message`, {
            session_id: fromSession.sessionId,
            to: toPhoneNumber,
            message: mockLaravelResponse.response_message
        });

        if (aiResponse.data.success) {
            console.log("✅ Message IA envoyé avec succès");
        } else {
            console.log("❌ Échec envoi message IA:", aiResponse.data.error);
            return;
        }

        // Attendre un peu avant d'envoyer les produits
        await new Promise(resolve => setTimeout(resolve, 2000));

        // 4. Envoyer chaque produit avec ses médias
        console.log("\n🛍️ Envoi des produits...");
        
        for (let i = 0; i < mockLaravelResponse.products.length; i++) {
            const product = mockLaravelResponse.products[i];
            
            console.log(`\n📦 Produit ${i + 1}/${mockLaravelResponse.products.length}`);
            
            // Envoyer le message du produit
            console.log("   📝 Envoi du message produit...");
            const productResponse = await axios.post(`${API_BASE}/api/bridge/send-message`, {
                session_id: fromSession.sessionId,
                to: toPhoneNumber,
                message: product.formattedProductMessage
            });

            if (productResponse.data.success) {
                console.log("   ✅ Message produit envoyé");
            } else {
                console.log("   ❌ Échec message produit:", productResponse.data.error);
                continue;
            }

            // Attendre avant d'envoyer les médias
            if (product.mediaUrls.length > 0) {
                await new Promise(resolve => setTimeout(resolve, 300));
            }

            // Envoyer chaque média
            for (let j = 0; j < product.mediaUrls.length; j++) {
                const mediaUrl = product.mediaUrls[j];
                
                console.log(`   📎 Envoi média ${j + 1}/${product.mediaUrls.length}...`);
                
                try {
                    const mediaResponse = await axios.post(`${API_BASE}/api/bridge/send-message`, {
                        session_id: fromSession.sessionId,
                        to: toPhoneNumber,
                        message: mediaUrl
                    });

                    if (mediaResponse.data.success) {
                        console.log(`   ✅ Média ${j + 1} envoyé`);
                    } else {
                        console.log(`   ❌ Échec média ${j + 1}:`, mediaResponse.data.error);
                    }
                } catch (error) {
                    console.log(`   ❌ Erreur média ${j + 1}:`, error.message);
                }

                // Délai entre médias (1.5s)
                if (j < product.mediaUrls.length - 1) {
                    await new Promise(resolve => setTimeout(resolve, 1500));
                }
            }

            // Délai entre produits (3s)
            if (i < mockLaravelResponse.products.length - 1) {
                console.log(`   ⏳ Attente 3s avant le produit suivant...`);
                await new Promise(resolve => setTimeout(resolve, 3000));
            }
        }

        console.log("\n" + "=".repeat(50));
        console.log("🎉 Test terminé avec succès !");
        console.log("=".repeat(50));
        console.log(`📱 Vérifie les messages sur: ${toPhoneNumber}`);
        console.log("💡 Tu devrais voir:");
        console.log("   - 1 message de réponse IA");
        console.log(`   - ${mockLaravelResponse.products.length} messages de produits`);
        console.log(`   - ${mockLaravelResponse.products.reduce((total, p) => total + p.mediaUrls.length, 0)} médias au total`);

    } catch (error) {
        console.error("❌ Erreur lors du test:", error.message);
        if (error.response) {
            console.error("Status:", error.response.status);
            console.error("Data:", error.response.data);
        }
    }
}

// Lancer le test
if (require.main === module) {
    testProductFlowViaAPI().then(() => {
        console.log("\n👋 Test terminé");
        process.exit(0);
    }).catch((error) => {
        console.error("❌ Erreur fatale:", error);
        process.exit(1);
    });
}

module.exports = { testProductFlowViaAPI };