const LaravelWebhookService = require("../services/LaravelWebhookService");
const TypingSimulatorService = require("../services/TypingSimulatorService");
const ResponseHandler = require("../services/ResponseHandler");
const MessageLogger = require("../services/MessageLogger");

class MessageManagerClean {
    constructor(sessionManager) {
        this.sessionManager = sessionManager;
        this.webhookService = new LaravelWebhookService();
        this.typingSimulator = new TypingSimulatorService();
    }

    async handleIncomingMessage(message, sessionData) {
        const context = {
            sessionId: sessionData.sessionId,
            userId: sessionData.userId,
            messageId: message.id._serialized,
            from: message.from,
            to: message.to,
            isGroup: message.from.includes("@g.us"),
            timestamp: message.timestamp,
            receivedAt: new Date().toISOString()
        };

        const messageDetails = {
            ...context,
            body: message.body,
            type: message.type,
            fromMe: message.fromMe,
            hasMedia: message.hasMedia,
            deviceType: message.deviceType || null,
            author: message.author || null
        };

        MessageLogger.logIncomingMessage("MESSAGE RECEIVED", messageDetails);
        
        // Handle group messages (ignore them)
        if (context.isGroup) {
            MessageLogger.logIncomingMessage("GROUP MESSAGE [IGNORED]", {
                ...context,
                groupId: message.from,
                author: message.author,
                messageBody: message.body.substring(0, 100) + (message.body.length > 100 ? "..." : ""),
                reason: "Group messages are temporarily disabled"
            });
            return;
        }
        
        // Private message only
        MessageLogger.logIncomingMessage("PRIVATE MESSAGE", {
            ...context,
            contact: message.from,
            messageBody: message.body.substring(0, 100) + (message.body.length > 100 ? "..." : "")
        });

        // Mark message as read
        await this._markMessageAsRead(message, context);

        // Log media info if applicable
        if (message.hasMedia) {
            MessageLogger.logIncomingMessage("MEDIA MESSAGE", {
                ...context,
                mediaType: message.type
            });
        }

        try {
            // Send to Laravel for processing
            await this._processMessageWithLaravel(message, sessionData, context);
        } catch (error) {
            MessageLogger.logError("❌ MESSAGE PROCESSING FAILED", {
                ...context,
                error: error.message,
                stack: error.stack,
                messageBody: message.body.substring(0, 100)
            });
        }
    }

    async _markMessageAsRead(message, context) {
        try {
            const session = this.sessionManager.getSession(context.sessionId);
            if (session && session.client) {
                await session.client.sendSeen(message.from);
                MessageLogger.logIncomingMessage("MESSAGE MARKED AS READ", {
                    ...context,
                    from: message.from
                });
            }
        } catch (readError) {
            MessageLogger.logWarning("FAILED TO MARK MESSAGE AS READ", {
                ...context,
                error: readError.message
            });
        }
    }

    async _processMessageWithLaravel(message, sessionData, context) {
        MessageLogger.logIncomingMessage("PROCESSING MESSAGE", {
            ...context,
            action: "sending_to_laravel"
        });

        // Send to Laravel webhook
        const response = await this.webhookService.notifyIncomingMessage(message, sessionData);

        MessageLogger.logIncomingMessage("MESSAGE PROCESSED", {
            ...context,
            success: response?.success || false,
            processed: response?.processed || false,
            hasAiResponse: !!response?.response_message,
            responseLength: response?.response_message?.length || 0,
            hasProducts: Array.isArray(response?.products) && response.products.length > 0,
            productsCount: response?.products?.length || 0,
            hasError: !!response?.error
        });

        // Get session for response handling
        const session = this.sessionManager.getSession(context.sessionId);
        if (!session || !session.client) {
            MessageLogger.logWarning("SESSION NOT FOUND FOR RESPONSE", context);
            return;
        }

        // Handle Laravel response using ResponseHandler
        const responseHandler = new ResponseHandler(session.client, this.typingSimulator);
        await responseHandler.handleLaravelResponse(response, message.from, {
            ...context,
            originalMessageId: message.id._serialized
        });
    }

    async sendMessage(sessionId, to, messageText) {
        const context = {
            sessionId,
            to: to?.substring(0, 10) + "...",
            messageLength: messageText?.length
        };

        MessageLogger.logInfo("🔥 TRACE: MessageManager.sendMessage called", context);
        
        const session = this.sessionManager.getSession(sessionId);

        MessageLogger.logInfo("🔥 TRACE: Session retrieved", { 
            ...context,
            sessionExists: !!session, 
            sessionStatus: session?.status 
        });

        if (!session || session.status !== "connected") {
            MessageLogger.logError("🔥 TRACE: Session not connected, throwing error", context);
            throw new Error("Session not connected");
        }

        try {
            const chatId = to.includes("@c.us") ? to : `${to}@c.us`;
            
            MessageLogger.logInfo("🔥 TRACE: About to log outgoing message", { ...context, chatId });
            
            // Log outgoing message
            MessageLogger.logOutgoingMessage("MESSAGE SENDING", {
                sessionId: sessionId,
                userId: session.userId,
                to: chatId,
                messageLength: messageText.length,
                messagePreview: messageText.substring(0, 100) + (messageText.length > 100 ? "..." : ""),
                timestamp: new Date().toISOString()
            });

            MessageLogger.logInfo("🔥 TRACE: About to call session.client.sendMessage", context);

            await session.client.sendMessage(chatId, messageText);

            MessageLogger.logInfo("🔥 TRACE: session.client.sendMessage completed successfully", context);

            session.lastActivity = new Date();

            // Log successful send
            MessageLogger.logOutgoingMessage("MESSAGE SENT SUCCESSFULLY", {
                sessionId: sessionId,
                userId: session.userId,
                to: chatId,
                messageLength: messageText.length,
                sentAt: new Date().toISOString()
            });

            MessageLogger.logInfo("🔥 TRACE: Returning success result", context);

            return {
                success: true,
                sessionId,
                to,
                message: messageText,
                timestamp: new Date().toISOString(),
            };
        } catch (error) {
            MessageLogger.logError("🔥 TRACE: Error in sendMessage", { 
                ...context,
                error: error.message 
            });
            
            MessageLogger.logError(`❌ OUTGOING MESSAGE FAILED`, {
                sessionId: sessionId,
                userId: session.userId,
                to: to,
                error: error.message,
                messageLength: messageText.length,
                stack: error.stack
            });
            throw error;
        }
    }
}

module.exports = MessageManagerClean;