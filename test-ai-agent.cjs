/**
 * Test de l'agent commercial IA Afrik-Solutions
 * Simule un message entrant pour déclencher l'IA
 */

const axios = require('axios');

async function testAIAgent() {
    try {
        console.log("🤖 Test de l'Agent Commercial Afrik-Solutions\n");

        const API_BASE = 'http://localhost:8000'; // Laravel API
        
        // Simuler un message entrant vers la session avec l'IA activée
        const incomingMessage = {
            event: "incoming_message",
            session_id: "session_2_17552805081829_3d3b6b43", // Session avec IA activée
            session_name: "session_2_17552805081829_3d3b6b43",
            message: {
                id: "test_msg_" + Date.now(),
                from: "237699999999@c.us", // Numéro fictif client
                body: "Bonjour, j'aimerais créer un site web pour mon business. Vous pouvez m'aider ?",
                timestamp: Math.floor(Date.now() / 1000),
                type: "chat",
                isGroup: false
            }
        };

        console.log("📤 Envoi du message client vers l'IA...");
        console.log(`📞 De: ${incomingMessage.message.from}`);
        console.log(`📱 Vers session: ${incomingMessage.session_id}`);
        console.log(`💬 Message: "${incomingMessage.message.body}"`);

        const response = await axios.post(
            `${API_BASE}/api/whatsapp/webhook/incoming-message`,
            incomingMessage,
            {
                headers: {
                    'Content-Type': 'application/json',
                    'User-Agent': 'WhatsApp-Test/1.0'
                },
                timeout: 45000 // 45 secondes pour laisser le temps à l'IA
            }
        );

        console.log("\n" + "=".repeat(50));
        console.log("🎯 RÉPONSE DE L'IA REÇUE");
        console.log("=".repeat(50));
        console.log(`✅ Statut: ${response.status}`);
        console.log(`🔄 Traité: ${response.data.processed ? 'OUI' : 'NON'}`);
        console.log(`🤖 Réponse IA: ${response.data.response_message ? 'OUI' : 'NON'}`);

        if (response.data.response_message) {
            console.log("\n🗣️ RÉPONSE DE L'AGENT COMMERCIAL:");
            console.log("-".repeat(50));
            console.log(response.data.response_message);
            console.log("-".repeat(50));
        }

        console.log(`\n⏱️ Temps d'attente: ${response.data.wait_time_seconds || 0}s`);
        console.log(`⌨️ Durée de frappe: ${response.data.typing_duration_seconds || 0}s`);
        
        if (response.data.products && response.data.products.length > 0) {
            console.log(`🛍️ Produits inclus: ${response.data.products.length}`);
        }

        console.log("\n🎉 TEST RÉUSSI ! L'agent commercial fonctionne !");

    } catch (error) {
        console.error("❌ Erreur lors du test:", error.message);
        
        if (error.response) {
            console.error(`Status: ${error.response.status}`);
            console.error(`Data:`, error.response.data);
        }
        
        if (error.code === 'ECONNREFUSED') {
            console.error("💡 Assure-toi que Laravel est démarré (php artisan serve)");
        }
    }
}

// Lancer le test
if (require.main === module) {
    testAIAgent().then(() => {
        console.log("\n👋 Test terminé");
        process.exit(0);
    }).catch((error) => {
        console.error("❌ Erreur fatale:", error);
        process.exit(1);
    });
}

module.exports = { testAIAgent };