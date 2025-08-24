const MessageLogger = require("./MessageLogger");
const ProductMessagingConfig = require("../config/productMessagingConfig");
const { MessageMedia } = require("whatsapp-web.js");

class MediaMessageSender {
    constructor(client) {
        this.client = client;
    }

    /**
     * Send media URLs to a contact
     * @param {string} to - WhatsApp contact ID
     * @param {Array<string>} mediaUrls - Array of media URLs to send
     * @param {Object} context - Context for logging (sessionId, messageId, etc.)
     */
    async sendMediaUrls(to, mediaUrls, context = {}) {
        if (!Array.isArray(mediaUrls) || mediaUrls.length === 0) {
            MessageLogger.logDebug("No media URLs to send", { ...context, to });
            return { success: true, sentCount: 0 };
        }

        const results = {
            success: true,
            sentCount: 0,
            failedCount: 0,
            errors: []
        };

        if (ProductMessagingConfig.logging.enableDetailedMediaLogs) {
            MessageLogger.logInfo(`${ProductMessagingConfig.logging.prefixes.media} SENDING MEDIA URLS`, {
                ...context,
                to,
                mediaCount: mediaUrls.length,
                mediaUrls: mediaUrls.map(url => url.substring(0, ProductMessagingConfig.limits.urlPreviewLength) + "...")
            });
        }

        for (let i = 0; i < mediaUrls.length; i++) {
            const mediaUrl = mediaUrls[i];
            
            try {
                // Download and send media as actual file instead of URL text
                const media = await MessageMedia.fromUrl(mediaUrl, {
                    unsafeMime: true, // Permet de télécharger même si le MIME type n'est pas détectable
                    timeout: ProductMessagingConfig.media.downloadTimeout
                });
                await this.client.sendMessage(to, media);
                results.sentCount++;
                
                if (ProductMessagingConfig.logging.enableDetailedMediaLogs) {
                    MessageLogger.logOutgoingMessage(`${ProductMessagingConfig.logging.prefixes.success} MEDIA SENT SUCCESSFULLY`, {
                        ...context,
                        to,
                        mediaIndex: i + 1,
                        mediaUrl: mediaUrl.substring(0, ProductMessagingConfig.limits.urlPreviewLength) + (mediaUrl.length > ProductMessagingConfig.limits.urlPreviewLength ? "..." : ""),
                        totalMedia: mediaUrls.length,
                        mediaType: media.mimetype || 'unknown',
                        mediaSize: media.data ? media.data.length : 0
                    });
                }

                // Delay between media using config
                if (i < mediaUrls.length - 1) {
                    await this._delay(ProductMessagingConfig.delays.betweenMediaOfSameProduct);
                }
            } catch (error) {
                results.failedCount++;
                results.success = false;
                results.errors.push({
                    mediaUrl,
                    error: error.message
                });
                
                MessageLogger.logError(`${ProductMessagingConfig.logging.prefixes.error} MEDIA SEND FAILED`, {
                    ...context,
                    to,
                    mediaIndex: i + 1,
                    mediaUrl,
                    error: error.message,
                    totalMedia: mediaUrls.length,
                    errorType: error.name || 'Unknown'
                });

                // Si le téléchargement du média échoue, essayer d'envoyer l'URL en fallback
                if (ProductMessagingConfig.errorHandling.continueOnMediaError) {
                    try {
                        MessageLogger.logWarning(`${ProductMessagingConfig.logging.prefixes.warning} FALLBACK: SENDING URL AS TEXT`, {
                            ...context,
                            to,
                            mediaIndex: i + 1,
                            mediaUrl
                        });
                        
                        await this.client.sendMessage(to, `Image: ${mediaUrl}`);
                        
                        MessageLogger.logInfo(`${ProductMessagingConfig.logging.prefixes.info} FALLBACK URL SENT`, {
                            ...context,
                            to,
                            mediaIndex: i + 1,
                            mediaUrl
                        });
                    } catch (fallbackError) {
                        MessageLogger.logError(`${ProductMessagingConfig.logging.prefixes.error} FALLBACK ALSO FAILED`, {
                            ...context,
                            to,
                            mediaIndex: i + 1,
                            originalError: error.message,
                            fallbackError: fallbackError.message
                        });
                    }
                }
            }
        }

        if (ProductMessagingConfig.logging.enableTimingLogs) {
            MessageLogger.logInfo(`${ProductMessagingConfig.logging.prefixes.media} MEDIA SENDING COMPLETED`, {
                ...context,
                to,
                totalMedia: mediaUrls.length,
                sentCount: results.sentCount,
                failedCount: results.failedCount,
                overallSuccess: results.success
            });
        }

        return results;
    }

    /**
     * Simple delay utility
     * @param {number} ms - Milliseconds to delay
     */
    _delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

module.exports = MediaMessageSender;