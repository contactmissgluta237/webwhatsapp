// docker/whatsapp-bridge/src/managers/MessageManager.js
const LaravelWebhookService = require("../services/LaravelWebhookService");

class MessageManager {
    constructor(sessionManager) {
        this.sessionManager = sessionManager;
        this.webhookService = new LaravelWebhookService();
    }

    async handleIncomingMessage(message, sessionData) {
        try {
            const response = await this.webhookService.notifyIncomingMessage(
                message,
                sessionData,
            );

            if (response?.response_message) {
                await message.reply(response.response_message);
                console.log(`[Message] AI response sent`);
            }
        } catch (error) {
            console.error("[Message] Processing failed:", error.message);
        }
    }

    async sendMessage(sessionId, to, messageText) {
        const session = this.sessionManager.getSession(sessionId);

        if (!session || session.status !== "connected") {
            throw new Error("Session not connected");
        }

        try {
            const chatId = to.includes("@c.us") ? to : `${to}@c.us`;
            await session.client.sendMessage(chatId, messageText);

            session.lastActivity = new Date();

            return {
                success: true,
                sessionId,
                to,
                message: messageText,
                timestamp: new Date().toISOString(),
            };
        } catch (error) {
            console.error(`[Message] Send failed:`, error.message);
            throw error;
        }
    }
}

module.exports = MessageManager;
