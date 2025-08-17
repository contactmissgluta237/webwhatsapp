const { Client } = require('whatsapp-web.js');

// Test script pour voir les méthodes disponibles pour marquer comme lu
console.log('Methods available on Client and Chat related to "read" or "seen":');

const client = new Client();

// Méthodes sur le client
const clientMethods = Object.getOwnPropertyNames(Client.prototype);
const readMethods = clientMethods.filter(method => 
    method.toLowerCase().includes('read') || 
    method.toLowerCase().includes('seen') ||
    method.toLowerCase().includes('ack')
);

console.log('Client methods:', readMethods);

// Test si sendSeen existe
console.log(`\nChecking common read/seen methods:`);
const commonMethods = [
    'sendSeen',
    'markAsRead', 
    'markChatAsRead',
    'sendReadReceipt',
    'markAsUnread'
];

commonMethods.forEach(method => {
    console.log(`client.${method}: ${typeof client[method]}`);
});

// Test sur un chat mock
console.log('\nNote: Pour Chat object, les méthodes sont probablement:');
console.log('- chat.sendSeen()');
console.log('- chat.markUnread()'); 
console.log('- Mais il faut tester avec un vrai chat object');
