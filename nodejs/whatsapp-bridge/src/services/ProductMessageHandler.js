const MediaMessageSender = require("./MediaMessageSender");
const MessageLogger = require("./MessageLogger");
const ProductMessagingConfig = require("../config/productMessagingConfig");

class ProductMessageHandler {
    constructor(client) {
        this.client = client;
        this.mediaMessageSender = new MediaMessageSender(client);
    }

    /**
     * Process and send all products from Laravel response
     * @param {Array} products - Array of product objects from Laravel
     * @param {string} to - WhatsApp contact ID to send to
     * @param {Object} context - Context for logging (sessionId, originalMessageId, etc.)
     */
    async handleProducts(products, to, context = {}) {
        if (!Array.isArray(products) || products.length === 0) {
            MessageLogger.logDebug("No products to handle", { ...context, to });
            return { success: true, processedCount: 0 };
        }

        if (ProductMessagingConfig.logging.enableDetailedProductLogs) {
            MessageLogger.logIncomingMessage(`${ProductMessagingConfig.logging.prefixes.product} PROCESSING PRODUCTS`, {
                ...context,
                to,
                productsCount: products.length
            });
        }

        const results = {
            success: true,
            processedCount: 0,
            failedCount: 0,
            errors: []
        };

        for (let i = 0; i < products.length; i++) {
            const product = products[i];
            const productContext = {
                ...context,
                productIndex: i + 1,
                totalProducts: products.length
            };

            try {
                await this._handleSingleProduct(product, to, productContext);
                results.processedCount++;
                
                // Delay between products using config
                if (i < products.length - 1) {
                    await this._delay(ProductMessagingConfig.delays.betweenProducts);
                }
            } catch (error) {
                results.failedCount++;
                results.success = false;
                results.errors.push({
                    productIndex: i + 1,
                    error: error.message
                });
                
                MessageLogger.logError(`${ProductMessagingConfig.logging.prefixes.error} PRODUCT PROCESSING FAILED`, {
                    ...productContext,
                    to,
                    error: error.message,
                    stack: error.stack
                });
            }
        }

        if (ProductMessagingConfig.logging.enableTimingLogs) {
            MessageLogger.logIncomingMessage(`${ProductMessagingConfig.logging.prefixes.product} PRODUCTS PROCESSING COMPLETED`, {
                ...context,
                to,
                totalProducts: products.length,
                processedCount: results.processedCount,
                failedCount: results.failedCount,
                overallSuccess: results.success
            });
        }

        return results;
    }

    /**
     * Handle a single product (message + media)
     * @param {Object} product - Single product object
     * @param {string} to - WhatsApp contact ID
     * @param {Object} context - Context for logging
     */
    async _handleSingleProduct(product, to, context) {
        MessageLogger.logIncomingMessage("📦 PROCESSING SINGLE PRODUCT", {
            ...context,
            to,
            hasMessage: !!product.formattedProductMessage,
            hasMedia: Array.isArray(product.mediaUrls) && product.mediaUrls.length > 0,
            mediaCount: product.mediaUrls?.length || 0
        });

        // 1. Send the formatted product message first
        if (product.formattedProductMessage) {
            await this._sendProductMessage(product.formattedProductMessage, to, context);
        } else {
            MessageLogger.logWarning("⚠️ PRODUCT WITHOUT MESSAGE", {
                ...context,
                to
            });
        }

        // 2. Send all media URLs if they exist
        if (product.mediaUrls && Array.isArray(product.mediaUrls) && product.mediaUrls.length > 0) {
            await this.mediaMessageSender.sendMediaUrls(to, product.mediaUrls, context);
        }
    }

    /**
     * Send the formatted product message
     * @param {string} message - Formatted product message
     * @param {string} to - WhatsApp contact ID
     * @param {Object} context - Context for logging
     */
    async _sendProductMessage(message, to, context) {
        try {
            await this.client.sendMessage(to, message);
            
            MessageLogger.logOutgoingMessage("PRODUCT MESSAGE SENT", {
                ...context,
                to,
                messageLength: message.length,
                messagePreview: message.substring(0, 100) + (message.length > 100 ? "..." : "")
            });
        } catch (error) {
            MessageLogger.logError("❌ PRODUCT MESSAGE SEND FAILED", {
                ...context,
                to,
                error: error.message,
                messageLength: message.length
            });
            throw error;
        }
    }

    /**
     * Simple delay utility
     * @param {number} ms - Milliseconds to delay
     */
    _delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

module.exports = ProductMessageHandler;