/**
 * Test spécifique pour vérifier le téléchargement et l'envoi de médias
 * Utilise directement la nouvelle architecture sans passer par l'API
 */

const { MessageMedia } = require("whatsapp-web.js");
const TestData = require('../src/test/testData');
const ProductMessagingConfig = require('../src/config/productMessagingConfig');

// Mock simple pour tester MessageMedia.fromUrl
async function testMediaDownload() {
    console.log("🧪 Test de téléchargement de médias\n");

    // URLs de test d'Unsplash
    const testUrls = [
        "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&h=400&fit=crop", // Smartphone
        "https://images.unsplash.com/photo-1583394838336-acd977736f90?w=400&h=400&fit=crop", // Casque
        "https://httpstat.us/404" // URL qui va échouer pour tester le fallback
    ];

    console.log("=" * 50);
    console.log("📸 Test de téléchargement des médias");
    console.log("=" * 50);

    for (let i = 0; i < testUrls.length; i++) {
        const url = testUrls[i];
        console.log(`\n${i + 1}. Test URL: ${url.substring(0, 60)}...`);

        try {
            console.log("   ⏳ Téléchargement en cours...");
            const startTime = Date.now();
            
            const media = await MessageMedia.fromUrl(url, {
                unsafeMime: true,
                timeout: ProductMessagingConfig.media.downloadTimeout
            });
            const duration = Date.now() - startTime;

            console.log("   ✅ Téléchargement réussi!");
            console.log(`   📄 Type MIME: ${media.mimetype || 'unknown'}`);
            console.log(`   📦 Taille: ${media.data ? (media.data.length / 1024).toFixed(2) : '0'} KB`);
            console.log(`   ⏱️ Durée: ${duration}ms`);
            
            // Vérifier si le type MIME est accepté
            const isAccepted = ProductMessagingConfig.media.acceptedMimeTypes.includes(media.mimetype);
            console.log(`   🎯 Type accepté: ${isAccepted ? '✅ Oui' : '❌ Non'}`);

        } catch (error) {
            console.log("   ❌ Téléchargement échoué:");
            console.log(`   🔴 Erreur: ${error.message}`);
            
            if (ProductMessagingConfig.media.fallbackToUrlOnError) {
                console.log("   🔄 Fallback activé - envoi en tant qu'URL texte");
            } else {
                console.log("   🚫 Fallback désactivé - média ignoré");
            }
        }
    }

    console.log("\n" + "=" * 50);
    console.log("📊 Configuration actuelle des médias:");
    console.log("=" * 50);
    console.log(`📥 Téléchargement comme fichiers: ${ProductMessagingConfig.media.downloadAndSendAsFiles ? '✅' : '❌'}`);
    console.log(`⏰ Timeout: ${ProductMessagingConfig.media.downloadTimeout}ms`);
    console.log(`📏 Taille max: ${(ProductMessagingConfig.media.maxFileSizeBytes / 1024 / 1024).toFixed(1)}MB`);
    console.log(`🔄 Fallback URL: ${ProductMessagingConfig.media.fallbackToUrlOnError ? '✅' : '❌'}`);
    console.log(`🎭 Types MIME acceptés: ${ProductMessagingConfig.media.acceptedMimeTypes.length} types`);

    console.log("\n💡 Pour tester l'envoi réel, les modifications prennent effet:");
    console.log("   1. Lors du redémarrage du serveur Node.js");
    console.log("   2. Ou lors d'un nouveau message depuis Laravel avec produits");
}

// Lancer le test
if (require.main === module) {
    testMediaDownload().then(() => {
        console.log("\n👋 Test terminé");
        process.exit(0);
    }).catch((error) => {
        console.error("❌ Erreur fatale:", error);
        process.exit(1);
    });
}

module.exports = { testMediaDownload };