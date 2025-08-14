// Squelette de test pour la route sessions


const request = require('supertest');
const express = require('express');


// Mock WhatsAppManager pour isoler la route
const mockGetAllSessions = jest.fn().mockReturnValue([
  { sessionId: 'sess1', userId: 'user1', status: 'connected' },
  { sessionId: 'sess2', userId: 'user2', status: 'disconnected' }
]);
const WhatsAppManager = function () {
  return { getAllSessions: mockGetAllSessions };
};

const sessionsRoute = require('../sessions');

describe('Route /api/sessions', () => {
  let app;

  beforeAll(() => {
    app = express();
    app.use(express.json());
    app.use('/api/sessions', sessionsRoute(new WhatsAppManager()));
  });

  it('GET /api/sessions - liste des sessions', async () => {
    const res = await request(app)
      .get('/api/sessions');
    expect(res.statusCode).toBe(200);
    expect(res.body.success).toBe(true);
    expect(Array.isArray(res.body.sessions)).toBe(true);
    expect(res.body.sessions.length).toBe(2);
    expect(res.body.sessions[0]).toHaveProperty('sessionId', 'sess1');
    expect(mockGetAllSessions).toHaveBeenCalled();
  });
});
