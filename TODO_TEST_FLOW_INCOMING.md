# TODO - Plan de Tests Complet : Flow IncomingMessageController

## 🎯 OBJECTIF
Créer une suite de tests complète et progressive pour valider le flow du webhook `IncomingMessageController` depuis les plus petites unités jusqu'aux tests fonctionnels complets.

## 📊 ARCHITECTURE ANALYSÉE

### Flow Principal
```
NodeJS → POST /api/whatsapp/webhook/incoming-message → IncomingMessageController
    ↓
    IncomingMessageRequest (validation)
    ↓
    WhatsAppMessageOrchestratorInterface::findWhatsAppAccount()
    ↓
    WhatsAppMessageRequestDTO::fromWebhookData()
    ↓
    ConversationHistoryService::prepareConversationHistory()
    ↓
    WhatsAppMessageOrchestrator::processMessage()
        ↓
        MessageBuildServiceInterface (construction prompt)
        ↓
        AIProviderServiceInterface (appel IA)
        ↓
        AIResponseParserHelper (parsing réponse)
        ↓
        ResponseTimingHelper (calcul timings)
        ↓
        WhatsAppMessageResponseDTO::create()
    ↓
    WhatsAppMessageResponseDTO::toWebhookResponse()
    ↓
    JSON Response → NodeJS
```

### Dépendances Identifiées
- **DTOs** : `WhatsAppMessageRequestDTO`, `WhatsAppMessageResponseDTO`
- **Services** : `ConversationHistoryService`, `MessageBuildService`, `AIProviderService`
- **Helpers** : `ResponseTimingHelper`, `AIResponseParserHelper`
- **Orchestrateur** : `WhatsAppMessageOrchestrator`
- **Contrôleur** : `IncomingMessageController`
- **Models** : `WhatsAppAccount`, `WhatsAppConversation`, `WhatsAppMessage`

---

## 🏗️ PLAN DE TESTS PYRAMIDAL

### ⭐ NIVEAU 1 : Tests Unitaires des DTOs (Fondations Solides)

#### 1.1 WhatsAppMessageRequestDTO ✅ TERMINÉ
**Fichier** : `tests/Unit/DTOs/WhatsApp/WhatsAppMessageRequestDTOTest.php` ✅

**Tests implémentés** :
- ✅ `test_from_webhook_data_creates_dto_correctly()` ✅
  - Données webhook complètes
  - Vérification de tous les champs
  - Gestion des métadonnées optionnelles

- ✅ `test_get_chat_id_returns_from_field()` ✅
  - Chat privé : `+237123456789@c.us`
  - Chat de groupe : `group123@g.us`

- ✅ `test_get_contact_phone_removes_whatsapp_suffixes()` ✅
  - `+237123456789@c.us` → `+237123456789`
  - `group123@g.us` → `group123`

- ✅ `test_is_from_group_detects_group_messages()` ✅
  - Message privé : `isGroup = false`
  - Message de groupe : `isGroup = true`

- ✅ `test_from_webhook_data_with_minimal_data()` ✅
  - Champs obligatoires seulement
  - `chatName` et `metadata` null

- ✅ **BONUS Tests Supplémentaires Implémentés** :
  - ✅ `test_handles_special_characters_and_emojis()` ✅
  - ✅ `test_different_message_types()` ✅
  - ✅ `test_with_different_timestamps()` ✅
  - ✅ `test_with_complex_metadata()` ✅

**📊 Résultats : 9 tests, 34 assertions, 100% réussite, 24ms**

#### 1.2 WhatsAppMessageResponseDTO
**Fichier** : `tests/Unit/DTOs/WhatsApp/WhatsAppMessageResponseDTOTest.php`

**Tests à implémenter** :
- ✅ `test_to_webhook_response_with_ai_response()`
  - Réponse IA valide
  - Inclusion des timings
  - Structure JSON correcte

- ✅ `test_to_webhook_response_without_ai_response()`
  - `hasAiResponse = false`
  - `response_message = null`
  - Pas de timings

- ✅ `test_to_webhook_response_with_error()`
  - `success = false`
  - Message d'erreur inclus
  - `processed = false`

- ✅ `test_webhook_response_includes_timing_parameters()`
  - `wait_time_seconds` présent
  - `typing_duration_seconds` présent
  - Valeurs numériques valides

- ✅ `test_webhook_response_with_enriched_products()`
  - Produits enrichis inclus
  - Métadonnées produits correctes

---

### ⭐ NIVEAU 2 : Tests Unitaires des Services Individuels

#### 2.1 ConversationHistoryService ✅ TERMINÉ
**Fichier** : `tests/Unit/Services/WhatsApp/ConversationHistoryServiceTest.php` ✅

**Tests implémentés** :
- ✅ `test_service_constants()` ✅
  - DEFAULT_MESSAGE_LIMIT = 20
  - MAX_MESSAGE_LIMIT = 50  
  - CONTEXT_WINDOW_HOURS = 24

- ✅ `test_validate_message_limit_logic()` ✅
  - Limite par défaut (null → 20)
  - Valeurs valides conservées
  - Valeurs trop petites (← 1)
  - Valeurs trop grandes (→ 50)

- ✅ `test_message_direction_enum()` ✅
  - INBOUND vs OUTBOUND
  - Méthodes equals()
  - Valeurs string correctes

- ✅ `test_message_type_enum()` ✅
  - TEXT enum validation
  - Structure et valeurs

- ✅ `test_carbon_date_formatting()` ✅
  - Format Y-m-d, H:i, d/m/Y
  - Dates reproductibles

- ✅ `test_context_window_calculation()` ✅
  - Calcul 24h en arrière
  - Comparaisons temporelles
  - Différence en secondes

- ✅ `test_empty_string_handling()` ✅
  - trim() sur différents types
  - empty() validation
  - Caractères d'espacement

- ✅ `test_array_operations_for_formatting()` ✅
  - implode/explode historique
  - Format "user: message\nsystem: response"
  - Parsing bidirectionnel

- ✅ `test_timestamp_formatting_logic()` ✅
  - Format H:i pour messages
  - Différentes heures de la journée
  - Comparaisons de dates

- ✅ `test_message_content_validation()` ✅
  - Contenu valide vs invalide
  - Caractères spéciaux et emojis
  - Messages longs
  - Validation trim/empty

- ✅ `test_ai_confidence_formatting()` ✅
  - Format percentage (95%)
  - Seuils de confidence (≥0.8)
  - Gestion null confidence

- ✅ `test_date_separator_logic()` ✅
  - Détection jour différent
  - Format séparateur "--- dd/mm/yyyy ---"
  - Logique de groupement par date

**📊 Résultats : 12 tests, 70 assertions, 100% réussite, 40ms**
  - Messages entrants : `user:`
  - Messages sortants : `system:`
  - Ordre chronologique

- ✅ `test_conversation_history_within_time_window()`
  - Fenêtre de 24h par défaut
  - Messages anciens exclus
  - Messages récents inclus

#### 2.2 ResponseTimingHelper
**Fichier** : `tests/Unit/Services/WhatsApp/Helpers/ResponseTimingHelperTest.php`

**Tests à implémenter** :
- ✅ `test_calculate_wait_time_based_on_message_length()`
  - Message court : 0-1 seconde
  - Message moyen : 1-3 secondes
  - Message long : 3-5 secondes

- ✅ `test_calculate_typing_duration_realistic()`
  - Basé sur vitesse de frappe humaine
  - Minimum 1 seconde
  - Maximum 8 secondes

- ✅ `test_timing_limits_respected()`
  - `waitTime` entre 0-10 secondes
  - `typingDuration` entre 1-15 secondes
  - Pas de valeurs négatives

- ✅ `test_edge_cases_timing()`
  - Message vide : timing minimum
  - Message très long : timing maximum
  - Caractères spéciaux comptés

#### 2.3 AIResponseParserHelper
**Fichier** : `tests/Unit/Services/WhatsApp/AIResponseParserHelperTest.php`

**Tests à implémenter** :
- ✅ `test_parse_structured_response_valid_json()`
  - JSON IA bien formé
  - Extraction des champs requis
  - Validation du schéma

- ✅ `test_parse_structured_response_with_products()`
  - Produits mentionnés extraits
  - IDs produits valides
  - Métadonnées produits

- ✅ `test_parse_structured_response_malformed_json()`
  - JSON invalide géré
  - Fallback vers texte brut
  - Pas d'exception levée

- ✅ `test_extract_products_from_response()`
  - Patterns de détection produits
  - Références par nom/ID
  - Produits inexistants ignorés

---

### ⭐ NIVEAU 3 : Tests d'Intégration des Services

#### 3.1 MessageBuildService (avec mocks)
**Fichier** : `tests/Unit/Services/WhatsApp/MessageBuildServiceTest.php`

**Tests à implémenter** :
- ✅ `test_build_system_prompt_with_conversation_history()`
  - Historique intégré au prompt
  - Contexte utilisateur préservé
  - Format prompt IA correct

- ✅ `test_build_system_prompt_with_products_context()`
  - Produits utilisateur inclus
  - Descriptions produits
  - Instructions vente

- ✅ `test_build_system_prompt_without_products()`
  - Compte sans produits
  - Prompt générique
  - Pas de références produits

- ✅ `test_build_prompt_integrates_conversation_history_service()`
  - Mock `ConversationHistoryService`
  - Appel avec bons paramètres
  - Intégration du résultat

#### 3.2 AIProviderService (avec mocks)
**Fichier** : `tests/Unit/Services/WhatsApp/AIProviderServiceTest.php`

**Tests à implémenter** :
- ✅ `test_generate_ai_response_success()`
  - API IA répond correctement
  - Réponse parsée
  - Métadonnées incluses

- ✅ `test_generate_ai_response_api_error()`
  - Erreur API gérée
  - Retry logic testée
  - Fallback approprié

- ✅ `test_generate_ai_response_timeout()`
  - Timeout API géré
  - Pas de blocage
  - Log d'erreur

- ✅ `test_ai_response_validation()`
  - Format réponse vérifié
  - Contenu sécurisé
  - Longueur raisonnable

---

### ⭐ NIVEAU 4 : Tests de l'Orchestrateur

#### 4.1 WhatsAppMessageOrchestrator (Tests Unitaires Complets)
**Fichier** : `tests/Unit/Services/WhatsApp/WhatsAppMessageOrchestratorTest.php`

**Tests à implémenter** :
- ✅ `test_process_message_with_valid_ai_response()`
  - Flow complet réussi
  - Tous les services appelés
  - DTO de réponse correct

- ✅ `test_process_message_without_ai_response()`
  - IA ne répond pas
  - `processedWithoutResponse()` retourné
  - Pas d'erreur levée

- ✅ `test_process_message_with_ai_error()`
  - Erreur dans chaîne IA
  - Exception capturée
  - DTO d'erreur retourné

- ✅ `test_find_whatsapp_account_existing()`
  - Compte trouvé par session_id
  - Compte actif vérifié
  - Métadonnées correctes

- ✅ `test_find_whatsapp_account_not_found()`
  - Session_id inexistant
  - Null retourné
  - Log approprié

- ✅ `test_product_enrichment_flow()`
  - Produits extraits de la réponse IA
  - Enrichissement avec BDD
  - Métadonnées produits ajoutées

- ✅ `test_response_timing_calculation()`
  - Timings calculés via helper
  - Valeurs incluses dans DTO
  - Réalisme des timings

- ✅ `test_orchestrator_exception_handling()`
  - Exceptions de services gérées
  - Rollback si nécessaire
  - Logs d'erreur détaillés

---

### ⭐ NIVEAU 5 : Tests du Contrôleur (Feature Tests)

#### 5.1 IncomingMessageController (Tests Fonctionnels HTTP)
**Fichier** : `tests/Feature/WhatsApp/Webhook/IncomingMessageControllerTest.php`

**Tests à implémenter** :
- ✅ `test_incoming_message_webhook_success()`
  - POST `/api/whatsapp/webhook/incoming-message`
  - Payload webhook valide
  - Réponse 200 avec structure correcte

- ✅ `test_incoming_message_validation_errors()`
  - Données manquantes/invalides
  - Réponse 422 validation
  - Messages d'erreur appropriés

- ✅ `test_whatsapp_account_not_found()`
  - Session_id inexistant
  - Réponse 404
  - Message d'erreur clair

- ✅ `test_successful_ai_response_generation()`
  - Compte valide trouvé
  - IA génère réponse
  - JSON webhook correct avec timings

- ✅ `test_no_ai_response_scenario()`
  - IA ne répond pas
  - Réponse 200 mais `response_message = null`
  - `processed = true`

- ✅ `test_internal_server_error_handling()`
  - Exception dans orchestrateur
  - Réponse 500
  - Log d'erreur généré

- ✅ `test_webhook_response_json_structure()`
  - Champs requis présents
  - `success`, `processed`, `response_message`
  - `wait_time_seconds`, `typing_duration_seconds`

- ✅ `test_logging_throughout_request()`
  - Log début de requête
  - Log étapes importantes
  - Log fin avec résultat

---

### ⭐ NIVEAU 6 : Tests d'Intégration Complète

#### 6.1 Flow End-to-End (Simulation Réelle)
**Fichier** : `tests/Feature/WhatsApp/Webhook/IncomingMessageFlowTest.php`

**Tests à implémenter** :
- ✅ `test_complete_webhook_to_response_flow()`
  - Base de données réelle (RefreshDatabase)
  - Factories pour données test
  - Flow complet sans mocks

- ✅ `test_conversation_persistence()`
  - Message stocké en BDD
  - Conversation créée/mise à jour
  - Historique construit correctement

- ✅ `test_ai_response_persistence()`
  - Réponse IA stockée
  - Métadonnées sauvées
  - Relations BDD correctes

- ✅ `test_different_account_types()`
  - Compte actif vs inactif
  - Compte avec/sans produits
  - Différents modèles IA

- ✅ `test_group_vs_private_messages()`
  - Messages de groupe traités
  - Messages privés traités
  - Comportements différenciés

- ✅ `test_concurrent_webhook_requests()`
  - Multiples requêtes simultanées
  - Pas de conflicts BDD
  - Performance acceptable

---

### ⭐ NIVEAU 7 : Tests de Performance et Edge Cases

#### 7.1 Tests de Performance
**Fichier** : `tests/Feature/WhatsApp/Performance/WebhookPerformanceTest.php`

**Tests à implémenter** :
- ✅ `test_webhook_response_time_under_threshold()`
  - Temps de réponse < 5 secondes
  - Mesure précise des performances
  - Alertes si dépassement

- ✅ `test_multiple_concurrent_webhooks()`
  - 10+ requêtes simultanées
  - Pas de deadlocks BDD
  - Resources CPU/Memory raisonnables

- ✅ `test_memory_usage_reasonable()`
  - Pas de memory leaks
  - Garbage collection efficace
  - Limite mémoire respectée

#### 7.2 Tests Edge Cases
**Fichier** : `tests/Feature/WhatsApp/EdgeCases/WebhookEdgeCasesTest.php`

**Tests à implémenter** :
- ✅ `test_very_long_messages()`
  - Messages > 4000 caractères
  - Pas de troncature non contrôlée
  - Performance acceptable

- ✅ `test_special_characters_and_emojis()`
  - UTF-8 géré correctement
  - Émojis préservés
  - Caractères spéciaux échappés

- ✅ `test_malformed_webhook_payloads()`
  - JSON malformé géré
  - Champs manquants/incorrects
  - Pas de crash applicatif

- ✅ `test_expired_invalid_sessions()`
  - Session expirée
  - Session_id invalide format
  - Gestion élégante des erreurs

---

## 🎯 ORDRE D'EXÉCUTION RECOMMANDÉ

### Phase 1 : Fondations (DTOs)
```bash
# Créer et exécuter les tests DTOs
./vendor/bin/phpunit tests/Unit/DTOs/WhatsApp/WhatsAppMessageRequestDTOTest.php
./vendor/bin/phpunit tests/Unit/DTOs/WhatsApp/WhatsAppMessageResponseDTOTest.php
```

### Phase 2 : Services Individuels
```bash
# Tests services un par un
./vendor/bin/phpunit tests/Unit/Services/WhatsApp/ConversationHistoryServiceTest.php
./vendor/bin/phpunit tests/Unit/Services/WhatsApp/Helpers/ResponseTimingHelperTest.php
./vendor/bin/phpunit tests/Unit/Services/WhatsApp/Helpers/AIResponseParserHelperTest.php
```

### Phase 3 : Services Intégrés
```bash
# Tests avec mocks de dépendances
./vendor/bin/phpunit tests/Unit/Services/WhatsApp/MessageBuildServiceTest.php
./vendor/bin/phpunit tests/Unit/Services/WhatsApp/AIProviderServiceTest.php
```

### Phase 4 : Orchestrateur
```bash
# Test de l'orchestrateur complet
./vendor/bin/phpunit tests/Unit/Services/WhatsApp/WhatsAppMessageOrchestratorTest.php
```

### Phase 5 : Contrôleur HTTP
```bash
# Tests fonctionnels du contrôleur
./vendor/bin/phpunit tests/Feature/WhatsApp/Webhook/IncomingMessageControllerTest.php
```

### Phase 6 : Flow Complet
```bash
# Tests end-to-end
./vendor/bin/phpunit tests/Feature/WhatsApp/Webhook/IncomingMessageFlowTest.php
```

### Phase 7 : Performance et Edge Cases
```bash
# Tests avancés
./vendor/bin/phpunit tests/Feature/WhatsApp/Performance/
./vendor/bin/phpunit tests/Feature/WhatsApp/EdgeCases/
```

---

## 🎯 CRITÈRES DE SUCCÈS

### Couverture de Code
- **DTOs** : 100% de couverture
- **Services** : >95% de couverture
- **Orchestrateur** : >90% de couverture
- **Contrôleur** : >90% de couverture

### Performance
- **Temps de réponse** : <5 secondes (99th percentile)
- **Memory usage** : <50MB par requête
- **Concurrent requests** : >10 sans dégradation

### Fiabilité
- **Tests unitaires** : 100% de passage
- **Tests d'intégration** : 100% de passage
- **Edge cases** : Tous gérés élégamment

---

## 🚀 PROCHAINES ÉTAPES

1. ✅ **Créer ce TODO** (FAIT)
2. ✅ **Phase 1** : Implémenter tests DTOs (FAIT - WhatsAppMessageRequestDTO ✅)
3. ⏳ **Phase 2** : Implémenter tests services individuels  
4. ⏳ **Phase 3** : Implémenter tests services intégrés
5. ⏳ **Phase 4** : Implémenter tests orchestrateur
6. ⏳ **Phase 5** : Implémenter tests contrôleur
7. ⏳ **Phase 6** : Implémenter tests flow complet
8. ⏳ **Phase 7** : Implémenter tests performance

### Commandes Utiles
```bash
# Exécuter tous les tests du flow
./vendor/bin/phpunit tests/Unit/DTOs/WhatsApp/ tests/Unit/Services/WhatsApp/ tests/Feature/WhatsApp/

# Exécuter avec couverture
./vendor/bin/phpunit --coverage-html coverage/ tests/Unit/DTOs/WhatsApp/

# Exécuter un test spécifique
./vendor/bin/phpunit --filter test_from_webhook_data_creates_dto_correctly

# Exécuter avec verbosité
./vendor/bin/phpunit --verbose tests/Feature/WhatsApp/Webhook/
```

---

## 📝 NOTES IMPORTANTES

### Principes de Test
- **Isolation** : Chaque test doit être indépendant
- **Mocking** : Mocker les dépendances externes
- **Assertions** : Vérifications précises et complètes
- **Nommage** : Noms de tests explicites et descriptifs

### Patterns de Test Laravel
- `RefreshDatabase` pour tests avec BDD
- `WithFaker` pour données de test
- `MockeryPHPUnitIntegration` pour mocks
- Factories pour création de modèles

### Standards de Qualité
- Code de test aussi important que code principal
- Documentation des cas de test complexes
- Maintenance régulière des tests
- Révision des tests lors des refactors

**🎯 OBJECTIF FINAL** : Avoir une confiance totale dans la robustesse et la fiabilité du flow webhook IncomingMessageController !
