// docker/whatsapp-bridge/src/services/LaravelWebhookService.js
const axios = require("axios");
const config = require("../config/config");
const logger = require("../config/logger");

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
            session_id: sessionData.sessionId, // Utiliser le vrai sessionId au lieu de userId
            session_name: sessionData.sessionId,
            message: {
                id: message.id._serialized,
                from: message.from,
                body: message.body,
                timestamp: message.timestamp,
                type: message.type,
                isGroup: message.from.includes("@g.us"),
            },
        };

        logger.whatsapp("🌐 SENDING TO LARAVEL", {
            sessionId: sessionData.sessionId,
            messageId: message.id._serialized,
            webhookUrl: `${this.webhookBase}/incoming-message`,
            payloadSize: JSON.stringify(payload).length,
            hasAuth: !!this.apiToken
        });

        try {
            const startTime = Date.now();
            const response = await axios.post(
                `${this.webhookBase}/incoming-message`,
                payload,
                {
                    headers: this.getHeaders(),
                    timeout: 10000,
                },
            );

            const duration = Date.now() - startTime;

            logger.whatsapp("✅ LARAVEL RESPONSE RECEIVED", {
                sessionId: sessionData.sessionId || sessionData.userId,
                messageId: message.id._serialized,
                status: response.status,
                duration: `${duration}ms`,
                hasResponseMessage: !!response.data?.response_message,
                responseLength: response.data?.response_message?.length || 0
            });

            if (response.data?.response_message) {
                logger.whatsapp("🤖 AI RESPONSE FROM LARAVEL", {
                    sessionId: sessionData.sessionId || sessionData.userId,
                    originalMessageId: message.id._serialized,
                    aiResponse: response.data.response_message.substring(0, 100) + (response.data.response_message.length > 100 ? "..." : ""),
                    fullResponseLength: response.data.response_message.length
                });
            }

            return response.data;
        } catch (error) {
            logger.error("❌ LARAVEL WEBHOOK FAILED", {
                sessionId: sessionData.sessionId || sessionData.userId,
                messageId: message.id._serialized,
                error: error.message,
                webhookUrl: `${this.webhookBase}/incoming-message`,
                statusCode: error.response?.status,
                responseData: error.response?.data,
                stack: error.stack
            });
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
