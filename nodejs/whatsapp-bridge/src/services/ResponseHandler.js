const AIResponseSender = require("./AIResponseSender");
const ProductMessageHandler = require("./ProductMessageHandler");
const MessageLogger = require("./MessageLogger");

class ResponseHandler {
    constructor(client, typingSimulator) {
        this.client = client;
        this.aiResponseSender = new AIResponseSender(client, typingSimulator);
        this.productMessageHandler = new ProductMessageHandler(client);
    }

    /**
     * Process Laravel webhook response and handle all components
     * @param {Object} response - Laravel webhook response
     * @param {string} to - WhatsApp contact ID to send to
     * @param {Object} context - Context for logging (sessionId, originalMessageId, etc.)
     */
    async handleLaravelResponse(response, to, context = {}) {
        if (!response) {
            MessageLogger.logError("❌ NO RESPONSE FROM LARAVEL", { ...context, to });
            return { success: false, reason: "No response from Laravel" };
        }

        // Check if Laravel processing was successful
        if (response.success === false) {
            MessageLogger.logError("❌ LARAVEL PROCESSING ERROR", {
                ...context,
                to,
                error: response.error || "Unknown error from Laravel",
                processed: response.processed || false
            });
            return { success: false, reason: response.error || "Laravel processing failed" };
        }

        const results = {
            success: true,
            aiResponse: null,
            products: null
        };

        // 1. Handle AI response first (if exists)
        if (response.response_message) {
            const waitTime = response.wait_time_seconds || 0;
            const typingDuration = response.typing_duration_seconds || 2;
            
            try {
                results.aiResponse = await this.aiResponseSender.sendResponse(
                    response.response_message,
                    to,
                    waitTime,
                    typingDuration,
                    context
                );
            } catch (error) {
                MessageLogger.logError("❌ AI RESPONSE HANDLING FAILED", {
                    ...context,
                    to,
                    error: error.message
                });
                results.success = false;
                results.aiResponse = { success: false, error: error.message };
            }
        }

        // 2. Handle products (if exists)
        if (response.products && Array.isArray(response.products) && response.products.length > 0) {
            try {
                results.products = await this.productMessageHandler.handleProducts(
                    response.products,
                    to,
                    context
                );
            } catch (error) {
                MessageLogger.logError("❌ PRODUCTS HANDLING FAILED", {
                    ...context,
                    to,
                    error: error.message
                });
                results.success = false;
                results.products = { success: false, error: error.message };
            }
        }

        MessageLogger.logIncomingMessage("✅ LARAVEL RESPONSE HANDLING COMPLETED", {
            ...context,
            to,
            overallSuccess: results.success,
            hadAiResponse: !!results.aiResponse,
            hadProducts: !!results.products,
            aiSuccess: results.aiResponse?.success,
            productsSuccess: results.products?.success
        });

        return results;
    }
}

module.exports = ResponseHandler;