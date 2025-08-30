const { Client, LocalAuth } = require("whatsapp-web.js");
const qrcode = require("qrcode-terminal");
const FileSystemService = require("../services/FileSystemService");
const LaravelWebhookService = require("../services/LaravelWebhookService");
const SessionPersistenceService = require("../services/SessionPersistenceService");
const logger = require("../config/logger");

class SessionManager {
    constructor() {
        this.sessions = new Map();
        this.webhookService = new LaravelWebhookService();
        this.persistenceService = new SessionPersistenceService();
        this.autosaveInterval = null;
        this.isRestoring = false;
        this.defaultMessageCallback = null; // Callback par défaut pour les sessions restaurées
    }

    setDefaultMessageCallback(callback) {
        logger.info("🔧 Configuration du callback par défaut pour les sessions", {
            callbackType: typeof callback,
            callbackExists: !!callback
        });
        this.defaultMessageCallback = callback;
    }

    async createSession(sessionId, userId, onMessageCallback, options = {}) {
        const asyncInit = options.asyncInit === true;
        logger.session(sessionId, "Creating session", { userId, asyncInit });

        if (this.sessions.has(sessionId)) {
            logger.warn(`Session ${sessionId} already exists`, { sessionId, userId });
            throw new Error(`Session ${sessionId} already exists`);
        }

        const client = new Client({
            authStrategy: new LocalAuth({ clientId: sessionId }),
            puppeteer: {
                headless: true,
                args: [
                    "--no-sandbox",
                    "--disable-setuid-sandbox",
                    "--disable-dev-shm-usage",
                    "--disable-accelerated-2d-canvas",
                    "--no-first-run",
                    "--no-zygote",
                    "--disable-gpu",
                    "--disable-background-timer-throttling",
                    "--disable-backgrounding-occluded-windows",
                    "--disable-renderer-backgrounding"
                ],
                handleSIGINT: false,
                timeout: 60000,
            },
            webVersionCache: {
                type: "remote",
                remotePath: "https://raw.githubusercontent.com/wppconnect-team/wa-version/main/html/2.2412.54.html",
            },
        });

        const sessionData = {
            client,
            sessionId,
            userId,
            status: "initializing",
            qrCode: null,
            lastActivity: new Date(),
            createdAt: new Date(),
        };

        this.webhookService.notifySessionStatusUpdate(sessionId, "initializing", null, sessionData);

        this.setupClientHandlers(
            client,
            sessionId,
            sessionData,
            onMessageCallback,
        );
        this.sessions.set(sessionId, sessionData);

        // Sauvegarder immédiatement les sessions actives
        await this.saveActiveSessions();

        if (asyncInit) {
            logger.session(sessionId, "Starting ASYNC initialization", { userId });
            client
                .initialize()
                .catch(async (error) => {
                    logger.session(sessionId, "Initialize error", {
                        error: error.message,
                        userId
                    });
                    sessionData.status = "error";
                    this.webhookService.notifySessionStatusUpdate(sessionId, "error", null, sessionData);
                    try {
                        await this.forceDestroy(sessionId);
                    } catch (_) {}
                });

            logger.session(sessionId, "Returning immediately (async mode)", { userId });
            return {
                success: true,
                sessionId,
                userId,
                status: sessionData.status,
                initializing: true,
            };
        }

        try {
            await client.initialize();
            return {
                success: true,
                sessionId,
                userId,
                status: sessionData.status,
            };
        } catch (error) {
            await this.forceDestroy(sessionId);
            throw error;
        }
    }

    setupClientHandlers(client, sessionId, sessionData, onMessageCallback) {
        client.on("qr", (qr) => {
            sessionData.qrCode = qr;
            sessionData.status = "qr_code_ready";
            this.webhookService.notifySessionStatusUpdate(sessionId, "qr_code_ready", null, sessionData);
            logger.session(sessionId, "QR generated", {
                qrLength: qr.length,
                userId: sessionData.userId
            });
            qrcode.generate(qr, { small: true });
        });

        client.on("authenticated", () => {
            sessionData.status = "authenticating";
            sessionData.qrCode = null;
            this.webhookService.notifySessionStatusUpdate(sessionId, "authenticating", null, sessionData);
            logger.session(sessionId, "Authentication successful", {
                userId: sessionData.userId
            });
        });

        client.on("ready", async () => {
            sessionData.status = "connected";
            sessionData.qrCode = null;
            logger.session(sessionId, "Session ready", {
                userId: sessionData.userId
            });

            try {
                const info = await client.info;
                const phoneNumber = info.wid.user;
                
                // Stocker le numéro de téléphone dans les données de session
                sessionData.phoneNumber = phoneNumber;
                
                logger.session(sessionId, "Phone connected", {
                    phoneNumber: phoneNumber.substring(0, 5) + "...",
                    userId: sessionData.userId
                });
                await this.webhookService.notifySessionStatusUpdate(
                    sessionId,
                    "connected",
                    phoneNumber,
                    sessionData,
                );
            } catch (error) {
                logger.session(sessionId, "Failed to notify connection", {
                    error: error.message,
                    userId: sessionData.userId
                });
            }
        });

        client.on("message", async (message) => {
            sessionData.lastActivity = new Date();
            
            console.log("🔥 TRACE: RAW MESSAGE EVENT", {
                sessionId: sessionId,
                messageId: message.id._serialized,
                from: message.from,
                body: message.body?.substring(0, 50),
                fromMe: message.fromMe,
                type: message.type
            });
            
            logger.whatsapp("📬 RAW MESSAGE EVENT", {
                sessionId: sessionId,
                userId: sessionData.userId,
                messageId: message.id._serialized,
                from: message.from,
                type: message.type,
                fromMe: message.fromMe,
                isGroup: message.from.includes("@g.us"),
                hasMedia: message.hasMedia,
                timestamp: message.timestamp,
                deviceType: message.deviceType || null
            });

            if (message.fromMe) {
                // Message sortant détecté
                logger.info("🔍 DEBUG: Message sortant détecté dans SessionManager", {
                    sessionId: sessionId,
                    messageId: message.id._serialized
                });
                logger.outgoingMessage("OUTGOING MESSAGE DETECTED", {
                    sessionId: sessionId,
                    messageId: message.id._serialized,
                    to: message.to,
                    userId: sessionData.userId,
                    messageLength: message.body?.length || 0,
                    timestamp: message.timestamp
                });
                return;
            }

            if (message.from.includes("@c.us") || message.from.includes("@g.us")) {
                logger.info("🔍 DEBUG: Message entrant détecté dans SessionManager", {
                    sessionId: sessionId,
                    messageId: message.id._serialized
                });
                logger.incomingMessage("INCOMING MESSAGE DETECTED", {
                    sessionId: sessionId,
                    userId: sessionData.userId,
                    from: message.from,
                    messageId: message.id._serialized,
                    messageLength: message.body?.length || 0,
                    isGroup: message.from.includes("@g.us"),
                    processingTime: new Date().toISOString()
                });
                
                logger.info("🔥 DEBUG: AVANT appel onMessageCallback", {
                    sessionId: sessionId,
                    messageId: message.id._serialized,
                    callbackType: typeof onMessageCallback,
                    callbackExists: !!onMessageCallback
                });
                
                try {
                    await onMessageCallback(message, { ...sessionData, sessionId });
                    
                    logger.info("✅ DEBUG: APRÈS appel onMessageCallback - succès", {
                        sessionId: sessionId,
                        messageId: message.id._serialized
                    });
                } catch (error) {
                    logger.error("❌ MESSAGE CALLBACK ERROR", {
                        sessionId: sessionId,
                        userId: sessionData.userId,
                        messageId: message.id._serialized,
                        error: error.message,
                        stack: error.stack
                    });
                }
            } else {
                logger.whatsapp("❌ UNKNOWN MESSAGE TYPE", {
                    sessionId: sessionId,
                    userId: sessionData.userId,
                    from: message.from,
                    messageId: message.id._serialized,
                    type: message.type
                });
            }
        });

        client.on("disconnected", () => {
            sessionData.status = "disconnected";
            this.webhookService.notifySessionStatusUpdate(sessionId, "disconnected", null, sessionData);
            logger.session(sessionId, "Session disconnected", {
                userId: sessionData.userId
            });
        });
    }

    async forceDestroy(sessionId) {
        const session = this.sessions.get(sessionId);
        if (session?.client) {
            try {
                await session.client.destroy();
                logger.session(sessionId, "Client destroyed successfully", {
                    userId: session.userId
                });
            } catch (error) {
                logger.session(sessionId, "Destroy error", {
                    error: error.message,
                    userId: session.userId
                });
            }
        }

        this.sessions.delete(sessionId);
        await FileSystemService.cleanupSessionFiles(sessionId);

        // Sauvegarder après suppression
        await this.saveActiveSessions();

        return { success: true, sessionId };
    }

    async destroyAllUserSessions(userId) {
        const destroyedSessions = [];
        for (const [sessionId, sessionData] of this.sessions.entries()) {
            if (sessionData.userId === userId) {
                await this.forceDestroy(sessionId);
                destroyedSessions.push(sessionId);
            }
        }
        return { success: true, sessions: destroyedSessions };
    }

    async destroyAllSessions() {
        const destroyedSessions = [];
        for (const [sessionId] of this.sessions.entries()) {
            await this.forceDestroy(sessionId);
            destroyedSessions.push(sessionId);
        }
        return { success: true, sessions: destroyedSessions };
    }

    async restoreSessionsFromPersistence() {
        if (this.isRestoring) {
            logger.warn("Session restoration already in progress");
            return { success: false, error: "Restoration already in progress" };
        }

        this.isRestoring = true;
        logger.info("Starting session restoration from persistence");

        try {
            const loadResult = await this.persistenceService.loadActiveSessions();
            if (!loadResult.success) {
                return loadResult;
            }

            const savedSessions = loadResult.sessions;
            const sessionIds = Object.keys(savedSessions);

            if (sessionIds.length === 0) {
                logger.info("No sessions to restore");
                return { success: true, restoredCount: 0 };
            }

            logger.info("Found saved sessions to restore", {
                sessionCount: sessionIds.length,
                sessionIds,
            });

            let restoredCount = 0;
            const restoredSessions = [];

            for (const sessionId of sessionIds) {
                const savedData = savedSessions[sessionId];
                
                // Valider l'intégrité de la session
                const isValid = await this.persistenceService.validateSessionIntegrity(sessionId, savedData);
                if (!isValid) {
                    logger.warn("Skipping invalid session during restoration", {
                        sessionId,
                        reason: "Failed integrity validation",
                    });
                    continue;
                }

                try {
                    // Recréer le client avec la session existante
                    const client = new Client({
                        authStrategy: new LocalAuth({ clientId: sessionId }),
                        puppeteer: {
                            headless: true,
                            args: [
                                "--no-sandbox",
                                "--disable-setuid-sandbox",
                                "--disable-dev-shm-usage",
                                "--disable-accelerated-2d-canvas",
                                "--no-first-run",
                                "--no-zygote",
                                "--disable-gpu",
                                "--disable-background-timer-throttling",
                                "--disable-backgrounding-occluded-windows",
                                "--disable-renderer-backgrounding"
                            ],
                            handleSIGINT: false,
                            timeout: 60000,
                        },
                        webVersionCache: {
                            type: "remote",
                            remotePath: "https://raw.githubusercontent.com/wppconnect-team/wa-version/main/html/2.2412.54.html",
                        },
                    });

                    const sessionData = {
                        client,
                        userId: savedData.userId,
                        status: "reconnecting",
                        qrCode: null,
                        lastActivity: new Date(savedData.lastActivity),
                        phoneNumber: savedData.phoneNumber,
                        createdAt: new Date(savedData.createdAt),
                        restoredAt: new Date(),
                    };
                    this.webhookService.notifySessionStatusUpdate(sessionId, "reconnecting", null, sessionData);

                    // Configurer les gestionnaires d'événements
                    this.setupClientHandlers(client, sessionId, sessionData, 
                        // Utiliser le callback par défaut s'il existe, sinon fonction vide
                        this.defaultMessageCallback || ((message, sessionData) => {
                            logger.warn("⚠️ Aucun callback configuré pour session restaurée", {
                                sessionId: sessionId,
                                messageId: message.id._serialized
                            });
                        })
                    );

                    // Ajouter à la map des sessions
                    this.sessions.set(sessionId, sessionData);

                    // Initialiser de manière asynchrone
                    client.initialize().catch(async (error) => {
                        logger.session(sessionId, "Restoration initialization failed", {
                            error: error.message,
                            userId: savedData.userId,
                        });
                        await this.forceDestroy(sessionId);
                    });

                    restoredCount++;
                    restoredSessions.push({
                        sessionId,
                        userId: savedData.userId,
                        phoneNumber: savedData.phoneNumber,
                    });

                    logger.session(sessionId, "Session restored successfully", {
                        userId: savedData.userId,
                        phoneNumber: savedData.phoneNumber?.substring(0, 5) + "...",
                    });

                } catch (error) {
                    logger.session(sessionId, "Failed to restore session", {
                        error: error.message,
                        userId: savedData.userId,
                    });
                }
            }

            logger.info("Session restoration completed", {
                totalFound: sessionIds.length,
                restoredCount,
                restoredSessions: restoredSessions.map(s => ({
                    sessionId: s.sessionId,
                    userId: s.userId,
                })),
            });

            // Nettoyer les répertoires d'authentification orphelins
            await this.persistenceService.cleanupOrphanedAuthDirs(
                restoredSessions.map(s => s.sessionId)
            );

            return {
                success: true,
                restoredCount,
                restoredSessions,
                totalFound: sessionIds.length,
            };

        } catch (error) {
            logger.error("Session restoration failed", {
                error: error.message,
                stack: error.stack,
            });
            return { success: false, error: error.message };
        } finally {
            this.isRestoring = false;
        }
    }

    async saveActiveSessions() {
        try {
            return await this.persistenceService.saveActiveSessions(this.sessions);
        } catch (error) {
            logger.error("Failed to save active sessions", {
                error: error.message,
            });
            return { success: false, error: error.message };
        }
    }

    startAutosave(intervalMinutes = 5) {
        if (this.autosaveInterval) {
            clearInterval(this.autosaveInterval);
        }

        this.autosaveInterval = setInterval(async () => {
            const result = await this.saveActiveSessions();
            if (result.success) {
                logger.debug("Autosave completed", {
                    sessionCount: result.sessionCount,
                });
            }
        }, intervalMinutes * 60 * 1000);

        logger.info("Autosave started", {
            intervalMinutes,
        });
    }

    stopAutosave() {
        if (this.autosaveInterval) {
            clearInterval(this.autosaveInterval);
            this.autosaveInterval = null;
            logger.info("Autosave stopped");
        }
    }

    getSession(sessionId) {
        return this.sessions.get(sessionId);
    }

    getSessionStatus(sessionId) {
        const session = this.sessions.get(sessionId);
        if (!session) {
            return { sessionId, status: "not_found" };
        }

        return {
            sessionId,
            status: session.status,
            lastActivity: session.lastActivity,
            userId: session.userId,
            phoneNumber: session.phoneNumber || null,
            qrCode: session.qrCode || null,
        };
    }

    getQRCode(sessionId) {
        const session = this.sessions.get(sessionId);
        return session?.qrCode || null;
    }

    getAllSessions() {
        return Array.from(this.sessions.entries()).map(([sessionId, data]) => ({
            sessionId,
            userId: data.userId,
            status: data.status,
            lastActivity: data.lastActivity,
            phoneNumber: data.phoneNumber,
            createdAt: data.createdAt,
            restoredAt: data.restoredAt,
        }));
    }
}

module.exports = SessionManager;
