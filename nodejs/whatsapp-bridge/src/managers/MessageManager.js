const LaravelWebhookService = require("../services/LaravelWebhookService");
const TypingSimulatorService = require("../services/TypingSimulatorService");
const logger = require("../config/logger");

class MessageManager {
    constructor(sessionManager) {
        this.sessionManager = sessionManager;
        this.webhookService = new LaravelWebhookService();
        this.typingSimulator = new TypingSimulatorService();
    }

    async handleIncomingMessage(message, sessionData) {
        const messageDetails = {
            sessionId: sessionData.sessionId,
            userId: sessionData.userId,
            messageId: message.id._serialized,
            from: message.from,
            to: message.to,
            body: message.body,
            type: message.type,
            fromMe: message.fromMe,
            isGroup: message.from.includes("@g.us"),
            timestamp: message.timestamp,
            hasMedia: message.hasMedia,
            deviceType: message.deviceType || null,
            author: message.author || null,
            receivedAt: new Date().toISOString()
        };

                // Log principal dans incoming-messages.log
        logger.info("🔍 DEBUG: Appel logger.incomingMessage dans MessageManager", {
            sessionId: sessionData.sessionId,
            messageId: messageDetails.messageId
        });
        logger.incomingMessage("MESSAGE RECEIVED", messageDetails);
        
        if (messageDetails.isGroup) {
            logger.incomingMessage("GROUP MESSAGE [IGNORED]", {
                sessionId: sessionData.sessionId,
                groupId: message.from,
                author: message.author,
                messageBody: message.body.substring(0, 100) + (message.body.length > 100 ? "..." : ""),
                messageId: message.id._serialized,
                reason: "Group messages are temporarily disabled"
            });
            
            // Ignorer complètement les messages de groupe
            logger.info("🚫 Message de groupe ignoré", {
                sessionId: sessionData.sessionId,
                groupId: message.from,
                messageId: message.id._serialized
            });
            return;
        }
        
        // Message privé seulement (les groupes sont ignorés)
        logger.incomingMessage("PRIVATE MESSAGE", {
            sessionId: sessionData.sessionId,
            contact: message.from,
            messageBody: message.body.substring(0, 100) + (message.body.length > 100 ? "..." : ""),
            messageId: message.id._serialized
        });

        // Marquer le message comme lu immédiatement
        try {
            const session = this.sessionManager.getSession(sessionData.sessionId);
            if (session && session.client) {
                await session.client.sendSeen(message.from);
                logger.incomingMessage("MESSAGE MARKED AS READ", {
                    sessionId: sessionData.sessionId,
                    messageId: message.id._serialized,
                    from: message.from
                });
            }
        } catch (readError) {
            logger.warning("FAILED TO MARK MESSAGE AS READ", {
                sessionId: sessionData.sessionId,
                messageId: message.id._serialized,
                error: readError.message
            });
        }

        if (message.hasMedia) {
            logger.incomingMessage("MEDIA MESSAGE", {
                sessionId: sessionData.sessionId,
                from: message.from,
                mediaType: message.type,
                messageId: message.id._serialized
            });
        }

        try {
            logger.incomingMessage("PROCESSING MESSAGE", {
                sessionId: sessionData.sessionId,
                messageId: message.id._serialized,
                action: "sending_to_laravel"
            });

            const response = await this.webhookService.notifyIncomingMessage(
                message,
                sessionData,
            );

            logger.incomingMessage("MESSAGE PROCESSED", {
                sessionId: sessionData.sessionId,
                messageId: message.id._serialized,
                success: response?.success || false,
                processed: response?.processed || false,
                hasAiResponse: !!response?.response_message,
                responseLength: response?.response_message?.length || 0,
                hasProducts: Array.isArray(response?.products) && response.products.length > 0,
                productsCount: response?.products?.length || 0,
                hasError: !!response?.error
            });

            // Handle Laravel processing errors
            if (response?.success === false) {
                logger.error("❌ LARAVEL PROCESSING ERROR", {
                    sessionId: sessionData.sessionId,
                    messageId: message.id._serialized,
                    error: response?.error || "Unknown error from Laravel",
                    processed: response?.processed || false
                });
                return; // Exit early if Laravel couldn't process the message
            }

            if (response?.response_message) {
                // Extract timing data from Laravel response
                const waitTimeSeconds = response.wait_time_seconds || 0;
                const typingDurationSeconds = response.typing_duration_seconds || 2;

                logger.incomingMessage("AI RESPONSE TIMING", {
                    sessionId: sessionData.sessionId,
                    messageId: message.id._serialized,
                    waitTimeSeconds: waitTimeSeconds,
                    typingDurationSeconds: typingDurationSeconds,
                    responseLength: response.response_message.length
                });

                // Get the session client for typing simulation
                const session = this.sessionManager.getSession(sessionData.sessionId);
                if (session && session.client) {
                    // Use TypingSimulatorService with Laravel timings
                    await this.typingSimulator.simulateResponseAndSendMessage(
                        session.client,
                        message.from, // Reply to sender
                        response.response_message,
                        waitTimeSeconds,
                        typingDurationSeconds
                    );
                } else {
                    // Fallback: send without simulation
                    logger.warning("SESSION NOT FOUND FOR TYPING", {
                        sessionId: sessionData.sessionId,
                        messageId: message.id._serialized
                    });
                    await message.reply(response.response_message);
                }
                
                // Log de la réponse envoyée dans outgoing-messages.log
                logger.debug("🔍 DEBUG: Appel logger.outgoingMessage AI RESPONSE SENT dans MessageManager", {
                    sessionId: sessionData.sessionId,
                    originalMessageId: message.id._serialized
                });
                logger.outgoingMessage("AI RESPONSE SENT", {
                    sessionId: sessionData.sessionId,
                    originalMessageId: message.id._serialized,
                    responseText: response.response_message.substring(0, 100) + (response.response_message.length > 100 ? "..." : ""),
                    responseLength: response.response_message.length,
                    to: message.from
                });
            }

            // Handle products if they exist in the response
            if (response?.products && Array.isArray(response.products) && response.products.length > 0) {
                await this.handleProductMessages(response.products, message.from, sessionData, message.id._serialized);
            }
        } catch (error) {
            logger.error("❌ MESSAGE PROCESSING FAILED", {
                sessionId: sessionData.sessionId,
                messageId: message.id._serialized,
                error: error.message,
                stack: error.stack,
                from: message.from,
                messageBody: message.body.substring(0, 100)
            });
        }
    }

    async handleProductMessages(products, to, sessionData, originalMessageId) {
        logger.incomingMessage("SENDING PRODUCT MESSAGES", {
            sessionId: sessionData.sessionId,
            originalMessageId: originalMessageId,
            productsCount: products.length,
            to: to
        });

        const session = this.sessionManager.getSession(sessionData.sessionId);
        if (!session || !session.client) {
            logger.error("❌ SESSION NOT FOUND FOR PRODUCT MESSAGES", {
                sessionId: sessionData.sessionId,
                originalMessageId: originalMessageId
            });
            return;
        }

        try {
            for (let i = 0; i < products.length; i++) {
                const product = products[i];
                
                // Send product message
                if (product.formattedProductMessage) {
                    await session.client.sendMessage(to, product.formattedProductMessage);
                    
                    logger.outgoingMessage("PRODUCT MESSAGE SENT", {
                        sessionId: sessionData.sessionId,
                        originalMessageId: originalMessageId,
                        productIndex: i + 1,
                        productMessageLength: product.formattedProductMessage.length,
                        to: to
                    });
                }

                // Send product media if available
                if (product.mediaUrls && Array.isArray(product.mediaUrls) && product.mediaUrls.length > 0) {
                    for (let j = 0; j < product.mediaUrls.length; j++) {
                        const mediaUrl = product.mediaUrls[j];
                        
                        try {
                            await session.client.sendMessage(to, mediaUrl);
                            
                            logger.outgoingMessage("PRODUCT MEDIA SENT", {
                                sessionId: sessionData.sessionId,
                                originalMessageId: originalMessageId,
                                productIndex: i + 1,
                                mediaIndex: j + 1,
                                mediaUrl: mediaUrl.substring(0, 100) + (mediaUrl.length > 100 ? "..." : ""),
                                to: to
                            });
                        } catch (mediaError) {
                            logger.error("❌ PRODUCT MEDIA SEND FAILED", {
                                sessionId: sessionData.sessionId,
                                originalMessageId: originalMessageId,
                                productIndex: i + 1,
                                mediaIndex: j + 1,
                                mediaUrl: mediaUrl,
                                error: mediaError.message,
                                to: to
                            });
                        }
                    }
                }

                // Add small delay between products to avoid spam detection
                if (i < products.length - 1) {
                    await new Promise(resolve => setTimeout(resolve, 1000));
                }
            }
        } catch (error) {
            logger.error("❌ PRODUCT MESSAGES HANDLING FAILED", {
                sessionId: sessionData.sessionId,
                originalMessageId: originalMessageId,
                error: error.message,
                stack: error.stack,
                to: to
            });
        }
    }

    async sendMessage(sessionId, to, messageText) {
        console.log("🔥 TRACE: MessageManager.sendMessage called", { sessionId, to: to?.substring(0, 10) + "...", messageLength: messageText?.length });
        logger.info("🔥 TRACE: MessageManager.sendMessage called", { sessionId, to: to?.substring(0, 10) + "...", messageLength: messageText?.length });
        
        const session = this.sessionManager.getSession(sessionId);

        console.log("🔥 TRACE: Session retrieved", { sessionId, sessionExists: !!session, sessionStatus: session?.status });
        logger.info("🔥 TRACE: Session retrieved", { sessionId, sessionExists: !!session, sessionStatus: session?.status });

        if (!session || session.status !== "connected") {
            console.log("🔥 TRACE: Session not connected, throwing error");
            logger.error("🔥 TRACE: Session not connected, throwing error");
            throw new Error("Session not connected");
        }

        try {
            const chatId = to.includes("@c.us") ? to : `${to}@c.us`;
            
            console.log("🔥 TRACE: About to log outgoing message", { chatId });
            logger.info("🔥 TRACE: About to log outgoing message", { chatId });
            
            // Log du message sortant avant envoi
            logger.info("🔍 DEBUG: Appel logger.outgoingMessage MESSAGE SENDING dans MessageManager", {
                sessionId: sessionId,
                to: chatId
            });
            logger.outgoingMessage("MESSAGE SENDING", {
                sessionId: sessionId,
                userId: session.userId,
                to: chatId,
                messageLength: messageText.length,
                messagePreview: messageText.substring(0, 100) + (messageText.length > 100 ? "..." : ""),
                timestamp: new Date().toISOString()
            });

            console.log("🔥 TRACE: About to call session.client.sendMessage");
            logger.info("🔥 TRACE: About to call session.client.sendMessage");

            await session.client.sendMessage(chatId, messageText);

            console.log("🔥 TRACE: session.client.sendMessage completed successfully");
            logger.info("🔥 TRACE: session.client.sendMessage completed successfully");

            session.lastActivity = new Date();

            // Log du message sortant envoyé avec succès
            logger.debug("🔍 DEBUG: Appel logger.outgoingMessage MESSAGE SENT SUCCESSFULLY dans MessageManager", {
                sessionId: sessionId,
                to: chatId
            });
            logger.outgoingMessage("MESSAGE SENT SUCCESSFULLY", {
                sessionId: sessionId,
                userId: session.userId,
                to: chatId,
                messageLength: messageText.length,
                sentAt: new Date().toISOString()
            });

            console.log("🔥 TRACE: Returning success result");
            logger.info("🔥 TRACE: Returning success result");

            return {
                success: true,
                sessionId,
                to,
                message: messageText,
                timestamp: new Date().toISOString(),
            };
        } catch (error) {
            console.log("🔥 TRACE: Error in sendMessage", { error: error.message });
            logger.error("🔥 TRACE: Error in sendMessage", { error: error.message });
            
            logger.error(`❌ OUTGOING MESSAGE FAILED`, {
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

module.exports = MessageManager;
