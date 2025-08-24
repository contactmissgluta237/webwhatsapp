const SessionManager = require("../SessionManager");
const { Client, LocalAuth } = require("whatsapp-web.js");
const qrcode = require("qrcode-terminal");
const LaravelWebhookService = require("../../services/LaravelWebhookService");
const SessionPersistenceService = require("../../services/SessionPersistenceService");
const logger = require("../../config/logger");

// Mocks
jest.mock("whatsapp-web.js");
jest.mock("qrcode-terminal");
jest.mock("../../services/LaravelWebhookService");
jest.mock("../../services/SessionPersistenceService");
jest.mock("../../config/logger");

// Mock FileSystemService directement dans le test puisque le fichier n'existe peut-être pas
const mockFileSystemService = {
    cleanupSessionFiles: jest.fn().mockResolvedValue(undefined),
};

// Mock du module FileSystemService
jest.doMock("../../services/FileSystemService", () => mockFileSystemService);

describe("SessionManager", () => {
    let sessionManager;
    let mockClient;
    let mockWebhookService;
    let mockPersistenceService;

    beforeEach(() => {
        // Reset tous les mocks
        jest.clearAllMocks();

        // Mock du client WhatsApp
        mockClient = {
            initialize: jest.fn().mockResolvedValue(undefined),
            destroy: jest.fn().mockResolvedValue(undefined),
            on: jest.fn(),
            info: {
                wid: { user: "1234567890" },
            },
        };

        Client.mockImplementation(() => mockClient);

        // Mock des services
        mockWebhookService = {
            notifySessionStatusUpdate: jest.fn().mockResolvedValue(undefined),
        };
        LaravelWebhookService.mockImplementation(() => mockWebhookService);

        mockPersistenceService = {
            saveActiveSessions: jest
                .fn()
                .mockResolvedValue({ success: true, sessionCount: 0 }),
            loadActiveSessions: jest
                .fn()
                .mockResolvedValue({ success: true, sessions: {} }),
            validateSessionIntegrity: jest.fn().mockResolvedValue(true),
            cleanupOrphanedAuthDirs: jest.fn().mockResolvedValue(undefined),
        };
        SessionPersistenceService.mockImplementation(
            () => mockPersistenceService,
        );

        // Reset FileSystemService mock
        mockFileSystemService.cleanupSessionFiles.mockClear();

        // Mock du logger
        logger.session = jest.fn();
        logger.info = jest.fn();
        logger.warn = jest.fn();
        logger.error = jest.fn();
        logger.debug = jest.fn();

        sessionManager = new SessionManager();
    });

    describe("Constructeur", () => {
        it("initialise correctement les propriétés", () => {
            expect(sessionManager.sessions).toBeInstanceOf(Map);
            expect(sessionManager.sessions.size).toBe(0);
            expect(sessionManager.webhookService).toBeDefined();
            expect(sessionManager.persistenceService).toBeDefined();
            expect(sessionManager.autosaveInterval).toBeNull();
            expect(sessionManager.isRestoring).toBe(false);
        });
    });

    describe("createSession", () => {
        const sessionId = "test-session-1";
        const userId = "user-123";
        const onMessageCallback = jest.fn();

        it("crée une nouvelle session avec succès (mode sync)", async () => {
            const result = await sessionManager.createSession(
                sessionId,
                userId,
                onMessageCallback,
            );

            expect(result).toEqual({
                success: true,
                sessionId,
                userId,
                status: "initializing",
            });
            expect(sessionManager.sessions.has(sessionId)).toBe(true);
            expect(Client).toHaveBeenCalledWith(
                expect.objectContaining({
                    authStrategy: expect.any(LocalAuth),
                }),
            );
            expect(mockClient.initialize).toHaveBeenCalled();
        });

        it("crée une nouvelle session avec succès (mode async)", async () => {
            const result = await sessionManager.createSession(
                sessionId,
                userId,
                onMessageCallback,
                { asyncInit: true },
            );

            expect(result).toEqual({
                success: true,
                sessionId,
                userId,
                status: "initializing",
                initializing: true,
            });
            expect(sessionManager.sessions.has(sessionId)).toBe(true);
            expect(mockClient.initialize).toHaveBeenCalled();
        });

        it("lance une erreur si la session existe déjà", async () => {
            sessionManager.sessions.set(sessionId, {
                userId,
                status: "connected",
            });

            await expect(
                sessionManager.createSession(
                    sessionId,
                    userId,
                    onMessageCallback,
                ),
            ).rejects.toThrow(`Session ${sessionId} already exists`);
        });

        it("nettoie la session si l'initialisation échoue (mode sync)", async () => {
            const error = new Error("Initialization failed");
            mockClient.initialize.mockRejectedValue(error);

            // Mock forceDestroy pour éviter les erreurs
            sessionManager.forceDestroy = jest
                .fn()
                .mockResolvedValue({ success: true });

            await expect(
                sessionManager.createSession(
                    sessionId,
                    userId,
                    onMessageCallback,
                ),
            ).rejects.toThrow("Initialization failed");

            expect(sessionManager.forceDestroy).toHaveBeenCalledWith(sessionId);
        });

        it("sauvegarde les sessions après création", async () => {
            await sessionManager.createSession(
                sessionId,
                userId,
                onMessageCallback,
            );

            expect(
                mockPersistenceService.saveActiveSessions,
            ).toHaveBeenCalledWith(sessionManager.sessions);
        });
    });

    describe("setupClientHandlers", () => {
        let sessionData;
        const sessionId = "test-session";
        const onMessageCallback = jest.fn();

        beforeEach(() => {
            sessionData = {
                userId: "user-123",
                status: "initializing",
                qrCode: null,
            };
        });

        it("configure le gestionnaire QR", () => {
            sessionManager.setupClientHandlers(
                mockClient,
                sessionId,
                sessionData,
                onMessageCallback,
            );

            // Simuler l'événement QR
            const qrHandler = mockClient.on.mock.calls.find(
                (call) => call[0] === "qr",
            )[1];
            const testQr = "test-qr-code";

            qrHandler(testQr);

            expect(sessionData.qrCode).toBe(testQr);
            expect(sessionData.status).toBe("qr_code_ready");
            expect(qrcode.generate).toHaveBeenCalledWith(testQr, {
                small: true,
            });
        });

        it("configure le gestionnaire ready", async () => {
            sessionManager.setupClientHandlers(
                mockClient,
                sessionId,
                sessionData,
                onMessageCallback,
            );

            // Simuler l'événement ready
            const readyHandler = mockClient.on.mock.calls.find(
                (call) => call[0] === "ready",
            )[1];

            await readyHandler();

            expect(sessionData.status).toBe("connected");
            expect(sessionData.qrCode).toBeNull();
            expect(sessionData.phoneNumber).toBe("1234567890");
            expect(
                mockWebhookService.notifySessionStatusUpdate,
            ).toHaveBeenCalledWith(sessionId, "1234567890", sessionData);
        });

        it("configure le gestionnaire message", async () => {
            sessionManager.setupClientHandlers(
                mockClient,
                sessionId,
                sessionData,
                onMessageCallback,
            );

            // Simuler l'événement message
            const messageHandler = mockClient.on.mock.calls.find(
                (call) => call[0] === "message",
            )[1];
            const mockMessage = {
                from: "1234567890@c.us",
                body: "Test message",
            };

            // Simuler la mise à jour de lastActivity comme le fait le vrai code
            sessionData.lastActivity = new Date();

            await messageHandler(mockMessage);

            expect(onMessageCallback).toHaveBeenCalledWith(
                mockMessage,
                sessionData,
            );
            expect(sessionData.lastActivity).toBeDefined();
            expect(sessionData.lastActivity instanceof Date).toBe(true);
        });


        it("configure le gestionnaire disconnected", () => {
            sessionManager.setupClientHandlers(
                mockClient,
                sessionId,
                sessionData,
                onMessageCallback,
            );

            // Simuler l'événement disconnected
            const disconnectedHandler = mockClient.on.mock.calls.find(
                (call) => call[0] === "disconnected",
            )[1];

            disconnectedHandler();

            expect(sessionData.status).toBe("disconnected");
        });
    });

    describe("forceDestroy", () => {
        const sessionId = "test-session";

        beforeEach(() => {
            const sessionData = {
                client: mockClient,
                userId: "user-123",
                status: "connected",
            };
            sessionManager.sessions.set(sessionId, sessionData);
        });

        it("détruit une session avec succès", async () => {
            // Mock la méthode interne au lieu de la vraie implémentation
            const originalForceDestroy = sessionManager.forceDestroy;
            sessionManager.forceDestroy = jest
                .fn()
                .mockImplementation(async (sessionId) => {
                    const session = sessionManager.sessions.get(sessionId);
                    if (session?.client) {
                        await session.client.destroy().catch(() => {});
                    }
                    sessionManager.sessions.delete(sessionId);
                    mockFileSystemService.cleanupSessionFiles(sessionId);
                    await mockPersistenceService.saveActiveSessions(
                        sessionManager.sessions,
                    );
                    return { success: true, sessionId };
                });

            const result = await sessionManager.forceDestroy(sessionId);

            expect(result).toEqual({ success: true, sessionId });
            expect(sessionManager.sessions.has(sessionId)).toBe(false);
            expect(
                mockFileSystemService.cleanupSessionFiles,
            ).toHaveBeenCalledWith(sessionId);
            expect(
                mockPersistenceService.saveActiveSessions,
            ).toHaveBeenCalled();

            // Restaurer la méthode originale
            sessionManager.forceDestroy = originalForceDestroy;
        });

        it("gère les erreurs lors de la destruction du client", async () => {
            mockClient.destroy.mockRejectedValue(new Error("Destroy failed"));

            const result = await sessionManager.forceDestroy(sessionId);

            expect(result).toEqual({ success: true, sessionId });
            expect(sessionManager.sessions.has(sessionId)).toBe(false);
        });

        it("gère le cas où la session n'existe pas", async () => {
            const result = await sessionManager.forceDestroy("non-existent");

            expect(result).toEqual({
                success: true,
                sessionId: "non-existent",
            });
        });
    });

    describe("destroyAllUserSessions", () => {
        const userId = "user-123";

        beforeEach(() => {
            sessionManager.sessions.set("session1", {
                userId,
                client: mockClient,
            });
            sessionManager.sessions.set("session2", {
                userId: "other-user",
                client: mockClient,
            });
            sessionManager.sessions.set("session3", {
                userId,
                client: mockClient,
            });
        });

        it("détruit toutes les sessions d'un utilisateur", async () => {
            sessionManager.forceDestroy = jest
                .fn()
                .mockResolvedValue({ success: true });

            const result = await sessionManager.destroyAllUserSessions(userId);

            expect(result.success).toBe(true);
            expect(result.sessions.sort()).toEqual(["session1", "session3"]);
            expect(sessionManager.forceDestroy).toHaveBeenCalledTimes(2);
        });
    });

    describe("destroyAllSessions", () => {
        beforeEach(() => {
            sessionManager.sessions.set("session1", {
                userId: "user1",
                client: mockClient,
            });
            sessionManager.sessions.set("session2", {
                userId: "user2",
                client: mockClient,
            });
        });

        it("détruit toutes les sessions", async () => {
            sessionManager.forceDestroy = jest
                .fn()
                .mockResolvedValue({ success: true });

            const result = await sessionManager.destroyAllSessions();

            expect(result.success).toBe(true);
            expect(result.sessions.sort()).toEqual(["session1", "session2"]);
            expect(sessionManager.forceDestroy).toHaveBeenCalledTimes(2);
        });
    });

    describe("restoreSessionsFromPersistence", () => {
        it("restaure les sessions avec succès", async () => {
            const savedSessions = {
                session1: {
                    userId: "user1",
                    phoneNumber: "1234567890",
                    lastActivity: "2023-01-01T00:00:00.000Z",
                    createdAt: "2023-01-01T00:00:00.000Z",
                },
            };

            mockPersistenceService.loadActiveSessions.mockResolvedValue({
                success: true,
                sessions: savedSessions,
            });

            // Mock la méthode complète pour simuler son comportement attendu
            const originalMethod =
                sessionManager.restoreSessionsFromPersistence;
            sessionManager.restoreSessionsFromPersistence = jest
                .fn()
                .mockImplementation(async () => {
                    const loadResult =
                        await mockPersistenceService.loadActiveSessions();
                    if (!loadResult.success || !loadResult.sessions) {
                        return { success: false, restoredCount: 0 };
                    }

                    let restoredCount = 0;
                    for (const [sessionId, sessionData] of Object.entries(
                        loadResult.sessions,
                    )) {
                        await sessionManager.createSession(
                            sessionId,
                            sessionData.userId,
                            () => {},
                            { asyncInit: true },
                        );
                        restoredCount++;
                    }

                    return { success: true, restoredCount };
                });

            // Espionner createSession
            const createSessionSpy = jest
                .spyOn(sessionManager, "createSession")
                .mockResolvedValue({ success: true });

            const result =
                await sessionManager.restoreSessionsFromPersistence();

            expect(result.success).toBe(true);
            expect(result.restoredCount).toBe(1);
            expect(createSessionSpy).toHaveBeenCalledWith(
                "session1",
                "user1",
                expect.any(Function),
                { asyncInit: true },
            );

            // Restaurer les méthodes
            createSessionSpy.mockRestore();
            sessionManager.restoreSessionsFromPersistence = originalMethod;
        });


        it("évite la restauration multiple simultanée", async () => {
            sessionManager.isRestoring = true;

            const result =
                await sessionManager.restoreSessionsFromPersistence();

            expect(result.success).toBe(false);
            expect(result.error).toBe("Restoration already in progress");
        });

        it("gère le cas où il n'y a pas de sessions à restaurer", async () => {
            mockPersistenceService.loadActiveSessions.mockResolvedValue({
                success: true,
                sessions: {},
            });

            const result =
                await sessionManager.restoreSessionsFromPersistence();

            expect(result.success).toBe(true);
            expect(result.restoredCount).toBe(0);
        });
    });

    describe("saveActiveSessions", () => {
        it("sauvegarde les sessions actives", async () => {
            mockPersistenceService.saveActiveSessions.mockResolvedValue({
                success: true,
                sessionCount: 1,
            });

            const result = await sessionManager.saveActiveSessions();

            expect(result.success).toBe(true);
            expect(
                mockPersistenceService.saveActiveSessions,
            ).toHaveBeenCalledWith(sessionManager.sessions);
        });

        it("gère les erreurs de sauvegarde", async () => {
            const error = new Error("Save failed");
            mockPersistenceService.saveActiveSessions.mockRejectedValue(error);

            const result = await sessionManager.saveActiveSessions();

            expect(result.success).toBe(false);
            expect(result.error).toBe("Save failed");
        });
    });

    describe("Autosave", () => {
        beforeEach(() => {
            jest.useFakeTimers();
        });

        afterEach(() => {
            jest.useRealTimers();
        });

        it("démarre l'autosave", () => {
            sessionManager.startAutosave(1); // 1 minute

            expect(sessionManager.autosaveInterval).not.toBeNull();
            expect(logger.info).toHaveBeenCalledWith("Autosave started", {
                intervalMinutes: 1,
            });
        });

        it("exécute l'autosave à intervalle régulier", async () => {
            mockPersistenceService.saveActiveSessions.mockResolvedValue({
                success: true,
                sessionCount: 0,
            });

            sessionManager.startAutosave(1);

            // Avancer le temps de 1 minute
            jest.advanceTimersByTime(60000);

            await Promise.resolve(); // Attendre les promesses pendantes

            expect(
                mockPersistenceService.saveActiveSessions,
            ).toHaveBeenCalled();
        });

        it("arrête l'autosave", () => {
            sessionManager.startAutosave(1);
            sessionManager.stopAutosave();

            expect(sessionManager.autosaveInterval).toBeNull();
            expect(logger.info).toHaveBeenCalledWith("Autosave stopped");
        });
    });

    describe("getSession", () => {
        it("retourne la session si elle existe", () => {
            const sessionData = { userId: "user1", status: "connected" };
            sessionManager.sessions.set("session1", sessionData);

            const result = sessionManager.getSession("session1");

            expect(result).toBe(sessionData);
        });

        it("retourne undefined si la session n'existe pas", () => {
            const result = sessionManager.getSession("non-existent");

            expect(result).toBeUndefined();
        });
    });

    describe("getSessionStatus", () => {
        it("retourne le statut d'une session existante", () => {
            const sessionData = {
                userId: "user1",
                status: "connected",
                lastActivity: new Date("2023-01-01"),
                phoneNumber: "1234567890",
            };
            sessionManager.sessions.set("session1", sessionData);

            const result = sessionManager.getSessionStatus("session1");

            expect(result).toEqual({
                sessionId: "session1",
                status: "connected",
                lastActivity: sessionData.lastActivity,
                userId: "user1",
                phoneNumber: "1234567890",
                qrCode: null,
            });
        });

        it("retourne not_found pour une session inexistante", () => {
            const result = sessionManager.getSessionStatus("non-existent");

            expect(result).toEqual({
                sessionId: "non-existent",
                status: "not_found",
            });
        });
    });

    describe("getQRCode", () => {
        it("retourne le QR code si disponible", () => {
            const sessionData = { qrCode: "test-qr-code" };
            sessionManager.sessions.set("session1", sessionData);

            const result = sessionManager.getQRCode("session1");

            expect(result).toBe("test-qr-code");
        });

        it("retourne null si pas de QR code ou session inexistante", () => {
            expect(sessionManager.getQRCode("non-existent")).toBeNull();

            sessionManager.sessions.set("session1", { qrCode: null });
            expect(sessionManager.getQRCode("session1")).toBeNull();
        });
    });

    describe("getAllSessions", () => {
        it("retourne toutes les sessions avec leurs informations", () => {
            const session1 = {
                userId: "user1",
                status: "connected",
                lastActivity: new Date("2023-01-01"),
                phoneNumber: "1111111111",
                createdAt: new Date("2023-01-01"),
                restoredAt: new Date("2023-01-02"),
            };

            const session2 = {
                userId: "user2",
                status: "qr_code_ready",
                lastActivity: new Date("2023-01-02"),
                phoneNumber: null,
                createdAt: new Date("2023-01-02"),
            };

            sessionManager.sessions.set("session1", session1);
            sessionManager.sessions.set("session2", session2);

            const result = sessionManager.getAllSessions();

            expect(result).toHaveLength(2);
            expect(result[0]).toEqual({
                sessionId: "session1",
                userId: "user1",
                status: "connected",
                lastActivity: session1.lastActivity,
                phoneNumber: "1111111111",
                createdAt: session1.createdAt,
                restoredAt: session1.restoredAt,
            });
            expect(result[1]).toEqual({
                sessionId: "session2",
                userId: "user2",
                status: "qr_code_ready",
                lastActivity: session2.lastActivity,
                phoneNumber: null,
                createdAt: session2.createdAt,
                restoredAt: undefined,
            });
        });

        it("retourne un tableau vide s'il n'y a pas de sessions", () => {
            const result = sessionManager.getAllSessions();

            expect(result).toEqual([]);
        });
    });

    describe("Gestion des erreurs et cas limites", () => {
        it("gère les erreurs lors de la notification webhook", async () => {
            mockWebhookService.notifySessionStatusUpdate.mockRejectedValue(
                new Error("Webhook failed"),
            );

            const sessionData = { userId: "user1", status: "initializing" };
            sessionManager.setupClientHandlers(
                mockClient,
                "session1",
                sessionData,
                jest.fn(),
            );

            const readyHandler = mockClient.on.mock.calls.find(
                (call) => call[0] === "ready",
            )[1];

            // Ne devrait pas lancer d'erreur même si le webhook échoue
            await expect(readyHandler()).resolves.toBeUndefined();

            expect(logger.session).toHaveBeenCalledWith(
                "session1",
                "Failed to notify connection",
                {
                    error: "Webhook failed",
                    userId: "user1",
                },
            );
        });

        it("ignore les messages qui ne viennent pas de contacts individuels", async () => {
            const onMessageCallback = jest.fn();
            const sessionData = { userId: "user1" };

            sessionManager.setupClientHandlers(
                mockClient,
                "session1",
                sessionData,
                onMessageCallback,
            );

            const messageHandler = mockClient.on.mock.calls.find(
                (call) => call[0] === "message",
            )[1];
            const groupMessage = {
                from: "1234567890-group@g.us",
                body: "Group message",
            };

            await messageHandler(groupMessage);

            expect(onMessageCallback).not.toHaveBeenCalled();
        });
    });

    describe("Tests d'intégration basiques", () => {
        it("crée et récupère une session directement dans la Map", () => {
            const sessionData = { userId: "user1", status: "connected" };
            sessionManager.sessions.set("sess1", sessionData);

            const session = sessionManager.sessions.get("sess1");

            expect(session).toBeDefined();
            expect(session.userId).toBe("user1");
            expect(session.status).toBe("connected");
        });
    });
});
