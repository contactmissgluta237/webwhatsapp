// docker/whatsapp-bridge/src/services/LaravelWebhookService.js
const axios = require("axios");
const config = require("../config/config");
const MessageLogger = require("./MessageLogger");

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

        MessageLogger.logWhatsApp("🌐 SENDING TO LARAVEL", {
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
                    timeout: 30000,
                },
            );

            const duration = Date.now() - startTime;

            MessageLogger.logWhatsApp("✅ LARAVEL RESPONSE RECEIVED", {
                sessionId: sessionData.sessionId || sessionData.userId,
                messageId: message.id._serialized,
                status: response.status,
                duration: `${duration}ms`,
                success: response.data?.success || false,
                processed: response.data?.processed || false,
                hasResponseMessage: !!response.data?.response_message,
                responseLength: response.data?.response_message?.length || 0,
                hasProducts: Array.isArray(response.data?.products) && response.data.products.length > 0,
                productsCount: response.data?.products?.length || 0
            });

            // Log error if Laravel indicates failure
            if (response.data?.success === false) {
                MessageLogger.logError("❌ LARAVEL PROCESSING FAILED", {
                    sessionId: sessionData.sessionId || sessionData.userId,
                    messageId: message.id._serialized,
                    error: response.data?.error || "Unknown error",
                    processed: response.data?.processed || false
                });
            }

            if (response.data?.response_message) {
                MessageLogger.logWhatsApp("🤖 AI RESPONSE FROM LARAVEL", {
                    sessionId: sessionData.sessionId || sessionData.userId,
                    originalMessageId: message.id._serialized,
                    aiResponse: response.data.response_message.substring(0, 100) + (response.data.response_message.length > 100 ? "..." : ""),
                    fullResponseLength: response.data.response_message.length,
                    waitTime: response.data?.wait_time_seconds || 0,
                    typingDuration: response.data?.typing_duration_seconds || 0
                });
            }

            if (response.data?.products && Array.isArray(response.data.products) && response.data.products.length > 0) {
                MessageLogger.logWhatsApp("🛍️ PRODUCTS INCLUDED IN RESPONSE", {
                    sessionId: sessionData.sessionId || sessionData.userId,
                    originalMessageId: message.id._serialized,
                    productsCount: response.data.products.length,
                    products: response.data.products.map((product, index) => ({
                        index: index + 1,
                        messageLength: product.formattedProductMessage?.length || 0,
                        mediaCount: Array.isArray(product.mediaUrls) ? product.mediaUrls.length : 0
                    }))
                });
            }

            return response.data;
        } catch (error) {
            MessageLogger.logError("❌ LARAVEL WEBHOOK FAILED", {
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

    async notifySessionStatusUpdate(sessionId, status, phoneNumber = null, sessionData = {}) {
        const payload = {
            session_id: sessionId,
            status: status, // Add status to payload
            phone_number: phoneNumber,
            whatsapp_data: {
                user_id: sessionData.userId,
                // connected_at: new Date().toISOString(), // This might not be relevant for all statuses
            },
        };

        try {
            const response = await axios.post(
                `${this.webhookBase}/session`, // Change URL
                payload,
                {
                    headers: this.getHeaders(),
                    timeout: 30000,
                },
            );

            MessageLogger.logInfo(`[Webhook] Session status updated: ${status}`, {
                sessionId,
                phoneNumber,
                status: response.status,
            });

            return response.data;
        } catch (error) {
            MessageLogger.logError(
                `[Webhook] Failed to notify session status update: ${status}`,
                {
                    sessionId,
                    phoneNumber,
                    error: error.message,
                },
            );
            throw error;
        }
    }
}

module.exports = LaravelWebhookService;
