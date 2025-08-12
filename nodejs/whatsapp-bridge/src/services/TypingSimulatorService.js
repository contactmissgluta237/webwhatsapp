class TypingSimulatorService {
    async simulateTypingAndSendMessage(client, chatId, messageText) {
        const TYPING_SPEED_CPS = 15; // Characters per second
        const MIN_TYPING_DURATION_MS = 1000; // Minimum 1 second typing
        const MAX_TYPING_VARIATION = 0.2; // +/- 20% variation

        const messageLength = messageText.length;
        let typingDuration = (messageLength / TYPING_SPEED_CPS) * 1000;

        // Add random variation
        typingDuration = typingDuration * (1 + (Math.random() * MAX_TYPING_VARIATION * 2) - MAX_TYPING_VARIATION);
        typingDuration = Math.max(typingDuration, MIN_TYPING_DURATION_MS); // Ensure minimum duration

        console.log(`[Typing Simulation] Simulating typing for ${typingDuration.toFixed(0)}ms for message length ${messageLength}`);

        try {
            await client.sendStateTyping(chatId); // Start typing indicator
            await new Promise(resolve => setTimeout(resolve, typingDuration)); // Wait
        } catch (error) {
            console.warn(`[Typing Simulation] Failed to send typing state or wait: ${error.message}`);
            // Continue anyway, don't block message sending
        } finally {
            // client.clearState(chatId); // sendMessage usually clears it, but good to have if needed
        }

        await client.sendMessage(chatId, messageText); // Send the actual message
    }
}

module.exports = TypingSimulatorService;
