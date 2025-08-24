const MessageLogger = require("./MessageLogger");

class AIResponseSender {
    constructor(client, typingSimulator) {
        this.client = client;
        this.typingSimulator = typingSimulator;
    }

    /**
     * Send AI response with typing simulation
     * @param {string} responseMessage - AI response message
     * @param {string} to - WhatsApp contact ID to send to
     * @param {number} waitTimeSeconds - Time to wait before starting typing
     * @param {number} typingDurationSeconds - Duration of typing simulation
     * @param {Object} context - Context for logging (sessionId, originalMessageId, etc.)
     */
    async sendResponse(responseMessage, to, waitTimeSeconds = 0, typingDurationSeconds = 2, context = {}) {
        if (!responseMessage) {
            MessageLogger.logDebug("No AI response message to send", { ...context, to });
            return { success: false, reason: "No message to send" };
        }

        MessageLogger.logIncomingMessage("🤖 AI RESPONSE TIMING", {
            ...context,
            to,
            waitTimeSeconds,
            typingDurationSeconds,
            responseLength: responseMessage.length
        });

        try {
            // Use TypingSimulatorService with Laravel timings
            await this.typingSimulator.simulateResponseAndSendMessage(
                this.client,
                to,
                responseMessage,
                waitTimeSeconds,
                typingDurationSeconds
            );

            MessageLogger.logOutgoingMessage("AI RESPONSE SENT", {
                ...context,
                to,
                responseText: responseMessage.substring(0, 100) + (responseMessage.length > 100 ? "..." : ""),
                responseLength: responseMessage.length,
                waitTimeUsed: waitTimeSeconds,
                typingDurationUsed: typingDurationSeconds
            });

            return { success: true };
        } catch (error) {
            MessageLogger.logError("❌ AI RESPONSE SEND FAILED", {
                ...context,
                to,
                error: error.message,
                responseLength: responseMessage.length,
                stack: error.stack
            });

            // Fallback: try direct message without typing simulation
            try {
                MessageLogger.logWarning("⚠️ FALLBACK: SENDING WITHOUT TYPING SIMULATION", {
                    ...context,
                    to
                });

                await this.client.sendMessage(to, responseMessage);
                
                MessageLogger.logOutgoingMessage("AI RESPONSE SENT (FALLBACK)", {
                    ...context,
                    to,
                    responseText: responseMessage.substring(0, 100) + (responseMessage.length > 100 ? "..." : ""),
                    responseLength: responseMessage.length
                });

                return { success: true, fallback: true };
            } catch (fallbackError) {
                MessageLogger.logError("❌ AI RESPONSE FALLBACK ALSO FAILED", {
                    ...context,
                    to,
                    error: fallbackError.message,
                    originalError: error.message
                });

                throw fallbackError;
            }
        }
    }
}

module.exports = AIResponseSender;