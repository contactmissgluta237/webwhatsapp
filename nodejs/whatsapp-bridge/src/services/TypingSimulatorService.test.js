const TypingSimulatorService = require('./TypingSimulatorService');

// On utilise les fake timers de Jest pour contrôler setTimeout
jest.useFakeTimers();

describe('TypingSimulatorService', () => {
    let service;
    let mockClient;

    beforeEach(() => {
        service = new TypingSimulatorService();
        // On crée un "mock" du client WhatsApp avec des fonctions Jest
        mockClient = {
            sendStateTyping: jest.fn(),
            sendMessage: jest.fn(),
        };
    });

    it('should send typing state, wait, and then send the message', async () => {
        const chatId = '12345@c.us';
        const message = 'Hello world';

        // On lance la méthode. Elle va s'arrêter au `await new Promise(...)`
        const promise = service.simulateTypingAndSendMessage(mockClient, chatId, message);

        // 1. On vérifie que `sendStateTyping` a été appelé immédiatement
        expect(mockClient.sendStateTyping).toHaveBeenCalledWith(chatId);

        // 2. On vérifie que `sendMessage` n'a PAS encore été appelé
        expect(mockClient.sendMessage).not.toHaveBeenCalled();

        // 3. On avance les timers de Jest pour simuler l'attente
        jest.runAllTimers();

        // 4. On attend que la promesse (et donc la méthode) se termine
        await promise;

        // 5. On vérifie que `sendMessage` a maintenant été appelé avec les bons arguments
        expect(mockClient.sendMessage).toHaveBeenCalledWith(chatId, message);
        expect(mockClient.sendMessage).toHaveBeenCalledTimes(1);
    });

    it('should still send the message even if sendStateTyping fails', async () => {
        const chatId = '54321@c.us';
        const message = 'Error case';

        // On simule une erreur pour `sendStateTyping`
        mockClient.sendStateTyping.mockRejectedValue(new Error('Failed to send state'));

        const promise = service.simulateTypingAndSendMessage(mockClient, chatId, message);

        // On avance les timers et on attend la fin de la méthode
        jest.runAllTimers();
        await promise;

        // On vérifie que `sendMessage` a quand même été appelé
        expect(mockClient.sendMessage).toHaveBeenCalledWith(chatId, message);
    });
});
