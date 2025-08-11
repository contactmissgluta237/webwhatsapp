// docker/whatsapp-bridge/src/managers/WhatsAppManager.js
const SessionManager = require("./SessionManager");
const MessageManager = require("./MessageManager");
const logger = require("../config/logger");

class WhatsAppManager {
    constructor(options = {}) {
        this.sessionManager = new SessionManager();
        this.messageManager = new MessageManager(this.sessionManager);
        this.laravelApiUrl = options.laravelApiUrl || null;

        logger.info("WhatsApp Manager initialized", {
            laravelApiUrl: this.laravelApiUrl
        });
    }

    async initialize() {
        logger.info("🚀 Initializing WhatsApp Manager...");
        
        try {
            // Démarrer l'autosave
            this.sessionManager.startAutosave(5); // Sauvegarde toutes les 5 minutes
            
            // Restaurer les sessions existantes
            logger.info("📱 Restoring existing WhatsApp sessions...");
            const restoreResult = await this.sessionManager.restoreSessionsFromPersistence();
            
            if (restoreResult.success) {
                logger.info("✅ Session restoration completed", {
                    restoredCount: restoreResult.restoredCount,
                    totalFound: restoreResult.totalFound,
                    sessions: restoreResult.restoredSessions?.map(s => ({
                        id: s.sessionId,
                        userId: s.userId,
                    })) || [],
                });

                // Important : Ne nettoyer qu'APRÈS la restauration et seulement si on a des sessions restaurées
                if (restoreResult.restoredCount > 0) {
                    const activeSessionIds = restoreResult.restoredSessions.map(s => s.sessionId);
                    setTimeout(async () => {
                        logger.info("🧹 Starting delayed cleanup of orphaned auth directories...");
                        const cleanupResult = await this.sessionManager.persistenceService.cleanupOrphanedAuthDirs(activeSessionIds);
                        logger.info("🧹 Cleanup completed", cleanupResult);
                    }, 30000); // Attendre 30 secondes après la restauration
                }
            } else {
                logger.warn("⚠️ Session restoration failed", {
                    error: restoreResult.error,
                });
            }
            
            return { success: true, restoreResult };
        } catch (error) {
            logger.error("❌ WhatsApp Manager initialization failed", {
                error: error.message,
                stack: error.stack,
            });
            return { success: false, error: error.message };
        }
    }

    async shutdown() {
        logger.info("🔄 Shutting down WhatsApp Manager...");
        
        try {
            // Sauvegarder les sessions actives avant fermeture
            await this.sessionManager.saveActiveSessions();
            
            // Arrêter l'autosave
            this.sessionManager.stopAutosave();
            
            logger.info("✅ WhatsApp Manager shutdown completed");
            return { success: true };
        } catch (error) {
            logger.error("❌ WhatsApp Manager shutdown failed", {
                error: error.message,
            });
            return { success: false, error: error.message };
        }
    }

    async createSession(sessionId, userId, onMessageCallback, options = {}) {
        return await this.sessionManager.createSession(
            sessionId,
            userId,
            onMessageCallback || ((message, sessionData) =>
                this.messageManager.handleIncomingMessage(message, sessionData)
            ),
            options,
        );
    }

    async sendMessage(sessionId, to, message) {
        return await this.messageManager.sendMessage(sessionId, to, message);
    }

    async destroySession(sessionId) {
        return await this.sessionManager.forceDestroy(sessionId);
    }

    async destroyAllUserSessions(userId) {
        return await this.sessionManager.destroyAllUserSessions(userId);
    }

    async destroyAllSessions() {
        return await this.sessionManager.destroyAllSessions();
    }

    getSessionStatus(sessionId) {
        return this.sessionManager.getSessionStatus(sessionId);
    }

    getQRCode(sessionId) {
        return this.sessionManager.getQRCode(sessionId);
    }

    getAllSessions() {
        return this.sessionManager.getAllSessions();
    }

    // Legacy aliases for backward compatibility
    async forceDestroySession(sessionId) {
        return await this.destroySession(sessionId);
    }

    async sendMessageFromLaravel(sessionId, to, messageText) {
        return await this.sendMessage(sessionId, to, messageText);
    }
}

module.exports = WhatsAppManager;
