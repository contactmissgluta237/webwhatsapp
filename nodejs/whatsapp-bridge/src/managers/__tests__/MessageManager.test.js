describe('MessageManager', () => {
const MessageManager = require('../MessageManager');

  it('handleIncomingMessage répond avec le message AI', async () => {
    const mockReply = jest.fn();
    const mockMessage = { reply: mockReply, id: { _serialized: 'id1' }, from: 'user', body: 'hello', timestamp: 123, type: 'chat' };
    const mockSessionData = { userId: 'user1', sessionId: 'sess1' };
    const mockWebhookService = {
      notifyIncomingMessage: jest.fn().mockResolvedValue({ response_message: 'AI response' })
    };
    const manager = new MessageManager({});
    manager.webhookService = mockWebhookService;

    await manager.handleIncomingMessage(mockMessage, mockSessionData);
    expect(mockWebhookService.notifyIncomingMessage).toHaveBeenCalledWith(mockMessage, mockSessionData);
    expect(mockReply).toHaveBeenCalledWith('AI response');
  });

  it('handleIncomingMessage ne répond pas si pas de réponse AI', async () => {
    const mockReply = jest.fn();
    const mockMessage = { reply: mockReply, id: { _serialized: 'id2' }, from: 'user', body: 'hello', timestamp: 123, type: 'chat' };
    const mockSessionData = { userId: 'user2', sessionId: 'sess2' };
    const mockWebhookService = {
      notifyIncomingMessage: jest.fn().mockResolvedValue({})
    };
    const manager = new MessageManager({});
    manager.webhookService = mockWebhookService;

    await manager.handleIncomingMessage(mockMessage, mockSessionData);
    expect(mockWebhookService.notifyIncomingMessage).toHaveBeenCalledWith(mockMessage, mockSessionData);
    expect(mockReply).not.toHaveBeenCalled();
  });
});
