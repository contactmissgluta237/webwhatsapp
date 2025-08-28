# TODO: Tests des Request Classes

**Résumé:** 30 classes Request - 5 avec tests (16.7%) - 25 sans tests (83.3%)

---

## 🎯 PRIORITÉ CRITIQUE - Auth Requests

### Auth (7 classes) - 2/7 avec tests
- ✅ **LoginRequest** → HAS TEST (mais n'utilise pas BaseRequestTestCase)
- ✅ **RegisterRequest** → HAS TEST (utilise BaseRequestTestCase)
- [ ] **ActivateAccountFormRequest** → NO TEST
- [ ] **ForgotPasswordFormRequest** → NO TEST  
- [ ] **ResetPasswordFormRequest** → NO TEST
- [ ] **VerifyOtpRequest** → NO TEST

---

## 🔥 PRIORITÉ HAUTE - Customer Requests

### Customer (6 classes) - 0/6 avec tests
- [ ] **CreateCustomerRechargeRequest** → NO TEST
- [ ] **CreateProductRequest** → NO TEST
- [ ] **UpdateProductRequest** → NO TEST
- [ ] **Ticket/CreateTicketRequest** → NO TEST
- [ ] **Ticket/ReplyTicketRequest** → NO TEST
- [ ] **WhatsApp/AiConfigurationRequest** → NO TEST

---

## 🟡 PRIORITÉ MOYENNE - Admin Requests

### Admin (10 classes) - 0/10 avec tests
- [ ] **CreateAdminRechargeRequest** → NO TEST
- [ ] **CreateUserRequest** → NO TEST
- [ ] **UpdateUserRequest** → NO TEST
- [ ] **Packages/StorePackageRequest** → NO TEST
- [ ] **Packages/UpdatePackageRequest** → NO TEST
- [ ] **SystemAccounts/RechargeRequest** → NO TEST
- [ ] **SystemAccounts/WithdrawalRequest** → NO TEST
- [ ] **Ticket/ReplyTicketRequest** → NO TEST
- [ ] **Withdrawal/AutomaticWithdrawalRequest** → NO TEST
- [ ] **Withdrawal/ManualWithdrawalRequest** → NO TEST

---

## 🟢 PRIORITÉ FAIBLE - Autres Requests

### Profile (3 classes) - 3/3 avec tests ✅
- ✅ **UpdateAvatarRequest** → HAS TEST (utilise BaseRequestTestCase)
- ✅ **UpdatePasswordRequest** → HAS TEST (utilise BaseRequestTestCase)
- ✅ **UpdateProfileRequest** → HAS TEST (utilise BaseRequestTestCase)

### Autres (5 classes) - 0/5 avec tests
- [ ] **Api/WhatsApp/SessionStatusWebhookRequest** → NO TEST
- [ ] **PushSubscription/StorePushSubscriptionRequest** → NO TEST
- [ ] **PushSubscription/DestroyPushSubscriptionRequest** → NO TEST
- [ ] **WhatsApp/AiConfigurationRequest** → NO TEST
- [ ] **WhatsApp/Webhook/IncomingMessageRequest** → NO TEST

---

## 📋 TÂCHES SPÉCIFIQUES

### Phase 1 - Nettoyage et cohérence
- [x] **Migrer LoginRequestTest** vers BaseRequestTestCase pour cohérence

### Phase 2 - Auth (Critique pour sécurité)
- [x] **ActivateAccountFormRequest** Test
- [x] **ForgotPasswordFormRequest** Test
- [🔄 EN COURS] **ResetPasswordFormRequest** Test
- [ ] **VerifyOtpRequest** Test

### Phase 3 - Customer (Critique pour business)
- [ ] **CreateCustomerRechargeRequest** Test
- [ ] **CreateProductRequest** Test
- [ ] **UpdateProductRequest** Test
- [ ] **Customer/Ticket/CreateTicketRequest** Test
- [ ] **Customer/Ticket/ReplyTicketRequest** Test
- [ ] **Customer/WhatsApp/AiConfigurationRequest** Test

### Phase 4 - Admin (Important pour administration)
- [ ] **CreateAdminRechargeRequest** Test
- [ ] **CreateUserRequest** Test
- [ ] **UpdateUserRequest** Test
- [ ] **Admin/Packages/StorePackageRequest** Test
- [ ] **Admin/Packages/UpdatePackageRequest** Test

### Phase 5 - Système (Optionnel)
- [ ] **Admin/SystemAccounts/RechargeRequest** Test
- [ ] **Admin/SystemAccounts/WithdrawalRequest** Test
- [ ] **Admin/Ticket/ReplyTicketRequest** Test
- [ ] **Admin/Withdrawal/AutomaticWithdrawalRequest** Test
- [ ] **Admin/Withdrawal/ManualWithdrawalRequest** Test

### Phase 6 - API et Webhooks (Technique)
- [ ] **Api/WhatsApp/SessionStatusWebhookRequest** Test
- [ ] **PushSubscription/StorePushSubscriptionRequest** Test
- [ ] **PushSubscription/DestroyPushSubscriptionRequest** Test
- [ ] **WhatsApp/AiConfigurationRequest** Test
- [ ] **WhatsApp/Webhook/IncomingMessageRequest** Test

---

## 📊 MÉTRIQUES DE PROGRESSION

**Total:** 30 Request classes
- **Avec tests:** 8/30 (26.7%)
- **Critiques sans tests:** 10/30 (33.3%)
- **Objectif Phase 1-3:** 15/30 (50%)
- **Objectif final:** 30/30 (100%)

---

## 🚀 STATUT ACTUEL

**En cours:** 🔄 ResetPasswordFormRequest Test  
**Suivant:** VerifyOtpRequest Test  
**Dernière mise à jour:** 29 août 2025

---

## 💡 NOTES

- **BaseRequestTestCase** permet de standardiser la validation des Request
- **Tests Auth** = sécurité critique de l'application
- **Tests Customer** = fonctionnalités business principales
- **Tests Admin** = administration et gestion du système
- Cocher [ ] → [x] quand terminé
- Marquer "🔄 EN COURS" quand travail en cours
