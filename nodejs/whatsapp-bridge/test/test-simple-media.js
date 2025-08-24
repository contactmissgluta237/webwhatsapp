/**
 * Test simple : 1 message + 1 média téléchargé
 */

const { MessageMedia } = require("whatsapp-web.js");
const axios = require('axios');

const API_BASE = 'http://localhost:3000';

async function testSimpleMedia() {
    try {
        console.log("🧪 Test simple : 1 message + 1 image téléchargée\n");

        // 1. Récupérer les sessions
        const sessionsResponse = await axios.get(`${API_BASE}/api/sessions`);
        const sessions = sessionsResponse.data.sessions || [];
        const activeSessions = sessions.filter(s => s.status === 'connected');
        
        if (activeSessions.length === 0) {
            console.log("❌ Aucune session active");
            return;
        }

        const fromSession = activeSessions[0];
        const toNumber = activeSessions.length > 1 ? activeSessions[1].phoneNumber : fromSession.phoneNumber;
        
        console.log(`📤 De: ${fromSession.phoneNumber}`);
        console.log(`📥 Vers: ${toNumber}`);

        // 2. Test de téléchargement d'image
        console.log("\n⏳ Test téléchargement image...");
        const imageUrl = "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=300&h=300";
        
        try {
            const media = await MessageMedia.fromUrl(imageUrl, { unsafeMime: true });
            console.log(`✅ Image téléchargée : ${media.mimetype}, ${(media.data.length/1024).toFixed(1)}KB`);
        } catch (error) {
            console.log(`❌ Échec téléchargement : ${error.message}`);
            return;
        }

        // 3. Envoyer message texte
        console.log("\n📝 Envoi message...");
        await axios.post(`${API_BASE}/api/bridge/send-message`, {
            session_id: fromSession.sessionId,
            to: toNumber,
            message: "Test : je vais t'envoyer une image 📸"
        });
        console.log("✅ Message envoyé");

        // 4. Attendre 2s
        await new Promise(resolve => setTimeout(resolve, 2000));

        // 5. Envoyer URL de l'image (sera envoyée comme texte par l'API actuelle)
        console.log("\n📎 Envoi URL image (comme texte)...");
        await axios.post(`${API_BASE}/api/bridge/send-message`, {
            session_id: fromSession.sessionId,
            to: toNumber,
            message: imageUrl
        });
        console.log("✅ URL envoyée (comme texte)");

        console.log("\n" + "=".repeat(40));
        console.log("📱 Vérifie ton WhatsApp !");
        console.log("📝 Tu verras : 1 message + 1 URL texte");
        console.log("🔄 Pour avoir une vraie image, il faut modifier l'API");

    } catch (error) {
        console.error("❌ Erreur:", error.message);
    }
}

testSimpleMedia().then(() => process.exit(0));