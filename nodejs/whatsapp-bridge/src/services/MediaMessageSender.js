const MessageLogger = require("./MessageLogger");

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

        MessageLogger.logInfo("📎 SENDING MEDIA URLS", {
            ...context,
            to,
            mediaCount: mediaUrls.length,
            mediaUrls: mediaUrls.map(url => url.substring(0, 50) + "...")
        });

        for (let i = 0; i < mediaUrls.length; i++) {
            const mediaUrl = mediaUrls[i];
            
            try {
                await this.client.sendMessage(to, mediaUrl);
                results.sentCount++;
                
                MessageLogger.logOutgoingMessage("MEDIA SENT SUCCESSFULLY", {
                    ...context,
                    to,
                    mediaIndex: i + 1,
                    mediaUrl: mediaUrl.substring(0, 100) + (mediaUrl.length > 100 ? "..." : ""),
                    totalMedia: mediaUrls.length
                });

                // Small delay between media to avoid spam detection
                if (i < mediaUrls.length - 1) {
                    await this._delay(500);
                }
            } catch (error) {
                results.failedCount++;
                results.success = false;
                results.errors.push({
                    mediaUrl,
                    error: error.message
                });
                
                MessageLogger.logError("❌ MEDIA SEND FAILED", {
                    ...context,
                    to,
                    mediaIndex: i + 1,
                    mediaUrl,
                    error: error.message,
                    totalMedia: mediaUrls.length
                });
            }
        }

        MessageLogger.logInfo("📎 MEDIA SENDING COMPLETED", {
            ...context,
            to,
            totalMedia: mediaUrls.length,
            sentCount: results.sentCount,
            failedCount: results.failedCount,
            overallSuccess: results.success
        });

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