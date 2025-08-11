// docker/whatsapp-bridge/src/server.js
const express = require("express");
const cors = require("cors");
const helmet = require("helmet");
const morgan = require("morgan");
const logger = require("./config/logger");
const WhatsAppManager = require("./managers/WhatsAppManager");
const ApiDocumentation = require("./config/apiDocumentation");
const path = require("path");

const config = require("./config/config");

const app = express();
const PORT = process.env.PORT || config.port || 3000;

// ===== MIDDLEWARE SETUP =====
app.use(helmet());
app.use(cors());

// Auth API Token (si défini)
app.use((req, res, next) => {
    const publicGetPaths = new Set(["/", "/health", "/api/bridge"]);
    if (req.method === "GET" && publicGetPaths.has(req.path)) return next();
    if (!config.apiToken) return next();
    const auth = req.headers["authorization"] || "";
    const token = auth.startsWith("Bearer ") ? auth.slice(7) : null;
    if (token === config.apiToken) return next();
    logger.warn("Unauthorized access", { url: req.url, ip: req.ip });
    return res.status(401).json({ success: false, message: "Unauthorized" });
});

// Morgan avec Winston
app.use(
    morgan("combined", {
        stream: {
            write: (message) => {
                logger.api(message.trim());
            },
        },
    }),
);

app.use(express.json({ limit: "10mb" }));
app.use(express.urlencoded({ extended: true, limit: "10mb" }));

// ===== INITIALIZE SERVICES =====
logger.info("🔧 Initializing WhatsApp Bridge services...");
const whatsappManager = new WhatsAppManager({
    laravelApiUrl: config.laravelApiUrl,
});
logger.info("✅ WhatsApp Manager created");

// Fonction d'initialisation asynchrone
async function initializeServices() {
    try {
        const initResult = await whatsappManager.initialize();
        if (initResult.success) {
            logger.info("🎉 All services initialized successfully");
            return true;
        } else {
            logger.error("❌ Service initialization failed", {
                error: initResult.error,
            });
            return false;
        }
    } catch (error) {
        logger.error("❌ Critical initialization error", {
            error: error.message,
            stack: error.stack,
        });
        return false;
    }
}

// ===== UTILITY FUNCTIONS =====
const handleError = (res, error, message = "Operation failed") => {
    logger.error(`[Server] ${message}`, {
        error: error.message,
        stack: error.stack,
    });
    res.status(500).json(ApiDocumentation.getErrorResponse(error.message));
};

// ===== HEALTH CHECK =====
app.get("/health", (req, res) => {
    try {
        const healthInfo = ApiDocumentation.getHealthInfo();
        logger.api("Health check requested", {
            status: "healthy",
            uptime: process.uptime(),
        });
        res.status(200).json(healthInfo);
    } catch (error) {
        handleError(res, error, "Health check failed");
    }
});

// ===== MAIN ROUTES =====
logger.info("🛣️ Setting up API routes...");
app.use("/api/sessions", require("./routes/sessions")(whatsappManager));
app.use("/api/bridge", require("./routes/bridge")(whatsappManager));
logger.info("✅ API routes configured");

// ===== ADMIN ENDPOINTS =====
app.post("/api/admin/clear-cache", async (req, res) => {
    try {
        logger.warn("Admin action: Clear cache requested", {
            ip: req.ip,
            userAgent: req.get("User-Agent"),
        });

        const result = await whatsappManager.destroyAllSessions();

        logger.warn("Admin action: Cache cleared", {
            destroyed: result.destroyed || 0,
        });

        res.json({
            success: true,
            message: "Cache cleared successfully",
            timestamp: new Date().toISOString(),
            details: result,
        });
    } catch (error) {
        handleError(res, error, "Failed to clear cache");
    }
});

app.post("/api/admin/cleanup-sessions", async (req, res) => {
    try {
        logger.warn("Admin action: Session cleanup requested", {
            ip: req.ip,
            userAgent: req.get("User-Agent"),
        });

        // Obtenir la liste des sessions actives
        const activeSessions = whatsappManager.getAllSessions();
        const activeSessionIds = activeSessions.map(s => s.sessionId);

        // Nettoyer les répertoires orphelins
        const cleanupResult = await whatsappManager.sessionManager.persistenceService.cleanupOrphanedAuthDirs(activeSessionIds);

        logger.warn("Admin action: Session cleanup completed", {
            activeSessionsCount: activeSessionIds.length,
            cleanupResult,
        });

        res.json({
            success: true,
            message: "Session cleanup completed successfully",
            timestamp: new Date().toISOString(),
            details: {
                activeSessionsCount: activeSessionIds.length,
                activeSessionIds,
                cleanupResult,
            },
        });
    } catch (error) {
        handleError(res, error, "Failed to cleanup sessions");
    }
});

app.get("/api/admin/saved-sessions", async (req, res) => {
    try {
        logger.info("Admin action: Checking saved sessions", {
            ip: req.ip,
            userAgent: req.get("User-Agent"),
        });

        const loadResult = await whatsappManager.sessionManager.persistenceService.loadActiveSessions();
        const authResult = await whatsappManager.sessionManager.persistenceService.getAuthDirectorySessions();

        const analysis = {
            savedSessions: loadResult.sessions || {},
            authDirectories: authResult.authSessions || [],
            currentActiveSessions: whatsappManager.getAllSessions(),
        };

        // Analyser les sessions perdues
        const savedSessionIds = Object.keys(analysis.savedSessions);
        const authSessionIds = analysis.authDirectories.map(a => a.sessionId);
        const activeSessionIds = analysis.currentActiveSessions.map(s => s.sessionId);

        const lostSessions = savedSessionIds.filter(id => 
            !authSessionIds.includes(id) && !activeSessionIds.includes(id)
        );

        res.json({
            success: true,
            analysis: {
                savedSessionsCount: savedSessionIds.length,
                authDirectoriesCount: authSessionIds.length,
                activeSessionsCount: activeSessionIds.length,
                lostSessionsCount: lostSessions.length,
                lostSessions: lostSessions.map(id => ({
                    sessionId: id,
                    ...analysis.savedSessions[id]
                }))
            },
            details: analysis,
        });
    } catch (error) {
        handleError(res, error, "Failed to check saved sessions");
    }
});

app.get("/api/admin/stats", (req, res) => {
    try {
        const sessions = whatsappManager.getAllSessions();
        const stats = {
            total_sessions: sessions.length,
            by_status: {},
            by_user: {},
            memory_usage: process.memoryUsage(),
            uptime: process.uptime(),
            timestamp: new Date().toISOString(),
        };

        sessions.forEach((session) => {
            stats.by_status[session.status] =
                (stats.by_status[session.status] || 0) + 1;
            stats.by_user[session.userId] =
                (stats.by_user[session.userId] || 0) + 1;
        });

        logger.api("Admin stats requested", {
            totalSessions: sessions.length,
            ip: req.ip,
        });

        res.json({
            success: true,
            stats,
            sessions,
        });
    } catch (error) {
        handleError(res, error, "Failed to get stats");
    }
});

// ===== START SERVER =====
const server = app.listen(PORT, "0.0.0.0", async () => {
    logger.info(`🚀 WhatsApp Bridge server started on port ${PORT}`);

    // Initialiser les services après le démarrage du serveur
    const initSuccess = await initializeServices();

    if (initSuccess) {
        logger.info("=".repeat(60));
        logger.info(`🎉 WhatsApp Bridge Server is fully ready!`);
        logger.info(`📋 API Documentation: http://localhost:${PORT}`);
        logger.info(`🔍 Health Check: http://localhost:${PORT}/health`);
        logger.info(`📱 Sessions API: http://localhost:${PORT}/api/sessions`);
        logger.info(`🌉 Bridge API: http://localhost:${PORT}/api/bridge`);
        logger.info(`📊 Environment: ${process.env.NODE_ENV || "development"}`);
        logger.info("=".repeat(60));
    } else {
        logger.warn("⚠️ Server started but services initialization failed");
    }
});

// ===== ERROR HANDLING =====
server.on("error", (error) => {
    if (error.code === "EADDRINUSE") {
        logger.error(`❌ Port ${PORT} is already in use`);
    } else {
        logger.error("❌ Server error:", error);
    }
    process.exit(1);
});

// ===== GRACEFUL SHUTDOWN =====
const gracefulShutdown = async (signal) => {
    logger.warn(`🛑 Received ${signal}, shutting down gracefully...`);

    try {
        // Sauvegarder et arrêter les services WhatsApp
        const shutdownResult = await whatsappManager.shutdown();
        if (shutdownResult.success) {
            logger.info("✅ WhatsApp services stopped gracefully");
        } else {
            logger.warn("⚠️ WhatsApp services shutdown had issues");
        }
    } catch (error) {
        logger.error("❌ Error during WhatsApp services shutdown", {
            error: error.message,
        });
    }

    server.close(() => {
        logger.warn("✅ HTTP server closed");
        logger.warn("👋 WhatsApp Bridge stopped");
        process.exit(0);
    });

    // Force close after 30 seconds
    setTimeout(() => {
        logger.error("❌ Forced shutdown after timeout");
        process.exit(1);
    }, 30000);
};

process.on("SIGTERM", () => gracefulShutdown("SIGTERM"));
process.on("SIGINT", () => gracefulShutdown("SIGINT"));

// ===== UNHANDLED ERRORS =====
process.on("unhandledRejection", (reason, promise) => {
    logger.error("Unhandled Rejection", {
        reason: reason?.message || reason,
        promise: promise.toString(),
    });
});

process.on("uncaughtException", (error) => {
    logger.error("Uncaught Exception", {
        error: error.message,
        stack: error.stack,
    });
    process.exit(1);
});

module.exports = server;
