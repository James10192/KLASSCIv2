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
- **17/10** : Fix filtrage étudiants attendances.create/loadStudents → ajout classe_id dans whereHas inscriptions (cohérence avec classes.show)
- **17/10** : Fix filtrage étudiants roll-call enseignant → ajout classe_id dans TeacherDashboardController::showRollCall()
- **17/10** : Fix filtrage attendances.index + rapport + rapportPdf → ajout status='active' + classe_id conditionnels sur inscriptions
- **18/10** : Fix calcul date séance attendances.create → utilisation date_seance BDD au lieu de getDateSeance() calculé (badge + date affichée corrects)
- **18/10** : Fix KPI Présences attendances.index → inclusion retards dans compteur présences (retards = présences métier)
- **18/10** : Fix comptage doublons attendances → refonte finalOnly() scope avec MAX(id) groupé par séance/étudiant (élimine doublons merged multiples)
- **18/10** : Fix stats par classe + graphique attendances.index → ajout filtre classe_id + fusion affichage Présences (present+retard) dans vue
- **18/10** : Fix graphique Chart.js attendances.index → fusion datasets Présences+Retards en un seul "Présences (incl. retards)" utilisant present_with_retards
- **18/10** : Fix KPI cards attendances.index affichaient zéro → variable `$stats` écrasée par boucle foreach (renommage en `$dailyStats`)
- **19/10** : Fix 404 route AJAX load-matieres → repositionnement AVANT routes /{evaluation} (Laravel route matching order)
- **19/10** : Fix 500 erreur SQL load-matieres → colonne 'name' au lieu de 'nom' dans orderBy + génération HTML options
- **19/10** : Fix double spinner load-matieres → suppression de TOUS les spinners existants avant création nouveau (querySelectorAll + forEach remove)
- **19/10** : Ajout évaluations programmées dans API LMS endpoint /api/lms/classes → permet au LMS de créer formulaires en ligne et soumettre notes
- **19/10** : Ajout endpoint GET /api/lms/me/dashboard pour étudiants → dashboard complet avec classe, cours, quiz, stats personnelles

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

### Fix calcul date séance attendances.create (18 octobre 2025)

**Problème** : La date affichée pour une séance et la détection de badge "Présence marquée" étaient incorrectes car le code utilisait `getDateSeance()` (méthode qui calcule la prochaine occurrence du jour de la semaine) au lieu de la date stockée en base de données (`date_seance`).

**Symptômes** :

1. **Date incorrecte affichée** : Une séance du vendredi 17/10/2025 affichait "18/10/2025" (samedi) car aujourd'hui c'est samedi à 00h28 et `getDateSeance()` cherche le "prochain vendredi"
2. **Badge "Non marquée" erroné** : Le système cherchait des attendances pour le 18/10 alors qu'elles existaient pour le 17/10, donc affichait "Non marquée" au lieu de "Présence marquée"
3. **Mode "Nouveau marquage" incorrect** : ACHILLE avait 2 attendances merged pour la séance mais le système affichait "Nouveau marquage" au lieu de "Modification"

**Contexte** :

- Séance #12 (Chimie) : `date_seance = 2025-10-17` (vendredi) stockée en base
- Attendances existantes : 4 enregistrements avec `date = 2025-10-17` et `call_type = 'merged'`
- Date du jour : Samedi 18/10/2025 à 00h28
- Problème : `getDateSeance()` retourne le prochain vendredi OU utilise `now()` comme fallback

**Cause technique** :

Le modèle `ESBTPSeanceCours` a deux champs pour la date :
- `date_seance` : Date réelle stockée en base (ex: `2025-10-17`)
- Méthode `getDateSeance()` : Calcule la date basée sur `emploi_temps.date_debut` + `jour` (numéro du jour de semaine)

La méthode `getDateSeance()` (lignes 320-365 dans `ESBTPSeanceCours.php`) était utilisée partout alors qu'elle est conçue pour calculer des dates futures, pas pour afficher des séances passées/planifiées.

**Fichier** : `app/Http/Controllers/ESBTPAttendanceController.php`

**Corrections appliquées**

1. **Méthode `create()`** - Détection date pour affichage et chargement attendances (lignes 494-513)

   ```diff
   - // Calculer la date de la séance
   - $dateCalculee = $seance->getDateSeance();
   - if ($dateCalculee) {
   -     $dateSeance = $dateCalculee->format('Y-m-d');
   - } else {
   -     $dateSeance = now()->format('Y-m-d');
   - }

   + // Utiliser la date de la séance stockée en base (date_seance)
   + // au lieu de calculer via getDateSeance() qui peut donner une date incorrecte
   + if (!empty($seance->date_seance)) {
   +     $dateSeance = \Carbon\Carbon::parse($seance->date_seance)->format('Y-m-d');
   +     $debug['date_source'] = 'database_date_seance';
   + } else {
   +     // Fallback: calculer si date_seance n'est pas définie
   +     $dateCalculee = $seance->getDateSeance();
   +     if ($dateCalculee) {
   +         $dateSeance = $dateCalculee->format('Y-m-d');
   +         $debug['date_source'] = 'calculated_via_emploi_temps';
   +     } else {
   +         $dateSeance = now()->format('Y-m-d');
   +         $debug['date_source'] = 'fallback_now';
   +     }
   + }
   ```

2. **Méthode `create()` - Enrichissement séances (lignes 433-441)**

   ```diff
   $seances->each(function($seance) {
       $seance->jour_nom = $seance->getNomJour();
   -    $seance->date_calculee = $seance->getDateSeance() ? $seance->getDateSeance()->format('Y-m-d') : null;
   +    // Utiliser date_seance de la base si disponible, sinon calculer
   +    if (!empty($seance->date_seance)) {
   +        $seance->date_calculee = \Carbon\Carbon::parse($seance->date_seance)->format('Y-m-d');
   +    } else {
   +        $seance->date_calculee = $seance->getDateSeance() ? $seance->getDateSeance()->format('Y-m-d') : null;
   +    }
   });
   ```

3. **Méthode `loadSeances()` AJAX** - Badge détection (lignes 616-624)

   ```diff
   foreach ($seances as $seance) {
       $seance->jour_nom = $seance->getNomJour();
   -    $seance->date_calculee = $seance->getDateSeance() ? $seance->getDateSeance()->format('Y-m-d') : null;
   +    if (!empty($seance->date_seance)) {
   +        $seance->date_calculee = \Carbon\Carbon::parse($seance->date_seance)->format('Y-m-d');
   +    } else {
   +        $seance->date_calculee = $seance->getDateSeance() ? $seance->getDateSeance()->format('Y-m-d') : null;
   +    }
   ```

   Et pour la détection de badge (lignes 620-644):

   ```diff
   - // Utiliser la date calculée ou aujourd'hui comme fallback
   - $dateRecherche = $seance->date_calculee ?: now()->format('Y-m-d');

   + // Utiliser la date stockée en base (date_seance) ou la date calculée comme fallback
   + $dateRecherche = !empty($seance->date_seance)
   +     ? \Carbon\Carbon::parse($seance->date_seance)->format('Y-m-d')
   +     : ($seance->date_calculee ?: now()->format('Y-m-d'));
   ```

4. **Méthode `loadStudents()` AJAX** - Date pour chargement attendances (lignes 729-739)

   ```diff
   - // Calculer la date de la séance
   - $dateCalculee = $seance->getDateSeance();
   - $dateSeance = $dateCalculee ? $dateCalculee->format('Y-m-d') : now()->format('Y-m-d');

   + // Utiliser la date de la séance stockée en base (date_seance)
   + // au lieu de calculer via getDateSeance() qui peut donner une date incorrecte
   + if (!empty($seance->date_seance)) {
   +     $dateSeance = \Carbon\Carbon::parse($seance->date_seance)->format('Y-m-d');
   +     \Log::info('📅 [AJAX] Date from database', ['date_seance' => $dateSeance]);
   + } else {
   +     $dateCalculee = $seance->getDateSeance();
   +     $dateSeance = $dateCalculee ? $dateCalculee->format('Y-m-d') : now()->format('Y-m-d');
   +     \Log::info('📅 [AJAX] Date calculated', ['date_seance' => $dateSeance, 'calculated' => (bool)$dateCalculee]);
   + }
   ```

**Logique de priorité**

```
1. Si date_seance existe en BDD → utiliser cette valeur (source fiable)
2. Sinon calculer via getDateSeance() → basé sur emploi_temps.date_debut + jour
3. Sinon fallback now() → dernier recours (séances sans emploi temps)
```

**Impact**

- ✅ **Date affichée correcte** : Séance du 17/10 affiche "17/10/2025" au lieu de "18/10/2025"
- ✅ **Badge précis** : Détecte correctement les attendances existantes (cherche au 17/10, pas au 18/10)
- ✅ **Mode correct** : Affiche "Modification" quand attendances existent, "Nouveau marquage" sinon
- ✅ **Chargement AJAX** : Remplit la liste d'étudiants avec les bonnes attendances existantes

**Logs de débogage**

```
📅 [AJAX] Date from database | date_seance: 2025-10-17
✅ [BADGE] Séance 12 (Chimie) a 4 attendances pour 2025-10-17
```

**Règle technique**

> **Pour afficher/utiliser la date d'une séance** :
> 1. Toujours vérifier si `$seance->date_seance` existe (champ BDD)
> 2. Utiliser `date_seance` en priorité (date réelle planifiée)
> 3. `getDateSeance()` sert uniquement pour CALCULER des dates futures lors de la création d'emplois du temps

**Fichiers modifiés** : 1 fichier, 4 méthodes corrigées

- ✅ `ESBTPAttendanceController::create()` - 2 locations (date + enrichissement)
- ✅ `ESBTPAttendanceController::loadSeances()` - badge detection + enrichissement
- ✅ `ESBTPAttendanceController::loadStudents()` - chargement attendances

**Exemple concret**

```php
// AVANT (incorrect)
$dateSeance = $seance->getDateSeance()->format('Y-m-d'); // → "2025-10-18" (samedi)
// Badge cherche attendances au 18/10 → aucune trouvée → "Non marquée"

// APRÈS (correct)
$dateSeance = \Carbon\Carbon::parse($seance->date_seance)->format('Y-m-d'); // → "2025-10-17" (vendredi)
// Badge cherche attendances au 17/10 → 4 trouvées → "Présence marquée"
```

### Fix KPI cards affichant zéro - Variable écrasée par foreach (18 octobre 2025)

**Problème** : Les KPI cards de la page `attendances.index` affichaient **toutes les valeurs à zéro** (Présences: 0, Absences: 0, Retards: 0, Excusés: 0), alors que le graphique "Tendance des 7 Derniers Jours" affichait les bonnes valeurs (Présences: 4, Absences: 0, Retards: 2).

**Symptômes** :
1. Console navigateur : `$stats = {present: 0, absent: 0, retard: 0, excuse: 0}` (sans `total` ni `total_present_with_retards`)
2. KPI cards affichaient : 0 / 0 / 0 / 0
3. Graphique affichait correctement : 4 présences, 0 absences, 2 retards

**Cause** : Variable `$stats` écrasée par une boucle `foreach` utilisant le même nom de variable.

**Localisation** : `app/Http/Controllers/ESBTPAttendanceController.php`

**Code problématique** (ligne 201) :

```php
// Lignes 122-133 : Calcul correct des stats
$stats = [
    'present' => (clone $statsQuery)->where('statut', 'present')->count(),
    'absent' => (clone $statsQuery)->where('statut', 'absent')->count(),
    'retard' => (clone $statsQuery)->whereIn('statut', ['retard', 'late'])->count(),
    'excuse' => (clone $statsQuery)->where('statut', 'excuse')->count()
];

$stats['total'] = $stats['present'] + $stats['absent'] + $stats['retard'] + $stats['excuse'];
$stats['total_present_with_retards'] = $stats['present'] + $stats['retard'];

// ... lignes 148-198 : calcul de $statsParStatus (stats par jour pour le graphique)

// Ligne 201 : BUG - la variable $stats est ÉCRASÉE par la boucle foreach !
foreach($statsParStatus as $jour => $stats) {  // ← $stats devient la variable de boucle
    $statsParStatus[$jour]['present_with_retards'] = $stats['present'] + $stats['retard'];
}

// Après cette boucle, $stats contient les valeurs du DERNIER jour du graphique
// au lieu des stats globales calculées aux lignes 122-136
```

**Explication technique** :

1. Lignes 122-136 : `$stats` est calculé correctement avec toutes les clés (`present`, `absent`, `retard`, `excuse`, `total`, `total_present_with_retards`)
2. Ligne 201 : La boucle `foreach($statsParStatus as $jour => $stats)` **réutilise** le nom `$stats` comme variable de boucle
3. PHP **écrase** la variable `$stats` à chaque itération avec les valeurs de `$statsParStatus[$jour]`
4. Après la boucle, `$stats` contient les stats du **dernier jour** (qui peuvent être à zéro si aucune attendance ce jour-là)
5. La vue reçoit donc un `$stats` incorrect, sans les clés `total` et `total_present_with_retards`

**Solution** (ligne 201) :

```diff
- foreach($statsParStatus as $jour => $stats) {
+ foreach($statsParStatus as $jour => $dailyStats) {
-     $statsParStatus[$jour]['present_with_retards'] = $stats['present'] + $stats['retard'];
+     $statsParStatus[$jour]['present_with_retards'] = $dailyStats['present'] + $dailyStats['retard'];
  }
```

**Impact** :

- ✅ **KPI cards** : Affichent maintenant les bonnes valeurs (4 présences, 0 absences, 2 retards)
- ✅ **Graphique** : Continue d'afficher les bonnes valeurs (non affecté car calculé avant la boucle)
- ✅ **Cohérence** : Les stats globales et les stats par jour sont maintenant indépendantes

**Logs de débogage ajoutés** :

```javascript
// Vue : resources/views/esbtp/attendances/index.blade.php (ligne 1271)
console.log('🔍 DEBUG KPI - Données $stats:', @json($stats ?? []));
console.log('🔍 DEBUG KPI - total_present_with_retards:', {{ $stats['total_present_with_retards'] ?? 'undefined' }});
```

**Résultat après fix** :

```
Console navigateur:
🔍 DEBUG KPI - Données $stats: {present: 2, absent: 0, retard: 2, excuse: 0, total: 4, total_present_with_retards: 4}
🔍 DEBUG KPI - total_present_with_retards: 4
🔍 DEBUG KPI - absent: 0
🔍 DEBUG KPI - retard: 2
🔍 DEBUG KPI - excuse: 0
🔍 DEBUG KPI - total: 4
```

**Leçon apprise** :

> ⚠️ **ATTENTION** : Ne jamais réutiliser le nom d'une variable importante comme variable de boucle `foreach`.
>
> ```php
> // ❌ MAUVAIS
> $stats = calculateStats();
> foreach($array as $key => $stats) { ... }  // écrase $stats !
>
> // ✅ BON
> $stats = calculateStats();
> foreach($array as $key => $item) { ... }  // préserve $stats
> ```

**Fichiers modifiés** :

1. ✅ `app/Http/Controllers/ESBTPAttendanceController.php` (L201-203) - Renommage `$stats` → `$dailyStats`
2. ✅ `resources/views/esbtp/attendances/index.blade.php` (L1271-1276) - Ajout console.log debug (à supprimer après validation)

**Commit** : `fix: KPI cards attendances.index affichaient zéro - variable écrasée par foreach`

---

### Fix filtrage attendances.index + rapport + rapportPdf (17 octobre 2025)

**Problème** : Les pages d'affichage des attendances (`index`, `rapport`, `rapportPdf`) ne filtraient pas correctement les étudiants pour ne montrer QUE ceux avec **inscriptions actives** dans leur **classe respective** pour l'année courante.

**Symptôme** :

1. **attendances.index** : Quand un filtre classe était appliqué, les attendances affichées incluaient potentiellement des étudiants avec inscriptions dans d'autres classes
2. **attendances.rapport** : Le rapport générait des statistiques pour TOUS les étudiants de la classe, même ceux sans inscription active
3. **attendances.rapportPdf** : Le PDF incluait des étudiants inactifs

**Cause** : Même bug que précédemment - manque du filtre `classe_id` ET `status='active'` dans les clauses `whereHas('inscriptions')`.

**Fichier** : `app/Http/Controllers/ESBTPAttendanceController.php`

**Corrections appliquées**

1. **Méthode `index()`** (ligne 76-80)

   **Problématique spécifique** : La requête affichait des attendances d'étudiants qui n'avaient PAS d'inscription active dans la classe mentionnée sur l'attendance. Il fallait filtrer SYSTÉMATIQUEMENT (pas conditionnellement) pour que chaque attendance corresponde à un étudiant inscrit activement dans SA classe.

   ```diff
   ->whereHas('etudiant.inscriptions', function($q) use ($anneeUniversitaire) {
       $q->where('annee_universitaire_id', $anneeUniversitaire->id)
   -      ->where('status', 'active');
   +      ->where('status', 'active')
   +      ->whereColumn('esbtp_inscriptions.classe_id', 'esbtp_attendances.classe_id'); // ← CRUCIAL
   });
   ```

   **Logique** : Utilisation de `whereColumn` pour comparer dynamiquement `classe_id` de l'inscription avec `classe_id` de l'attendance. Cela garantit que chaque ligne affichée correspond à un étudiant ayant une inscription active dans **LA classe de cette attendance spécifique**.

2. **Méthode `rapport()`** (ligne 996-1001)

   ```diff
   // Récupérer uniquement les étudiants inscrits pour l'année universitaire courante
   +// avec inscription ACTIVE et pour CETTE classe spécifiquement
   $etudiants = $classe->etudiants()
   -    ->whereHas('inscriptions', function($q) use ($anneeUniversitaire) {
   +    ->whereHas('inscriptions', function($q) use ($anneeUniversitaire, $classe) {
           $q->where('annee_universitaire_id', $anneeUniversitaire->id)
   +         ->where('status', 'active')
   +         ->where('classe_id', $classe->id);
   -     });
   +    })
       ->get();
   ```

3. **Méthode `rapportPdf()`** (ligne 1079-1084)

   **Identique au fix de `rapport()`** - même changement appliqué.

**Impact**

- ✅ **attendances.index** : Filtre classe applique maintenant le filtrage complet (inscriptions actives DANS cette classe)
- ✅ **attendances.rapport** : Rapport ne montre QUE les étudiants avec inscription active dans la classe sélectionnée
- ✅ **attendances.rapportPdf** : PDF exclut les étudiants inactifs ou inscrits ailleurs
- ✅ **Cohérence** : Alignement total avec `classes.show`, `attendances.create`, et `teacher.roll-call`

**Règle métier confirmée**

> **PARTOUT dans le système attendance, filtrage étudiants identique** :
> ```php
> $classe->etudiants()
>     ->whereHas('inscriptions', function($q) use ($anneeUniversitaire, $classe) {
>         $q->where('annee_universitaire_id', $anneeUniversitaire->id)
>           ->where('status', 'active')
>           ->where('classe_id', $classe->id);
>     });
> ```

**Cas d'usage**

| Méthode | Classe connue ? | Pattern filtrage | But |
|---------|----------------|------------------|-----|
| `index()` | ❌ Non (dans attendance) | `whereColumn()` dynamique | Afficher TOUTES les attendances avec inscriptions valides |
| `rapport()` | ✅ Oui (parameter) | `where('classe_id', $classe->id)` statique | Rapport pour UNE classe spécifique |
| `rapportPdf()` | ✅ Oui (parameter) | `where('classe_id', $classe->id)` statique | PDF pour UNE classe spécifique |
| `create()` / `loadStudents()` | ✅ Oui (parameter) | `where('classe_id', $classe->id)` statique | Marquage présences d'une classe |

**Fichiers modifiés au total** (7 locations corrigées)

1. ✅ `ESBTPAttendanceController::loadStudents()` (L702-707) - Fix précédent
2. ✅ `ESBTPAttendanceController::create()` - loc 1 (L476-481) - Fix précédent
3. ✅ `ESBTPAttendanceController::create()` - loc 2 (L543-547) - Fix précédent
4. ✅ `TeacherDashboardController::showRollCall()` (L196-199) - Fix précédent
5. ✅ `ESBTPAttendanceController::index()` (L75-82) ← **Ce fix**
6. ✅ `ESBTPAttendanceController::rapport()` (L996-1001) ← **Ce fix**
7. ✅ `ESBTPAttendanceController::rapportPdf()` (L1079-1084) ← **Ce fix**

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

## 🌐 API LMS-KLASSCI Integration (19 octobre 2025)

### Vue d'ensemble

API REST permettant l'intégration d'un Learning Management System (LMS) externe avec KLASSCI via Laravel Sanctum.

**Endpoints base** : `http://domain/api/lms`

**Authentification** : Bearer Token (Laravel Sanctum)

### Corrections majeures appliquées

**Problèmes initiaux** :
1. ❌ Utilisation table obsolète `esbtp_classe_matiere` pour relations matières-classes
2. ❌ Pas de validation inscription active pour connexion étudiants
3. ❌ Filtrage incomplet des étudiants (manque `status='active'`)
4. ❌ Logique incorrecte pour récupérer matières disponibles par classe

**Solutions implémentées** :

#### 1. AuthController - Validation inscription étudiante (lignes 125-138)

```php
// Blocage connexion étudiants sans inscription active
if ($user->hasRole('etudiant')) {
    $etudiantData = $this->getEtudiantData($user);

    if (empty($etudiantData)) {
        Auth::logout();
        return $this->errorResponse(
            'Vous n\'êtes pas encore réinscrit pour l\'année universitaire en cours...',
            ['code' => 'NO_ACTIVE_ENROLLMENT'],
            403
        );
    }
}
```

**Impact** :
- ✅ Étudiants DOIVENT avoir `status='active'` dans année `is_current=true`
- ✅ Message clair avec code erreur `NO_ACTIVE_ENROLLMENT`
- ✅ HTTP 403 au lieu de 200 avec données vides

#### 2. LMSDataController::matieres() - Combinaisons globales (lignes 52-138)

```php
// Utilisation pivot tables globales (NOT obsolete esbtp_classe_matiere)
$query = ESBTPMatiere::with([
    'filieres',  // BelongsToMany via esbtp_matiere_filiere
    'niveaux',   // BelongsToMany via esbtp_matiere_niveau
    'enseignants' => function ($q) use ($annee) {
        $q->where('esbtp_enseignant_matiere.annee_universitaire_id', $annee->id)
          ->where('esbtp_enseignant_matiere.is_active', true);
    }
])->where('esbtp_matieres.is_active', true);

// Calculer TOUTES les combinaisons (filiere_id, niveau_id)
$combinaisons = [];
foreach ($matiere->filieres as $filiere) {
    foreach ($matiere->niveaux as $niveau) {
        $combinaisons[] = [
            'filiere_id' => $filiere->id,
            'filiere_nom' => $filiere->name,
            'niveau_id' => $niveau->id,
            'niveau_nom' => $niveau->name,
        ];
    }
}
```

**Impact** :
- ✅ Chaque matière retourne tableau `combinaisons` avec paires `(filiere_id, niveau_id)`
- ✅ LMS peut filtrer matières pour classe via combinaison
- ✅ Plus besoin de `esbtp_classe_matiere` (obsolète)

#### 3. LMSDataController::classes() - Matières via combinaisons (lignes 207-220)

```php
// Récupérer matières via combinaisons globales (NOT obsolete table)
$matieres = ESBTPMatiere::where('is_active', true)
    ->whereHas('filieres', function ($q) use ($classe) {
        $q->where('esbtp_filieres.id', $classe->filiere_id);
    })
    ->whereHas('niveaux', function ($q) use ($classe) {
        $q->where('esbtp_niveau_etudes.id', $classe->niveau_etude_id);
    })
    ->get();
```

**Impact** :
- ✅ Matières disponibles = celles avec combinaison `(classe.filiere_id, classe.niveau_id)`
- ✅ Cohérence avec `ESBTPClasseController::show()` et `::matieres()`
- ✅ Coefficient identique pour toutes classes même filière+niveau

#### 4. LMSDataController::etudiantsClasse() - Filtrage complet (lignes 318-330)

```php
// Filtrage complet: année courante + status active + classe spécifique
$etudiants = ESBTPEtudiant::whereHas('inscriptions', function ($q) use ($classeId, $annee) {
    $q->where('classe_id', $classeId)
      ->where('annee_universitaire_id', $annee->id)
      ->where('status', 'active');  // ← AJOUTÉ
})->with(['user', 'inscriptions' => function ($q) use ($annee, $classeId) {
    $q->where('annee_universitaire_id', $annee->id)
      ->where('classe_id', $classeId)
      ->where('status', 'active');  // ← AJOUTÉ
}])->get();
```

**Impact** :
- ✅ Seuls étudiants avec inscription **active** année **courante**
- ✅ Cohérence avec `ESBTPClasseController::show()`
- ✅ Évite étudiants transférés/suspendus

### Règles métier clés

#### Relations matières-classes

```php
// ✅ CORRECT - Pivot tables globales
'filieres' => BelongsToMany (esbtp_matiere_filiere)
'niveaux'  => BelongsToMany (esbtp_matiere_niveau)

// ❌ OBSOLÈTE - Ne JAMAIS utiliser
'classes' => BelongsToMany (esbtp_classe_matiere)
```

#### Filtrage étudiants - Pattern obligatoire

```php
$classe->etudiants()
    ->whereHas('inscriptions', function($q) use ($anneeUniversitaire, $classe) {
        $q->where('annee_universitaire_id', $anneeUniversitaire->id)
          ->where('status', 'active')
          ->where('classe_id', $classe->id); // ← TOUJOURS !
    });
```

### Endpoints principaux

#### POST `/api/lms/auth/login`

**Paramètres** :
```json
{
  "username": "email@example.com",
  "password": "mot_de_passe",
  "remember": false
}
```

**Réponse succès (200)** :
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "token": "1|abc123...",
    "token_type": "Bearer",
    "user": {
      "id": 42,
      "role": "etudiant",
      "etudiant_data": {
        "matricule": "ESB2024001",
        "classe": { "id": 15, "nom": "L3 GC - 2024/2025" },
        "statut_inscription": "active"
      }
    }
  }
}
```

**Erreur - Pas d'inscription (403)** :
```json
{
  "success": false,
  "message": "Vous n'êtes pas encore réinscrit...",
  "errors": { "code": "NO_ACTIVE_ENROLLMENT" }
}
```

#### GET `/api/lms/matieres`

**Headers** : `Authorization: Bearer {token}`

**Réponse** :
```json
{
  "success": true,
  "data": [
    {
      "id": 42,
      "nom": "Mathématiques",
      "code": "MATH101",
      "coefficient": 3.0,
      "combinaisons": [
        {
          "filiere_id": 1,
          "filiere_nom": "Génie Civil",
          "niveau_id": 3,
          "niveau_nom": "Licence 3"
        }
      ],
      "enseignants": [{ "id": 5, "nom": "Prof. KOUASSI" }]
    }
  ]
}
```

#### GET `/api/lms/classes`

**Filtres optionnels** : `?filiere_id=1&niveau_id=3&search=GC`

**Réponse** :
```json
{
  "success": true,
  "data": [
    {
      "id": 15,
      "nom": "L3 GC - 2024/2025",
      "filiere": { "id": 1, "nom": "Génie Civil" },
      "niveau": { "id": 3, "nom": "Licence 3" },
      "matieres_disponibles": [
        {
          "id": 42,
          "nom": "Mathématiques",
          "coefficient": 3.0
        }
      ],
      "nb_etudiants": 25,
      "nb_matieres": 8
    }
  ]
}
```

#### GET `/api/lms/classes/{classeId}/etudiants`

**Réponse** :
```json
{
  "success": true,
  "data": [
    {
      "id": 15,
      "matricule": "ESB2024001",
      "nom_complet": "Jean DUPONT",
      "user": { "email": "jean@example.com" },
      "inscription": {
        "status": "active",
        "date_inscription": "2024-09-01"
      }
    }
  ]
}
```

### Sécurité

**Authentification** :
- Type : Bearer Token (Laravel Sanctum)
- Header : `Authorization: Bearer {token}`
- Scope : `lms:access`

**Roles autorisés** :
- ✅ `enseignant` - Accès classes enseignées
- ✅ `coordinateur` - Accès complet
- ✅ `superAdmin` - Accès complet
- ✅ `etudiant` - Données personnelles uniquement

**Restrictions** :
- ❌ `secretaire` n'a PAS accès au LMS
- ❌ Étudiants sans inscription active bloqués
- ❌ Utilisateurs inactifs bloqués

### Tables clés

| Table | Type | Status | Usage |
|-------|------|--------|-------|
| `esbtp_matiere_filiere` | Pivot | ✅ Actuelle | Matières par filière |
| `esbtp_matiere_niveau` | Pivot | ✅ Actuelle | Matières par niveau |
| `esbtp_classe_matiere` | Pivot | ❌ OBSOLÈTE | Ne plus utiliser |
| `esbtp_config_matieres` | Config | ✅ Actuelle | Bulletins seulement |
| `esbtp_inscriptions` | Data | ✅ Actuelle | Inscriptions étudiants |

### Fichiers modifiés

1. ✅ `app/Http/Controllers/API/AuthController.php` (L125-138)
2. ✅ `app/Http/Controllers/API/LMSDataController.php` :
   - `matieres()` (L52-138) - Pivot tables globales
   - `classes()` (L207-220) - Combinaisons globales
   - `etudiantsClasse()` (L318-330) - Filtrage complet

### Documentation complète

Voir : [API_LMS_CORRECTIONS_SUMMARY.md](API_LMS_CORRECTIONS_SUMMARY.md)

---

## 📝 Pattern AJAX - Chargement Matières par Classe (19 octobre 2025)

### Vue d'ensemble

Implémentation du pattern AJAX pour le chargement dynamique des matières disponibles lors de la création d'évaluations, identique au pattern utilisé dans `attendances.create` pour cohérence UX.

**Page concernée** : `/esbtp/evaluations/create` - Création d'une nouvelle évaluation

**Workflow** : Sélection classe → AJAX charge matières → Sélection matière

### Problème initial

**Avant** : Système statique avec toutes les matières chargées via JSON côté client
- ❌ Affichait TOUTES les matières indépendamment de la classe sélectionnée
- ❌ Pas de filtrage par combinaisons globales (filière + niveau)
- ❌ Incohérent avec le reste du système (classes.show, API LMS)

### Solution implémentée

**Après** : Chargement AJAX des matières via combinaisons globales
- ✅ Charge UNIQUEMENT les matières disponibles pour la classe sélectionnée
- ✅ Utilise les pivot tables `esbtp_matiere_filiere` + `esbtp_matiere_niveau`
- ✅ Pattern identique à `attendances.create` (cohérence UX)
- ✅ Cohérence avec logique API LMS et `classes.matieres`

### Architecture technique

#### 1. Route AJAX

**Fichier** : `routes/web.php` (ligne 1223)

```php
// AJAX: Charger matières disponibles pour une classe (via combinaisons globales)
Route::get('/load-matieres', [ESBTPEvaluationController::class, 'loadMatieres'])
    ->name('esbtp.evaluations.load-matieres');
```

**Middleware** : `auth` (hérité du groupe parent)

#### 2. Méthode Controller

**Fichier** : `app/Http/Controllers/ESBTPEvaluationController.php` (lignes 1088-1157)

```php
public function loadMatieres(Request $request)
{
    $classeId = $request->input('classe_id');
    $classe = ESBTPClasse::findOrFail($classeId);

    // Récupérer matières via combinaisons globales (filière + niveau)
    $matieres = ESBTPMatiere::where('is_active', true)
        ->whereHas('filieres', function ($q) use ($classe) {
            $q->where('esbtp_filieres.id', $classe->filiere_id);
        })
        ->whereHas('niveaux', function ($q) use ($classe) {
            $q->where('esbtp_niveau_etudes.id', $classe->niveau_etude_id);
        })
        ->orderBy('nom')
        ->get();

    // Générer options HTML pour select
    $options = '<option value="">-- Sélectionner une matière --</option>';
    foreach ($matieres as $matiere) {
        $matiereNom = $matiere->nom ?? $matiere->name;
        $matiereCode = $matiere->code ? ' (' . $matiere->code . ')' : '';
        $options .= '<option value="' . $matiere->id . '">' . $matiereNom . $matiereCode . '</option>';
    }

    return response()->json([
        'success' => true,
        'options' => $options,
        'count' => $matieres->count(),
        'classe' => [
            'id' => $classe->id,
            'nom' => $classe->name,
            'filiere' => $classe->filiere->name ?? 'N/A',
            'niveau' => $classe->niveau->name ?? 'N/A'
        ]
    ]);
}
```

**Logique métier** :
- Même requête que API LMS (`LMSDataController::classes()`)
- Même requête que `classes.matieres` (page gestion matières)
- **AUCUNE utilisation** de `esbtp_classe_matiere` (obsolète)

#### 3. JavaScript Frontend

**Fichier** : `resources/views/esbtp/evaluations/create.blade.php` (lignes 517-621)

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const classeSelect = document.getElementById('classe_id');
    const matiereSelect = document.getElementById('matiere_id');

    if (classeSelect && matiereSelect) {
        classeSelect.addEventListener('change', function(e) {
            e.preventDefault();
            const classeId = this.value;

            // Reset matière select
            matiereSelect.innerHTML = '<option value="">-- Sélectionner une matière --</option>';
            matiereSelect.disabled = true;

            if (classeId) {
                loadMatieres(classeId);
            }

            return false;
        });
    }

    function loadMatieres(classeId) {
        // Afficher spinner sur label
        const label = document.querySelector('label[for="matiere_id"]');
        const spinner = document.createElement('span');
        spinner.className = 'loading-spinner';
        spinner.innerHTML = ' <i class="fas fa-spinner fa-spin text-primary"></i>';
        label.appendChild(spinner);

        const url = '{{ route("esbtp.evaluations.load-matieres") }}?classe_id=' + classeId;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            spinner.remove();

            if (data.success) {
                // Mettre à jour select avec options HTML
                matiereSelect.innerHTML = data.options;
                matiereSelect.disabled = false;

                // Alert si aucune matière
                if (data.count === 0) {
                    matiereSelect.innerHTML = '<option value="">Aucune matière disponible</option>';
                    alert('Attention: Aucune matière pour ' + data.classe.filiere +
                          ' / ' + data.classe.niveau);
                }
            } else {
                alert('Erreur: ' + data.message);
                matiereSelect.disabled = false;
            }
        })
        .catch(error => {
            spinner.remove();
            alert('Erreur AJAX: ' + error.message);
            matiereSelect.disabled = false;
        });
    }
});
```

**Caractéristiques** :
- Pattern identique à `attendances.create` (loadSeances → loadStudents)
- Spinner visuel pendant chargement
- Désactivation du select matière pendant AJAX
- Alert utilisateur si aucune matière disponible
- Gestion erreurs complète

### Règle métier

> **Matières disponibles pour une classe** = Matières avec combinaison `(classe.filiere_id, classe.niveau_id)` dans les pivot tables globales

**Tables utilisées** :
- ✅ `esbtp_matiere_filiere` - Pivot filière → matière
- ✅ `esbtp_matiere_niveau` - Pivot niveau → matière
- ❌ `esbtp_classe_matiere` - **OBSOLÈTE, ne jamais utiliser**

**Exemple** :
- Classe : L3 Génie Civil (`filiere_id=1`, `niveau_id=3`)
- Matières retournées : TOUTES les matières ayant `filiere_id=1` ET `niveau_id=3` dans les pivots

### Logs de débogage

```
📚 [AJAX] loadMatieres - Début | classe_id: 15 | user_id: 1
✅ [AJAX] loadMatieres - Matières trouvées | classe: L3 GC | filiere_id: 1 | niveau_id: 3 | nb_matieres: 8
```

### Comportement UX

1. **Page charge** : Select matière désactivé, affiche "-- Sélectionner une matière --"
2. **Utilisateur sélectionne classe** :
   - Événement `change` déclenché
   - Select matière reset + désactivé
   - Spinner apparaît sur le label
   - Requête AJAX envoyée
3. **Réponse AJAX reçue** :
   - Spinner disparaît
   - Select matière rempli avec options
   - Select matière réactivé
4. **Si aucune matière** :
   - Alert utilisateur avec message explicatif
   - Suggestion d'ajouter des matières via "Matières de classe"

### Cohérence système

**Pattern appliqué dans** :
- ✅ `attendances.create` - Classe → Séances → Étudiants (AJAX double)
- ✅ `evaluations.create` - Classe → Matières (AJAX simple)

**Logique matières identique à** :
- ✅ API LMS (`LMSDataController::classes()`)
- ✅ Page classes/matieres (`ESBTPClasseController::matieres()`)
- ✅ Édition résultats classe (`ESBTPBulletinController::classeEdit()`)

### Impact

- ✅ **UX cohérente** : Pattern AJAX identique partout dans l'application
- ✅ **Performance** : Charge uniquement les matières nécessaires (pas tout le catalogue)
- ✅ **Précision** : Affiche EXACTEMENT les matières disponibles pour la combinaison
- ✅ **Maintenance** : Logique centralisée dans controller, pas JS statique

### Problème 404 et solution (19 octobre 2025)

**Problème initial** : Route retournait HTTP 404

**Cause** : La route `/load-matieres` était placée APRÈS les routes avec paramètre `/{evaluation}`, donc Laravel matchait "load-matieres" comme une valeur du paramètre `{evaluation}` au lieu de la route spécifique.

```php
// ❌ INCORRECT - 404 Error
Route::get('/{evaluation}', ...)->name('show');           // Ligne 1213
Route::get('/load-matieres', ...)->name('load-matieres'); // Ligne 1223
// Laravel matche "load-matieres" avec {evaluation} !
```

**Solution** : Déplacer la route `/load-matieres` AVANT toutes les routes avec paramètres wildcards

```php
// ✅ CORRECT - Fonctionne
Route::get('/load-matieres', ...)->name('load-matieres'); // Ligne 1213
Route::get('/{evaluation}', ...)->name('show');           // Ligne 1218
// Laravel matche d'abord la route spécifique
```

**Règle Laravel** :
> Les routes spécifiques DOIVENT être déclarées AVANT les routes avec paramètres wildcards `{param}` pour éviter les conflits de matching.

### Fichiers modifiés

1. ✅ `routes/web.php` (L1213) - Route AJAX **repositionnée AVANT /{evaluation}**
2. ✅ `app/Http/Controllers/ESBTPEvaluationController.php` (L1088-1157) - Méthode `loadMatieres()`
3. ✅ `resources/views/esbtp/evaluations/create.blade.php` (L517-621) - Pattern AJAX
4. ✅ Suppression ligne 31 - JSON statique `data-matieres` (plus nécessaire)

### Exemple requête/réponse

**Requête** :
```http
GET /esbtp/evaluations/load-matieres?classe_id=15
X-Requested-With: XMLHttpRequest
Accept: application/json
```

**Réponse succès** :
```json
{
  "success": true,
  "options": "<option value=\"\">-- Sélectionner une matière --</option><option value=\"42\">Mathématiques (MATH101)</option><option value=\"43\">Physique (PHY102)</option>",
  "count": 2,
  "classe": {
    "id": 15,
    "nom": "L3 GC - 2024/2025",
    "filiere": "Génie Civil",
    "niveau": "Licence 3"
  }
}
```

**Réponse erreur** :
```json
{
  "success": false,
  "message": "Erreur lors du chargement des matières: Classe introuvable"
}
```

---

*Dernière mise à jour: 19 octobre 2025*
