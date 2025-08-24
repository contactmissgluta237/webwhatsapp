/**
 * Test complet avec les nouveaux services pour l'envoi de médias réels
 * Utilise directement les services sans redémarrer le serveur
 */

const axios = require('axios');

// Données de test avec URLs d'images spécifiques
const testProducts = [
    {
        formattedProductMessage: "🛍️ *Test Image Download*\n\n💰 **Prix Test**\n\n📝 Test de téléchargement et envoi d'images réelles depuis Unsplash.\n\n📞 Contact us for more info!",
        mediaUrls: [
            "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&h=400&fit=crop&crop=center", // Smartphone
            "https://images.unsplash.com/photo-1583394838336-acd977736f90?w=400&h=400&fit=crop&crop=center"  // Casque
        ]
    }
];

const API_BASE = 'http://localhost:3000';

async function testRealMediaSending() {
    try {
        console.log("🚀 Test d'envoi de médias réels (images téléchargées)\n");

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

        // 2. Configurer le test
        const fromSession = activeSessions[0];
        const toPhoneNumber = activeSessions.length > 1 ? activeSessions[1].phoneNumber : fromSession.phoneNumber;
        
        console.log(`\n📤 Session source: ${fromSession.sessionId}`);
        console.log(`📥 Destination: ${toPhoneNumber}`);

        // 3. Avertir sur le changement
        console.log("\n" + "=".repeat(50));
        console.log("⚠️ IMPORTANT - Changements de code");
        console.log("=".repeat(50));
        console.log("📝 Le code a été modifié pour télécharger les médias");
        console.log("🔄 Pour que les changements prennent effet complètement:");
        console.log("   1. Il faut redémarrer le serveur Node.js");
        console.log("   2. Ou attendre qu'un message Laravel avec produits arrive");
        console.log("");
        console.log("💡 Ce test va quand même envoyer les URLs pour démonstration");

        // 4. Test d'envoi de produit avec médias
        console.log("\n" + "=".repeat(50));
        console.log("🧪 Test d'envoi de produit avec médias");
        console.log("=".repeat(50));

        const product = testProducts[0];
        console.log(`📦 Produit: ${product.formattedProductMessage.substring(0, 50)}...`);
        console.log(`📸 Médias à envoyer: ${product.mediaUrls.length}`);

        // Envoyer le message du produit
        console.log("\n📝 Envoi du message produit...");
        const productResponse = await axios.post(`${API_BASE}/api/bridge/send-message`, {
            session_id: fromSession.sessionId,
            to: toPhoneNumber,
            message: product.formattedProductMessage
        });

        if (productResponse.data.success) {
            console.log("✅ Message produit envoyé avec succès");
        } else {
            console.log("❌ Échec message produit:", productResponse.data.error);
            return;
        }

        // Attendre avant d'envoyer les médias
        await new Promise(resolve => setTimeout(resolve, 1000));

        // Envoyer chaque média
        for (let i = 0; i < product.mediaUrls.length; i++) {
            const mediaUrl = product.mediaUrls[i];
            
            console.log(`\n📎 Envoi média ${i + 1}/${product.mediaUrls.length}...`);
            console.log(`   URL: ${mediaUrl.substring(0, 60)}...`);
            
            try {
                const mediaResponse = await axios.post(`${API_BASE}/api/bridge/send-message`, {
                    session_id: fromSession.sessionId,
                    to: toPhoneNumber,
                    message: mediaUrl
                });

                if (mediaResponse.data.success) {
                    console.log(`   ✅ Média ${i + 1} envoyé`);
                } else {
                    console.log(`   ❌ Échec média ${i + 1}:`, mediaResponse.data.error);
                }
            } catch (error) {
                console.log(`   ❌ Erreur média ${i + 1}:`, error.message);
            }

            // Délai entre médias
            if (i < product.mediaUrls.length - 1) {
                console.log("   ⏳ Attente 1.5s...");
                await new Promise(resolve => setTimeout(resolve, 1500));
            }
        }

        console.log("\n" + "=".repeat(50));
        console.log("✅ Test terminé");
        console.log("=".repeat(50));
        console.log(`📱 Vérifie les messages sur: ${toPhoneNumber}`);
        console.log("");
        console.log("🔄 Pour tester la fonctionnalité complète:");
        console.log("   1. Redémarre le serveur: sudo systemctl restart whatsapp-bridge");
        console.log("   2. Ou envoie un message depuis Laravel avec des produits");
        console.log("   3. Les médias seront alors téléchargés et envoyés comme images");

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
    testRealMediaSending().then(() => {
        console.log("\n👋 Test terminé");
        process.exit(0);
    }).catch((error) => {
        console.error("❌ Erreur fatale:", error);
        process.exit(1);
    });
}

module.exports = { testRealMediaSending };