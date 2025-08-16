#!/usr/bin/env node

// Test script pour vérifier les logs de messages
const logger = require('../src/config/logger');

console.log('🧪 Testing message logging system...\n');

// Simulation d'un message entrant
const mockMessage = {
    id: { _serialized: 'fake_message_id_12345' },
    from: '237676636794@c.us',
    to: 'status@broadcast',
    body: 'Hello, this is a test message!',
    type: 'chat',
    fromMe: false,
    timestamp: Math.floor(Date.now() / 1000),
    hasMedia: false,
    deviceType: 'android',
    author: null
};

const mockSessionData = {
    sessionId: 'session_2_test_123456',
    userId: 2,
    phoneNumber: '237676636794'
};

// Test des différents types de logs
logger.incomingMessage("MESSAGE RECEIVED", {
    sessionId: mockSessionData.sessionId,
    userId: mockSessionData.userId,
    messageId: mockMessage.id._serialized,
    from: mockMessage.from,
    to: mockMessage.to,
    body: mockMessage.body,
    type: mockMessage.type,
    fromMe: mockMessage.fromMe,
    isGroup: mockMessage.from.includes("@g.us"),
    timestamp: mockMessage.timestamp,
    hasMedia: mockMessage.hasMedia,
    deviceType: mockMessage.deviceType,
    author: mockMessage.author,
    receivedAt: new Date().toISOString()
});

logger.incomingMessage("PRIVATE MESSAGE", {
    sessionId: mockSessionData.sessionId,
    contact: mockMessage.from,
    messageBody: mockMessage.body.substring(0, 100) + (mockMessage.body.length > 100 ? "..." : ""),
    messageId: mockMessage.id._serialized
});

logger.incomingMessage("PROCESSING MESSAGE", {
    sessionId: mockSessionData.sessionId,
    messageId: mockMessage.id._serialized,
    action: "sending_to_laravel"
});

logger.incomingMessage("MESSAGE PROCESSED", {
    sessionId: mockSessionData.sessionId,
    messageId: mockMessage.id._serialized,
    hasAiResponse: true,
    responseLength: 25
});

logger.outgoingMessage("AI RESPONSE SENT", {
    sessionId: mockSessionData.sessionId,
    originalMessageId: mockMessage.id._serialized,
    responseText: "Hello! How can I help you?",
    responseLength: 25,
    to: mockMessage.from
});

// Test d'un message de groupe
const mockGroupMessage = {
    ...mockMessage,
    id: { _serialized: 'fake_group_message_id_67890' },
    from: '237123456789-1234567890@g.us',
    author: '237676636794@c.us'
};

logger.incomingMessage("GROUP MESSAGE", {
    sessionId: mockSessionData.sessionId,
    groupId: mockGroupMessage.from,
    author: mockGroupMessage.author,
    messageBody: mockGroupMessage.body.substring(0, 100) + (mockGroupMessage.body.length > 100 ? "..." : ""),
    messageId: mockGroupMessage.id._serialized
});

// Test d'un message avec média
const mockMediaMessage = {
    ...mockMessage,
    id: { _serialized: 'fake_media_message_id_11111' },
    hasMedia: true,
    type: 'image'
};

logger.incomingMessage("MEDIA MESSAGE", {
    sessionId: mockSessionData.sessionId,
    from: mockMediaMessage.from,
    mediaType: mockMediaMessage.type,
    messageId: mockMediaMessage.id._serialized
});

// Test de messages sortants
logger.outgoingMessage("MESSAGE SENDING", {
    sessionId: mockSessionData.sessionId,
    userId: mockSessionData.userId,
    to: '237123456789@c.us',
    messageLength: 45,
    messagePreview: "Test de message sortant...",
    timestamp: new Date().toISOString()
});

logger.outgoingMessage("MESSAGE SENT SUCCESSFULLY", {
    sessionId: mockSessionData.sessionId,
    userId: mockSessionData.userId,
    to: '237123456789@c.us',
    messageLength: 45,
    sentAt: new Date().toISOString()
});

// Test d'erreur
logger.error("❌ MESSAGE PROCESSING FAILED", {
    sessionId: mockSessionData.sessionId,
    messageId: mockMessage.id._serialized,
    error: "Connection timeout",
    stack: "Error stack trace here...",
    from: mockMessage.from,
    messageBody: mockMessage.body.substring(0, 100)
});

console.log('\n✅ Test logs generated successfully!');
console.log('📁 Check the logs in: nodejs/whatsapp-bridge/logs/');
console.log('📄 Incoming messages: incoming-messages-YYYY-MM-DD.log');
console.log('📄 Outgoing messages: outgoing-messages-YYYY-MM-DD.log');
console.log('📄 Main log: whatsapp-YYYY-MM-DD.log');
console.log('📄 Error log: error-YYYY-MM-DD.log');
console.log('\n💡 Commands to monitor specific logs:');
console.log('   tail -f logs/incoming-messages-*.log');
console.log('   tail -f logs/outgoing-messages-*.log');
console.log('\n💡 Command to monitor all message logs:');
console.log('   tail -f logs/incoming-messages-*.log logs/outgoing-messages-*.log');
