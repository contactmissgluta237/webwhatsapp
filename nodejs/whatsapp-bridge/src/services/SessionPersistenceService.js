const fs = require("fs").promises;
const path = require("path");
const logger = require("../config/logger");

class SessionPersistenceService {
    constructor() {
        this.sessionFilePath = path.join(__dirname, "../data/active_sessions.json");
        this.authPath = path.join(__dirname, "../../.wwebjs_auth");
    }

    async ensureDataDirectory() {
        const dataDir = path.dirname(this.sessionFilePath);
        try {
            await fs.access(dataDir);
        } catch {
            await fs.mkdir(dataDir, { recursive: true });
            logger.info("Created data directory for session persistence");
        }
    }

    async saveActiveSessions(sessionsMap) {
        try {
            await this.ensureDataDirectory();
            
            const sessionsToSave = {};
            for (const [sessionId, sessionData] of sessionsMap.entries()) {
                sessionsToSave[sessionId] = {
                    userId: sessionData.userId,
                    status: sessionData.status,
                    phoneNumber: sessionData.phoneNumber || null,
                    lastActivity: sessionData.lastActivity,
                    createdAt: sessionData.createdAt || new Date(),
                    savedAt: new Date(),
                };
            }

            await fs.writeFile(
                this.sessionFilePath,
                JSON.stringify(sessionsToSave, null, 2),
                "utf8"
            );

            logger.info("Active sessions saved to disk", {
                sessionCount: Object.keys(sessionsToSave).length,
                file: this.sessionFilePath,
            });

            return { success: true, sessionCount: Object.keys(sessionsToSave).length };
        } catch (error) {
            logger.error("Failed to save active sessions", {
                error: error.message,
                file: this.sessionFilePath,
            });
            return { success: false, error: error.message };
        }
    }

    async loadActiveSessions() {
        try {
            const data = await fs.readFile(this.sessionFilePath, "utf8");
            const savedSessions = JSON.parse(data);

            logger.info("Loaded saved sessions from disk", {
                sessionCount: Object.keys(savedSessions).length,
                file: this.sessionFilePath,
            });

            return { success: true, sessions: savedSessions };
        } catch (error) {
            if (error.code === "ENOENT") {
                logger.info("No saved sessions file found, starting fresh");
                return { success: true, sessions: {} };
            }

            logger.error("Failed to load saved sessions", {
                error: error.message,
                file: this.sessionFilePath,
            });
            return { success: false, error: error.message };
        }
    }

    async getAuthDirectorySessions() {
        try {
            const authDirs = await fs.readdir(this.authPath);
            const sessionDirs = authDirs.filter(dir => dir.startsWith("session-"));
            
            const authSessions = [];
            for (const dirName of sessionDirs) {
                const sessionIdMatch = dirName.match(/^session-(.+)_\d+_[a-f0-9]+$/);
                if (sessionIdMatch) {
                    const sessionId = sessionIdMatch[1];
                    const dirPath = path.join(this.authPath, dirName);
                    const stats = await fs.stat(dirPath);
                    
                    authSessions.push({
                        sessionId,
                        authDir: dirName,
                        lastModified: stats.mtime,
                        path: dirPath,
                    });
                }
            }

            logger.info("Found authentication directories", {
                count: authSessions.length,
                sessions: authSessions.map(s => s.sessionId),
            });

            return { success: true, authSessions };
        } catch (error) {
            logger.error("Failed to scan auth directory", {
                error: error.message,
                authPath: this.authPath,
            });
            return { success: false, error: error.message };
        }
    }

    async cleanupOrphanedAuthDirs(activeSessionIds) {
        try {
            const authResult = await this.getAuthDirectorySessions();
            if (!authResult.success) return authResult;

            // Sécurité : Ne pas nettoyer si aucune session active fournie
            if (!activeSessionIds || activeSessionIds.length === 0) {
                logger.warn("No active sessions provided for cleanup - skipping for safety", {
                    authDirectoriesFound: authResult.authSessions.length,
                });
                return { success: true, cleanedCount: 0, skipped: true, reason: "No active sessions to compare" };
            }

            const orphanedDirs = authResult.authSessions.filter(
                authSession => !activeSessionIds.includes(authSession.sessionId)
            );

            if (orphanedDirs.length === 0) {
                logger.info("No orphaned auth directories found", {
                    authDirectoriesCount: authResult.authSessions.length,
                    activeSessionsCount: activeSessionIds.length,
                });
                return { success: true, cleanedCount: 0 };
            }

            // Sécurité supplémentaire : ne supprimer que les dossiers anciens (plus de 1 heure)
            const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000);
            const safeToDeleteDirs = orphanedDirs.filter(
                orphaned => orphaned.lastModified < oneHourAgo
            );

            let cleanedCount = 0;
            for (const orphaned of safeToDeleteDirs) {
                try {
                    await fs.rmdir(orphaned.path, { recursive: true });
                    logger.info("Cleaned orphaned auth directory", {
                        sessionId: orphaned.sessionId,
                        path: orphaned.path,
                        age: Math.round((Date.now() - orphaned.lastModified.getTime()) / (1000 * 60)) + " minutes",
                    });
                    cleanedCount++;
                } catch (error) {
                    logger.warn("Failed to clean auth directory", {
                        sessionId: orphaned.sessionId,
                        error: error.message,
                    });
                }
            }

            const skippedCount = orphanedDirs.length - safeToDeleteDirs.length;
            logger.info("Cleanup completed", {
                orphanedFound: orphanedDirs.length,
                cleanedCount,
                skippedCount: skippedCount,
                activeSessionsCount: activeSessionIds.length,
            });

            return { 
                success: true, 
                cleanedCount,
                skippedCount,
                orphanedFound: orphanedDirs.length,
                activeSessionsCount: activeSessionIds.length,
            };
        } catch (error) {
            logger.error("Failed during cleanup process", {
                error: error.message,
            });
            return { success: false, error: error.message };
        }
    }

    async validateSessionIntegrity(sessionId, sessionData) {
        try {
            const authResult = await this.getAuthDirectorySessions();
            if (!authResult.success) return false;

            const hasAuthDir = authResult.authSessions.some(
                authSession => authSession.sessionId === sessionId
            );

            const isRecentlyActive = sessionData.lastActivity && 
                new Date() - new Date(sessionData.lastActivity) < 24 * 60 * 60 * 1000; // 24 hours

            const hasValidStatus = ["connected", "qr_code_ready", "initializing"].includes(sessionData.status);

            return hasAuthDir && isRecentlyActive && hasValidStatus;
        } catch (error) {
            logger.warn("Session integrity validation failed", {
                sessionId,
                error: error.message,
            });
            return false;
        }
    }
}

module.exports = SessionPersistenceService;
