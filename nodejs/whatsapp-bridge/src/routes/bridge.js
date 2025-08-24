const express = require('express');
const { MessageMedia } = require("whatsapp-web.js");

module.exports = (whatsappManager) => {
    const router = express.Router();

    router.get('/', (req, res) => {
        res.json({
            success: true,
            endpoints: {
                sendMessage: { method: 'POST', path: '/api/bridge/send-message' },
                sessionStatus: { method: 'GET', path: '/api/bridge/session/:sessionId/status' },
            },
        });
    });

    // Helper function to check if message is a media URL (image, video, document, etc.)
    const isMediaUrl = (text) => {
        // Images
        const imageUrlRegex = /^https?:\/\/.*\.(jpg|jpeg|png|gif|webp|bmp|tiff|svg)($|\?)/i;
        const unsplashRegex = /^https?:\/\/images\.unsplash\.com\//i;
        
        // Videos
        const videoUrlRegex = /^https?:\/\/.*\.(mp4|avi|mov|wmv|flv|webm|mkv|m4v)($|\?)/i;
        
        // Documents
        const documentUrlRegex = /^https?:\/\/.*\.(pdf|doc|docx|xls|xlsx|ppt|pptx|txt|rtf)($|\?)/i;
        
        // Audio
        const audioUrlRegex = /^https?:\/\/.*\.(mp3|wav|ogg|aac|flac|m4a|wma)($|\?)/i;
        
        // Archives
        const archiveUrlRegex = /^https?:\/\/.*\.(zip|rar|7z|tar|gz)($|\?)/i;
        
        // Known media hosting domains
        const mediaHostingRegex = /^https?:\/\/(images|video|media|files|docs|drive)\./i;
        
        return imageUrlRegex.test(text) || 
               unsplashRegex.test(text) ||
               videoUrlRegex.test(text) ||
               documentUrlRegex.test(text) ||
               audioUrlRegex.test(text) ||
               archiveUrlRegex.test(text) ||
               mediaHostingRegex.test(text);
    };

    // Laravel can send messages through WhatsApp
    router.post('/send-message', async (req, res) => {
        try {
            const { session_id, to, message } = req.body;

            if (!session_id || !to || !message) {
                return res.status(400).json({
                    success: false,
                    message: 'Missing required fields: session_id, to, message'
                });
            }

            // Check if message is a media URL - if so, download and send as media
            if (isMediaUrl(message)) {
                try {
                    console.log(`[Bridge] Detected media URL, downloading: ${message.substring(0, 60)}...`);
                    
                    const media = await MessageMedia.fromUrl(message, { 
                        unsafeMime: true,
                        timeout: 30000 
                    });
                    
                    console.log(`[Bridge] Media downloaded: ${media.mimetype}, ${(media.data.length/1024).toFixed(1)}KB`);
                    
                    const result = await whatsappManager.sendMediaMessage(session_id, to, media);
                    
                    if (result.success) {
                        res.json({
                            success: true,
                            data: result,
                            message: 'Media sent successfully',
                            mediaType: media.mimetype,
                            mediaSize: media.data.length
                        });
                    } else {
                        // Fallback to text if media send fails
                        const textResult = await whatsappManager.sendMessageFromLaravel(session_id, to, message);
                        res.json({
                            success: textResult.success,
                            data: textResult,
                            message: 'Media send failed, sent as text URL',
                            fallback: true
                        });
                    }
                } catch (mediaError) {
                    console.log(`[Bridge] Media download failed: ${mediaError.message}, sending as text`);
                    
                    // Fallback to sending as text
                    const result = await whatsappManager.sendMessageFromLaravel(session_id, to, message);
                    
                    if (result.success) {
                        res.json({
                            success: true,
                            data: result,
                            message: 'Media download failed, sent as text URL',
                            fallback: true
                        });
                    } else {
                        res.status(400).json({
                            success: false,
                            message: result.message
                        });
                    }
                }
            } else {
                // Regular text message
                const result = await whatsappManager.sendMessageFromLaravel(session_id, to, message);

                if (result.success) {
                    res.json({
                        success: true,
                        data: result,
                        message: 'Message sent successfully'
                    });
                } else {
                    res.status(400).json({
                        success: false,
                        message: result.message
                    });
                }
            }
        } catch (error) {
            console.error('[Bridge] Send message error:', error);
            res.status(500).json({
                success: false,
                message: 'Internal server error'
            });
        }
    });

    // Get session status for Laravel
    router.get('/session/:sessionId/status', async (req, res) => {
        try {
            const { sessionId } = req.params;
            const status = await whatsappManager.getSessionStatus(sessionId);
            
            res.json({
                success: true,
                data: status
            });
        } catch (error) {
            console.error('[Bridge] Get status error:', error);
            res.status(500).json({
                success: false,
                message: 'Internal server error'
            });
        }
    });

    return router;
};
