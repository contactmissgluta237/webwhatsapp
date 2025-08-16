# 📨 Système de Logs des Messages WhatsApp

Ce document décrit le système de logs amélioré pour les messages WhatsApp entrants dans le bridge Node.js.

## 🔍 Types de Logs Ajoutés

### Messages Entrants
- **📨 MESSAGE RECEIVED** : Log détaillé de chaque message reçu
- **👤 PRIVATE MESSAGE** : Messages privés (conversation 1-1)
- **👥 GROUP MESSAGE** : Messages de groupe
- **📎 MEDIA MESSAGE** : Messages avec pièces jointes (images, vidéos, etc.)

### Traitement des Messages
- **🔄 PROCESSING MESSAGE** : Début du traitement d'un message
- **✅ MESSAGE PROCESSED** : Message traité avec succès
- **🤖 AI RESPONSE SENT** : Réponse IA envoyée
- **❌ MESSAGE PROCESSING FAILED** : Erreur lors du traitement

### Communications Laravel
- **🌐 SENDING TO LARAVEL** : Envoi vers l'API Laravel
- **✅ LARAVEL RESPONSE RECEIVED** : Réponse de Laravel reçue
- **🤖 AI RESPONSE FROM LARAVEL** : Réponse IA reçue de Laravel
- **❌ LARAVEL WEBHOOK FAILED** : Erreur de communication avec Laravel

## 📁 Fichiers de Logs

Les logs sont stockés dans le dossier `logs/` avec rotation quotidienne :

- **`app-YYYY-MM-DD.log`** : Tous les logs de l'application
- **`whatsapp-YYYY-MM-DD.log`** : Logs spécifiques WhatsApp
- **`error-YYYY-MM-DD.log`** : Logs d'erreurs uniquement

## 🛠️ Outils de Monitoring

### 1. Surveillance en Temps Réel
```bash
./monitor-messages.sh
```
Affiche les messages entrants en temps réel avec formatage lisible.

### 2. Analyse des Messages
```bash
./analyze-messages.sh [date]
# Exemple:
./analyze-messages.sh 2025-08-15
```
Génère des statistiques détaillées sur les messages d'une journée.

### 3. Test du Système
```bash
node test-message-logging.js
```
Génère des logs de test pour vérifier le bon fonctionnement.

## 📊 Exemples de Logs

### Message Privé Reçu
```json
{
  "level": "info",
  "message": "[WhatsApp] 📨 MESSAGE RECEIVED",
  "sessionId": "session_2_123456",
  "userId": 2,
  "messageId": "msg_ABC123",
  "from": "237676636794@c.us",
  "body": "Bonjour, comment allez-vous ?",
  "type": "chat",
  "fromMe": false,
  "isGroup": false,
  "hasMedia": false,
  "timestamp": "2025-08-15T10:30:00.000Z"
}
```

### Message de Groupe
```json
{
  "level": "info",
  "message": "[WhatsApp] 👥 GROUP MESSAGE",
  "sessionId": "session_2_123456",
  "groupId": "237123456789-1234567890@g.us",
  "author": "237676636794@c.us",
  "messageBody": "Salut tout le monde!"
}
```

### Réponse IA Envoyée
```json
{
  "level": "info",
  "message": "[WhatsApp] 🤖 AI RESPONSE SENT",
  "sessionId": "session_2_123456",
  "originalMessageId": "msg_ABC123",
  "responseText": "Bonjour ! Je vais bien, merci...",
  "responseLength": 45
}
```

## 🔧 Commandes Utiles

### Voir les Messages d'Aujourd'hui
```bash
grep "MESSAGE RECEIVED" logs/whatsapp-$(date +%Y-%m-%d).log
```

### Compter les Messages par Heure
```bash
grep "MESSAGE RECEIVED" logs/whatsapp-$(date +%Y-%m-%d).log | \
grep -o '"timestamp":"[^"]*"' | \
cut -d'"' -f4 | cut -d' ' -f2 | cut -d':' -f1 | \
sort | uniq -c
```

### Surveiller les Erreurs
```bash
tail -f logs/error-*.log | grep "MESSAGE"
```

### Voir les Réponses IA
```bash
grep "AI RESPONSE" logs/whatsapp-$(date +%Y-%m-%d).log
```

## 🚨 Dépannage

### Vérifier la Session Connectée
```bash
curl http://localhost:3000/api/sessions
```

### Vérifier les Logs en Temps Réel
```bash
tail -f logs/whatsapp-*.log | grep -E "(📨|❌)" --color=always
```

### Analyser les Erreurs
```bash
grep "PROCESSING FAILED" logs/error-*.log
```

## 📈 Métriques Importantes

Les logs permettent de suivre :
- ✅ Nombre de messages reçus par jour/heure
- ✅ Taux de succès du traitement des messages
- ✅ Performance des réponses IA
- ✅ Erreurs de communication avec Laravel
- ✅ Activité par session/utilisateur

## 🔄 Intégration avec Laravel

Le système log automatiquement :
1. **Envoi vers Laravel** : Chaque message est envoyé à l'API Laravel
2. **Réponse Laravel** : Les réponses IA sont loggées
3. **Erreurs de Communication** : Problèmes réseau ou API loggés
4. **Performance** : Temps de réponse de Laravel mesuré

## 💡 Bonnes Pratiques

1. **Surveillance Quotidienne** : Utiliser `analyze-messages.sh` chaque jour
2. **Monitoring Temps Réel** : Lancer `monitor-messages.sh` pendant les tests
3. **Archivage** : Les logs sont automatiquement rotés (14 jours pour app, 7 jours pour whatsapp)
4. **Alertes** : Surveiller le nombre d'erreurs `MESSAGE PROCESSING FAILED`

---

**Note** : Ce système de logs est conçu pour être informatif sans impacter les performances. Tous les logs sont asynchrones et n'bloquent pas le traitement des messages.
