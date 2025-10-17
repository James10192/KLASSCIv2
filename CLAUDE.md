# KLASSCI - Documentation Système SaaS Multi-Tenant

## 🎯 Vue d'ensemble

**Architecture** : 2 applications distinctes
- **Master** (`klassci-master`) : Admin SaaS centralisé - gère tous les tenants
- **Tenant** (`KLASSCIv2`) : Application métier par établissement

**Tenants actifs** (Octobre 2025)
- `esbtp-abidjan` : ESBTP Abidjan (Pro - 30 users, 3000 inscriptions)
- `esbtp-yakro` : ESBTP Yakro (Essentiel - 20 users, 700 inscriptions)
- `presentation` : Test (Free - 5 users, 50 inscriptions)

## 🚀 Phase 1-3 : Infrastructure SaaS (TERMINÉ)

### Phase 1 : Base Laravel 12 ✅
- 8 migrations + 8 modèles Eloquent
- BDD `klassci_master` : tenants, deployments, health_checks, backups, features, activity_logs, admins, invoices

### Phase 2 : Commandes Artisan ✅
6 commandes créées :
```bash
saas:create-admin              # Créer admin SaaS
tenant:provision               # Provisionner nouveau tenant (17 étapes)
tenant:deploy [--all]          # Déployer mises à jour
tenant:health-check [--all]    # Vérifier santé (HTTP, DB, SSL, etc.)
tenant:backup [--all]          # Backup DB + fichiers
tenant:update-stats [--all]    # Mettre à jour statistiques
```

### Phase 3 : Dashboard Filament ✅
- Panel admin `/admin` avec Filament v3.3
- Tenant Resource complet (5 onglets)
- KPI globaux + monitoring temps réel

## 🔧 Fonctionnalités métier

### Inscriptions & Paiements

**Détection doublons** (`StudentDuplicateDetector`)
- Recherche fuzzy (tokenisation + similarité)
- Modal d'avertissement avec confirmation
- Route : `esbtp.inscriptions.duplicates`

**Refresh AJAX partiel**
- Partiels : `metrics.blade.php`, `table.blade.php`, `results.blade.php`
- Polling auto 30s + bouton manuel
- Animation "travelling light" lors màj statut
- Routes : `paiements.refresh`, `inscriptions.refresh-ligne`, `paiements.refresh-ligne`

**Actions groupées paiements**
- Validation/rejet en masse
- Protection doublons (fenêtre 10s)
- Logging détaillé avec emojis (🔵 🆕 ✅ ⚠️ ❌)

**Matricules tolérants**
- Génération auto avec retry (3 tentatives)
- Helper `MatriculeGenerator`
- Gestion collision SQL 1062

### Notifications Multi-Canal

**Système complet parents** (`NotificationService`)
- Canaux : App + Email + WhatsApp + SMS
- Table `parent_notification_logs` (tracking coûts)
- Préférences : `parent_notification_preferences`

**Templates email** (11 types)
- Layout moderne blanc/bleu
- Logo embed (CID attachment)
- Inscription, paiements, absences, bulletins, notes

**WhatsApp** (`WhatsAppService`)
- Meta Cloud API
- 6 templates UTILITY approuvés
- Coût : ~3 FCFA/msg hors fenêtre 24h

**SMS** (`SmsService`)
- Providers : Orange CI, Beem, SMS.to
- Fallback urgences uniquement
- Coût : ~7 FCFA/SMS

**Configuration**
```env
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_ACCESS_TOKEN=
WHATSAPP_ENABLED=false

SMS_PROVIDER=orange
SMS_API_KEY=
SMS_SENDER_ID=KLASSCI
SMS_ENABLED=false
```

### Bulletins & Évaluations

**Workflow génération bulletin**
1. Configuration matières
2. Vérification moyennes
3. Édition professeurs (propagation classe)
4. Édition absences (optionnel)
5. Génération PDF

**Système refresh AJAX évaluations**
- Filtres : recherche, pagination, per-page
- Statuts auto : brouillon, planifiée, en_cours, terminée, annulée
- Actions : Annuler/Activer/Réactiver + suppression JSON
- KPI dynamiques

### Gestion Classes

**Lazy loading étudiants** (suivi-categories)
- Pagination 20 par batch
- Bouton "Charger plus"
- Polling non-intrusif (pas d'overlay)

**Load More AJAX** (classes.index)
- Pagination manuelle avec `slice()`
- KPI globaux (toutes classes actives)
- Helper functions DOM dynamiques

### Permissions & Accès

**Rôle étudiant** (11 permissions)
- `view_own_*` : grades, exams, profile, timetable, attendances, bulletin

**Rôle coordinateur**
- `view_classes` (lecture seule)
- Pas de create/edit classes

**Dashboard étudiant** - Design moderne `dashboard-acasi`
- Stat cards, badges, tableaux stylisés
- Pages : profil, notes, évaluations, emploi du temps, absences, paiements

## 🛠️ Architecture technique

### Fuzzy Search
- Service `FuzzyNameMatcher`
- Protection SQL via escape `%`
- Scoring similarité + fallback
- Pagination mémoire `LengthAwarePaginator`

### AJAX Pattern
```javascript
// Fetch + pushState + DOM update
fetch(url).then(data => {
    container.innerHTML = data.html;
    history.pushState({}, '', data.url);
    rebindEvents();
});
```

### Logging
```php
\Log::info('🔵 START', $context);
\Log::info('⏳ PROCESSING', $stats);
\Log::info('✅ COMPLETED', ['duration' => $ms]);
```

## 📊 Statistiques Code

**Phase 1-2** : 1,700+ lignes PHP
- Commande complexe : `tenant:provision` (465 lignes)
- Total tables : 10 (master DB)

**Frontend**
- 11 templates email parents
- 10+ partiels Blade réutilisables
- JS vanilla (pas jQuery sauf Select2)

## 🔐 Sécurité

**MySQL readonly master**
```sql
GRANT SELECT ON klassci_master.tenants TO 'klassci_readonly'@'localhost';
```

**Anti-doublons paiements**
- Fingerprint requête (MD5 user+IP+UA)
- Fenêtre temporelle 10s
- Logging complet

**Credentials**
- WhatsApp/SMS : jamais exposés (.env)
- Mots de passe : session temporaire uniquement

## 📈 Coûts estimés (500 parents/an)

- **Email** : 0 FCFA (gratuit)
- **WhatsApp** : 3,300 FCFA (~5€) - 80% fenêtre gratuite
- **SMS fallback** : 1,750 FCFA (~2.70€) - 5% parents
- **Total** : ~5,050 FCFA/an (~8€)

## 🚦 Commandes utiles

```bash
# SaaS Master
php artisan tenant:provision --code=xxx --name="..." --plan=pro
php artisan tenant:deploy --all
php artisan tenant:health-check --all

# Maintenance
php artisan config:clear && cache:clear && view:clear
php artisan permission:cache-reset

# Tests
php artisan tinker
>>> app(\App\Services\WhatsAppService::class)->send...()
```

## 📝 Configuration SMTP

```env
MAIL_MAILER=smtp
MAIL_HOST=mail.klassci.com
MAIL_PORT=465
MAIL_USERNAME=support@klassci.com
MAIL_ENCRYPTION=ssl
MAIL_FROM_NAME="KLASSCI"
```

## 🎨 Design System

**Dashboard moderne**
- Container : `dashboard-acasi`
- Cartes : `main-card`, `stat-card`
- Badges : `status-badge-success/danger/warning`
- Boutons : `btn-acasi primary/secondary`
- Tables : `table-modern`

**Emails**
- Blanc (#ffffff) + Bleu (#007bff)
- Responsive mobile
- Logo embed CID
- Pas d'emojis

## 🐛 Fixes récents

- **09/10** : Erreur 403 pages étudiants → permissions ajoutées
- **10/10** : Logo email manquant → `public_path()` + `embed()`
- **10/10** : Filtrage année courante → `is_current` vs `is_active`
- **11/10** : Orange SMS OAuth2 → token caché 50min
- **13/10** : Polling non-intrusif → paramètre `showOverlay`
- **13/10** : Doublons paiements → protection backend 10s
- **16/10** : Réinitialisation sélection étudiants après save + nettoyage modals
- **16/10** : Erreur getRelationExistenceQuery → select colonnes explicites
- **17/10** : Configuration type enseignement groupée → accordion avec stats temps réel

## ✨ Fonctionnalités récentes

### Édition groupée résultats classe (16 octobre 2025)

**Vue** : `/esbtp/resultats/classe/{classe}/edit`

**Fonctionnalités**
- Sélection multiple étudiants (checkboxes)
- 4 modals d'édition groupée :
  - **Moyennes** : 2 modes (par matière / par étudiant)
  - **Professeurs** : assignation par matière
  - **Absences** : justifiées/non justifiées
  - **Matières** : coefficients + type d'enseignement (général/technique)

**Pattern UX**
- Refresh AJAX partiel après save (pas de reload complet)
- Animation "travelling light" sur lignes modifiées
- Réinitialisation auto de la sélection après save moyennes/absences
- Préservation sélection après save professeurs/matières
- Nettoyage contenu modals à la fermeture
- Validation obligatoire du semestre avant édition

**Fichiers modifiés**
- `resources/views/esbtp/resultats/classe-edit.blade.php` : Vue principale + JS
- `resources/views/esbtp/resultats/modals/edit-matieres.blade.php` : Modal avec accordion (coefficients + types)
- `app/Http/Controllers/ESBTPBulletinController.php` :
  - `bulkUpdateMoyennes()` : validation flexible `moyennes.*.matiere_id`
  - `getAbsences()` : select colonnes explicites (évite accessors bugués)
  - `bulkUpdateAbsences()` : sauvegarde absences groupées
  - `bulkUpdateProfesseurs()` : assignation professeurs groupée
  - `bulkUpdateMatieresConfig()` : coefficients + types dans `esbtp_config_matiere`

**Bugs corrigés**
1. Erreur "Call to a member function getRelationExistenceQuery() on null"
   - **Cause** : `->get()` déclenchait les accessors Eloquent avec relations inexistantes
   - **Fix** : `->select([colonnes])` pour éviter les accessors
2. Checkboxes restaient cochées après save
   - **Fix** : fonction `resetStudentSelection()` + paramètre `shouldResetSelection`
3. Contenu modal persistait après fermeture
   - **Fix** : `initializeModalCleanup()` avec listeners `hidden.bs.modal`

---

*Dernière mise à jour: 16 octobre 2025*
