// docker/whatsapp-bridge/src/services/LaravelWebhookService.js
const axios = require("axios");
const config = require("../config/config");

class LaravelWebhookService {
    constructor() {
        const base = (config.laravelApiUrl || "http://localhost:8000").replace(/\/$/, "");
        this.webhookBase = `${base}/api/whatsapp/webhook`;
        this.apiToken = config.apiToken || null;
    }

    getHeaders() {
        const headers = {
            "Content-Type": "application/json",
            "User-Agent": "WhatsApp-Bridge/1.0",
        };
        if (this.apiToken) {
            headers["Authorization"] = `Bearer ${this.apiToken}`;
        }
        return headers;
    }

    async notifyIncomingMessage(message, sessionData) {
        const payload = {
            event: "incoming_message",
            session_id: sessionData.userId,
            session_name: sessionData.sessionId || sessionData.userId,
            message: {
                id: message.id._serialized,
                from: message.from,
                body: message.body,
                timestamp: message.timestamp,
                type: message.type,
                isGroup: message.from.includes("@g.us"),
            },
        };

        try {
            const response = await axios.post(
                `${this.webhookBase}/incoming-message`,
                payload,
                {
                    headers: this.getHeaders(),
                    timeout: 10000,
                },
            );

            console.log(`[Webhook] Message sent to Laravel`, {
                sessionId: sessionData.userId,
                messageId: message.id._serialized,
            });

            return response.data;
        } catch (error) {
            console.error("[Webhook] Failed to send message:", error.message);
            throw error;
        }
    }

    async notifySessionConnected(sessionId, phoneNumber, sessionData) {
        const payload = {
            session_id: sessionId,
            phone_number: phoneNumber,
            whatsapp_data: {
                user_id: sessionData.userId,
                connected_at: new Date().toISOString(),
            },
        };

        try {
            const response = await axios.post(
                `${this.webhookBase}/session-connected`,
                payload,
                {
                    headers: this.getHeaders(),
                    timeout: 10000,
                },
            );

            console.log(`[Webhook] Session connection notified`, {
                sessionId,
                phoneNumber,
            });

            return response.data;
        } catch (error) {
            console.error(
                "[Webhook] Failed to notify connection:",
                error.message,
            );
            throw error;
        }
    }
}

module.exports = LaravelWebhookService;
