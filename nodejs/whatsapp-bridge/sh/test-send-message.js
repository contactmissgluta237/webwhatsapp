#!/usr/bin/env node

// Script pour tester l'envoi d'un message et surveiller les logs

const axios = require('axios');

async function testMessage() {
    console.log('🧪 Test d\'envoi de message...\n');
    
    try {
        // D'abord, récupérer les sessions connectées
        console.log('🔍 Récupération des sessions...');
        const sessionsResponse = await axios.get('http://localhost:3000/api/sessions');
        const connectedSessions = sessionsResponse.data.sessions.filter(s => s.status === 'connected');
        
        if (connectedSessions.length === 0) {
            console.log('❌ Aucune session connectée trouvée');
            console.log('💡 Connectez d\'abord une session WhatsApp');
            return;
        }
        
        const session = connectedSessions[0];
        console.log(`✅ Session connectée trouvée: ${session.sessionId}`);
        console.log(`📱 Numéro: ${session.phoneNumber}\n`);
        
        // Envoyer un message de test
        const testNumber = session.phoneNumber; // Envoyer à soi-même
        const testMessage = `🧪 Message de test - ${new Date().toISOString()}`;
        
        console.log('📤 Envoi du message de test...');
        console.log(`📞 Vers: ${testNumber}`);
        console.log(`💬 Message: ${testMessage}\n`);
        
        const sendResponse = await axios.post('http://localhost:3000/api/bridge/send-message', {
            session_id: session.sessionId,
            to: testNumber,
            message: testMessage
        });
        
        if (sendResponse.data.success) {
            console.log('✅ Message envoyé avec succès!');
            console.log(`🕐 Timestamp: ${sendResponse.data.timestamp}`);
            console.log('\n💡 Pour voir les logs en temps réel:');
            console.log('   ./monitor-messages.sh');
            console.log('\n💡 Pour analyser les logs:');
            console.log('   ./analyze-messages.sh');
        } else {
            console.log('❌ Erreur lors de l\'envoi:', sendResponse.data.message);
        }
        
    } catch (error) {
        console.error('❌ Erreur:', error.response?.data || error.message);
    }
}

testMessage();
