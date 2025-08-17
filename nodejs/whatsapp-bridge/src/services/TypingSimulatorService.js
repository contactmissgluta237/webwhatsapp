const logger = require("../config/logger");

class TypingSimulatorService {
    constructor() {
        this.activeSimulations = new Map();
    }

    async simulateResponseAndSendMessage(client, fromNumber, aiResponse, waitTimeSeconds = null, typingDurationSeconds = null) {
        const simulationKey = `${fromNumber}_${Date.now()}`;
        
        try {
            // Nettoyer toute simulation précédente
            this.stopSimulation(simulationKey);
            
            logger.info("🔄 Starting typing simulation", {
                from: fromNumber,
                responseLength: aiResponse?.length || 0
            });

            // Calculer le délai de frappe basé sur la longueur de la réponse
            const baseDelay = 2000; // 2 secondes minimum
            const charDelay = Math.min(aiResponse?.length * 50 || 1000, 8000); // Max 8 secondes
            const totalDelay = baseDelay + charDelay;

            // Obtenir le chat avec gestion d'erreur
            const chatId = fromNumber.includes('@') ? fromNumber : `${fromNumber}@c.us`;
            let chat;
            
            try {
                chat = await client.getChatById(chatId);
            } catch (error) {
                logger.error("❌ Failed to get chat", { 
                    chatId, 
                    error: error.message,
                    fromNumber 
                });
                return;
            }
            
            if (!chat) {
                logger.error("❌ Chat not found", { chatId, fromNumber });
                return;
            }

            // Démarrer l'indicateur de frappe
            await chat.sendStateTyping();
            logger.info("✅ Typing indicator started", {
                from: fromNumber,
                chatId: chatId
            });

            // Maintenir l'indicateur de frappe pendant la simulation
            const typingInterval = setInterval(async () => {
                try {
                    await chat.sendStateTyping();
                    logger.debug("🔄 Typing indicator refreshed", { chatId });
                } catch (error) {
                    logger.error("❌ Failed to refresh typing indicator", { 
                        error: error.message,
                        chatId 
                    });
                }
            }, 3000); // Rafraîchir toutes les 3 secondes

            // Stocker la simulation active
            this.activeSimulations.set(simulationKey, {
                interval: typingInterval,
                startTime: Date.now()
            });

            // Attendre le délai calculé
            await new Promise(resolve => setTimeout(resolve, totalDelay));

            // Nettoyer l'indicateur de frappe
            this.stopSimulation(simulationKey);

            // Arrêter l'indicateur de frappe
            await chat.clearState();
            logger.info("⏹️ Typing indicator stopped", {
                from: fromNumber,
                chatId: chatId
            });

            // Envoyer la réponse
            if (aiResponse && aiResponse.trim()) {
                await client.sendMessage(chatId, aiResponse);
                logger.info("✅ AI response sent", {
                    to: fromNumber,
                    messageLength: aiResponse.length
                });
            }

        } catch (error) {
            logger.error("❌ Error in typing simulation", {
                error: error.message,
                stack: error.stack,
                from: fromNumber
            });
            
            // Nettoyer en cas d'erreur
            this.stopSimulation(simulationKey);
        }
    }

    stopSimulation(simulationKey) {
        const simulation = this.activeSimulations.get(simulationKey);
        if (simulation) {
            clearInterval(simulation.interval);
            this.activeSimulations.delete(simulationKey);
            logger.debug("🛑 Typing simulation stopped", { simulationKey });
        }
    }

    stopAllSimulations() {
        for (const [key, simulation] of this.activeSimulations) {
            clearInterval(simulation.interval);
        }
        this.activeSimulations.clear();
        logger.info("🛑 All typing simulations stopped");
    }

    getActiveSimulations() {
        return Array.from(this.activeSimulations.keys());
    }
}

module.exports = TypingSimulatorService;
