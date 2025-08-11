const express = require('express');

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
