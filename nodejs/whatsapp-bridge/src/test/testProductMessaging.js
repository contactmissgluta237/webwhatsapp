/**
 * Script de test pour la messagerie de produits WhatsApp
 * Utilise les données de test et simule une réponse Laravel complète
 */

const TestData = require("./testData");
const ResponseHandler = require("../services/ResponseHandler");
const MessageLogger = require("../services/MessageLogger");

class ProductMessagingTester {
    constructor(sessionManager) {
        this.sessionManager = sessionManager;
    }

    /**
     * Vérifier les sessions actives
     */
    getActiveSessions() {
        const sessions = this.sessionManager.getAllSessions();
        const activeSessions = Object.values(sessions).filter(session => 
            session.status === 'connected'
        );

        MessageLogger.logInfo("📱 ACTIVE SESSIONS CHECK", {
            totalSessions: Object.keys(sessions).length,
            activeSessions: activeSessions.length,
            activeSessionIds: activeSessions.map(s => s.sessionId)
        });

        return activeSessions;
    }

    /**
     * Tester l'envoi de produits entre deux sessions
     */
    async testProductMessaging(fromSessionId, toPhoneNumber) {
        const fromSession = this.sessionManager.getSession(fromSessionId);
        
        if (!fromSession || fromSession.status !== 'connected') {
            throw new Error(`Session ${fromSessionId} not found or not connected`);
        }

        MessageLogger.logInfo("🧪 STARTING PRODUCT MESSAGING TEST", {
            fromSessionId,
            toPhoneNumber,
            testProductsCount: TestData.sampleProducts.length
        });

        // Créer une réponse Laravel simulée avec nos produits de test
        const mockResponse = {
            ...TestData.mockLaravelResponse,
            products: TestData.sampleProducts
        };

        // Utiliser le ResponseHandler pour traiter la réponse
        const responseHandler = new ResponseHandler(fromSession.client, null);
        
        const context = {
            sessionId: fromSessionId,
            originalMessageId: "test_message_" + Date.now(),
            testMode: true
        };

        try {
            const result = await responseHandler.handleLaravelResponse(
                mockResponse,
                toPhoneNumber,
                context
            );

            MessageLogger.logInfo("✅ PRODUCT MESSAGING TEST COMPLETED", {
                fromSessionId,
                toPhoneNumber,
                success: result.success,
                aiResponseSent: !!result.aiResponse?.success,
                productsSent: !!result.products?.success,
                productsProcessed: result.products?.processedCount || 0
            });

            return result;
        } catch (error) {
            MessageLogger.logError("❌ PRODUCT MESSAGING TEST FAILED", {
                fromSessionId,
                toPhoneNumber,
                error: error.message,
                stack: error.stack
            });
            throw error;
        }
    }

    /**
     * Tester avec des médias spécifiques
     */
    async testWithCustomMedia(fromSessionId, toPhoneNumber, mediaType = 'smartphones') {
        const fromSession = this.sessionManager.getSession(fromSessionId);
        
        if (!fromSession || fromSession.status !== 'connected') {
            throw new Error(`Session ${fromSessionId} not found or not connected`);
        }

        // Créer un produit personnalisé avec les médias spécifiés
        const customProduct = {
            formattedProductMessage: `🛍️ *Test ${mediaType.toUpperCase()}*\n\n💰 **Prix test**\n\n📝 Produit de test avec médias ${mediaType}.\n\n📞 Interested? Contact us for more information!`,
            mediaUrls: TestData.freeMediaUrls[mediaType] || []
        };

        const mockResponse = {
            ...TestData.mockLaravelResponse,
            response_message: `Voici un produit ${mediaType} pour test :`,
            products: [customProduct]
        };

        const responseHandler = new ResponseHandler(fromSession.client, null);
        
        const context = {
            sessionId: fromSessionId,
            originalMessageId: "test_custom_" + Date.now(),
            testMode: true,
            mediaType
        };

        MessageLogger.logInfo("🧪 STARTING CUSTOM MEDIA TEST", {
            fromSessionId,
            toPhoneNumber,
            mediaType,
            mediaCount: customProduct.mediaUrls.length
        });

        return await responseHandler.handleLaravelResponse(
            mockResponse,
            toPhoneNumber,
            context
        );
    }

    /**
     * Afficher le résumé des sessions
     */
    displaySessionsSummary() {
        const activeSessions = this.getActiveSessions();
        
        console.log("\n" + "=".repeat(50));
        console.log("📱 SESSIONS ACTIVES DÉTECTÉES");
        console.log("=".repeat(50));
        
        if (activeSessions.length === 0) {
            console.log("❌ Aucune session active trouvée");
            console.log("💡 Assure-toi que tes sessions WhatsApp sont connectées");
            return [];
        }

        activeSessions.forEach((session, index) => {
            console.log(`${index + 1}. Session ID: ${session.sessionId}`);
            console.log(`   Status: ${session.status}`);
            console.log(`   User ID: ${session.userId || 'N/A'}`);
            console.log(`   Phone: ${session.phoneNumber || 'N/A'}`);
            console.log(`   Last Activity: ${session.lastActivity || 'N/A'}`);
            console.log("-".repeat(30));
        });

        return activeSessions;
    }
}

module.exports = ProductMessagingTester;