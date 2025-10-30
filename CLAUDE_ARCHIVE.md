# KLASSCI - Archive Documentation (Avant Octobre 2025)

---

> **Note** : Ce fichier archive le contenu détaillé des développements effectués avant octobre 2025.
> Pour la documentation actuelle et les développements récents, consultez [CLAUDE.md](CLAUDE.md)

---

## 📚 Table des Matières Archive

- [Fix: Résolution Sélection Rapide Enseignant (Septembre 2025)](#fix-selection-rapide-enseignant)
- [Inscriptions & Paiements](#inscriptions-paiements)
- [Notifications Multi-Canal](#notifications-multi-canal)
- [Bulletins & Évaluations](#bulletins-evaluations)
- [Gestion Classes](#gestion-classes)
- [Permissions & Accès](#permissions-acces)
- [Architecture Technique Détaillée](#architecture-technique)

---

## Fix: Résolution Sélection Rapide Enseignant (Septembre 2025) {#fix-selection-rapide-enseignant}

**Date:** 21 septembre 2025
**Branche:** presentation

### Problèmes résolus

#### 1. Erreur de validation "La valeur sélectionnée pour periode est invalide"
- **Localisation:** `app/Http/Controllers/ESBTPBulletinController.php:2517`
- **Cause:** La méthode `resultatEtudiant` acceptait seulement les valeurs '1,2' mais recevait 'semestre2' lors de la redirection
- **Solution:** Mise à jour de la validation pour accepter les formats: '1,2,semestre1,semestre2'
- **Code ajouté:** Logique de conversion complète entre les formats entiers et string

#### 2. Fonctionnalité de sélection rapide d'enseignant non fonctionnelle
- **Localisation:** `resources/views/esbtp/bulletins/edit-professeurs.blade.php`
- **Cause:** Erreur JavaScript "selectEnseignant is not defined" due aux attributs `onchange`
- **Solution:** Remplacement par des `addEventListener` et placement direct du script dans le HTML
- **Résultat:** La sélection d'un enseignant dans le dropdown remplit automatiquement l'input correspondant

#### 3. Interface utilisateur peu moderne
- **Problème:** Design des inputs/selects et boutons trop près des bords
- **Solution:** Refonte complète avec design moderne basé sur des cartes
- **Améliorations:**
  - Cartes modernes avec hover effects
  - Meilleur espacement et placement des boutons
  - Icônes et couleurs améliorées
  - Responsive design

### Fichiers modifiés
- `app/Http/Controllers/ESBTPBulletinController.php`
- `app/Http/Controllers/ESBTPEvaluationController.php`
- `resources/views/components/student-results/results-overview-card.blade.php`
- `resources/views/esbtp/bulletins/edit-professeurs.blade.php`

### Fonctionnalités ajoutées
- Support des formats de période multiples (1, 2, semestre1, semestre2)
- Logging détaillé pour le débogage des erreurs de validation
- Interface moderne avec cartes pour l'assignation des enseignants
- Sélection rapide d'enseignant fonctionnelle avec animation
- Gestion robuste des événements JavaScript

### Structure des composants

#### Teacher Assignment Interface
Le composant d'assignation des enseignants utilise une structure moderne :

```html
<div class="subject-card">
    <div class="subject-header">
        <div class="subject-icon"><!-- Icône matière --></div>
        <div class="subject-info"><!-- Nom et code matière --></div>
    </div>
    <div class="quick-select-section"><!-- Sélection rapide --></div>
    <div class="teacher-input-section"><!-- Input enseignant --></div>
</div>
```

#### JavaScript Events
Les événements JavaScript sont gérés via `addEventListener` :

```javascript
select.addEventListener('change', function() {
    // Logique de transfert de valeur vers l'input
    const targetInput = parentCard.querySelector('.form-control-modern');
    if (targetInput) {
        targetInput.value = this.value;
        // Animation et reset du select
    }
});
```

---

## Inscriptions & Paiements {#inscriptions-paiements}

### Détection doublons (`StudentDuplicateDetector`)
- Recherche fuzzy (tokenisation + similarité)
- Modal d'avertissement avec confirmation
- Route : `esbtp.inscriptions.duplicates`

### Refresh AJAX partiel
- Partiels : `metrics.blade.php`, `table.blade.php`, `results.blade.php`
- Polling auto 30s + bouton manuel
- Animation "travelling light" lors màj statut
- Routes : `paiements.refresh`, `inscriptions.refresh-ligne`, `paiements.refresh-ligne`

### Actions groupées paiements
- Validation/rejet en masse
- Protection doublons (fenêtre 10s)
- Logging détaillé avec emojis (🔵 🆕 ✅ ⚠️ ❌)

### Matricules tolérants
- Génération auto avec retry (3 tentatives)
- Helper `MatriculeGenerator`
- Gestion collision SQL 1062

---

## Notifications Multi-Canal {#notifications-multi-canal}

### Système complet parents (`NotificationService`)
- Canaux : App + Email + WhatsApp + SMS
- Table `parent_notification_logs` (tracking coûts)
- Préférences : `parent_notification_preferences`

### Templates email (11 types)
- Layout moderne blanc/bleu
- Logo embed (CID attachment)
- Inscription, paiements, absences, bulletins, notes

### WhatsApp (`WhatsAppService`)
- Meta Cloud API
- 6 templates UTILITY approuvés
- Coût : ~3 FCFA/msg hors fenêtre 24h

### SMS (`SmsService`)
- Providers : Orange CI, Beem, SMS.to
- Fallback urgences uniquement
- Coût : ~7 FCFA/SMS

### Configuration
```env
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_ACCESS_TOKEN=
WHATSAPP_ENABLED=false

SMS_PROVIDER=orange
SMS_API_KEY=
SMS_SENDER_ID=KLASSCI
SMS_ENABLED=false
```

### Coûts estimés (500 parents/an)
- **Email** : 0 FCFA (gratuit)
- **WhatsApp** : 3,300 FCFA (~5€) - 80% fenêtre gratuite
- **SMS fallback** : 1,750 FCFA (~2.70€) - 5% parents
- **Total** : ~5,050 FCFA/an (~8€)

---

## Bulletins & Évaluations {#bulletins-evaluations}

### Workflow génération bulletin
1. Configuration matières
2. Vérification moyennes
3. Édition professeurs (propagation classe)
4. Édition absences (optionnel)
5. Génération PDF

### Système refresh AJAX évaluations
- Filtres : recherche, pagination, per-page
- Statuts auto : brouillon, planifiée, en_cours, terminée, annulée
- Actions : Annuler/Activer/Réactiver + suppression JSON
- KPI dynamiques

---

## Gestion Classes {#gestion-classes}

### Lazy loading étudiants (suivi-categories)
- Pagination 20 par batch
- Bouton "Charger plus"
- Polling non-intrusif (pas d'overlay)

### Load More AJAX (classes.index)
- Pagination manuelle avec `slice()`
- KPI globaux (toutes classes actives)
- Helper functions DOM dynamiques

---

## Permissions & Accès {#permissions-acces}

### Rôle étudiant (11 permissions)
- `view_own_*` : grades, exams, profile, timetable, attendances, bulletin

### Rôle coordinateur
- `view_classes` (lecture seule)
- Pas de create/edit classes

### Dashboard étudiant
Design moderne `dashboard-acasi`
- Stat cards, badges, tableaux stylisés
- Pages : profil, notes, évaluations, emploi du temps, absences, paiements

---

## Architecture Technique Détaillée {#architecture-technique}

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

### Statistiques Code (Phase 1-2)
**Backend** : 1,700+ lignes PHP
- Commande complexe : `tenant:provision` (465 lignes)
- Total tables : 10 (master DB)

**Frontend**
- 11 templates email parents
- 10+ partiels Blade réutilisables
- JS vanilla (pas jQuery sauf Select2)

### Sécurité
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

---

## Commandes SaaS Master

```bash
# SaaS Master
php artisan saas:create-admin
php artisan tenant:provision --code=xxx --name="..." --plan=pro
php artisan tenant:deploy --all
php artisan tenant:health-check --all
php artisan tenant:backup [--all]
php artisan tenant:update-stats [--all]

# Maintenance
php artisan config:clear && cache:clear && view:clear
php artisan permission:cache-reset

# Tests
php artisan tinker
>>> app(\App\Services\WhatsAppService::class)->send...()
```

---

## Configuration SMTP

```env
MAIL_MAILER=smtp
MAIL_HOST=mail.klassci.com
MAIL_PORT=465
MAIL_USERNAME=support@klassci.com
MAIL_ENCRYPTION=ssl
MAIL_FROM_NAME="KLASSCI"
```

---

## Design System Legacy

### Dashboard moderne
- Container : `dashboard-acasi`
- Cartes : `main-card`, `stat-card`
- Badges : `status-badge-success/danger/warning`
- Boutons : `btn-acasi primary/secondary`
- Tables : `table-modern`

### Emails
- Blanc (#ffffff) + Bleu (#007bff)
- Responsive mobile
- Logo embed CID
- Pas d'emojis

---

## Fixes Historiques Détaillés

### 09/10 - Erreur 403 pages étudiants
**Cause** : Permissions manquantes
**Fix** : Ajout des permissions `view_own_*`

### 10/10 - Logo email manquant
**Cause** : Chemin relatif
**Fix** : `public_path()` + `embed()`

### 10/10 - Filtrage année courante
**Cause** : Confusion `is_current` vs `is_active`
**Fix** : Utilisation correcte de `is_current`

### 11/10 - Orange SMS OAuth2
**Cause** : Token non persisté
**Fix** : Cache token 50min

### 13/10 - Polling non-intrusif
**Cause** : Overlay bloque UI
**Fix** : Paramètre `showOverlay`

### 13/10 - Doublons paiements
**Cause** : Soumissions multiples
**Fix** : Protection backend 10s

### 16/10 - Réinitialisation sélection étudiants
**Cause** : Checkboxes persistantes
**Fix** : Fonction `resetStudentSelection()`

### 16/10 - Erreur getRelationExistenceQuery
**Cause** : `->get()` déclenche accessors
**Fix** : `->select([colonnes])` explicite

### 17/10 - Configuration type enseignement groupée
**Cause** : Interface trop encombrée
**Fix** : Accordion avec stats temps réel

### 17/10 - Marquage manuel attendance enseignants
**Détails** : Cache Eloquent + priorité dates + création automatique

### 17/10 - Exclusion séances absentes du calcul heures
**Détails** : Planning général + emploi temps

### 17/10 - Système AJAX marquage présences étudiants
**Détails** : No-reload + badges FontAwesome + détection attendances

### 17/10 - Correction terminologie attendances.index
**Avant** : "Étudiants Présents/Absents"
**Après** : "Présences/Absences"

### 17/10-18/10 - Multiples fixes attendances
- Fix filtrage étudiants (classe_id)
- Fix calcul date séance (date_seance vs getDateSeance)
- Fix KPI cards (variable écrasée par foreach)
- Fix comptage doublons (finalOnly scope)

### 19/10 - Fix 404 route load-matieres
**Cause** : Route après `/{evaluation}`
**Fix** : Repositionnement AVANT

### 19/10 - Fix double spinner load-matieres
**Cause** : Spinners cumulés
**Fix** : `querySelectorAll + forEach remove`

### 19/10 - API LMS évaluations programmées
**Détails** : Endpoint pour LMS, mode présentiel vs en ligne

### 19/10 - API LMS dashboard étudiant/enseignant
**Détails** : Endpoints personnalisés avec statistiques

### 20/10 - Horodatage complet évaluations
**Détails** : DATETIME, heures début/fin, durée auto-calculée

### 21/10 - Refonte formulaire seances-cours.edit
**Détails** : UI harmonisée, devoirs synchronisés, helper `combineDateAndTime()`

### 21/10 - Harmonisation prévisualisation/export PDF emplois du temps
**Détails** : Pattern liste d'appel, grille factorisée

### 26/10 - Correctif placement grille hebdomadaire
**Détails** : Recalcul index pour minutes ≠ 00

### 27/10 - Raffinement export PDF emploi du temps
**Détails** : Limitations dompdf, header table, légende simple

### 27/10 - Refactor timeline emploi du temps
**Détails** : CSS Grid 07h-18h, PDF rowspan, normalisation temps

---

*Archive créée le: 30 octobre 2025*
*Contenu historique: Développements avant octobre 2025*
