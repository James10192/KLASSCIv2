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
- **17/10** : Marquage manuel attendance enseignants → cache Eloquent + priorité dates + création automatique
- **17/10** : Exclusion séances absentes du calcul heures effectuées → planning général + emploi temps
- **17/10** : Système AJAX marquage présences étudiants → no-reload + badges FontAwesome + détection correcte attendances
- **17/10** : Correction terminologie attendances.index → "Présences/Absences" au lieu d'"Étudiants" (KPI + graphique + stats classe)
- **17/10** : Fix filtrage étudiants attendances → ajout classe_id dans whereHas inscriptions (cohérence avec classes.show)
- **17/10** : Fix filtrage étudiants roll-call enseignant → ajout classe_id dans TeacherDashboardController::showRollCall()

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

### Marquage manuel attendance enseignants (17 octobre 2025)

**Fonctionnalité** : Les coordinateurs/admins peuvent marquer manuellement le statut de présence des enseignants

**Pages concernées**
- `/esbtp/teacher-attendance/report` : Liste des séances avec boutons d'action
- `/esbtp/seances-cours/{id}` : Page détail séance

**Problèmes résolus**

1. **Cache Eloquent dans refresh AJAX**
   - **Cause** : `$seance->teacherAttendances` retournait données en cache même après `save()`
   - **Solution** : `unsetRelation('teacherAttendances')` puis `load()` pour forcer reload DB
   - **Fichier** : `ESBTPTeacherAttendanceController::refreshSeanceLigne()` (L606-656)

2. **Mauvaise priorité de détection des attendances**
   - **Cause** : Les vues cherchaient uniquement l'attendance à `date_seance`, ignorant celle d'aujourd'hui
   - **Solution** : Priorité `today()` > `date_seance` > plus récent
   - **Fichiers** :
     - `seance-row.blade.php` (L1-33) : Logique partiel
     - `show.blade.php` (L137-202) : Logique page détail

3. **Erreur 404 pour enseignants "non émargé"**
   - **Cause** : Le code refusait de traiter les séances sans attendance existante
   - **Solution** : Création automatique d'attendance manuel avec `attempts=0` et `date=today()`
   - **Fichier** : `ESBTPTeacherAttendanceController::updateStatus()` (L527-600)

**Caractéristiques importantes**

- ⚠️ **Workflow JAMAIS modifié** : Le marquage manuel ne change pas le workflow de la séance
- 🏷️ **Attendances manuelles** : `attempts = 0` (vs ≥1 pour émargements enseignants)
- 📅 **Date marquage** : `date = today()` (vs date séance originale)
- 🎨 **Animation** : "Travelling light" effet visuel lors du refresh AJAX
- 🔄 **Persistance** : Les changements restent après F5

**Routes ajoutées**
```php
POST   /esbtp/teacher-attendance/seance/{seance}/update-status
GET    /esbtp/teacher-attendance/seance/{seance}/refresh-ligne
```

**Middleware** : `auth`, `role:superAdmin|coordinateur`

**Pattern technique**
```javascript
// 1. Update status via POST
fetch('/update-status', { method: 'POST', body: { status: 'present' } })
// 2. Refresh HTML via GET (unsetRelation + load)
fetch('/refresh-ligne').then(data => replaceRow(data.html))
// 3. Animation "travelling light" pendant le remplacement
triggerSeanceRowHighlight(row, 'present')
```

**Logs de débogage**
```
🔵 START updateStatus
📝 Avant update (si existe)
🆕 Création attendance manuel (si non émargé)
✅ Attendance updated/créé
ℹ️ Workflow non modifié
🔄 Refresh seance ligne
📊 Attendances après reload
```

### Exclusion absences du calcul heures effectuées (17 octobre 2025)

**Problématique** : Les heures des séances où l'enseignant était absent étaient comptabilisées dans les "heures effectuées", faussant les statistiques du planning général et de l'emploi du temps.

**Exemple** : Un enseignant avec 11H planifiées et une séance de 2H marquée "absent" affichait "2H / 11H" au lieu de "0H / 11H".

**Solution** : Modification des requêtes SQL pour exclure les séances où `latest_attendance.status = 'absent'`.

**Pages affectées**
- `/esbtp/planning-general/repartition-matieres` : Vue globale heures par matière/filière/niveau
- `/esbtp/emploi-temps/{id}` : Détail emploi du temps avec stats par matière

**Logique implémentée**

```sql
-- Sous-requête pour obtenir l'attendance la plus récente par séance
-- Priorité: today() > date_seance > created_at DESC
SELECT ta1.course_id, ta1.status
FROM esbtp_teacher_attendances ta1
INNER JOIN (
    SELECT course_id,
           MAX(CASE
               WHEN DATE(date) = CURDATE() THEN CONCAT("1_", created_at)
               WHEN DATE(date) = date_seance THEN CONCAT("2_", created_at)
               ELSE CONCAT("3_", created_at)
           END) as max_priority
    FROM esbtp_teacher_attendances
    WHERE type = "start"
    GROUP BY course_id
) ta2 ON ta1.course_id = ta2.course_id
WHERE ta1.type = "start"

-- Filtrer les séances NON absentes
WHERE latest_attendance.status IS NULL
   OR latest_attendance.status != 'absent'
```

**Fichiers modifiés**

1. **ESBTPPlanningGeneralController::calculerRepartitionMatieresDetaillees()** (L1296-1338)
   - Left join avec sous-requête pour attendance la plus récente
   - Exclusion des séances avec `status = 'absent'`
   - COUNT et SUM appliqués uniquement aux séances présentes/retard/non émargées

2. **ESBTPEmploiTempsController::getPlanificationDataForClasse()** (L217-258)
   - Même logique pour le calcul fallback des heures effectuées
   - Sous-requête corrélée dans le `whereRaw` pour éviter les erreurs de scope

**Impact**

- ✅ **Heures effectuées** : Maintenant précises, excluent les absences
- ✅ **Nb séances** : Compte seulement les séances effectuées
- ✅ **Pourcentage réalisé** : Calcul correct (heures_effectuées / heures_planifiées)
- ✅ **Heures restantes** : Calcul précis pour la planification

**Exemple résultat**

```
Avant : Math - L3 Info : 2H effectuées / 11H planifiées (18%)
Après : Math - L3 Info : 0H effectuées / 11H planifiées (0%)
                        [Si 2H de séance = enseignant absent]
```

**Règle métier**

> **Séances comptabilisées** : `status IS NULL` (non émargé) OU `status IN ('present', 'late')`
>
> **Séances EXCLUES** : `status = 'absent'`

Cela signifie qu'une séance "non émargée" compte comme effectuée (bénéfice du doute), mais une séance explicitement marquée "absent" est exclue.

### Système AJAX marquage présences étudiants (17 octobre 2025)

**Fonctionnalité** : Interface de création de présences étudiantes sans rechargement de page lors des sélections de classe et séance.

**Page** : `/esbtp/attendances/create` - Marquage manuel des présences étudiantes

**Problèmes résolus**

1. **Erreur JavaScript "impossible de trouver le formulaire de sélection"**
   - **Cause** : Le selector `querySelector('#selectionForm .row')` cherchait `.row` DANS `#selectionForm`, mais le form lui-même a la classe `row`
   - **Solution** : Changement à `document.getElementById('selectionForm')`
   - **Localisations** : Lignes 371, 567, 408 dans `create.blade.php`

2. **Boutons "Enregistrer" non créés dynamiquement**
   - **Cause** : Les boutons étaient dans le template Blade statique mais absents lors du chargement AJAX des étudiants
   - **Solution** : Création dynamique des boutons après insertion du tableau d'étudiants
   - **Code** :
     ```javascript
     submitButtons = document.createElement('div');
     submitButtons.className = 'mt-4 submit-buttons';
     submitButtons.innerHTML = `
         <button type="submit" class="btn btn-gradient-primary">
             <i class="mdi mdi-content-save"></i> Enregistrer les présences
         </button>
         <a href="{{ route('esbtp.attendances.index') }}" class="btn btn-light">Annuler</a>
     `;
     ```

3. **Badges contradictoires : "Non marquée" dans select vs "Modification" sur lignes étudiants**
   - **Cause principale** : La méthode `store()` trouvait TOUTES les attendances (incluant `call_type='start'` de l'émargement enseignant) alors que la détection de badge filtrait seulement `call_type='merged'` ou `NULL`
   - **Symptôme** : Message "2 présences mises à jour" quand devraient être "2 nouvelles présences créées"
   - **Solution** : Ajout du même filtrage dans `store()` (lignes 802-814) :
     ```php
     $attendance = ESBTPAttendance::where([
         'seance_cours_id' => $validatedData['seance_cours_id'],
         'etudiant_id' => $etudiantId,
         'date' => $validatedData['date']
     ])
     ->where(function($query) {
         $query->where('call_type', 'merged')
               ->orWhereNull('call_type');
     })
     ->first();
     ```

4. **Badge "Non marquée" persistant malgré attendances existantes**
   - **Cause** : La condition `if ($seance->date_calculee)` empêchait la vérification quand `date_calculee` était `NULL`
   - **Solution** : Utiliser `now()->format('Y-m-d')` comme fallback (lignes 606-628) :
     ```php
     // Utiliser la date calculée ou aujourd'hui comme fallback
     $dateRecherche = $seance->date_calculee ?: now()->format('Y-m-d');

     $hasAttendances = ESBTPAttendance::where('seance_cours_id', $seance->id)
         ->where('date', $dateRecherche)
         ->where(function($q) {
             $q->where('call_type', 'merged')->orWhereNull('call_type');
         })
         ->exists();
     ```

**Caractéristiques implémentées**

- ✅ **AJAX no-reload** : Sélection classe → séances, sélection séance → étudiants
- ✅ **Icônes FontAwesome** : Remplacement emojis par `fas fa-check-circle` (vert) et `far fa-circle` (gris)
- ✅ **Badges dynamiques** :
  - Select séance : "Présence marquée" (vert) / "Non marquée" (gris)
  - Lignes étudiants : "Modification" (orange) / "Nouveau marquage" (vert)
- ✅ **Distinction call_type** : Seules les attendances manuelles (`merged`/`NULL`) comptent, pas l'émargement enseignant (`start`)
- ✅ **Date auto-calculée** : Champ readonly basé sur la date de la séance
- ✅ **Save classique** : Pas d'AJAX pour le submit → redirect vers `attendances.index`

**Fichiers modifiés**

1. **ESBTPAttendanceController.php** (L606-628 + L802-814)
   - `loadSeances()` : Ajout fallback date + filtrage call_type pour badge
   - `store()` : Filtrage call_type pour éviter confusion avec émargement enseignant

2. **create.blade.php**
   - Fix sélecteurs JavaScript (`getElementById` au lieu de `querySelector`)
   - Création dynamique boutons submit
   - Création dynamique champ date avec message info

3. **student-list.blade.php** (L12)
   - Remplacement emojis par icônes FontAwesome dans badges
   - Utilisation `{!! $modeLabel !!}` pour affichage HTML non-échappé

**Routes utilisées**

```php
GET  /esbtp/attendances/create
POST /esbtp/attendances/store
GET  /esbtp/attendances/load-seances  (AJAX)
GET  /esbtp/attendances/load-students (AJAX)
```

**Logs de débogage**

```
✅ [BADGE] Séance {id} ({matière}) a {count} attendances pour {date}
⭕ [BADGE] Séance {id} ({matière}) AUCUNE attendance pour {date}
```

**Règle métier**

> **Attendances manuelles (comptabilisées)** : `call_type = 'merged'` OU `call_type IS NULL`
>
> **Attendances émargement enseignant (ignorées)** : `call_type = 'start'`

Cette distinction permet d'éviter les doublons lors du marquage manuel après un émargement automatique enseignant.

### Correction terminologie page attendances.index (17 octobre 2025)

**Problématique** : Toute la page `attendances.index` utilisait le terme **"Étudiants"** (ex: "Étudiants Présents", "Étudiants Absents") alors qu'elle comptait en réalité des **enregistrements d'attendance**, pas des étudiants uniques.

**Confusion métier** : Un même étudiant peut avoir plusieurs enregistrements (présent à une séance, absent à une autre). Dire "4 étudiants présents" implique 4 étudiants différents, mais le système comptait "4 enregistrements avec statut=present".

**Page affectée** : `/esbtp/attendances` - Vue liste des présences avec KPI, graphique et stats par classe

**Corrections appliquées**

1. **KPI du haut** (4 cartes - lignes 590-626)
   ```diff
   - "Étudiants Présents" → "Présences"
   - "Étudiants Absents" → "Absences"
   - Icône: fa-user-check → fa-check-circle
   - Icône: fa-user-times → fa-times-circle
   ```

2. **Graphique Chart.js** "Tendance des 7 Derniers Jours" (lignes 1276-1308)
   ```diff
   - label: 'Présents' → 'Présences'
   - label: 'Absents' → 'Absences'
   - Les autres labels (Retards, Excusés) étaient déjà corrects
   ```

3. **Section "Présences par Classe"** (lignes 880-905)
   ```diff
   - "Présents" → "Présences"
   - "Absents" → "Absences"
   ```

4. **Section coordinateur - KPI "Appels Terminés"** (lignes 662-675)
   ```diff
   - "X présents" → "X présences"
   - Icône: fa-users-check → fa-check-double
   ```

5. **Résumé du jour coordinateur** (ligne 1010)
   ```diff
   - "Étudiants présents:" → "Présences enregistrées:"
   ```

**Clarification terminologique**

| Avant (incorrect) | Après (correct) | Signification |
|-------------------|-----------------|---------------|
| "Étudiants Présents" | "Présences" | Nombre d'enregistrements avec `statut='present'` |
| "Étudiants Absents" | "Absences" | Nombre d'enregistrements avec `statut='absent'` |
| "Présents" (graphique) | "Présences" | Comptage d'enregistrements, pas d'étudiants uniques |
| "Absents" (graphique) | "Absences" | Comptage d'enregistrements, pas d'étudiants uniques |

**Fichier modifié**
- `resources/views/esbtp/attendances/index.blade.php` : 8 changements de labels + 3 icônes

**Impact UX**
- ✅ **Clarté métier** : Terminologie exacte reflétant ce qui est vraiment compté
- ✅ **Cohérence** : Alignement vocabulaire frontend/backend
- ✅ **Compréhension** : Plus de confusion entre "nombre d'étudiants" et "nombre d'enregistrements"

**Note technique**

Le controller `ESBTPAttendanceController::index()` calcule :
```php
$stats = [
    'present' => (clone $statsQuery)->where('statut', 'present')->count(), // COUNT des enregistrements
    'absent' => (clone $statsQuery)->where('statut', 'absent')->count(),
    // ...
];
```

Ce sont des `COUNT(*)` sur `esbtp_attendances`, donc des enregistrements, pas des `COUNT(DISTINCT etudiant_id)`.

### Fix filtrage étudiants attendances (17 octobre 2025)

**Problème** : Dans `ESBTPAttendanceController`, les méthodes `create()` et `loadStudents()` ne filtraient pas correctement les étudiants par rapport à leur inscription **active** dans la **classe sélectionnée** pour l'année courante.

**Symptôme** : Les étudiants retournés dans `attendances.create` et `attendances.loadStudents` n'étaient **pas les mêmes** que ceux affichés dans `classes.show`, car le filtre `classe_id` manquait dans la clause `whereHas('inscriptions')`.

**Cause** : Utilisation de `$classe->etudiants()->whereHas('inscriptions')` sans vérifier que l'inscription active correspond bien à **cette classe** (un étudiant peut avoir plusieurs inscriptions dans différentes classes).

**Logique correcte** (comme dans `classes.show`) :

```php
// ESBTPClasseController::show() - Ligne 268-271
$classe->etudiants()
    ->whereHas('inscriptions', function ($inscriptionQuery) use ($anneeCourante, $classe) {
        $inscriptionQuery->where('annee_universitaire_id', $anneeCourante->id)
                         ->where('status', 'active')
                         ->where('classe_id', $classe->id); // ← Ce filtre manquait !
    });
```

**Corrections appliquées**

**Fichier** : `app/Http/Controllers/ESBTPAttendanceController.php`

1. **Méthode `loadStudents()`** (ligne 700-706)
   ```diff
   $etudiants = $classe->etudiants()
   -    ->whereHas('inscriptions', function($q) use ($anneeUniversitaire) {
   +    ->whereHas('inscriptions', function($q) use ($anneeUniversitaire, $classe) {
           $q->where('annee_universitaire_id', $anneeUniversitaire->id)
   -         ->where('status', 'active');
   +         ->where('status', 'active')
   +         ->where('classe_id', $classe->id);
       })
       ->get();
   ```

2. **Méthode `create()` - Premier endroit** (ligne 476-482)
   ```diff
   $etudiants = $classe->etudiants()
   -    ->whereHas('inscriptions', function($q) use ($anneeUniversitaire) {
   +    ->whereHas('inscriptions', function($q) use ($anneeUniversitaire, $classe) {
           $q->where('annee_universitaire_id', $anneeUniversitaire->id)
   -         ->where('status', 'active');
   +         ->where('status', 'active')
   +         ->where('classe_id', $classe->id);
       })
       ->get();
   ```

3. **Méthode `create()` - Deuxième endroit** (ligne 542-548)
   ```diff
   $etudiants = $classe->etudiants()
   -    ->whereHas('inscriptions', function($q) use ($anneeUniversitaire) {
   +    ->whereHas('inscriptions', function($q) use ($anneeUniversitaire, $classe) {
           $q->where('annee_universitaire_id', $anneeUniversitaire->id)
   -         ->where('status', 'active');
   +         ->where('status', 'active')
   +         ->where('classe_id', $classe->id);
       })
       ->get();
   ```

**Impact**

- ✅ **Cohérence** : Les étudiants affichés dans `attendances.create` sont maintenant **identiques** à ceux de `classes.show`
- ✅ **Précision** : Seuls les étudiants avec une **inscription active** pour **cette classe** dans l'année courante sont retournés
- ✅ **Évite les doublons** : Un étudiant avec plusieurs inscriptions n'apparaît que dans la bonne classe

**Règle métier**

> Pour récupérer les étudiants d'une classe, **TOUJOURS** filtrer par :
> - `annee_universitaire_id = année_courante`
> - `status = 'active'`
> - `classe_id = classe_selectionnee` ← **Crucial !**

### Fix filtrage étudiants roll-call enseignant (17 octobre 2025)

**Problème** : Le même problème de filtrage existait dans `TeacherDashboardController::showRollCall()` - les étudiants affichés lors de l'appel par l'enseignant n'étaient **pas les mêmes** que dans `classes.show`.

**Symptôme** : Lors de l'appel (start/end) via la route `teacher.roll-call`, les étudiants retournés incluaient potentiellement des étudiants avec des inscriptions actives dans **d'autres classes**, car le filtre `classe_id` manquait.

**Fichier** : `app/Http/Controllers/TeacherDashboardController.php`

**Correction appliquée** (ligne 193-201)

```diff
$etudiants = $seance->classe->etudiants()
    ->with('user')
-    ->whereHas('inscriptions', function($query) use ($anneeUniversitaire) {
+    ->whereHas('inscriptions', function($query) use ($anneeUniversitaire, $seance) {
        $query->where('annee_universitaire_id', $anneeUniversitaire->id)
-             ->where('status', 'active');
+             ->where('status', 'active')
+             ->where('classe_id', $seance->classe_id); // ← FIX: Filter par classe_id
    })
    ->get();
```

**Impact**

- ✅ **Cohérence totale** : Les étudiants dans l'appel enseignant = ceux de `classes.show` = ceux de `attendances.create`
- ✅ **Workflow complet** : Fix appliqué à tous les points d'entrée du système d'attendance
- ✅ **Évite erreurs** : Plus de confusion entre étudiants de différentes classes

**Routes concernées**

```php
GET  /dashboard/teacher/roll-call/{seance}?type=start|end
POST /dashboard/teacher/roll-call/{seance}
```

**Vues utilisées**

- `dashboard.teacher-roll-call` : Interface d'appel avec liste des étudiants
- `teacher.select-call-type` : Sélection type d'appel (redirects vers roll-call)

**Règle métier réaffirmée**

> **Filtrage étudiants : Pattern obligatoire dans TOUT le système**
> ```php
> $classe->etudiants()
>     ->whereHas('inscriptions', function($q) use ($anneeUniversitaire, $classe) {
>         $q->where('annee_universitaire_id', $anneeUniversitaire->id)
>           ->where('status', 'active')
>           ->where('classe_id', $classe->id); // ← TOUJOURS !
>     });
> ```

**Fichiers corrigés au total** (même problème dans 4 endroits)

1. ✅ `ESBTPAttendanceController::loadStudents()` (L700-706)
2. ✅ `ESBTPAttendanceController::create()` - location 1 (L476-482)
3. ✅ `ESBTPAttendanceController::create()` - location 2 (L542-548)
4. ✅ `TeacherDashboardController::showRollCall()` (L193-201) ← **Nouveau fix**

---

## 🔮 Fonctionnalités à implémenter

### Calcul honoraires enseignants (À venir)

**Objectif** : Calculer automatiquement les honoraires des enseignants basés sur leurs heures de présence effective aux cours.

**Principe** : Paiement à l'heure selon les attendances validées

**Données sources**
- Table : `esbtp_teacher_attendances`
- Critères : `status IN ('present', 'late')` ET `type = 'start'`
- Priorité attendance : same que calcul heures effectuées (today() > date_seance)

**Logique de calcul**

```php
// Pour chaque enseignant sur une période donnée
$heuresEffectuees = ESBTPSeanceCours::join('esbtp_teacher_attendances', ...)
    ->where('teacher_id', $teacherId)
    ->whereBetween('date_seance', [$dateDebut, $dateFin])
    ->where(function($q) {
        $q->whereNull('latest_attendance.status')  // Non émargé = payé
          ->orWhereIn('latest_attendance.status', ['present', 'late']);
    })
    ->sum(TIME_TO_SEC(TIMEDIFF(heure_fin, heure_debut)) / 3600);

$honoraires = $heuresEffectuees * $tauxHoraire;
```

**Règles métier à implémenter**

1. **Statuts payables**
   - `present` : 100% du taux horaire
   - `late` : 100% du taux horaire (optionnel : pénalité configurable)
   - `null` (non émargé) : 100% (bénéfice du doute)

2. **Statuts NON payables**
   - `absent` : 0% du taux horaire

3. **Taux horaire**
   - Stocké dans profil enseignant (`esbtp_teachers.taux_horaire`)
   - Peut varier par matière/niveau (optionnel)

4. **Période de calcul**
   - Par mois (défaut)
   - Par semestre
   - Par année universitaire

**Interface à créer**

- **Page** : `/esbtp/honoraires/enseignants`
- **Filtres** : Période, enseignant, statut (payé/impayé)
- **Tableau** : Enseignant | Heures présentes | Heures absentes | Taux | Montant total
- **Actions** : Générer fiche de paie, Exporter Excel, Marquer comme payé

**Champs à ajouter**

```php
// Migration: add to esbtp_teachers
$table->decimal('taux_horaire', 10, 2)->default(0)->comment('FCFA/heure');

// Nouvelle table: esbtp_honoraires
Schema::create('esbtp_honoraires', function (Blueprint $table) {
    $table->id();
    $table->foreignId('teacher_id')->constrained('esbtp_teachers')->cascadeOnDelete();
    $table->date('periode_debut');
    $table->date('periode_fin');
    $table->decimal('heures_presentes', 8, 2);
    $table->decimal('heures_absentes', 8, 2);
    $table->decimal('taux_horaire', 10, 2);
    $table->decimal('montant_total', 12, 2);
    $table->enum('statut', ['en_attente', 'validé', 'payé'])->default('en_attente');
    $table->timestamp('paye_le')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

**Dépendances**

- ✅ Système d'attendance enseignants (déjà implémenté)
- ✅ Calcul heures effectuées avec exclusion absences (déjà implémenté)
- ⏳ Gestion taux horaire par enseignant
- ⏳ Interface de génération des fiches d'honoraires
- ⏳ Export PDF/Excel
- ⏳ Workflow validation (coordinateur → admin → paiement)

**Estimation**

- Complexité : Moyenne
- Durée : 2-3 jours
- Priorité : À définir selon besoins métier

---

*Dernière mise à jour: 17 octobre 2025*
