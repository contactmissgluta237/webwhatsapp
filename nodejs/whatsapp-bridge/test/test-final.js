/**
 * Test final : 1 message + 1 produit avec 1 image
 */

const axios = require('axios');
const API_BASE = 'http://localhost:3000';

async function testFinal() {
    try {
        console.log("🎉 Test final : Message + Produit + Image téléchargée\n");

        const sessionsResponse = await axios.get(`${API_BASE}/api/sessions`);
        const sessions = sessionsResponse.data.sessions || [];
        const activeSessions = sessions.filter(s => s.status === 'connected');
        
        if (activeSessions.length === 0) {
            console.log("❌ Aucune session active");
            return;
        }

        const fromSession = activeSessions[0];
        const toNumber = fromSession.phoneNumber; // Envoie à soi-même
        
        console.log(`📱 Test sur : ${toNumber}`);

        // 1. Message d'intro
        console.log("\n📝 Envoi message d'intro...");
        await axios.post(`${API_BASE}/api/bridge/send-message`, {
            session_id: fromSession.sessionId,
            to: toNumber,
            message: "🛍️ Voici un produit qui pourrait t'intéresser :"
        });
        console.log("✅ Message d'intro envoyé");

        await new Promise(resolve => setTimeout(resolve, 2000));

        // 2. Message produit
        console.log("\n📦 Envoi message produit...");
        const productMessage = "🛍️ *Smartphone Galaxy Ultra*\n\n💰 **285 000 XAF**\n\n📝 Smartphone avec caméra 108MP, parfait état.\n\n📞 Contact us for more info!";
        await axios.post(`${API_BASE}/api/bridge/send-message`, {
            session_id: fromSession.sessionId,
            to: toNumber,
            message: productMessage
        });
        console.log("✅ Message produit envoyé");

        await new Promise(resolve => setTimeout(resolve, 2000));

        // 3. Image du produit (sera téléchargée et envoyée comme vraie image)
        console.log("\n📸 Envoi image produit...");
        const imageUrl = "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&h=400";
        
        const response = await axios.post(`${API_BASE}/api/bridge/send-message`, {
            session_id: fromSession.sessionId,
            to: toNumber,
            message: imageUrl
        });

        if (response.data.mediaType) {
            console.log(`✅ Image envoyée ! Type: ${response.data.mediaType}, Taille: ${(response.data.mediaSize/1024).toFixed(1)}KB`);
        } else {
            console.log("✅ Message envoyé (fallback URL)");
        }

        console.log("\n" + "=".repeat(40));
        console.log("🎉 TEST TERMINÉ AVEC SUCCÈS !");
        console.log("📱 Vérifie ton WhatsApp, tu devrais voir :");
        console.log("   1️⃣ Message d'intro");
        console.log("   2️⃣ Description du produit");
        console.log("   3️⃣ Image téléchargée du smartphone");

    } catch (error) {
        console.error("❌ Erreur:", error.message);
    }
}

testFinal().then(() => process.exit(0));