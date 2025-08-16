// docker/whatsapp-bridge/src/config/apiDocumentation.js
class ApiDocumentation {
    static getEndpoints(port = 3000) {
        return {
            message: "WhatsApp Bridge API - Available Endpoints",
            version: "1.0.0",
            timestamp: new Date().toISOString(),
            base_url: `http://localhost:${port}`,
            environment: process.env.NODE_ENV || "development",
            routes: {
                General: [
                    {
                        path: "/health",
                        methods: ["GET"],
                        description: "Service health check",
                        response: "Service status and uptime",
                    },
                    {
                        path: "/",
                        methods: ["GET"],
                        description: "API documentation",
                        response: "Complete API documentation",
                    },
                ],
                Sessions: [
                    {
                        path: "/api/sessions",
                        methods: ["GET"],
                        description: "List all active sessions",
                        response: "Array of active WhatsApp sessions",
                    },
                    {
                        path: "/api/sessions/create",
                        methods: ["POST"],
                        description: "Create new WhatsApp session",
                        body: ["sessionId", "userId"],
                        example: {
                            sessionId: "user_123_session",
                            userId: 123,
                        },
                    },
                    {
                        path: "/api/sessions/:sessionId/status",
                        methods: ["GET"],
                        description: "Get session status",
                        params: ["sessionId"],
                        response: "Session connection status",
                    },
                    {
                        path: "/api/sessions/:sessionId/qr",
                        methods: ["GET"],
                        description: "Get QR code for session",
                        params: ["sessionId"],
                        response: "Base64 QR code for WhatsApp pairing",
                    },
                    {
                        path: "/api/sessions/:sessionId/send",
                        methods: ["POST"],
                        description: "Send message from session",
                        params: ["sessionId"],
                        body: ["to", "message"],
                        example: {
                            to: "1234567890@c.us",
                            message: "Hello from WhatsApp Bridge!",
                        },
                    },
                    {
                        path: "/api/sessions/:sessionId",
                        methods: ["DELETE"],
                        description: "Delete specific session",
                        params: ["sessionId"],
                    },
                    {
                        path: "/api/sessions/reset-all",
                        methods: ["POST"],
                        description: "Reset all sessions",
                        warning: "This will destroy ALL active sessions",
                    },
                    {
                        path: "/api/sessions/reset-user/:userId",
                        methods: ["POST"],
                        description: "Reset all sessions for specific user",
                        params: ["userId"],
                    },
                    {
                        path: "/api/sessions/force/:sessionId",
                        methods: ["DELETE"],
                        description: "Force delete session (cleanup files)",
                        params: ["sessionId"],
                        warning: "Use only if normal delete fails",
                    },
                    {
                        path: "/api/sessions/save",
                        methods: ["POST"],
                        description: "Save all active sessions to disk",
                        response: "Success confirmation with session count",
                        example_response: {
                            success: true,
                            message: "Sessions saved successfully",
                            sessionCount: 5
                        },
                        note: "Triggers backup of all active sessions"
                    },
                    {
                        path: "/api/sessions/:sessionId/save",
                        methods: ["POST"],
                        description: "Save specific session to disk (OPTIMIZED)",
                        params: ["sessionId"],
                        response: "Success confirmation with session details",
                        example_response: {
                            success: true,
                            message: "Session session_123 saved successfully",
                            sessionId: "session_123",
                            savedAt: "2025-08-15T20:26:05.148Z"
                        },
                        note: "Efficient backup of single session - recommended for production",
                        performance: "~76ms vs several seconds for bulk save"
                    },
                ],
                Bridge: [
                    {
                        path: "/api/bridge/send-message",
                        methods: ["POST"],
                        description: "Send message from Laravel application",
                        body: ["session_id", "to", "message"],
                        example: {
                            session_id: "user_123_session",
                            to: "1234567890@c.us",
                            message: "Message from Laravel app",
                        },
                        note: "Primary endpoint for Laravel integration",
                    },
                ],
                Admin: [
                    {
                        path: "/api/admin/clear-cache",
                        methods: ["POST"],
                        description: "Clear all sessions cache",
                        warning: "Destroys all sessions and clears memory",
                    },
                    {
                        path: "/api/admin/stats",
                        methods: ["GET"],
                        description: "Get detailed system statistics",
                        response: "Memory usage, session counts, uptime stats",
                    },
                ],
            },
            common_responses: {
                success: {
                    success: true,
                    message: "Operation completed successfully",
                    data: "...",
                },
                error: {
                    success: false,
                    message: "Error description",
                    timestamp: "ISO timestamp",
                },
            },
            notes: [
                "All timestamps are in ISO 8601 format",
                "WhatsApp numbers should include country code without +",
                "Session IDs should be unique per user",
                "QR codes are only available during initial pairing",
                "Messages are sent asynchronously",
            ],
        };
    }

    static getHealthInfo() {
        return {
            status: "OK",
            timestamp: new Date().toISOString(),
            service: "whatsapp-bridge",
            version: "1.0.0",
            uptime: process.uptime(),
            memory: process.memoryUsage(),
            node_version: process.version,
            platform: process.platform,
            environment: process.env.NODE_ENV || "development",
        };
    }

    static get404Response(req) {
        return {
            success: false,
            message: "Endpoint not found",
            requested_path: req.path,
            requested_method: req.method,
            timestamp: new Date().toISOString(),
            available_endpoints: "See GET / for full API documentation",
        };
    }

    static getErrorResponse(message = "Internal server error") {
        return {
            success: false,
            message,
            timestamp: new Date().toISOString(),
            support: "Check logs for more details",
        };
    }
}

module.exports = ApiDocumentation;
