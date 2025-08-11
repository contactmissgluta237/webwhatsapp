// docker/whatsapp-bridge/src/routes/sessions.js
const express = require("express");
const fs = require("fs");
const path = require("path");
const logger = require("../config/logger");

// Helper pour les erreurs
const handleError = (res, error, message = "Operation failed") => {
    logger.error(`[Sessions] ${message}`, {
        error: error.message,
        stack: error.stack,
    });
    res.status(500).json({
        success: false,
        message: error.message,
    });
};

// Helper pour validation
const validateRequiredFields = (req, res, fields) => {
    const missing = fields.filter((field) => !req.body[field]);
    if (missing.length > 0) {
        logger.warn(`[Sessions] Missing required fields`, {
            missing,
            received: Object.keys(req.body),
        });
        res.status(400).json({
            success: false,
            message: `Required fields missing: ${missing.join(", ")}`,
        });
        return false;
    }
    return true;
};

module.exports = (whatsappManager) => {
    const router = express.Router();

    // GET /api/sessions - List all sessions
    router.get("/", (req, res) => {
        try {
            const sessions = whatsappManager.getAllSessions();
            logger.api("Sessions list requested", { count: sessions.length });

            res.json({
                success: true,
                sessions,
                count: sessions.length,
            });
        } catch (error) {
            handleError(res, error, "Failed to get sessions list");
        }
    });

    // POST /api/sessions/create - Create new session (async)
    router.post("/create", async (req, res) => {
        const { sessionId, userId } = req.body;
        const requestId = Date.now() + '-' + Math.random().toString(36).substr(2, 9);

        logger.whatsapp("🚨 Session creation request received", {
            requestId,
            requestBody: req.body,
            sessionId,
            userId,
            ip: req.ip,
            userAgent: req.get("User-Agent"),
            timestamp: new Date().toISOString()
        });

        if (!validateRequiredFields(req, res, ["sessionId", "userId"])) {
            return;
        }

        try {
            logger.session(sessionId, "Starting async initialization", { userId, requestId });

            whatsappManager
                .createSession(sessionId, userId, (message, sessionData) => {
                    return whatsappManager.messageManager.handleIncomingMessage(
                        message,
                        sessionData,
                    );
                }, { asyncInit: true })
                .catch((error) => {
                    logger.session(sessionId, "Background init failed", {
                        error: error.message,
                        userId,
                        requestId,
                    });
                });

            logger.session(sessionId, "Responding with 202 Accepted", { requestId });
            res.status(202).json({
                success: true,
                sessionId,
                userId,
                status: "initializing",
                message: "Session initialization started",
                requestId,
            });
        } catch (error) {
            logger.session(sessionId, "Session creation failed", {
                error: error.message,
                userId,
                requestId,
            });
            handleError(res, error, `Failed to create session ${sessionId}`);
        }
    });

    // GET /api/sessions/:sessionId/status - Get session status
    router.get("/:sessionId/status", async (req, res) => {
        const { sessionId } = req.params;

        try {
            logger.session(sessionId, "Status check requested");
            const status = await whatsappManager.getSessionStatus(sessionId);

            logger.session(sessionId, "Status retrieved", {
                status: status.status,
            });
            res.json(status);
        } catch (error) {
            handleError(
                res,
                error,
                `Failed to get status for session ${sessionId}`,
            );
        }
    });

    // GET /api/sessions/:sessionId/qr - Get QR code
    router.get("/:sessionId/qr", async (req, res) => {
        const { sessionId } = req.params;

        try {
            logger.session(sessionId, "QR code requested");
            const qrCode = await whatsappManager.getQRCode(sessionId);

            if (!qrCode) {
                logger.session(sessionId, "QR code not available");
                return res.status(404).json({
                    success: false,
                    message: "QR code not available",
                });
            }

            logger.session(sessionId, "QR code provided");
            res.json({
                success: true,
                qrCode,
            });
        } catch (error) {
            handleError(
                res,
                error,
                `Failed to get QR code for session ${sessionId}`,
            );
        }
    });

    // POST /api/sessions/:sessionId/send - Send message
    router.post("/:sessionId/send", async (req, res) => {
        const { sessionId } = req.params;
        const { to, message } = req.body;

        logger.session(sessionId, "Send message request", {
            to: to?.substring(0, 10) + "...", // Masquer le numéro complet
            messageLength: message?.length,
        });

        if (!validateRequiredFields(req, res, ["to", "message"])) {
            return;
        }

        try {
            const result = await whatsappManager.sendMessage(
                sessionId,
                to,
                message,
            );

            logger.session(sessionId, "Message sent", {
                success: result.success,
                to: to?.substring(0, 10) + "...",
            });

            res.json(result);
        } catch (error) {
            logger.session(sessionId, "Failed to send message", {
                error: error.message,
                to: to?.substring(0, 10) + "...",
            });
            handleError(
                res,
                error,
                `Failed to send message via session ${sessionId}`,
            );
        }
    });

    // DELETE /api/sessions/:sessionId - Delete session
    router.delete("/:sessionId", async (req, res) => {
        const { sessionId } = req.params;

        try {
            logger.session(sessionId, "Session deletion requested");
            const result = await whatsappManager.destroySession(sessionId);

            logger.session(sessionId, "Session deleted", {
                success: result.success,
            });
            res.json(result);
        } catch (error) {
            handleError(res, error, `Failed to delete session ${sessionId}`);
        }
    });

    // ===== ADMIN ROUTES =====

    // POST /api/sessions/reset-all - Reset all sessions
    router.post("/reset-all", async (req, res) => {
        try {
            logger.warn("Admin action: Reset all sessions requested", {
                ip: req.ip,
            });
            const result = await whatsappManager.destroyAllSessions();

            logger.warn("Admin action: All sessions reset completed", {
                destroyed: result.destroyed || 0,
            });
            res.json(result);
        } catch (error) {
            handleError(res, error, "Failed to reset all sessions");
        }
    });

    // POST /api/sessions/reset-user/:userId - Reset all sessions for a user
    router.post("/reset-user/:userId", async (req, res) => {
        const { userId } = req.params;

        try {
            logger.warn("Admin action: Reset user sessions", {
                userId,
                ip: req.ip,
            });
            const result = await whatsappManager.destroyAllUserSessions(
                parseInt(userId),
            );

            logger.warn("Admin action: User sessions reset completed", {
                userId,
                destroyed: result.destroyed || 0,
            });
            res.json(result);
        } catch (error) {
            handleError(
                res,
                error,
                `Failed to reset sessions for user ${userId}`,
            );
        }
    });

    // DELETE /api/sessions/force/:sessionId - Force delete session
    router.delete("/force/:sessionId", async (req, res) => {
        const { sessionId } = req.params;

        try {
            logger.warn("Admin action: Force delete session", {
                sessionId,
                ip: req.ip,
            });
            const result = await whatsappManager.forceDestroySession(sessionId);

            logger.warn("Admin action: Session force deleted", {
                sessionId,
                success: result.success,
            });
            res.json(result);
        } catch (error) {
            handleError(
                res,
                error,
                `Failed to force delete session ${sessionId}`,
            );
        }
    });

    return router;
};
