// Squelette de test pour la route bridge


const request = require('supertest');
const express = require('express');

// On mocke WhatsAppManager pour isoler la route
const mockSendMessageFromLaravel = jest.fn().mockResolvedValue({ success: true, data: {}, message: 'Message sent successfully' });
const WhatsAppManager = function () {
  return { sendMessageFromLaravel: mockSendMessageFromLaravel };
};

const bridgeRoute = require('../bridge');

describe('Route /api/bridge', () => {
  let app;

  beforeAll(() => {
    app = express();
    app.use(express.json());
    app.use('/api/bridge', bridgeRoute(new WhatsAppManager()));
  });

  it('POST /api/bridge/send-message - succès', async () => {
    const res = await request(app)
      .post('/api/bridge/send-message')
      .send({ session_id: 'test', to: '12345@c.us', message: 'Hello' });
    expect(res.statusCode).toBe(200);
    expect(res.body.success).toBe(true);
    expect(res.body.message).toBe('Message sent successfully');
    expect(mockSendMessageFromLaravel).toHaveBeenCalled();
  });

  it('POST /api/bridge/send-message - champs manquants', async () => {
    const res = await request(app)
      .post('/api/bridge/send-message')
      .send({ session_id: 'test', to: '12345@c.us' });
    expect(res.statusCode).toBe(400);
    expect(res.body.success).toBe(false);
    expect(res.body.message).toMatch(/Missing required fields/);
  });
});
