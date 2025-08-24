# TODO Node.js – Couverture de tests automatisés

## Objectif
Couvrir l’ensemble du code Node.js (whatsapp-bridge) avec des tests unitaires et d’intégration, module par module.

## Architecture cible
- Un dossier `__tests__` dans chaque sous-module (services, managers, routes, config)
- Un fichier de test par module, nom explicite : `NomDuFichier.test.js`
- Utilisation de Jest

## Plan de couverture

### services/
- [ ] FileSystemService.test.js
- [ ] LaravelWebhookService.test.js
- [ ] SessionPersistenceService.test.js
- [ ] TypingSimulatorService.test.js

### managers/
- [ ] MessageManager.test.js
- [ ] SessionManager.test.js
- [ ] WhatsAppManager.test.js

### routes/
- [ ] bridge.test.js
- [ ] sessions.test.js

### config/
- [ ] logger.test.js
- [ ] config.test.js
- [ ] apiDocumentation.test.js

### server.js
- [ ] server.test.js

## Règles
- Mock des dépendances externes (API, fichiers, clients WhatsApp)
- Tests unitaires pour la logique pure
- Tests d’intégration pour les routes/API

---
On coche chaque case au fur et à mesure de l’avancement.
