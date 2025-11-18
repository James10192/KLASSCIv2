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

---

## 🚀 Développements Octobre 2025

### 🔒 Refactoring Sécurité & Performance (20 octobre)

**Corrections appliquées** :

1. **Protection Mass Assignment** : `$request->all()` → `$request->validated()`
   - Controllers corrigés : ESBTPExamenController, ESBTPSecretaireController, ESBTPReinscriptionController

2. **Audit Event 'retrieved'** : Désactivé pour performance
   - Modèles : ESBTPPaiement, ESBTPFacture, ESBTPDepense
   - Impact : Pages 10x plus rapides

3. **Controllers volumineux** (limite recommandée: 500 lignes) :
   - ESBTPBulletinController : 6852 lignes (13.7x) - À refactorer
   - ESBTPComptabiliteController : 4150 lignes (8.3x)
   - ESBTPInscriptionController : 3275 lignes (6.5x)
   - ESBTPPaiementController : 3024 lignes (6.0x)

**Référence** : [AUDIT_SECURITE_PERFORMANCE.md](AUDIT_SECURITE_PERFORMANCE.md)

---

### 🤖 Chatbot IA Gemini (21 octobre)

**Principe** : Chatbot intelligent avec **exploration autonome** du code source pour apprendre à récupérer les données.

#### Architecture (6 tables BDD)

1. `chatbot_conversations` - Sessions utilisateur
2. `chatbot_messages` - Historique messages
3. `chatbot_actions_log` - Audit trail CRUD
4. `chatbot_system_prompts` - Pre-prompts configurables par rôle
5. `chatbot_display_templates` - Templates HTML affichage
6. `chatbot_knowledge_base` ⭐ **CŒUR DU SYSTÈME**

#### Fonctionnement

**Workflow d'exploration** :
```
User: "Montre-moi les paiements en attente"
  ↓
1. extractKeywordFromIntent('get_paiements') → 'paiements'
2. findRouteInSidebar('paiements') → 'esbtp.paiements.index'
3. findControllerFromRoute() → 'ESBTPPaiementController@index'
4. analyzeController() → Model, filtres, vue
5. analyzeModel() → Table, fillable, casts
6. extractPermissionsForRoute() → @can('view_paiements')
7. buildDeepLinkPattern() → '/esbtp/paiements?status={status}'
8. saveKnowledge() → Cache dans chatbot_knowledge_base
9. Prochaine fois : getKnowledge() → 10x plus rapide
```

#### Technologie

- **API** : Google Gemini 2.0 Flash
- **Gratuit** : 1500 requêtes/jour, 1M tokens/mois
- **Coût après limite** : ~750 FCFA/mois (~$1.13)

#### Intents Implémentés (2/5)

| Intent | État | Fonctionnalités |
|--------|------|-----------------|
| `get_inscriptions` | ✅ Validé | Filtres : classe, filière, niveau, status, without_paiements, année |
| `get_frais` | ✅ Validé | Filtres : categorie_frais, type_affectation, filière, niveau |
| `get_paiements` | 🟡 Exploré | Deep link prêt, à tester |
| `get_etudiants` | ❌ TODO | - |
| `get_classes` | ❌ TODO | - |

#### Améliorations (21 octobre)

**Prompt LLM Few-Shot Learning** :
- Clés standardisées : `categorie_frais`, `type_affectation`, `filiere`, `niveau`, `status`
- 4 exemples concrets dans le system instruction
- Stabilité : filtres consistants entre conversations

**Support Relations Manquantes** :
- Filtre `without_paiements: true` → `whereDoesntHave('paiements')`
- Extensible : `without_inscriptions`, `without_notes`, `without_attendances`

**Fixes Deep Link** :
- WHERE clause classe : Grouper OR conditions avec `where(function)`
- Placeholder replacement : Concat au lieu d'interpolation PHP
- Exclusion classes supprimées : `whereNull('deleted_at')`

**Fichiers** :
- `app/Services/Chatbot/ChatbotExplorerService.php` (366 lignes)
- `app/Services/Chatbot/ChatbotService.php` (584 lignes)
- `app/Http/Controllers/ChatbotController.php` (4 endpoints)
- `database/migrations/2025_10_21_034757_create_chatbot_tables.php`

---

### 📊 Dashboard Super Admin (25 octobre)

**Page** : `/dashboard/superadmin`

**Graphique "Évolution des inscriptions et paiements"** :

**3 Courbes** :
1. **Bleue - Inscriptions créées** : `created_at`
2. **Verte - Inscriptions validées** : `date_validation` (décalée dans le temps)
3. **Orange - Inscriptions en attente de paiement** : STOCK CUMULATIF

**Logique courbe orange** :
```php
// STOCK CUMULATIF : toutes les inscriptions créées AVANT fin du mois
$pendingPaymentsCount = ESBTPInscription::where('created_at', '<=', $endOfMonth)
    ->where(function($query) {
        // Cas 1: Aucun paiement existe
        $query->whereDoesntHave('paiements')
            // Cas 2: A des paiements mais tous en attente
            ->orWhereHas('paiements', function($q) {
                $q->where('status', 'en_attente');
            })
            ->whereDoesntHave('paiements', function($q) {
                $q->whereIn('status', ['validé', 'payé']);
            });
    })
    ->count();
```

**Avantage STOCK vs FLUX** :
- Montre le **travail restant** (combien d'inscriptions à relancer)
- Détecte si situation empire (courbe monte = accumulation)
- Disparaît quand paiement validé

---

### 👥 Assignation Professeurs - Planning Général (25 octobre)

**Problème** : Deux pages d'assignation affichaient TOUS les enseignants au lieu de filtrer selon planning général.

**Pages corrigées** :
1. `/esbtp/resultats/classe/{id}/edit` → Modal "Assigner Professeurs"
2. `/esbtp-special/bulletins/edit-professeurs` → Page bulletin individuel

**Solution** :
```php
// Récupérer planifications pour filière + niveau de cette classe
$planifications = DB::table('esbtp_planifications_academiques')
    ->where('filiere_id', $classe->filiere_id)
    ->where('niveau_etude_id', $classe->niveau_etude_id)
    ->where('annee_universitaire_id', $annee_universitaire_id)
    ->pluck('id');

// Récupérer enseignants assignés dans ces planifications
$enseignantIds = DB::table('esbtp_planification_teachers')
    ->whereIn('planification_id', $planifications)
    ->pluck('teacher_id');

// Charger enseignants complets
$enseignants = ESBTPTeacherProfile::whereIn('id', $enseignantIds)
    ->actif()
    ->get();
```

**Cohérence** : Planning général = source de vérité pour assignation professeurs

---

### 🐛 Fix Critique: Professeurs Non Persistants (25 octobre)

**Problème** : 3 bugs majeurs empêchaient la sauvegarde des professeurs dans les bulletins.

#### Bug #1: Modal Sans Pré-Sélection

**Cause** : Controller ne passait pas `$professeursGroupes` à la vue

**Solution** : Mapping teacher name → teacher ID
```php
// Récupérer bulletin exemple
$sampleBulletin = ESBTPBulletin::where('classe_id', $classe_id)
    ->where('periode', $periode)
    ->whereNotNull('professeurs')
    ->first();

// Mapper JSON {"matiere_id": "Teacher Name"} → {"matiere_id": teacher_id}
foreach (json_decode($sampleBulletin->professeurs, true) as $matiereId => $teacherName) {
    $foundTeacher = $enseignantsDeMatiere->first(fn($e) => $e->user->name === $teacherName);
    if ($foundTeacher) {
        $professeursGroupes[$matiereId] = $foundTeacher->id;
    }
}
```

#### Bug #2: Corruption JSON par array_merge()

**Problème** : `array_merge()` convertit tableaux associatifs avec clés numériques en tableaux indexés

**Solution** : `array_replace()` préserve les clés
```php
// ❌ AVANT
$professeursFusionnes = array_merge($professeursExistants, $professeursMap);
// Résultat: [0 => "BAMBA Marie", 1 => null, ...]

// ✅ APRÈS
$professeursFusionnes = array_replace($professeursExistants, $professeursMap);
// Résultat: ["2" => "BAMBA Marie", "21" => "KOUASSI Jean"]
```

#### Bug #3: Eloquent Cache Empêchant Writes BDD (CRITIQUE)

**Symptôme** :
- ✅ Logs : "38 bulletins updated"
- ❌ BDD : `updated_at` inchangé
- ❌ `$bulletin->update()` retournait `true` sans écrire

**Cause** : Dirty checking Eloquent pensait qu'il n'y avait rien à faire

**Solution DÉFINITIVE** : Bypass Eloquent avec Query Builder
```php
// ❌ AVANT (ne fonctionnait pas)
$bulletin->update([
    'professeurs' => json_encode($professeursFusionnes),
    'updated_by' => auth()->id()
]);

// ✅ APRÈS (fonctionne)
DB::table('esbtp_bulletins')
    ->where('id', $bulletin->id)
    ->update([
        'professeurs' => $professeursJson,
        'updated_by' => auth()->id(),
        'updated_at' => now()
    ]);
```

**Leçon** : Query Builder garantit l'écriture en base, Eloquent peut skip si dirty checking échoue.

---

### 📊 Résultats Classe - Calcul Automatique Moyennes (25 octobre)

**Page** : `/esbtp/resultats/classe/{id}/edit`

**Fonctionnalité** : Calcul automatique des moyennes depuis les évaluations/notes existantes.

**Nouvelle méthode** : `calculateMoyennesForStudent()` (ESBTPBulletinController.php L6877-6961)

**Logique** :
```php
// Pour chaque matière
$moyenne = Σ(note/bareme × 20 × coefficient) / Σ(coefficient)

// Exemple : Math (coef 3)
// - Devoir 1 : 15/20 (bareme 20, coef 1)
// - Devoir 2 : 18/25 (bareme 25, coef 2)
// Moyenne = ((15/20 × 20 × 1) + (18/25 × 20 × 2)) / (1 + 2)
//         = (15 + 28.8) / 3 = 14.6/20
```

**Affichage** :
- Badge "Auto" (vert) : Moyenne calculée depuis évaluations
- Badge "Manuel" (orange) : Aucune évaluation, saisie manuelle requise
- Colonne "Moyenne calculée" : Affiche le calcul
- Input "Moyenne à enregistrer" : Pré-rempli avec moyenne calculée

---

### 🎓 API LMS - Endpoint Enseignants Enrichi (25 octobre)

**GET** `/api/lms/enseignants?with_details=true`

**Approche** : Opt-in pour éviter breaking changes

**Données retournées** :
```json
{
  "id": 1634,
  "teacher_id": 1,
  "nom": "KOUASSI Jean",
  "classes": [
    {
      "id": 15,
      "nom": "L3 GC - 2024/2025",
      "filiere": {"id": 1, "nom": "Génie Civil"},
      "niveau": {"id": 3, "nom": "Licence 3"}
    }
  ],
  "matieres": [
    {
      "id": 42,
      "nom": "Mathématiques",
      "heures_prevues": 40,
      "heures_effectuees": 28,
      "heures_restantes": 12,
      "taux_realisation": 70,
      "seances": [...]
    }
  ],
  "statistiques": {
    "total_classes": 3,
    "total_matieres": 5,
    "total_heures_prevues": 120,
    "total_heures_effectuees": 85,
    "taux_realisation_global": 70.83
  }
}
```

**Source des données** :
- **Classes** : Séances via `emploi_temps` (année courante)
- **Matières** : Double source
  1. Pivot `esbtp_enseignant_matiere`
  2. Planning général `esbtp_planifications_academiques`
  3. Fallback : Somme durées séances

**Calcul volume horaire** :
```php
// Heures effectuées = attendances present/late × durée moyenne
$attendancesCount = ESBTPTeacherAttendance::where('teacher_id', $teacher->id)
    ->whereIn('course_id', $seanceIds)
    ->whereIn('status', ['present', 'late'])
    ->where('type', 'start')
    ->count();

$heuresEffectuees = $attendancesCount * $dureeMoyenne;
```

**Performance** :
- Format simple : ~14ms
- Format enrichi : ~30ms

---

### 📊 Export Classes - Excel, CSV, PDF (30 octobre)

**Page** : `/esbtp/classes`

**Formats** : Excel (.xlsx), CSV, PDF

**Colonnes Excel/CSV** (10) :
- N°, Nom classe, Code, Filière, Niveau, Effectif actuel, Capacité, Places restantes, Taux remplissage (%), Statut

**Colonnes PDF** (8 - compact) :
- N°, Nom, Filière, Niveau, Effectif, Capacité, Places, Statut

**Statistiques incluses** :
- Total classes
- Effectif total
- Capacité totale
- Taux moyen remplissage

**Filtres supportés** :
- `filiere_id`, `niveau_id`, `statut`, `capacite`, `search`
- Export respecte TOUS les filtres actifs
- Export TOUTES les classes (pas de pagination)

**Interface** :
- Dropdown "Exporter" (dropup pour éviter conflit avec KPI)
- 3 options avec icônes : Excel (vert), CSV (bleu), PDF (rouge)

**Pattern** : Identique à `paiements.index` pour cohérence

---

### 🔄 Emploi du Temps - Filtrage AJAX sans Page Reload (30 octobre)

**Page** : `/esbtp/emploi-temps`

**Problèmes résolus** :

1. ✅ **Ordre des routes** : Route statique `refresh` AVANT `Route::resource`
   ```php
   // Route AJAX - DOIT ÊTRE AVANT Route::resource
   Route::get('emploi-temps/refresh', [ESBTPEmploiTempsController::class, 'refresh'])
       ->name('emploi-temps.refresh');

   Route::resource('emploi-temps', ESBTPEmploiTempsController::class)
       ->parameters(['emploi-temps' => 'emploi_temp']);
   ```

2. ✅ **Blade directive** : `@push('scripts')` au lieu de `@section('scripts')`
   - Layout utilise `@stack('scripts')`

3. ✅ **Pattern AJAX** : Identique à `classes.index`
   - Select2 déclenche événement natif `change`
   - Fonction `fetchEmploisTempsData()` définie EN PREMIER
   - Event listeners sur TOUS les selects
   - Empêcher soumission formulaire

4. ✅ **Bouton** : `type="button"` au lieu de `type="submit"`

5. ✅ **Filtres persistants** : `index()` applique filtres depuis URL
   ```php
   if ($request->filled('filiere_id')) {
       $emploisTempsQuery->whereHas('classe', function($q) use ($request) {
           $q->where('filiere_id', $request->filiere_id);
       });
   }
   ```

6. ✅ **DataTables** : Vérification jQuery/DataTables avant utilisation
   ```javascript
   if (typeof $ !== 'undefined' && typeof $.fn.dataTable !== 'undefined') {
       if ($.fn.dataTable.isDataTable('#emploiTempsTable')) {
           $('#emploiTempsTable').DataTable().destroy();
       }
   }
   ```

---

### 🎨 Timeline Emploi du Temps - Alignement Visuel (30 octobre)

**Page** : `/esbtp/emploi-temps` - Vue timeline

**Problème** : Les séances n'étaient pas parfaitement alignées avec les lignes horizontales d'heure de début et de fin.

**Solution implémentée** : Utilisation de `transform: translateY()` au lieu de `margin-top`

**Pourquoi `translateY` ?**
- ✅ Déplace visuellement les éléments sans créer d'espace dans le layout
- ✅ Les séances qui se suivent (ex: 09h-10h puis 10h-11h) restent collées
- ❌ `margin-top` créait un espace entre séances consécutives

**Code** (`timetable-grid.blade.php` L484) :
```html
<div class="timeline-session type-{{ $session['type'] }}"
     style="grid-column: {{ $columnIndex }};
            grid-row: {{ $session['gridRowStart'] }} / {{ $session['gridRowEnd'] }};
            transform: translateY(12px);
            ...">
```

**Impact** :
- Séances commencent exactement sur la ligne d'heure de début
- Séances se terminent exactement sur la ligne d'heure de fin
- Pas d'espace indésirable entre séances qui se suivent

---

### 🎨 Preview PDF Emploi du Temps - Grille CSS Proportionnelle (30 octobre)

**Page** : `/esbtp/emploi-temps` - Preview PDF

**Problème** : La preview PDF utilisait un tableau HTML standard où toutes les lignes avaient la même hauteur, rendant une séance de 30 minutes visuellement identique à une séance de 60 minutes.

**Solution implémentée** : Remplacement complet du tableau HTML par CSS Grid (même structure que la version web).

**Architecture** :
- **Avant** : `<table>` avec rows fixes → toutes les périodes égales
- **Après** : CSS Grid avec `grid-template-rows: repeat({{ $segmentCount }}, 1fr)` → hauteurs proportionnelles

**Code** (`timetable-grid.blade.php` L522-586) :
```html
<div style="display: grid;
            grid-template-columns: 80px repeat({{ count($normalizedDays) }}, 1fr);
            grid-template-rows: 40px repeat({{ $segmentCount }}, 1fr);">

    {{-- Sessions avec grid-row spanning --}}
    <div style="grid-row: {{ $session['gridRowStart'] }} / {{ $session['gridRowEnd'] }};">
        <!-- Contenu séance -->
    </div>
</div>
```

**Alignement labels d'heures** :
- Utilisation de `transform: translateY(50%)` pour aligner les labels avec les lignes horizontales
- Les labels étaient centrés dans leurs cellules (au-dessus de la ligne)
- Le translateY les décale vers le bas pour toucher la ligne

**Impact** :
- Séances proportionnelles à leur durée réelle (30 min = moitié de 60 min)
- Labels de minutes affichés (heures pleines + minutes importantes)
- Labels alignés avec les lignes horizontales
- Cohérence visuelle parfaite avec la version web timeline

---

### 📄 Export PDF Emploi du Temps - Template Compact (31 octobre)

**Page** : `/esbtp/emploi-temps/{id}/export-pdf`

**Problème** : L'export PDF utilisait le template preview (CSS Grid) et générait 2 pages :
- Page 1 : Grille vide avec header et KPI volumineux
- Page 2 : Liste des séances déconnectée de la grille

**Solution** : Nouveau template dédié export avec HTML table classique

#### Architecture Séparée

**3 templates distincts** :
- 🌐 **Web** : `esbtp/emploi-temps/partials/timetable-grid.blade.php` (CSS Grid interactif)
- 👁️ **Preview** : `pdf/emploi-temps.blade.php` (CSS Grid pour aperçu navigateur)
- 📄 **Export** : `pdf/emploi-temps-export.blade.php` (HTML table pour téléchargement) ✨ NOUVEAU

#### Template Export Compact

**Fichier** : `resources/views/pdf/emploi-temps-export.blade.php` (450 lignes)

**Header compact (économie 40% hauteur)** :
```
┌─────────────────────────────────────────────────────────┐
│  ESBTP  │ Localisation │ Classe & Filière │ Couverture │
│         │ Yamoussoukro │ TP C - TP        │ 27/10-02/11│
└─────────────────────────────────────────────────────────┘
```

**Structure HTML Table** :
- Segments de **1 heure** (07:00, 08:00, 09:00...)
- **Marqueurs minutes** affichés uniquement si séance commence/finit (ex: 08:30, 11:15)
- Séances positionnées avec `rowspan` proportionnel
- Tout tient sur **1 page A4 landscape**

**Hiérarchie visuelle cartes séances** :
```
┌──────────────────────┐
│ COURS (petit)        │ ← Haut : Type
│                      │
│  Mathématiques       │ ← Centre : Matière (GRAND, bold)
│                      │
│ KOUASSI • Salle A    │ ← Bas : Infos (petit)
│ 08:30 - 10:00        │
└──────────────────────┘
```

**Calcul intelligent rowspan** :
```php
// Séance 08:30-10:00 → rowspan = 1 (couvre 1 ligne 60min) + fraction
// Séance 10:00-12:00 → rowspan = 2 (couvre 2 lignes 60min)
$rowspan = 1;
for ($i = $startIndex + 1; $i < count($finalSegments); $i++) {
    if ($finalSegments[$i]['minutes'] < $endMinutes) {
        $rowspan++;
    }
}
```

#### Modifications

**Fichier** : `app/Services/ESBTPPDFService.php` (ligne 404)
```php
// ❌ AVANT
$html = view('pdf.emploi-temps', $data)->render();

// ✅ APRÈS
$html = view('pdf.emploi-temps-export', $data)->render();
```

#### Résultat

**Avant (2 pages)** :
- Page 1 : Header volumineux + KPI cards + Grille vide
- Page 2 : Liste séances déconnectée

**Après (1 page)** :
- Header compact 4 colonnes (30% hauteur)
- Grille avec séances intégrées (70% hauteur)
- Design identique au PDF de référence

**Avantages** :
- ✅ Tout tient sur 1 page A4 landscape
- ✅ Séances visibles à leur position horaire
- ✅ Print-ready, cohérent, professionnel
- ✅ Preview PDF reste inchangé (pas de régression)

**Templates préservés** :
- ❌ `pdf/emploi-temps.blade.php` - Preview (NON TOUCHÉ)
- ❌ `esbtp/emploi-temps/partials/timetable-grid.blade.php` - Web (NON TOUCHÉ)

---

### 📄 Export PDF Emploi du Temps - Browserless.io + CSS Grid (1er novembre)

**Page** : `/esbtp/emploi-temps/{id}/export-pdf`

**Problème** : Export PDF incompatible avec hébergement mutualisé (Puppeteer nécessite Chromium)

#### Solution : Browserless.io Cloud Service

**Architecture** :
- **Développement local** : Puppeteer-core local (si Chromium installé manuellement)
- **Production (web44)** : Browserless.io API (Chrome headless cloud)

**Installation** :
```json
{
  "dependencies": {
    "puppeteer-core": "^24.27.0"  // ← Plus léger (pas de Chromium bundlé)
  }
}
```

**Pourquoi puppeteer-core ?**
- ✅ Ne télécharge pas Chromium (~300MB économisés)
- ✅ Compatible hébergement mutualisé (pas de binaire requis)
- ✅ Fallback local si Chromium installé séparément
- ✅ Production utilise Browserless.io de toute façon

**Configuration** :
```env
BROWSERLESS_ENABLED=true
BROWSERLESS_API_KEY=2TLMQwRTHrQAQJb5b91c28c67e37e8aabdbbee6d08bd10e7d
BROWSERLESS_ENDPOINT=https://production-sfo.browserless.io
```

**Avantages** :
✅ **CSS Grid parfait** : Positionnement proportionnel exact (08:30 visuellement à mi-chemin entre 08:00-09:00)
✅ **Hauteurs proportionnelles** : Séance 2h50 visuellement plus grande que 1h30
✅ **Compatible hébergement mutualisé** : Pas de Chromium local requis
✅ **Coût raisonnable** : ~5€/mois (100h génération PDF)
✅ **Fallback local** : Puppeteer si Browserless désactivé
✅ **Design cohérent** : Identique à emploi-temps.show

---

### ⏱️ Format Heures - Emploi du Temps (1er novembre)

**Pages concernées** :
- `/esbtp/emploi-temps` (show)
- `/esbtp/emploi-temps` (index)

**Problème** : Affichage heures avec décimales excessives (`47.166666666667h`, `46.5h`)

#### Solution : Formatage XXhYY

**Fonction helper** : `app/Http/Controllers/ESBTPEmploiTempsController.php`

**Lignes 434-442 & 486-494** :
```php
$formatHeures = function($heures) {
    $h = floor($heures);
    $m = round(($heures - $h) * 60);
    if ($m > 0) {
        return $h . 'h' . ($m < 10 ? '0' : '') . $m;
    }
    return $h . 'h';
};
```

#### Résultats

**Avant** :
- 50.0h
- 47.166666666667h
- 46.5h

**Après** :
- 50h
- 47h10 (47h + 10min)
- 46h30 (46h + 30min)

**Impact** : Affichage propre sans virgules ni décimales excessives

---

### 📊 Planning Général - Interface Tableau Professeurs avec Recherche Floue (1er novembre)

**Page** : `/esbtp/planning-general`

**Problème** : Interface d'assignation des professeurs peu ergonomique et recherche stricte.

#### Améliorations Implémentées

**1. Tableau HTML structuré** (au lieu de liste checkboxes)

**Avantages** :
- Header sticky (visible au scroll)
- Max-height 400px avec scroll automatique
- 3 colonnes : Checkbox | Nom complet | Spécialisation

**2. Checkbox header (remplace bouton "Tout sélectionner")**

**Comportement** :
- Coché → Sélectionne TOUTES les lignes VISIBLES (après filtrage)
- Décoché → Désélectionne TOUTES les lignes VISIBLES
- État indéterminé (`indeterminate`) → Quelques lignes cochées
- Respecte le filtre de recherche

**3. Recherche floue (Fuzzy Search) avec tolérance 80%**

**Algorithmes** :
- **Levenshtein Distance** : Calcule le nombre d'opérations (insertion, suppression, substitution) pour transformer une chaîne
- **Similarité en %** : `((maxLen - distance) / maxLen) * 100`
- **Normalisation** : Supprime accents, majuscules, caractères spéciaux
- **4 niveaux de matching** :
  1. Correspondance exacte (substring)
  2. Correspondance par mots (chaque mot ≥ 80%)
  3. Similarité globale (≥ 80%)
  4. Inversion de noms ("Jean KOUASSI" = "KOUASSI Jean")

**Exemples** :
```javascript
// Tolérance fautes d'orthographe
"kouasi" → trouve "KOUASSI Jean" (85.7% similarité) ✅
"jea kouas" → trouve "KOUASSI Jean" (84% similarité) ✅

// Inversion de noms
"jean kouassi" → trouve "KOUASSI Jean" ✅

// Recherche spécialisation
"math" → trouve spé "Mathematics" ✅
"physic" → trouve spé "Physics" (85.7% similarité) ✅
```

#### Avantages

- Interface moderne et professionnelle
- Recherche tolérante (fautes d'orthographe, inversion noms)
- Checkbox header intuitif (select all/deselect all)
- Debug facile avec logs console détaillés
- Performance optimisée (filtrage client-side)
- UX cohérente (sélection lignes visibles uniquement)

---

### 👥 Gestion Enseignants - Amélioration UX & Fonctionnalités (2 novembre)

**Contexte** : Refonte complète de l'interface de gestion des enseignants pour améliorer l'expérience utilisateur et ajouter de nouvelles fonctionnalités.

#### Améliorations principales

1. **Header Unifié Pages Création** - Cohérence visuelle entre pages création/édition
2. **Affichage Titre Académique** - Titre (M., Mme, Mlle, Dr., Pr.) AVANT le nom complet
3. **Option "Mademoiselle" Ajoutée** - Nouvelle option dans titres académiques
4. **Titre Académique Modifiable** - Select modifiable au lieu de lecture seule
5. **Suppression Section Disponibilité** - Disponibilités gérées uniquement dans `enseignants.show`
6. **Message Création Enrichi** - Carte info + bouton "Voir la fiche"
7. **Tip Gestion Disponibilité** - Carte tip au-dessus de la liste
8. **Nettoyage Debugs Conditionnels** - Wrapping avec `@if(config('app.debug'))`
9. **Détection Doublons Enseignants** - Système AJAX avec fuzzy search
10. **Bouton Toggle Status Fonctionnel** - Implémentation complète AJAX

#### Avantages UX

✅ **Cohérence visuelle** : Headers unifiés entre toutes les pages
✅ **Informations complètes** : Titre académique affiché partout
✅ **Workflow simplifié** : Disponibilités gérées dans fiche détaillée uniquement
✅ **Guidance utilisateur** : Tips et messages clairs
✅ **Prévention doublons** : Détection automatique nom + spécialisation
✅ **Actions fonctionnelles** : Toggle status opérationnel pour enseignants ET secrétaires
✅ **Code propre** : Debug masqué en production

---

### 🎬 Landing Page - Lecteur Vidéo Témoignages Style Shorts (3 novembre)

**Page** : `/` - Section témoignages Mme Mangoua Nadège

**Problème** : Design du témoignage vidéo trop fade et video player HTML5 basique sans contrôles personnalisés.

**Solution implémentée** : Lecteur vidéo personnalisé style YouTube Shorts avec contrôles audio avancés.

#### Fonctionnalités

- ✅ Ratio portrait 9:16 (max-width 320px)
- ✅ Play/Pause au clic sur vidéo
- ✅ Overlay icône animé (fade in/out 500ms)
- ✅ Contrôles audio bottom-center
- ✅ Bouton mute/unmute avec icônes color-coded
- ✅ Slider volume vertical (0-100%)
- ✅ Transitions smooth (opacity + transform)
- ✅ Mémoire volume avant mute
- ✅ Border-radius permanent (16px)

**Icônes color-coded** :
- 🔴 `fa-volume-mute` (rouge) : Muted ou volume = 0
- 🟠 `fa-volume-down` (orange) : Volume 1-30%
- 🔵 `fa-volume-down` (bleu) : Volume 31-70%
- 🟢 `fa-volume-up` (vert) : Volume > 70%

**Résultat** :
- Lecteur vidéo moderne style Shorts
- Contrôles audio smooth et intuitifs
- Design cohérent avec reste de la landing page
- UX optimale (hover, transitions, feedback visuel)

**Performance** :
- Autoplay loop en background
- Poster image pour chargement initial
- Contrôles légers (pas de bibliothèque externe)

---

### 📝 Modal Édition Rapide Étudiants & Inscriptions (6 novembre)

**Page** : `/esbtp/etudiants` (index)

**Problème** : Besoin d'une interface d'édition rapide sans quitter la page de liste, avec accès aux informations de l'étudiant et de ses inscriptions.

**Solution implémentée** : Modal moderne avec onglets pour édition étudiant et inscriptions via iframes.

#### Architecture Modal

**Structure** :
- Modal Bootstrap avec design moderne (80vw × 80vh)
- 2 onglets : "Étudiant" et "Inscriptions"
- Contenu chargé via iframes pour isolation complète
- Accordéon pour les inscriptions multiples

#### Composants Créés

**Nouveaux fichiers** :
- `resources/views/esbtp/etudiants/embed/edit.blade.php` - Version iframe de l'édition étudiant
- `resources/views/esbtp/inscriptions/embed/edit.blade.php` - Version iframe de l'édition inscription
- `resources/views/layouts/embedded.blade.php` - Layout minimaliste pour iframes

**Partials extraits** :
- `resources/views/esbtp/etudiants/partials/edit-form.blade.php` - Formulaire étudiant réutilisable
- `resources/views/esbtp/etudiants/partials/edit-form-scripts.blade.php` - Scripts formulaire étudiant
- `resources/views/esbtp/inscriptions/partials/edit-form.blade.php` - Formulaire inscription réutilisable
- `resources/views/esbtp/inscriptions/partials/edit-form-scripts.blade.php` - Scripts formulaire inscription

#### Avantages Architecture

**1. Isolation complète** :
- ✅ Iframe = contexte JS/CSS séparé
- ✅ Pas de conflit avec page parent
- ✅ Chargement à la demande (performance)

**2. Réutilisabilité** :
- ✅ Partials partagés entre vue normale et embedded
- ✅ Un seul formulaire maintenu
- ✅ Logique controller centralisée

**3. UX moderne** :
- ✅ Pas de rechargement page
- ✅ Modal large (80% écran)
- ✅ Onglets intuitifs
- ✅ Accordéon pour inscriptions multiples
- ✅ Messages success dans iframe

**4. Performance** :
- ✅ Lazy loading iframes
- ✅ Chargement au clic sur accordéon
- ✅ Cache-busting avec timestamp

---

### 🐛 Corrections Bugs Inscriptions & Filtres (6 novembre)

**Contexte** : Quatre bugs critiques identifiés après l'implémentation du modal d'édition rapide.

#### Bug #1 : Bouton "Valider Définitivement" Affiché à Tort

**Problème** : Le bouton s'affichait même pour les inscriptions finalisées (`etudiant_cree`)

**Solution** :
```php
@if(!($inscription->status === 'active' && $inscription->workflow_step === 'etudiant_cree'))
    <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#validationModal">
        <i class="fas fa-check"></i>Valider définitivement
    </button>
@endif
```

#### Bug #2 : Filtre Classe + Année Incorrecte

**Problème** : Filtres séparés vérifiaient des inscriptions DIFFÉRENTES

**Solution** : Fusionner dans UN SEUL `whereHas` pour garantir que toutes conditions s'appliquent à la MÊME inscription

#### Bug #3 : Route AJAX Chemin Incorrect

**Problème** : Route définie au mauvais endroit (hors préfixe `/esbtp/`)

**Solution** : Déplacer route AVANT `Route::resource` avec bon préfixe

#### Bug #4 : Bouton "Valider Définitivement" dans Modal Non Fonctionnel

**Problème** : Interaction complexe iframe → parent → modal non implémentée

**Solution adoptée** : Suppression bouton du modal, utiliser page complète pour actions critiques

---

### 📱 Page Étudiants Index - Responsive Design Complet (6 novembre)

**Page** : `/esbtp/etudiants` (index - Gestion des étudiants)

**Objectif** : Rendre la page complètement responsive pour tous les appareils.

**Solution implémentée** : Media queries complètes avec 5 breakpoints principaux.

#### Breakpoints

**1. Desktop (> 1200px)** : Design par défaut
**2. Tablette (≤ 992px)** : Filtres 1 colonne, modal 95vw
**3. Mobile paysage (≤ 768px)** : Textes réduits, tabs 50%
**4. Mobile portrait (≤ 576px)** : Modal fullscreen, tabs verticaux, colonnes cachées
**5. Petit mobile (≤ 400px)** : Colonnes supplémentaires cachées, ultra-compact

#### Colonnes Table Visibles par Breakpoint

| Colonne | Desktop | Tablette | Mobile | Petit Mobile |
|---------|---------|----------|--------|--------------|
| Matricule | ✅ | ✅ | ✅ | ✅ |
| Photo | ✅ | ✅ | ❌ | ❌ |
| Nom complet | ✅ | ✅ | ✅ | ✅ |
| Genre | ✅ | ✅ | ❌ | ❌ |
| Contact | ✅ | ✅ | ✅ | ✅ |
| Résidence | ✅ | ✅ | ❌ | ❌ |
| Classe actuelle | ✅ | ✅ | ✅ | ✅ |
| Date inscription | ✅ | ✅ | ❌ | ❌ |
| Statut affectation | ✅ | ✅ | ✅ | ❌ |
| Statut | ✅ | ✅ | ✅ | ✅ |
| Actions | ✅ | ✅ | ✅ | ✅ |

**Total colonnes** : 11 → 11 → 6 → 5

---

### 🛠️ Scripts d'Initialisation Unifiés (6 novembre)

**Contexte** : Système unifié pour orchestrer tous les scripts d'initialisation et seeders.

#### Architecture

**3 composants principaux** :

1. **setup.php** - Orchestrateur principal (684 lignes)
2. **verify.php** - Script de vérification (553 lignes)
3. **.setup.lock** - Fichier de tracking JSON

**Ordre d'exécution** :
```bash
1. storage      → init_storage.php (structure dossiers + symlinks)
2. permissions  → fix_permissions.php (210 permissions Spatie)
3. settings     → deploy_settings.php (paramètres système)
4. seeders      → ChatbotSeeder, ServiceTechniqueSeeder, SettingsSeeder
```

**Avantages** :
- ✅ Idempotence garantie
- ✅ Gestion des erreurs robuste
- ✅ UX développeur optimale
- ✅ Intégration CI/CD
- ✅ Documentation complète

---

### 📱 Page Étudiants - UX Responsive + AJAX (7 novembre)

**Page** : `/esbtp/etudiants` (index)

**Solution implémentée** : 5 améliorations majeures suivant meilleures pratiques UX 2025.

#### Améliorations

**1. Système 8px Grid - Spacing Standard**
- Valeurs standardisées : 8px, 16px, 24px, 32px, 48px, 56px, 64px
- Principe Gestalt : Internal ≤ External
- Design cohérent et prévisible

**2. Border-radius Main-Content Mobile**
- Border-radius 16px sur mobile (< 992px)
- Design moderne et doux

**3. Séparation Icône/Titre - Visual Hierarchy**
- Conteneur flexbox avec gap standardisé
- Icône non-compressible (`flex-shrink: 0`)

**4. AJAX Drawer Mobile - Pas de Refresh Page**
- Conversion complète en AJAX avec `fetch()` API
- Drawer se ferme automatiquement (300ms delay)

**5. Indicateur Filtres Actifs - Feedback Visuel**
- Conteneur dynamique avec tags filtres
- Suppression individuelle (bouton ×)
- Suppression globale ("Tout effacer")

#### Fixes Bugs

**Bug #1 : Filtres actifs ne se mettent pas à jour automatiquement**
- Cause : Race condition dans timing
- Solution : `updateActiveFiltersIndicator()` appelé dans `.then()` après `pushState`

**Bug #2 : Classe affiche l'ID au lieu du nom**
- Solution : Mapping ID → Label généré depuis data Laravel

---

### 🔀 Tri Colonnes - Server-Side au lieu de Client-Side (7 novembre)

**Page** : `/esbtp/etudiants` (index)

**Problème** : Tri limité à la page actuelle (10 étudiants)

**Solution** : Tri server-side avec AJAX

**Backend** :
```php
$sortColumn = $request->input('sort', 'created_at');
$sortOrder = $request->input('order', 'desc');
$baseQuery->orderBy($sortColumn, $sortOrder);
```

**Frontend** :
- Clic header → Construct URL `?sort=nom&order=asc&page=1`
- Appel AJAX → Serveur trie TOUS les étudiants
- Flèches ▲/▼ indiquent tri actif

**Impact** :
- ✅ Tri sur TOUS les étudiants (toutes pages)
- ✅ Pagination respecte le tri
- ✅ URL bookmarkable

---

### 🔄 Reset Filtres - Synchronisation Selects après AJAX (7 novembre)

**Page** : `/esbtp/etudiants` (index)

**Problème** : Selects ne se mettaient pas à jour après suppression filtres

**Solution** :

**Nouvelles fonctions** :
- `resetSelectByName(name)` - Reset un select spécifique
- `resetAllSelects()` - Reset TOUS les selects

**Modifications** :
- `removeFilter(key)` - AJAX puis reset select correspondant
- `clearAllFilters()` - AJAX puis reset tous selects
- Bouton "Réinitialiser" : Remplacé `<a href>` par `<button>` avec AJAX

**Alpine.js Event Listeners** :
- `reset-searchable-select` - Reset composant individuel
- `reset-all-searchable-selects` - Reset tous composants

**Impact** :
- ✅ État selects toujours cohérent avec filtres actifs
- ✅ Pas de refresh page
- ✅ Desktop ET mobile synchronisés

---

*Archivé le: 12 novembre 2025*
*Contenu détaillé: Développements Octobre 2025*
