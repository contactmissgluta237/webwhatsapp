# 🗑️ FICHIERS ET DOSSIERS À SUPPRIMER - PROJET WEB-WHATSAPP

## ✅ SCRIPTS DE CONVERSION/MIGRATION TEMPORAIRES (À supprimer immédiatement)

## ✅ ASSETS DUPLIQUÉS/OBSOLÈTES DANS PUBLIC
- [ ] `public/modern/` - Assets du template moderne non utilisés
  - `css/`, `fonts/`, `images/`, `js/`, `vendors/`
  - **Justification**: Redondant avec les assets compilés par Vite dans `public/build/`
- [ ] `public/app-assets/` - Assets redondants (seul `data/` présent)
- [ ] `public/assets/` - Anciens assets potentiellement non utilisés
- [ ] `public/css/` - CSS non compilé (Vite gère la compilation)
- [ ] `public/js/` - JS non compilé (Vite gère la compilation)



## ✅ OUTILS DE DÉVELOPPEMENT CHROME
- [ ] `bin/chrome-for-testing/` - Chrome de test pour Dusk (très volumineux)
  - **Justification**: Peut être retéléchargé automatiquement par Dusk si nécessaire
  - Prend beaucoup d'espace disque

### Fichiers de Configuration AI/Documentation
- [ ] `CLAUDE.md` - Instructions spécifiques Claude (garder si utilisé activement)
- [ ] `GEMINI.md` - Instructions Gemini (évaluer utilité)
- [ ] `MyCoolpay.md` - Documentation spécifique (évaluer pertinence)
- [ ] `AI_TESTS_PROTECTION.md` - Protection des tests IA (évaluer si nécessaire)

## 🔒 FICHIERS SENSIBLES À NE PAS SUPPRIMER
- ✅ `vapid_private.pem` / `vapid_public.pem` - Clés VAPID pour push notifications (CRITIQUES)
- ✅ `.env*` - Fichiers d'environnement
- ✅ `composer.lock` / `package-lock.json` - Lock files des dépendances
- ✅ `logs/` - Logs applicatifs (peuvent être nettoyés mais pas supprimés)

## 💾 ESTIMATION ESPACE RÉCUPÉRÉ
- Template moderne: ~50-100MB
- Chrome testing: ~200-500MB
- Assets dupliqués: ~20-50MB
- Scripts temporaires: ~1-5MB
- **Total estimé: 270-655MB**

## 🎯 PRIORITÉS DE SUPPRESSION
1. **IMMÉDIAT**: Scripts de conversion temporaires
2. **URGENT**: Template HTML non utilisé et bin/chrome-for-testing/
3. **MOYEN**: Assets dupliqués dans public/
4. **FAIBLE**: Consolidation des TODO et documentation
