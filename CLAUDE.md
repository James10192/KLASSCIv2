# KLASSCI - Documentation Système SaaS Multi-Tenant

> **Note** : Ce fichier contient la documentation actuelle du projet. Pour l'historique détaillé et les anciennes fonctionnalités, voir [CLAUDE_ARCHIVE.md](CLAUDE_ARCHIVE.md).

---

## 📋 Table des Matières

1. [Règles pour l'IA](#règles-pour-lia)
2. [Vue d'ensemble](#vue-densemble)
3. [Architecture](#architecture)
4. [Fonctionnalités Métier](#fonctionnalités-métier)
5. [Développements Octobre 2025](#développements-octobre-2025)
6. [TODO & Prochaines Étapes](#todo--prochaines-étapes)
7. [Archive](#archive)

---

## ⚠️ RÈGLES IMPORTANTES POUR IA - DOCUMENTATION API

### 📚 EMPLACEMENT DOCUMENTATION API : `docs/api/`

**RÈGLE ABSOLUE** : Toute documentation d'API REST doit être placée dans le dossier `docs/api/`.

#### Quand CRÉER une NOUVELLE documentation :
- ✅ Nouvelle API créée → Créer `docs/api/NOM_API.md`
- ✅ Nommer le fichier selon l'entité principale (ex: `LMS_ENSEIGNANTS.md`, `LMS_CLASSES.md`)
- ✅ Utiliser le template standard : Vue d'ensemble, Authentification, Endpoints, Exemples, Performance

#### Quand ENRICHIR une documentation EXISTANTE :
- ✅ API modifiée → Mettre à jour `docs/api/NOM_API.md`
- ✅ Ajouter section "Historique des modifications" avec date
- ✅ Breaking changes marqués clairement

---

## 🎯 Vue d'ensemble

### Architecture Globale

**Type** : SaaS Multi-Tenant Laravel

**Applications** :
- **Master** (`klassci-master`) : Admin SaaS centralisé - gère tous les tenants
- **Tenant** (`KLASSCIv2`) : Application métier par établissement

**Tenants actifs** (Octobre 2025) :
- `esbtp-abidjan` : ESBTP Abidjan (Pro - 30 users, 3000 inscriptions)
- `esbtp-yakro` : ESBTP Yakro (Essentiel - 20 users, 700 inscriptions)
- `presentation` : Test (Free - 5 users, 50 inscriptions)

### Stack Technique

- **Framework** : Laravel 12.x
- **Base de données** : MySQL 8.x
- **Frontend** : Blade + Alpine.js + Chart.js + DataTables
- **Exports** : Maatwebsite Excel, DomPDF
- **API** : Laravel Sanctum + JSON REST
- **IA** : Google Gemini 2.0 Flash (chatbot)

---

## 🏗️ Architecture

### Infrastructure SaaS (TERMINÉ)

#### Phase 1 : Base Laravel 12 ✅
- 8 migrations + 8 modèles Eloquent
- BDD `klassci_master` : tenants, deployments, health_checks, backups, features, activity_logs

#### Phase 2 : Commandes Artisan ✅
```bash
tenant:provision               # Provisionner nouveau tenant (17 étapes)
tenant:deploy [--all]          # Déployer mises à jour
tenant:health-check [--all]    # Vérifier santé (HTTP, DB, SSL)
tenant:backup [--all]          # Backup DB + fichiers
tenant:update-stats [--all]    # Mettre à jour statistiques
```

#### Phase 3 : Dashboard Filament ✅
- Panel admin `/admin` avec Filament v3.3
- Tenant Resource complet (5 onglets)
- KPI globaux + monitoring temps réel

### Tables Principales (BDD Tenant)

**Gestion Académique** :
- `esbtp_classes` - Classes (filière + niveau + capacité)
- `esbtp_matieres` - Matières (pivot filière/niveau)
- `esbtp_planifications_academiques` - Planning général volumes horaires
- `esbtp_emploi_temps` - Emplois du temps hebdomadaires
- `esbtp_seance_cours` - Séances planifiées

**Étudiants & Inscriptions** :
- `esbtp_etudiants` - Profils étudiants
- `esbtp_inscriptions` - Inscriptions annuelles
- `esbtp_paiements` - Paiements scolarité

**Évaluations & Notes** :
- `esbtp_evaluations` - Évaluations programmées
- `esbtp_notes` - Notes par évaluation
- `esbtp_resultats` - Moyennes par matière/période
- `esbtp_bulletins` - Bulletins (JSON professeurs)

**Présences** :
- `esbtp_attendances` - Présences étudiants
- `esbtp_teacher_attendances` - Émargements enseignants

---

## 🔧 Fonctionnalités Métier

### Inscriptions & Paiements

- **Détection doublons** : Fuzzy search sur nom/prénom/date naissance
- **Refresh AJAX** : Polling 30s + animation "travelling light"
- **Actions groupées** : Validation/rejet en masse avec protection anti-doublons
- **Matricules tolérants** : Génération auto avec retry (3 tentatives)

### Notifications Multi-Canal

**Canaux supportés** : App + Email + WhatsApp + SMS

**Templates email** : 11 types (inscription, paiements, absences, bulletins, notes)

**Configuration** :
```env
WHATSAPP_ENABLED=false
SMS_PROVIDER=orange
SMS_ENABLED=false
```

### Bulletins & Évaluations

**Workflow génération bulletin** :
1. Configuration matières
2. Vérification moyennes
3. Édition professeurs (propagation classe)
4. Édition absences (optionnel)
5. Génération PDF

**Système refresh AJAX évaluations** :
- Filtres : recherche, pagination, per-page
- Statuts auto : brouillon, planifiée, en_cours, terminée, annulée
- Actions : Annuler/Activer/Réactiver + suppression JSON
- KPI dynamiques

### Gestion Classes

- **Lazy loading étudiants** : Pagination 20 par batch
- **Load More AJAX** : Pagination manuelle avec `slice()`
- **KPI globaux** : Toutes classes actives

### Permissions & Accès

**Rôle étudiant** (11 permissions) :
- `view_own_*` : grades, exams, profile, timetable, attendances, bulletin

**Dashboard étudiant** : Design moderne `dashboard-acasi`
- Stat cards, badges, tableaux stylisés
- Pages : profil, notes, évaluations, emploi du temps, absences, paiements

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
┌─────────────────────────────────────────────────────┐
│  ESBTP  │ Localisation │ Classe & Filière │ Couverture │
│         │ Yamoussoukro │ TP C - TP        │ 27/10-02/11│
└─────────────────────────────────────────────────────┘
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

**Fichier** : `config/services.php`
```php
'browserless' => [
    'enabled' => env('BROWSERLESS_ENABLED', false),
    'api_key' => env('BROWSERLESS_API_KEY'),
    'endpoint' => env('BROWSERLESS_ENDPOINT', 'https://production-sfo.browserless.io'),
],
```

#### Modifications Service PDF

**Fichier** : `app/Services/ESBTPPDFService.php` (lignes 390-440)

**Logique** :
```php
// Si Browserless.io configuré (production)
if (config('services.browserless.enabled', false)) {
    $apiKey = config('services.browserless.api_key');
    $endpoint = config('services.browserless.endpoint');

    // Appel API via Guzzle HTTP Client
    $client = new \GuzzleHttp\Client(['timeout' => 60]);
    $response = $client->post("{$endpoint}/pdf?token={$apiKey}", [
        'json' => [
            'html' => $html,
            'options' => [
                'format' => 'A4',
                'landscape' => true,
                'margin' => ['top' => '10mm', 'right' => '10mm', 'bottom' => '10mm', 'left' => '10mm'],
                'printBackground' => true,
            ],
            'gotoOptions' => [
                'waitUntil' => 'networkidle0',
            ],
        ],
    ]);

    return $response->getBody()->getContents();
}

// Fallback: Puppeteer local (développement)
return \Spatie\Browsershot\Browsershot::html($html)
    ->paperSize(297, 210)
    ->margins(10, 10, 10, 10)
    ->waitUntilNetworkIdle()
    ->pdf();
```

**Controller** : `app/Http/Controllers/ESBTPEmploiTempsController.php` (ligne 1285)
```php
// Browsershot retourne contenu binaire directement (pas d'objet)
return response($pdf, 200, [
    'Content-Type' => 'application/pdf',
    'Content-Disposition' => 'attachment; filename="' . $filename . '"'
]);
```

#### Template PDF Optimisé

**Suppression KPI cards** : `resources/views/pdf/emploi-temps.blade.php`
- Lignes 57-90 : Styles CSS KPI supprimés
- Lignes 209-225 : Bloc HTML KPI supprimé

**Grille compacte** : `resources/views/esbtp/emploi-temps/partials/timetable-grid.blade.php` (variant PDF)
- Colonne heures : 80px → **45px** (-44%)
- Header : 40px → **22px** (-45%)
- Font sizes réduits : **0.6rem** (header), **0.55rem** (heures), **0.5-0.75rem** (sessions)
- Padding sessions : 6px 8px → **3px 4px** (-50%)
- Margins : 4px → **2px**

**Structure cards séances** (identique à emploi-temps.show) :
```html
<div class="session-card">
    <!-- Type en haut (petit, uppercase) -->
    <div style="font-size: 0.5rem;">COURS</div>

    <!-- Matière au centre (GRAND, bold) -->
    <div style="font-size: 0.75rem; font-weight: 800;">
        Mathématiques
    </div>

    <!-- Détails en bas (même ligne, séparateurs •) -->
    <div style="display: flex; gap: 4px;">
        <span><i class="fas fa-user-tie"></i> KOUASSI Jean</span>
        <span>•</span>
        <span><i class="fas fa-door-open"></i> Salle A12</span>
        <span>•</span>
        <span><i class="fas fa-clock"></i> 08:30 - 10:00</span>
    </div>
</div>
```

#### Avantages

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

**Données enrichies** :
- Lignes 454-456 : `volume_horaire_total_formatted`, `heures_utilisees_formatted`, `heures_restantes_formatted`
- Lignes 498-499 : `heures_totales_formatted`, `heures_restantes_formatted`
- Lignes 990-992 : `volume_info` avec champs formatés

**Vue mise à jour** : `resources/views/components/emploi-temps/planification-section.blade.php`
- Ligne 66 : H. Total → `{{ $matiere['volume_horaire_total_formatted'] }}`
- Ligne 70 : H. Restantes → `{{ $matiere['heures_restantes_formatted'] }}`
- Ligne 89 : Résumé H. planifiées → `{{ $planificationData['heures_totales_formatted'] }}`
- Ligne 93 : Résumé H. restantes → `{{ $planificationData['heures_restantes_formatted'] }}`

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

**Fichier** : `app/Http/Controllers/ESBTPPlanningGeneralController.php` (lignes 308-401)

**Structure** :
```html
<table class="table table-hover teacher-selection-table">
  <thead style="position: sticky; top: 0;">
    <tr>
      <th><input type="checkbox" class="teacher-select-all-checkbox" /></th>
      <th>Nom complet</th>
      <th>Spécialisation</th>
    </tr>
  </thead>
  <tbody>
    <tr class="teacher-row" data-teacher-name="jean kouassi" data-teacher-spec="mathematics">
      <td><input type="checkbox" name="teachers[42][]" value="1" /></td>
      <td><strong>Jean KOUASSI</strong></td>
      <td>Mathematics</td>
    </tr>
  </tbody>
</table>
```

**Avantages** :
- Header sticky (visible au scroll)
- Max-height 400px avec scroll automatique
- 3 colonnes : Checkbox | Nom complet | Spécialisation

**2. Checkbox header (remplace bouton "Tout sélectionner")**

**Fichier** : `resources/views/esbtp/planning-general/index.blade.php` (lignes 2414-2433)

**Comportement** :
- Coché → Sélectionne TOUTES les lignes VISIBLES (après filtrage)
- Décoché → Désélectionne TOUTES les lignes VISIBLES
- État indéterminé (`indeterminate`) → Quelques lignes cochées
- Respecte le filtre de recherche

**3. Recherche floue (Fuzzy Search) avec tolérance 80%**

**Fichier** : `resources/views/esbtp/planning-general/index.blade.php` (lignes 2449-2603)

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

**Configuration seuil** :
```javascript
const matchName = fuzzyMatch(searchText, teacherName, 80);  // 80% = tolérance
```

**4. Logs console détaillés**

**Fichier** : `resources/views/esbtp/planning-general/index.blade.php`

**Logs disponibles** :
```javascript
// Checkbox header
console.log('🔍 Header checkbox clicked - Matiere:', matiereId);
console.log('  👁️ Visible rows before:', visibleCheckboxes.length);
console.log('  ✅ Checked before/after:', checkedBefore, checkedAfter);

// Recherche floue
console.log('🔍 Fuzzy search - Query:', searchText);
console.log('  🔎 Checking:', teacherName);
console.log('  ✅ Exact match / Word match / Fuzzy match (85.7%)');
console.log('  ❌ No match - Similarity: 45.5%');
console.log('  📊 Search results:', visibleCount, 'rows visible');

// Compteur
console.log('📊 updateTeacherCount - Matiere:', matiereId);
console.log('  📈 Total teachers / 👁️ Visible / ✅ Selected');
console.log('  🔲 Header: unchecked / ✅ checked / ➖ indeterminate');
```

**5. Fixes CSS checkboxes invisibles**

**Fichier** : `resources/views/esbtp/planning-general/index.blade.php` (lignes 1271-1290)

**Problème** : Ancien CSS cachait toutes les checkboxes (`opacity: 0`)

**Solution** : Sélecteur spécifique
```css
/* Ancien système seulement */
.teacher-checkbox-label .teacher-checkbox {
    opacity: 0;
}

/* Nouveau tableau - visibles */
.teacher-selection-table .teacher-checkbox {
    cursor: pointer;
    width: 18px;
    height: 18px;
}
```

#### Fichiers Modifiés

| Fichier | Lignes | Modifications |
|---------|--------|---------------|
| `ESBTPPlanningGeneralController.php` | 308-401 | Génération tableau HTML + recherche |
| `index.blade.php` (view) | 2345-2603 | JavaScript fuzzy search + checkbox header |
| `index.blade.php` (CSS) | 1271-1290 | Fix checkboxes invisibles |
| `index.blade.php` (debug) | 3173-3193 | Logs boutons matières |

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

#### 1. Header Unifié Pages Création

**Fichiers modifiés** :
- `resources/views/esbtp/enseignants/create.blade.php` (lignes 458-474)
- `resources/views/esbtp/coordinateurs/create.blade.php` (lignes 206-221)

**Changement** : Remplacement du header `card-moderne` par `main-header` (copié depuis `enseignants.edit`)

**Structure** :
```html
<div class="main-header">
    <div class="header-content">
        <div class="header-left">
            <h1>
                <i class="fas fa-user-plus me-2"></i>
                Nouveau Enseignant
            </h1>
            <p>Créez un profil complet pour le nouvel enseignant</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('esbtp.personnel.unified.index') }}" class="btn-header">
                <i class="fas fa-arrow-left"></i>
                Retour à la liste
            </a>
        </div>
    </div>
</div>
```

**Avantages** :
- Cohérence visuelle entre pages création/édition
- Design moderne et professionnel
- Bouton "Retour" toujours visible

---

### 🎬 Landing Page - Lecteur Vidéo Témoignages Style Shorts (3 novembre)

**Page** : `/` - Section témoignages Mme Mangoua Nadège

**Problème** : Design du témoignage vidéo trop fade et video player HTML5 basique sans contrôles personnalisés.

**Solution implémentée** : Lecteur vidéo personnalisé style YouTube Shorts avec contrôles audio avancés.

#### Architecture Lecteur Vidéo

**Fichier** : `resources/views/welcome-software.blade.php` (lignes 2628-2884)

**Fonctionnalités** :
- ✅ Ratio portrait 9:16 (max-width 320px)
- ✅ Play/Pause au clic sur vidéo
- ✅ Overlay icône animé (fade in/out 500ms)
- ✅ Contrôles audio bottom-center
- ✅ Bouton mute/unmute avec icônes color-coded
- ✅ Slider volume vertical (0-100%)
- ✅ Transitions smooth (opacity + transform)
- ✅ Mémoire volume avant mute
- ✅ Border-radius permanent (16px)

#### Contrôles Audio

**Positionnement** : Bottom-center avec `transform: translateX(-50%)`

**Structure HTML** :
```html
<div id="audioControls" style="position: absolute; bottom: 1rem; left: 50%; transform: translateX(-50%); display: flex; flex-direction: column-reverse; align-items: center; gap: 0;">
  <!-- Volume Slider (apparaît au hover) -->
  <div id="volumeSliderContainer" style="opacity: 0; transform: translateY(10px); pointer-events: none; transition: opacity 0.3s ease, transform 0.3s ease;">
    <input type="range" id="volumeSlider" min="0" max="100" value="50" orient="vertical" style="height: 100px;">
  </div>

  <!-- Bouton Mute/Unmute -->
  <button id="muteBtn" style="width: 48px; height: 48px; background: rgba(255, 255, 255, 0.95); border-radius: 50%; flex-shrink: 0;">
    <i id="muteIcon" class="fas fa-volume-mute"></i>
  </button>
</div>
```

**Icônes color-coded** :
- 🔴 `fa-volume-mute` (rouge) : Muted ou volume = 0
- 🟠 `fa-volume-down` (orange) : Volume 1-30%
- 🔵 `fa-volume-down` (bleu) : Volume 31-70%
- 🟢 `fa-volume-up` (vert) : Volume > 70%

#### JavaScript - Gestion Audio

**Variables état** :
```javascript
let previousVolume = 50;  // Volume avant mute (50% par défaut)
let isSliderVisible = false;
let volumeHoverTimeout;
```

**Toggle Mute/Unmute** :
```javascript
muteBtn.addEventListener('click', function(e) {
  e.stopPropagation();

  if (video.muted) {
    video.muted = false;
    video.volume = previousVolume / 100;
    volumeSlider.value = previousVolume;
    showVolumeIndicator('Son activé');
  } else {
    previousVolume = volumeSlider.value;
    video.muted = true;
    showVolumeIndicator('Son coupé');
  }
  updateMuteIcon();
});
```

**Animation Slider** :
```javascript
// Afficher slider au hover du conteneur parent (évite disparition)
audioControls.addEventListener('mouseenter', function() {
  clearTimeout(volumeHoverTimeout);
  volumeHoverTimeout = setTimeout(() => {
    volumeSliderContainer.style.opacity = '1';
    volumeSliderContainer.style.transform = 'translateY(0)';
    volumeSliderContainer.style.pointerEvents = 'auto';
  }, 200);
});

audioControls.addEventListener('mouseleave', function() {
  clearTimeout(volumeHoverTimeout);
  volumeSliderContainer.style.opacity = '0';
  volumeSliderContainer.style.transform = 'translateY(10px)';
  volumeSliderContainer.style.pointerEvents = 'none';
});
```

#### CSS Styling

**Border-radius permanent** :
```css
#videoContainer {
  border-radius: 16px !important;
  overflow: hidden !important;
}

#testimonialVideo {
  border-radius: 16px !important;
}
```

**Hover effect card** :
```css
.col-lg-5 > div {
  border-radius: 24px !important;
}

.col-lg-5 > div:hover {
  transform: perspective(1000px) rotateY(0deg) translateY(-4px) !important;
  box-shadow: 0 24px 80px rgba(4, 83, 203, 0.2) !important;
}
```

#### Fixes Appliqués

**Fix #1: Audio ne fonctionnait pas** :
- Initialisation volume à 50% au lieu de 0%
- Ajout variable `previousVolume` pour mémoire
- Console.log debugging extensif
- Visual feedback avec indicateur status

**Fix #2: Border-radius disparaissait** :
- Ajout `!important` sur toutes règles CSS
- Application sur container ET vidéo

**Fix #3: Bouton se déplaçait au hover** :
- `flex-shrink: 0` sur bouton (empêche rétrécissement)
- `gap: 0` entre bouton et slider
- Transition opacity/transform au lieu de display none/block
- Hover listener sur parent container (`audioControls`) au lieu d'éléments individuels
- Suppression background card blanc (transparent avec drop-shadow)

**Fix #4: Position bouton** :
- Déplacement de `bottom-right` vers `bottom-center`
- Utilisation `left: 50%; transform: translateX(-50%);`

#### Résultat

**Avant** :
- Liste HTML basique
- Video player HTML5 standard
- Pas de contrôles personnalisés
- Design fade

**Après** :
- Lecteur vidéo moderne style Shorts
- Contrôles audio smooth et intuitifs
- Design cohérent avec reste de la landing page
- UX optimale (hover, transitions, feedback visuel)

**Performance** :
- Autoplay loop en background
- Poster image pour chargement initial
- Contrôles légers (pas de bibliothèque externe)

---

#### 2. Affichage Titre Académique

**Fichier modifié** : `resources/views/esbtp/personnel/unified-index.blade.php` (lignes 658-663)

**Changement** : Affichage du titre académique (M., Mme, Mlle, Dr., Pr.) AVANT le nom complet

**Code** :
```php
<div class="personnel-name">
    @if($teacher->title)
        <span style="font-weight: 500;">{{ $teacher->title }}</span>
    @endif
    {{ $teacher->user->name }}
</div>
```

**Exemples** :
- Avant : `KOUASSI Jean`
- Après : `Dr. KOUASSI Jean` ou `Pr. KOUASSI Jean`

---

#### 3. Option "Mademoiselle" Ajoutée

**Fichiers modifiés** :
- `app/Http/Controllers/ESBTPEnseignantController.php` (lignes 87-93 & 390-396)

**Changement** : Ajout de l'option `'Mlle' => 'Mademoiselle'` dans le tableau `$titres_academiques`

**Liste complète** :
- M. (Monsieur)
- Mme (Madame)
- **Mlle (Mademoiselle)** ← NOUVEAU
- Dr. (Docteur)
- Pr. (Professeur)

---

#### 4. Titre Académique Modifiable

**Fichier modifié** : `resources/views/esbtp/enseignants/edit.blade.php` (lignes 721-737)

**Problème** : Le titre académique était affiché en lecture seule

**Solution** : Remplacement par un select modifiable

**Code** :
```php
<div class="form-group-moderne">
    <label for="titre_academique" class="form-label-moderne">
        Titre Académique
    </label>
    <select name="titre_academique" id="titre_academique"
            class="form-select-moderne @error('titre_academique') is-invalid @enderror">
        <option value="">Sélectionnez un titre</option>
        @foreach($titres_academiques as $key => $value)
            <option value="{{ $key }}" {{ old('titre_academique', $teacher->title) == $key ? 'selected' : '' }}>
                {{ $value }}
            </option>
        @endforeach
    </select>
    @error('titre_academique')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
```

**Backend** : `ESBTPEnseignantController.php` (lignes 440-452, 469-470)
- Validation : `'titre_academique' => 'nullable|string|max:10'`
- Mapping : `'title' => $request->titre_academique`

---

#### 5. Suppression Section Disponibilité

**Fichiers modifiés** :
- `resources/views/esbtp/enseignants/create.blade.php` (lignes 481-501, 844-922)
- `resources/views/esbtp/enseignants/edit.blade.php` (lignes 634-654, 892-965)

**Problème** : Section "Disponibilités" trop complexe pour les pages création/édition

**Solution** :
- Suppression complète de l'étape 4 (Disponibilités)
- Changement `totalSteps` de 5 à 4
- Suppression fonction JavaScript `toggleAvailability()`
- **Disponibilités gérées uniquement dans `enseignants.show`**

**Wizard steps** :
1. Informations personnelles
2. Coordonnées
3. Informations professionnelles
4. ~~Disponibilités~~ ← SUPPRIMÉ
5. Confirmation → devient étape 4

---

#### 6. Message Création Enrichi

**Fichiers modifiés** :
- `app/Http/Controllers/ESBTPEnseignantController.php` (lignes 274-277)
- `resources/views/partials/credentials-modal.blade.php` (lignes 47-71)

**Problème** : Pas de message clair sur où gérer la disponibilité après création

**Solution** : Ajout d'une carte info + bouton "Voir la fiche"

**Controller** :
```php
return redirect()->route('esbtp.personnel.unified.index')
    ->with('credentials', $credentials)
    ->with('created_teacher_id', $teacher->id);  // ← NOUVEAU
```

**Modal credentials** :
```html
@if(session('created_teacher_id'))
<div style="background-color: rgba(59, 130, 246, 0.1); border-radius: var(--radius-small); padding: var(--space-md); margin-bottom: var(--space-lg); border-left: 4px solid var(--primary);">
    <div style="display: flex; align-items: flex-start; gap: var(--space-sm);">
        <i class="fas fa-info-circle" style="color: var(--primary); margin-top: 2px;"></i>
        <div>
            <p style="margin: 0; font-size: var(--text-small); color: var(--text-primary); font-weight: 600;">
                Gestion de la disponibilité
            </p>
            <p style="margin: var(--space-xs) 0 0 0; font-size: var(--text-small); color: var(--text-secondary);">
                Pour gérer la disponibilité de cet enseignant, consultez sa fiche détaillée. Vous pourrez le faire à tout moment.
            </p>
        </div>
    </div>
</div>

<div style="display: flex; gap: var(--space-sm); justify-content: center; flex-wrap: wrap;">
    <button type="button" onclick="printCredentials()" class="btn-acasi secondary" style="flex: 1; min-width: 120px;">
        <i class="fas fa-print" style="margin-right: var(--space-xs);"></i>
        Imprimer
    </button>
    <a href="{{ route('esbtp.enseignants.show', session('created_teacher_id')) }}" class="btn-acasi success" style="flex: 1; min-width: 120px; text-decoration: none;">
        <i class="fas fa-user" style="margin-right: var(--space-xs);"></i>
        Voir la fiche
    </a>
    <button type="button" onclick="closeCredentialsModal()" class="btn-acasi primary" style="flex: 1; min-width: 120px;">
        <i class="fas fa-check" style="margin-right: var(--space-xs);"></i>
        Compris
    </button>
</div>
@endif
```

**Résultat** :
- Carte bleue avec icône info
- Bouton "Voir la fiche" → Redirige vers `enseignants.show`
- Message clair sur la gestion de disponibilité

---

#### 7. Tip Gestion Disponibilité

**Fichier modifié** : `resources/views/esbtp/personnel/unified-index.blade.php` (lignes 645-658)

**Changement** : Ajout d'une carte tip au-dessus de la liste des enseignants

**Code** :
```html
<div style="background-color: rgba(59, 130, 246, 0.1); border-radius: var(--radius-medium); padding: var(--space-md); margin-bottom: var(--space-lg); border-left: 4px solid var(--primary);">
    <div style="display: flex; align-items: flex-start; gap: var(--space-sm);">
        <i class="fas fa-lightbulb" style="color: var(--primary); margin-top: 2px; font-size: 1.2rem;"></i>
        <div>
            <p style="margin: 0; font-size: var(--text-normal); color: var(--text-primary); font-weight: 600;">
                <i class="fas fa-info-circle" style="margin-right: 4px;"></i>Astuce
            </p>
            <p style="margin: var(--space-xs) 0 0 0; font-size: var(--text-small); color: var(--text-secondary);">
                Pour gérer la disponibilité d'un enseignant (horaires, jours disponibles, préférences), consultez sa fiche détaillée en cliquant sur le bouton "Voir détails".
            </p>
        </div>
    </div>
</div>
```

**Avantages** :
- Informe les utilisateurs AVANT de créer un enseignant
- Évite la confusion sur la gestion de disponibilité

---

#### 8. Nettoyage Debugs Conditionnels

**Fichiers modifiés** :
- `resources/views/esbtp/enseignants/edit.blade.php` (lignes 1046-1064)
- `resources/views/esbtp/enseignants/show.blade.php` (lignes 1054-1088, 1246-1267)

**Problème** : Code debug (`console.log`, `alert`) visible en production

**Solution** : Wrapping avec `@if(config('app.debug'))`

**Exemples** :
```php
@if(config('app.debug'))
<script>
    console.log('🎯 Form submission debug');
    console.log('Données:', formData);
</script>
@endif

@if(config('app.debug'))
    fetch('/api/debug')
        .then(response => response.json())
        .then(data => console.log('🔍 AJAX Debug:', data));
@endif
```

**Impact** :
- Console propre en production (`APP_DEBUG=false`)
- Debugging facile en développement (`APP_DEBUG=true`)

---

#### 9. Détection Doublons Enseignants

**Fichiers modifiés** :
- `app/Http/Controllers/ESBTPEnseignantController.php` (lignes 296-335)
- `routes/web.php` (ligne 1275)
- `resources/views/esbtp/enseignants/create.blade.php` (lignes 951-968, 988-1010, 1171-1357)

**Problème** : Risque de créer des enseignants en double

**Solution** : Système de détection AJAX avec fuzzy search

**Backend - Nouvelle méthode** :
```php
public function duplicates(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'specialization' => 'nullable|string|max:255',
    ]);

    $name = $request->input('name');
    $specialization = $request->input('specialization');

    $duplicates = ESBTPTeacher::with('user')
        ->whereHas('user', function($query) use ($name) {
            $query->where('name', 'LIKE', '%' . $name . '%');
        })
        ->when($specialization, function($query) use ($specialization) {
            $query->where('specialization', 'LIKE', '%' . $specialization . '%');
        })
        ->limit(10)
        ->get()
        ->map(function($teacher) {
            return [
                'id' => $teacher->id,
                'name' => $teacher->user->name ?? '',
                'email' => $teacher->user->email ?? '',
                'specialization' => $teacher->specialization,
                'matricule' => $teacher->matricule,
                'status' => $teacher->status,
                'show_url' => route('esbtp.enseignants.show', $teacher->id),
            ];
        });

    return response()->json([
        'duplicates' => $duplicates,
    ]);
}
```

**Route** :
```php
// DOIT ÊTRE AVANT Route::resource
Route::get('enseignants/duplicates', [ESBTPEnseignantController::class, 'duplicates'])
    ->name('enseignants.duplicates');
```

**Frontend** :
- **Debounced input listeners** (500ms) sur champs nom + spécialisation
- **Alert warning** si doublons détectés
- **Modal Bootstrap** avec liste détaillée
- **Hidden input** `duplicate_override` pour forcer création

**Workflow** :
1. Utilisateur tape nom + spécialisation
2. Après 500ms d'inactivité → Requête AJAX
3. Si doublons → Alert jaune + bouton "Voir les doublons"
4. Modal affiche : Nom, Email, Spécialisation, Matricule, Statut
5. Liens "Voir la fiche" pour chaque doublon
6. Bouton "Ignorer et créer quand même" → `duplicate_override = 1`

---

#### 10. Bouton Toggle Status Fonctionnel

**Fichiers modifiés** :
- `app/Http/Controllers/ESBTPEnseignantController.php` (lignes 704-723)
- `app/Http/Controllers/ESBTPSecretaireController.php` (lignes 153-176) ← NOUVEAU
- `routes/web.php` (ligne 1271)
- `resources/views/esbtp/personnel/unified-index.blade.php` (lignes 943-1001)

**Problème** : Bouton "Activer/Désactiver" marqué "En cours de développement"

**Solution** : Implémentation complète AJAX

**Backend Enseignants** :
```php
public function toggleStatus(Request $request, ESBTPTeacher $teacher)
{
    $newStatus = $teacher->status === 'active' ? 'inactive' : 'active';

    $teacher->update([
        'status' => $newStatus,
        'updated_by' => auth()->id(),
    ]);

    // Support AJAX
    if ($request->wantsJson() || $request->ajax()) {
        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour avec succès',
            'new_status' => $newStatus
        ]);
    }

    return redirect()->back()->with('success', 'Statut mis à jour avec succès');
}
```

**Backend Secrétaires** (même logique) :
```php
public function toggleStatus(Request $request, $id)
{
    $secretaire = User::role('secretaire')->findOrFail($id);
    $newStatus = $secretaire->is_active ? 0 : 1;

    $secretaire->update([
        'is_active' => $newStatus,
    ]);

    if ($request->wantsJson() || $request->ajax()) {
        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour avec succès',
            'new_status' => $newStatus ? 'active' : 'inactive'
        ]);
    }

    return redirect()->back()->with('success', 'Statut mis à jour avec succès');
}
```

**Frontend** :
```javascript
function toggleTeacherStatus(teacherId) {
    if (confirm('Êtes-vous sûr de vouloir changer le statut de cet enseignant ?')) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(`/esbtp/enseignants/${teacherId}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();  // Rafraîchir la page
            } else {
                alert('Erreur lors de la mise à jour du statut');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la mise à jour du statut');
        });
    }
}
```

**Routes** :
```php
// Enseignants (déjà existante)
Route::post('enseignants/{teacher}/toggle-status', [ESBTPEnseignantController::class, 'toggleStatus'])
    ->name('enseignants.toggle-status');

// Secrétaires (nouvelle)
Route::post('secretaires/{id}/toggle-status', [ESBTPSecretaireController::class, 'toggleStatus'])
    ->name('secretaires.toggle-status');
```

**Résultat** :
- ✅ Bouton "Activer" fonctionne pour enseignants
- ✅ Bouton "Activer" fonctionne pour secrétaires
- ✅ Confirmation avant action
- ✅ Message de succès
- ✅ Page rafraîchie automatiquement

---

#### Fichiers Modifiés (Récapitulatif)

| Fichier | Type | Lignes modifiées | Changements |
|---------|------|------------------|-------------|
| `ESBTPEnseignantController.php` | Controller | 87-93, 390-396, 440-452, 469-470, 274-277, 296-335, 704-723 | Titre académique, duplicate detection, toggle status |
| `ESBTPSecretaireController.php` | Controller | 153-176 | Toggle status (nouveau) |
| `enseignants/create.blade.php` | View | 458-474, 358-432, 481-501, 844-922, 973, 951-968, 988-1010, 1171-1357 | Header, availability removal, duplicates |
| `enseignants/edit.blade.php` | View | 634-654, 721-737, 892-965, 940, 1046-1064 | Header, title field, availability removal, debug cleanup |
| `enseignants/show.blade.php` | View | 1054-1088, 1246-1267 | Debug cleanup |
| `coordinateurs/create.blade.php` | View | 206-221, 122-196 | Header unification |
| `personnel/unified-index.blade.php` | View | 658-663, 645-658, 943-1001 | Title display, tip, toggle status |
| `credentials-modal.blade.php` | Partial | 47-71 | Availability tip, "View teacher" button |
| `web.php` | Routes | 1275, 1271 | Duplicates route, secretaire toggle |

---

#### Améliorations UX

✅ **Cohérence visuelle** : Headers unifiés entre toutes les pages
✅ **Informations complètes** : Titre académique affiché partout
✅ **Workflow simplifié** : Disponibilités gérées dans fiche détaillée uniquement
✅ **Guidance utilisateur** : Tips et messages clairs
✅ **Prévention doublons** : Détection automatique nom + spécialisation
✅ **Actions fonctionnelles** : Toggle status opérationnel pour enseignants ET secrétaires
✅ **Code propre** : Debug masqué en production

---

### 📝 Modal Édition Rapide Étudiants & Inscriptions (6 novembre)

**Page** : `/esbtp/etudiants` (index)

**Problème** : Besoin d'une interface d'édition rapide sans quitter la page de liste, avec accès aux informations de l'étudiant et de ses inscriptions.

**Solution implémentée** : Modal moderne avec onglets pour édition étudiant et inscriptions via iframes.

#### Architecture Modal

**Fichier principal** : `resources/views/esbtp/etudiants/index.blade.php`

**Structure** :
- Modal Bootstrap avec design moderne (80vw × 80vh)
- 2 onglets : "Étudiant" et "Inscriptions"
- Contenu chargé via iframes pour isolation complète
- Accordéon pour les inscriptions multiples

#### Composants Créés

**1. Vues Embedded (mode iframe)**

**Nouveaux fichiers** :
- `resources/views/esbtp/etudiants/embed/edit.blade.php` - Version iframe de l'édition étudiant
- `resources/views/esbtp/inscriptions/embed/edit.blade.php` - Version iframe de l'édition inscription
- `resources/views/layouts/embedded.blade.php` - Layout minimaliste pour iframes

**Partials extraits** :
- `resources/views/esbtp/etudiants/partials/edit-form.blade.php` - Formulaire étudiant réutilisable
- `resources/views/esbtp/etudiants/partials/edit-form-scripts.blade.php` - Scripts formulaire étudiant
- `resources/views/esbtp/inscriptions/partials/edit-form.blade.php` - Formulaire inscription réutilisable
- `resources/views/esbtp/inscriptions/partials/edit-form-scripts.blade.php` - Scripts formulaire inscription

#### Modifications Controllers

**ESBTPStudentController.php** :
```php
public function edit(Request $request, ESBTPEtudiant $etudiant)
{
    // ... chargement relations

    // Détection mode embedded
    if ($request->boolean('embedded')) {
        return view('esbtp.etudiants.embed.edit', compact('etudiant'));
    }

    return view('esbtp.etudiants.edit', compact(...));
}
```

**ESBTPEtudiantController.php** :
```php
public function update(Request $request, ESBTPEtudiant $etudiant)
{
    // ... validation et mise à jour

    // Redirection différente en mode embedded
    if ($request->boolean('embedded_mode')) {
        return redirect()
            ->route('esbtp.etudiants.edit', ['etudiant' => $etudiant->id, 'embedded' => 1])
            ->with('embedded_success_student', $successMessage);
    }

    return redirect()->route('esbtp.etudiants.show', $etudiant->id)
        ->with('success', $successMessage);
}
```

**ESBTPInscriptionController.php** (même logique) :
```php
public function edit(Request $request, ESBTPInscription $inscription)
{
    if ($request->boolean('embedded')) {
        return view('esbtp.inscriptions.embed.edit', compact(...));
    }
    // ...
}

public function update(Request $request, ESBTPInscription $inscription)
{
    if ($request->boolean('embedded_mode')) {
        return redirect()
            ->route('esbtp.inscriptions.edit', ['inscription' => $inscription->id, 'embedded' => 1])
            ->with('embedded_success_inscription', $successMessage);
    }
    // ...
}
```

#### Design Modal Moderne

**Dimensions** :
```css
.modal-modern .modal-dialog {
    width: clamp(1024px, 80vw, 1800px);  /* Large sur grands écrans */
    height: 80vh;
    margin: 10vh auto;
}
```

**Responsive** :
- **> 1400px** : 80vw (max 1800px)
- **1200-1400px** : 85vw
- **992-1200px** : 90vw × 85vh
- **< 992px** : 95vw × 90vh

**Tabs Bootstrap** :
```css
.student-tabs-container .nav-link.active {
    background: #ffffff;
    font-weight: 700;
    box-shadow: 0 -2px 20px rgba(15, 23, 42, 0.12);
}

.modern-tab-content {
    background: #ffffff;
    border-radius: 0 16px 16px 16px;
    flex: 1;
    overflow: hidden;  /* Pas de scrollbar sur le modal */
}
```

**Gestion affichage onglets** :
```css
/* Par défaut tous cachés */
.modern-tab-content .tab-pane {
    display: none;
}

/* Seulement l'onglet actif visible */
.modern-tab-content .tab-pane.show.active {
    display: flex;
    flex: 1;
    flex-direction: column;
}
```

#### Iframe Étudiant

```css
.modal-iframe-wrapper {
    background: #ffffff;
    width: 100%;
    height: 100%;
    flex: 1;
}

.modal-iframe-wrapper iframe {
    width: 100%;
    height: 100%;
    border: none;
}
```

**Chargement lazy** :
```javascript
const separator = payload.edit_url.includes('?') ? '&' : '?';
studentFrame.src = `${payload.edit_url}${separator}embedded=1&_=${Date.now()}`;
```

#### Accordéon Inscriptions

**Génération dynamique** :
```javascript
function renderInscriptionsAccordion(payload) {
    const inscriptions = payload?.inscriptions ?? [];

    const items = inscriptions.map((inscription, index) => `
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button ${index === 0 ? '' : 'collapsed'}"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapse-${inscription.id}">
                    <i class="fas fa-graduation-cap me-2"></i>
                    ${inscription.annee} - ${inscription.classe ?? 'Non affectée'}
                    <span class="badge bg-${inscription.status_color} ms-auto">
                        ${inscription.status}
                    </span>
                </button>
            </h2>
            <div id="collapse-${inscription.id}"
                 class="accordion-collapse collapse ${index === 0 ? 'show' : ''}"
                 data-bs-parent="#inscriptions-accordion">
                <div class="accordion-body">
                    <div class="modal-iframe-wrapper">
                        <iframe class="inscription-frame"
                                data-src="${inscription.edit_url}?embedded=1"
                                loading="lazy"></iframe>
                    </div>
                </div>
            </div>
        </div>
    `).join('');

    inscriptionsContainer.innerHTML =
        `<div class="accordion accordion-modern">${items}</div>`;
}
```

**Hauteur iframes accordéon** :
```css
.accordion-modern .accordion-body .modal-iframe-wrapper {
    min-height: 500px;  /* Hauteur minimum garantie */
    height: 60vh;       /* 60% viewport sur grands écrans */
}

@media (max-width: 992px) {
    .accordion-modern .accordion-body .modal-iframe-wrapper {
        height: 50vh;  /* Plus compact sur mobile */
    }
}
```

**Chargement iframe au clic** :
```javascript
function attachAccordionListeners(container) {
    container.querySelectorAll('.accordion-button').forEach(button => {
        button.addEventListener('shown.bs.collapse', () => {
            const iframe = button.closest('.accordion-item')
                                 .querySelector('.inscription-frame');
            if (iframe && !iframe.src) {
                const separator = iframe.dataset.src.includes('?') ? '&' : '?';
                iframe.src = `${iframe.dataset.src}${separator}_=${Date.now()}`;
            }
        }, { once: true });
    });
}
```

#### Layout Embedded

**Fichier** : `resources/views/layouts/embedded.blade.php`

**Caractéristiques** :
- Header minimaliste (pas de menu, pas de sidebar)
- Formulaire prend toute la largeur
- Messages success/error intégrés
- Redirection interne (pas de navigation externe)

```blade
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <!-- CSS uniquement -->
</head>
<body style="background: #f8fafc;">
    @if(session('embedded_success_student') || session('embedded_success_inscription'))
        <div class="alert alert-success">
            {{ session('embedded_success_student') ?? session('embedded_success_inscription') }}
        </div>
    @endif

    @yield('content')

    <!-- Scripts uniquement -->
</body>
</html>
```

#### Refactorisation Code

**Avant** : Formulaires dupliqués dans `edit.blade.php` et vues embedded

**Après** : Extraction dans partials réutilisables

**edit.blade.php** (version normale) :
```blade
@extends('layouts.app')

@section('content')
    <div class="main-header"><!-- ... --></div>
    @include('esbtp.etudiants.partials.edit-form', [
        'etudiant' => $etudiant,
        'isEmbedded' => false
    ])
@endsection

@push('scripts')
    @include('esbtp.etudiants.partials.edit-form-scripts')
@endpush
```

**embed/edit.blade.php** (version iframe) :
```blade
@extends('layouts.embedded')

@section('content')
    @include('esbtp.etudiants.partials.edit-form', [
        'etudiant' => $etudiant,
        'isEmbedded' => true
    ])

    @include('esbtp.etudiants.partials.edit-form-scripts')
@endsection
```

**Paramètre `isEmbedded`** :
- `true` → Formulaire avec `embedded_mode` hidden input
- `false` → Formulaire normal

#### Bouton Ouverture Modal

**Fichier** : `resources/views/esbtp/etudiants/partials/results.blade.php`

**Ajout data-student** :
```blade
<button type="button"
        class="btn btn-sm btn-primary btn-open-edit-modal"
        data-student='@json([
            "id" => $etudiant->id,
            "matricule" => $etudiant->matricule,
            "name" => $etudiant->nom_complet,
            "edit_url" => route('esbtp.etudiants.edit', $etudiant->id),
            "inscriptions" => $etudiant->inscriptions->map(fn($i) => [
                "id" => $i->id,
                "annee" => $i->annee_universitaire->display_name ?? "",
                "classe" => $i->classe->name ?? null,
                "status" => $i->status,
                "status_color" => match($i->status) {
                    "validée" => "success",
                    "en_attente" => "warning",
                    default => "secondary"
                },
                "edit_url" => route('esbtp.inscriptions.edit', $i->id)
            ])
        ])'>
    <i class="fas fa-edit"></i> Modifier
</button>
```

**Event listener** :
```javascript
resultsContainer.addEventListener('click', function (event) {
    const trigger = event.target.closest('.btn-open-edit-modal');
    if (!trigger) return;

    event.preventDefault();
    openEditModal(trigger.getAttribute('data-student'));
});
```

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

#### Fichiers Modifiés/Créés

| Fichier | Type | Changements |
|---------|------|-------------|
| `ESBTPStudentController.php` | Controller | +4 lignes (embedded mode detection) |
| `ESBTPEtudiantController.php` | Controller | +10 lignes (embedded redirect) |
| `ESBTPInscriptionController.php` | Controller | +22 lignes (embedded mode support) |
| `etudiants/index.blade.php` | View | +170 lignes (modal + CSS + JS) |
| `etudiants/edit.blade.php` | View | -703 lignes (extraction partials) |
| `etudiants/partials/edit-form.blade.php` | Partial | +700 lignes (formulaire extrait) |
| `etudiants/partials/edit-form-scripts.blade.php` | Partial | +450 lignes (scripts extraits) |
| `etudiants/embed/edit.blade.php` | View | +20 lignes (vue iframe) |
| `inscriptions/edit.blade.php` | View | -370 lignes (extraction partials) |
| `inscriptions/partials/edit-form.blade.php` | Partial | +360 lignes (formulaire extrait) |
| `inscriptions/partials/edit-form-scripts.blade.php` | Partial | +200 lignes (scripts extraits) |
| `inscriptions/embed/edit.blade.php` | View | +18 lignes (vue iframe) |
| `layouts/embedded.blade.php` | Layout | +50 lignes (layout minimaliste) |
| `etudiants/partials/results.blade.php` | Partial | +167 lignes (bouton + data) |

**Total** :
- **7 fichiers modifiés** (-1210 lignes nettes après extraction)
- **7 nouveaux fichiers** (+1798 lignes de code réutilisable)
- **Résultat** : Code mieux organisé, +588 lignes de fonctionnalités

---

### 🐛 Corrections Bugs Inscriptions & Filtres (6 novembre)

**Contexte** : Deux bugs critiques identifiés après l'implémentation du modal d'édition rapide.

#### Bug #1 : Bouton "Valider Définitivement" Affiché à Tort

**Page** : `/esbtp/inscriptions/{id}` (show)

**Problème** :
Le bouton "Valider définitivement" s'affichait même pour les inscriptions qui avaient atteint l'étape finale du workflow (`etudiant_cree`), ce qui n'avait pas de sens car il n'y a plus d'étape de validation après la création de l'étudiant.

**Workflow complet des inscriptions** :
```
prospect → documents_complets → en_validation → valide → etudiant_cree (final)
```

**Code avant** :
```php
@if($inscription->paiement_validation_id && $inscription->workflow_step === 'en_validation')
    <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#validationModal">
        <i class="fas fa-check"></i>Valider définitivement
    </button>
@endif
```

**Problème** : Le bouton ne s'affichait QUE pour `en_validation`, alors qu'il devrait s'afficher pour TOUTES les étapes SAUF `etudiant_cree`.

**Solution** :
```php
@if(!($inscription->status === 'active' && $inscription->workflow_step === 'etudiant_cree'))
    <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#validationModal">
        <i class="fas fa-check"></i>Valider définitivement
    </button>
@endif
```

**Logique finale simplifiée** :
- ✅ Le bouton s'affiche dans TOUS les cas SAUF quand `status='active' ET workflow_step='etudiant_cree'`
- ✅ S'affiche même pour `en_attente`, `prospect`, `documents_complets`, `en_validation`, `valide`
- ✅ S'affiche même si `status='active'` mais que `workflow_step` n'est pas encore `etudiant_cree`
- ✅ **CACHÉ UNIQUEMENT** quand inscription finalisée (`active` + `etudiant_cree`)

**Fichier modifié** : `resources/views/esbtp/inscriptions/show.blade.php` (lignes 250-254)

---

#### Bug #2 : Filtre Classe + Année Incorrecte

**Page** : `/esbtp/etudiants` (index - Gestion Étudiants)

**Problème** :
Lorsqu'un utilisateur sélectionnait simultanément un filtre **Classe** ET un filtre **Année universitaire**, le système affichait AUSSI les étudiants qui avaient été dans cette classe mais dans une AUTRE année universitaire.

**Exemple concret** :
- Filtre : Classe = "L3 GC" + Année = "2024/2025"
- Résultat incorrect : Affichait aussi un étudiant qui était en L3 GC en 2023/2024

**Cause racine** :
Le code avait deux `whereHas('inscriptions')` séparés, chacun vérifiant des conditions sur potentiellement DIFFÉRENTES inscriptions de l'étudiant.

**Code avant** :
```php
if ($classe) {
    $baseQuery->whereHas('inscriptions', function ($q) use ($classe) {
        $q->where('classe_id', $classe);
    });
}

if ($annee) {
    $baseQuery->whereHas('inscriptions', function ($q) use ($annee) {
        $q->where('annee_universitaire_id', $annee);
    });
}
```

**Problème** : Ces deux `whereHas` indépendants vérifient des inscriptions DIFFÉRENTES :
- Premier whereHas : "L'étudiant a-t-il UNE inscription en L3 GC ?" → OUI (2023/2024)
- Deuxième whereHas : "L'étudiant a-t-il UNE inscription en 2024/2025 ?" → OUI (L2 GC)
- Résultat : L'étudiant passe les deux filtres même si aucune inscription ne respecte TOUS les critères simultanément

**Solution** :
Fusionner tous les filtres dans un SEUL `whereHas` pour garantir que les conditions s'appliquent à la MÊME inscription.

```php
if ($filiere || $niveau || $annee || $classe) {
    $baseQuery->whereHas('inscriptions', function ($q) use ($filiere, $niveau, $annee, $classe) {
        if ($filiere) {
            $q->where('filiere_id', $filiere);
        }
        if ($niveau) {
            $q->where('niveau_id', $niveau);
        }
        if ($annee) {
            $q->where('annee_universitaire_id', $annee);
        }
        if ($classe) {
            $q->where('classe_id', $classe);
        }
    });
}
```

**Logique corrigée** :
- ✅ UN SEUL `whereHas('inscriptions')` pour tous les filtres académiques
- ✅ Toutes les conditions (`filiere`, `niveau`, `annee`, `classe`) s'appliquent à la MÊME inscription
- ✅ Un étudiant n'est retourné QUE s'il a UNE inscription qui respecte TOUS les critères simultanément

**Fichier modifié** : `app/Http/Controllers/ESBTPStudentController.php` (lignes 73-88)

---

#### Impact des Corrections

**Bug #1 - Bouton "Valider Définitivement"** :
- ✅ Plus d'affichage inutile du bouton pour inscriptions finalisées
- ✅ Workflow clair : le bouton disparaît après création étudiant
- ✅ UX améliorée : moins de confusion pour les utilisateurs

**Bug #2 - Filtre Classe + Année** :
- ✅ Résultats de recherche précis et cohérents
- ✅ Respect de la logique métier (inscription = classe + année indissociables)
- ✅ Performance identique (pas de requêtes supplémentaires)

**Fichiers Modifiés (Récapitulatif)** :

| Fichier | Lignes | Type Correction |
|---------|--------|-----------------|
| `resources/views/esbtp/inscriptions/show.blade.php` | 250-256 | Logique d'affichage conditionnelle |
| `app/Http/Controllers/ESBTPStudentController.php` | 73-88 | Requête Eloquent (fusion whereHas) |

---

#### Bug #3 : Route AJAX Chemin Incorrect

**Page** : `/esbtp/etudiants` (index - Modal édition rapide)

**Problème** :
L'appel AJAX pour charger toutes les inscriptions d'un étudiant échouait silencieusement - le modal s'ouvrait mais n'affichait que l'inscription de l'année courante au lieu de toutes les inscriptions.

**Symptôme utilisateur** :
> "Quand j'appuie sur le modal edition rapide je ne vois toujours pas toutes les inscriptions, ici on met reinscription donc normalement il doit avoir la premiere inscription mais je ne vois pas"

**Cause racine** :
La route AJAX était définie au mauvais endroit dans `routes/web.php` :
- Route définie ligne 1291 : `Route::get('etudiants/{etudiant}/all-inscriptions', ...)`
- Mais la page est accessible via le préfixe `/esbtp/etudiants/`
- JavaScript appelait `/etudiants/${payload.id}/all-inscriptions` qui n'existe pas dans le contexte `/esbtp/`

**Solution implémentée** :

1. **Déplacement de la route** (routes/web.php ligne 1663) :
```php
// Route AJAX pour charger toutes les inscriptions d'un étudiant (modal édition rapide) - AVANT Route::resource
Route::get('esbtp/etudiants/{etudiant}/all-inscriptions', [ESBTPStudentController::class, 'getAllInscriptions'])
    ->name('esbtp.etudiants.all-inscriptions');
```

**IMPORTANT** : Cette route DOIT être placée AVANT `Route::resource('esbtp/etudiants', ...)` sinon les routes resource interceptent la requête.

2. **Correction URL JavaScript** (etudiants/index.blade.php ligne 1106) :
```javascript
// AVANT
fetch(`/etudiants/${payload.id}/all-inscriptions`, {

// APRÈS
fetch(`/esbtp/etudiants/${payload.id}/all-inscriptions`, {
```

3. **Suppression route dupliquée** :
   - Supprimé l'ancienne route incorrecte ligne 1290-1292

**Résultat** :
- ✅ L'appel AJAX fonctionne correctement
- ✅ Le modal affiche maintenant TOUTES les inscriptions de l'étudiant (toutes années confondues)
- ✅ Indicateur "Année courante" visible pour l'inscription active
- ✅ Performance optimisée : chargement uniquement à l'ouverture du modal

**Fichiers modifiés** :

| Fichier | Lignes | Modifications |
|---------|--------|---------------|
| `routes/web.php` | 1663-1664 | Route déplacée au bon endroit + suppression doublon |
| `resources/views/esbtp/etudiants/index.blade.php` | 1106 | Correction URL fetch AJAX |

---

#### Simplification Modal - Suppression Bouton "Valider Définitivement"

**Page** : `/esbtp/etudiants` (index - Modal édition rapide)

**Problème** :
Le bouton "Valider définitivement" dans le modal d'édition rapide ne fonctionnait pas - aucune action ne se produisait au clic.

**Symptôme utilisateur** :
> "le bouton valider definitivement dans le modal edition rapide ne fonctionne pas je n'ai pas accès au modal pour valider, quand j'appuie sur le bouton rien en se passe"

**Cause racine** :
Le bouton "Valider définitivement" essayait d'exécuter une action complexe (ouvrir un modal de validation, appeler une API, etc.) dans un contexte d'iframe imbriqué (modal Bootstrap contenant des iframes pour chaque inscription). Ce type d'interaction nécessite une gestion complexe des événements entre contextes parent/enfant qui n'était pas implémentée.

**Solution adoptée** :
Suppression complète du bouton "Valider définitivement" du modal d'édition rapide. L'utilisateur peut :
1. **Utiliser le bouton "Voir l'inscription"** qui ouvre la page complète de l'inscription dans un nouvel onglet
2. **Valider l'inscription depuis la page dédiée** `esbtp/inscriptions/{id}/show` qui a le modal de validation complet

**Avantages** :
- ✅ Suppression d'une fonctionnalité non fonctionnelle (meilleure UX que bouton cassé)
- ✅ Simplifie le code (suppression de 59 lignes)
- ✅ Workflow clair : édition rapide pour modifications mineures, page complète pour actions critiques
- ✅ Évite complexité gestion événements inter-contextes (iframe → parent → modal)

**Code supprimé** :

**Fichier** : `resources/views/esbtp/etudiants/index.blade.php`

1. **Logique conditionnelle bouton** (lignes 1010-1020) :
```javascript
const canValidate = !(inscription.status === 'active' && inscription.workflow_step === 'etudiant_cree');

const validationButton = canValidate ? `
    <button type="button"
            class="btn btn-success btn-sm mt-3"
            onclick="handleValidateInscription(${inscription.id}, '${inscription.validate_url}')">
        <i class="fas fa-check me-2"></i>Valider définitivement
    </button>
` : '';
```

2. **Référence dans template** (ligne 1040) :
```javascript
${validationButton}
```

3. **Fonction handler** (lignes 942-986) :
```javascript
function handleValidateInscription(inscriptionId, validateUrl) {
    // 44 lignes de code AJAX pour validation
}
```

**Workflow utilisateur recommandé** :

1. Ouvrir modal édition rapide pour modifications mineures (nom, téléphone, etc.)
2. Cliquer "Voir l'inscription" pour actions critiques (validation, etc.)
3. Page complète s'ouvre dans nouvel onglet avec bouton "Valider définitivement" fonctionnel

**Fichiers modifiés** :

| Fichier | Lignes supprimées | Description |
|---------|-------------------|-------------|
| `resources/views/esbtp/etudiants/index.blade.php` | 1010-1020, 1040, 942-986 | Suppression bouton + fonction (59 lignes) |

---

### 📱 Page Étudiants Index - Responsive Design Complet (6 novembre)

**Page** : `/esbtp/etudiants` (index - Gestion des étudiants)

**Objectif** : Rendre la page complètement responsive pour tous les appareils (desktop, tablette, mobile, petit mobile).

**Problème** :
La page avait quelques media queries basiques mais l'expérience mobile était sous-optimale :
- Filtres trop larges sur mobile
- Tableau avec trop de colonnes visibles
- Modal trop grand pour les petits écrans
- Boutons actions difficiles à cliquer
- Textes trop grands/petits selon l'appareil

**Solution implémentée** : Media queries complètes avec 5 breakpoints principaux.

#### Breakpoints et Adaptations

**1. Desktop (> 1200px)** : Design par défaut
- Filtres en grille 3 colonnes (col-md-4)
- Modal 80vw × 80vh
- Toutes colonnes table visibles
- Boutons header horizontaux

**2. Tablette (≤ 992px)** :
```css
/* Filtres */
- Tous les filtres passent en 100% largeur (1 colonne)
- Gap entre champs : 1rem
- Boutons "Filtrer/Réinitialiser" en colonne pleine largeur

/* Header */
- Titre réduit : 1.75rem
- Sous-titre : 0.9rem
- Boutons actions en colonne verticale (width: 100%)

/* Modal */
- Largeur : 95vw × 90vh
- Padding réduit : 16px/20px
- Accordion iframe : 50vh (au lieu de 60vh)

/* Table */
- Padding cellules : 0.5rem
- Font-size : 0.875rem
- Photos : 40px (au lieu de 50px)
- Boutons actions compacts
```

**3. Mobile paysage (≤ 768px)** :
```css
/* Textes */
- Titre : 1.5rem
- Labels : 0.875rem
- Inputs : 0.875rem, padding 8px 12px

/* Modal tabs */
- 2 tabs côte à côte (flex: 1 1 50%)
- Font-size : 0.85rem
- Icônes : 12px

/* Table */
- Padding cellules : 0.4rem
- Font-size : 0.8rem
- Photos : 35px
- Actions en colonne (width: 100%)
```

**4. Mobile portrait (≤ 576px)** :
```css
/* Header */
- Titre : 1.25rem
- Sous-titre : 0.8rem

/* Card */
- Padding : 1rem (au lieu de 1.5rem)

/* Modal fullscreen */
- 100vw × 100vh (plein écran)
- Border-radius : 0 (pas d'arrondi)
- Margin : 0

/* Tabs verticaux */
- Nav-tabs en colonne (flex-direction: column)
- Chaque tab : 100% largeur

/* Table - Colonnes cachées */
- Photo (col 2) : display: none
- Genre (col 4) : display: none
- Résidence (col 6) : display: none
- Date inscription (col 8) : display: none
- Garde : Matricule, Nom, Contact, Classe, Statut affectation, Statut, Actions

/* Formulaire */
- Labels : 0.8rem
- Inputs : 0.8rem, padding 6px 10px, min-height 36px
- Boutons : 0.8rem, padding 8px 14px
```

**5. Petit mobile (≤ 400px)** :
```css
/* Textes ultra-compacts */
- Titre : 1.1rem
- Section title : 0.85rem
- Inputs : 0.75rem, padding 6px 8px
- Boutons : 0.75rem

/* Card padding minimal */
- 0.75rem partout

/* Table - Colonne supplémentaire cachée */
- Statut affectation (col 9) : display: none
- Garde uniquement : Matricule, Nom, Contact, Classe, Statut, Actions
```

#### Améliorations Spécifiques

**Formulaire de filtres** :
```css
/* Breakpoint 992px */
.row > [class*='col-'] {
    width: 100%;
    flex: 0 0 100%;
    max-width: 100%;
}

/* Boutons en colonne */
.col-md-4.d-flex.align-items-end {
    flex-direction: column !important;
}

.btn-acasi {
    width: 100%;
    margin-bottom: 0.5rem;
}
```

**Searchable Select Component** :
```css
@media (max-width: 576px) {
    .searchable-select-dropdown {
        max-height: 60vh; /* Évite débordement écran */
    }

    .searchable-select-icon {
        font-size: 0.8rem;
        right: 10px;
    }

    .searchable-select-option {
        padding: 10px 12px;
        font-size: 0.85rem;
    }
}
```

**Table responsive progressive** :
```css
/* Tablette - scroll horizontal */
@media (max-width: 992px) {
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch; /* Smooth scroll iOS */
    }
}

/* Mobile - cacher colonnes non-essentielles */
@media (max-width: 576px) {
    /* nth-child(2,4,6,8) : display: none */
}

/* Petit mobile - cacher encore plus */
@media (max-width: 400px) {
    /* nth-child(9) : display: none */
}
```

**Modal d'édition rapide** :
```css
/* Mobile portrait */
@media (max-width: 576px) {
    .modal-modern .modal-dialog {
        width: 100vw;
        max-width: 100vw;
        height: 100vh;
        max-height: 100vh;
        margin: 0;
        border-radius: 0; /* Fullscreen sans bordure */
    }

    /* Tabs en pile verticale */
    .student-tabs-container .nav-tabs {
        flex-direction: column;
    }

    .student-tabs-container .nav-link {
        border-radius: 12px !important;
        margin-bottom: 4px;
    }
}
```

**Accordion inscriptions** :
```css
@media (max-width: 576px) {
    .accordion-modern .accordion-button {
        padding: 12px;
        font-size: 0.85rem;
    }

    .accordion-modern .accordion-body .modal-iframe-wrapper {
        min-height: 300px;
        height: 40vh; /* Réduit pour mobile */
    }
}
```

#### Résultats

**Desktop (1920px)** :
- ✅ Design complet avec toutes colonnes
- ✅ Modal spacieux 80vw
- ✅ Filtres en grille 3 colonnes

**Tablette (768px)** :
- ✅ Filtres empilés en 1 colonne
- ✅ Modal 95vw adapté
- ✅ Table scroll horizontal
- ✅ Boutons actions compacts

**Mobile (375px - iPhone)** :
- ✅ Modal fullscreen
- ✅ 5 colonnes cachées (photo, genre, résidence, date, affectation)
- ✅ Tabs verticaux
- ✅ Textes et inputs lisibles
- ✅ Boutons pleine largeur

**Petit mobile (360px - Galaxy)** :
- ✅ 6 colonnes cachées
- ✅ Textes ultra-compacts mais lisibles
- ✅ Card padding minimal (0.75rem)
- ✅ Tout accessible sans zoom

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

#### Performance

**Avantages** :
- ✅ CSS pur (pas de JS pour responsive)
- ✅ Progressive enhancement
- ✅ Touch-friendly sur mobile (boutons 100% largeur)
- ✅ Scroll smooth natif iOS (`-webkit-overflow-scrolling`)
- ✅ Pas de recalcul layout (uniquement display/padding)

**Tests effectués** :
- ✅ Chrome DevTools responsive mode
- ✅ iPhone 12 Pro (375px)
- ✅ iPad Air (820px)
- ✅ Desktop 1920px

**Fichiers modifiés** :

| Fichier | Lignes ajoutées | Description |
|---------|-----------------|-------------|
| `resources/views/esbtp/etudiants/index.blade.php` | +400 | Media queries complètes (5 breakpoints) |

**Breakpoints summary** :
- 992px : Tablette (filtres 1 col, modal 95vw)
- 768px : Mobile paysage (textes réduits, tabs 50%)
- 576px : Mobile portrait (modal fullscreen, tabs verticaux, colonnes cachées)
- 400px : Petit mobile (colonnes supplémentaires cachées, ultra-compact)

---

### 🛠️ Scripts d'Initialisation Unifiés (6 novembre)

**Contexte** : Besoin d'un système unifié pour orchestrer tous les scripts d'initialisation et seeders nécessaires au déploiement de KLASSCI.

**Problème** :
- 4 scripts PHP séparés (`init_storage.php`, `fix_permissions.php`, `deploy_settings.php`, `create_storage_link.php`)
- 3 seeders critiques à exécuter manuellement
- Pas de tracking de l'état d'initialisation
- Risque d'oublier des étapes lors du déploiement

**Solution implémentée** : Système d'orchestration avec tracking automatique.

#### Architecture

**3 composants principaux** :

1. **setup.php** - Orchestrateur principal
2. **verify.php** - Script de vérification
3. **.setup.lock** - Fichier de tracking JSON

#### 1. setup.php - Orchestrateur Principal

**Fichier** : `/setup.php` (684 lignes)

**Fonctionnalités** :
- ✅ Exécution automatique de tous les scripts dans l'ordre correct
- ✅ Exécution de tous les seeders critiques
- ✅ Tracking détaillé de chaque étape
- ✅ Mode interactif avec confirmations
- ✅ Options granulaires (--only, --skip, --force)
- ✅ Gestion des erreurs avec rollback partiel
- ✅ Interface CLI moderne avec codes couleur ANSI

**Ordre d'exécution (dépendances respectées)** :
```bash
1. storage      → init_storage.php (structure dossiers + symlinks)
2. permissions  → fix_permissions.php (210 permissions Spatie)
3. settings     → deploy_settings.php (paramètres système)
4. seeders      → ChatbotSeeder, ServiceTechniqueSeeder, SettingsSeeder
```

**Options CLI** :

| Option | Description | Exemple |
|--------|-------------|---------|
| `--interactive` / `-i` | Mode interactif avec confirmations | `php setup.php -i` |
| `--force` / `-f` | Réexécuter même si déjà fait | `php setup.php --force` |
| `--only=<step>` | Exécuter seulement certaines étapes | `php setup.php --only=storage,permissions` |
| `--skip=<step>` | Sauter certaines étapes | `php setup.php --skip=seeders` |

**Code clé - Vérification état initial** :
```php
private function isFullySetup(): bool
{
    $required = ['storage', 'permissions', 'settings', 'seeders'];

    foreach ($required as $key) {
        if ($key === 'seeders') {
            $criticalSeeders = ['ChatbotSeeder', 'ServiceTechniqueSeeder', 'SettingsSeeder'];
            foreach ($criticalSeeders as $seeder) {
                if (!isset($this->lockData['seeders'][$seeder]) ||
                    $this->lockData['seeders'][$seeder]['status'] !== 'success') {
                    return false;
                }
            }
        } else {
            if (!isset($this->lockData[$key]) || $this->lockData[$key]['status'] !== 'success') {
                return false;
            }
        }
    }

    return true;
}
```

**Gestion des erreurs** :
```php
try {
    $this->runStorage();
    $this->lockData['storage'] = [
        'status' => 'success',
        'date' => date('Y-m-d H:i:s'),
        'errors' => []
    ];
} catch (Exception $e) {
    $this->lockData['storage'] = [
        'status' => 'failed',
        'date' => date('Y-m-d H:i:s'),
        'errors' => [$e->getMessage()]
    ];
    throw $e;
}
```

---

#### 2. verify.php - Script de Vérification

**Fichier** : `/verify.php` (553 lignes)

**Fonctionnalités** :
- ✅ Vérification complète de l'état du système
- ✅ Lecture du fichier `.setup.lock` pour historique
- ✅ Vérifications physiques (fichiers, dossiers, symlinks)
- ✅ Suggestions de correction automatiques
- ✅ Export JSON pour CI/CD
- ✅ Affichage détaillé avec `--verbose`

**Vérifications effectuées** :

| Vérification | Description | Sévérité |
|--------------|-------------|----------|
| `lock_file` | Existence et validité du `.setup.lock` | Critical |
| `storage` | Dossiers + lien symbolique `public/storage` | Critical |
| `permissions` | Permissions Spatie configurées | Critical |
| `settings` | Paramètres système déployés | High |
| `seeders` | 3 seeders critiques exécutés | High |
| `database` | Connexion DB et configuration `.env` | Info |

**Options CLI** :

| Option | Description | Exemple |
|--------|-------------|---------|
| `--verbose` / `-v` | Affichage détaillé | `php verify.php --verbose` |
| `--fix` | Suggère commandes de correction | `php verify.php --fix` |
| `--json` | Output JSON pour CI/CD | `php verify.php --json` |

**Code clé - Vérification stockage** :
```php
private function verifyStorage(): void
{
    $checks = [
        'storage/app/public' => 'Dossier de stockage principal',
        'storage/app/public/photos' => 'Dossier photos',
        'storage/app/public/logos' => 'Dossier logos',
        'storage/app/public/documents' => 'Dossier documents',
        'public/storage' => 'Lien symbolique storage'
    ];

    $allOk = true;
    $details = [];

    foreach ($checks as $path => $description) {
        $fullPath = $this->baseDir . '/' . $path;
        $exists = file_exists($fullPath);

        if (!$exists) {
            $this->error("  ❌ $description manquant: $path");
            $details[$path] = 'missing';
            $allOk = false;
        } else {
            $details[$path] = 'ok';
        }
    }

    $this->results['storage'] = [
        'status' => $allOk ? 'ok' : 'error',
        'message' => $allOk ? 'Stockage correctement configuré' : 'Problèmes de stockage détectés',
        'severity' => $allOk ? 'info' : 'critical',
        'details' => $details
    ];
}
```

**Export JSON** :
```json
{
  "ready": false,
  "timestamp": "2025-11-06 19:30:00",
  "results": {
    "lock_file": {
      "status": "missing",
      "message": "Fichier .setup.lock introuvable",
      "severity": "critical"
    },
    "storage": {
      "status": "error",
      "message": "Problèmes de stockage détectés",
      "severity": "critical",
      "details": {
        "storage/app/public": "ok",
        "public/storage": "missing"
      }
    }
  }
}
```

---

#### 3. .setup.lock - Fichier de Tracking

**Format** : JSON

**Localisation** : `/. setup.lock` (racine projet)

**Gitignore** : ✅ Ajouté automatiquement (spécifique à chaque environnement)

**Structure** :
```json
{
  "version": "1.0",
  "last_run": "2025-11-06 19:00:00",
  "storage": {
    "status": "success",
    "date": "2025-11-06 19:00:05",
    "errors": []
  },
  "permissions": {
    "status": "success",
    "date": "2025-11-06 19:01:23",
    "errors": []
  },
  "settings": {
    "status": "success",
    "date": "2025-11-06 19:02:10",
    "errors": []
  },
  "seeders": {
    "ChatbotSeeder": {
      "status": "success",
      "date": "2025-11-06 19:03:00",
      "errors": []
    },
    "ServiceTechniqueSeeder": {
      "status": "success",
      "date": "2025-11-06 19:03:15",
      "errors": []
    },
    "SettingsSeeder": {
      "status": "success",
      "date": "2025-11-06 19:03:30",
      "errors": []
    }
  }
}
```

**Utilité** :
- ✅ Évite réexécution inutile (idempotence)
- ✅ Historique des exécutions
- ✅ Détection des échecs passés
- ✅ Diagnostic rapide (via `verify.php`)

---

#### Workflow Recommandé

**Nouveau déploiement** :
```bash
# 1. Cloner + installer
git clone https://github.com/James10192/KLASSCIv2.git
cd KLASSCIv2
composer install && npm install

# 2. Configuration
cp .env.example .env
php artisan key:generate
# Éditer .env

# 3. Migrations
php artisan migrate

# 4. Initialisation COMPLÈTE
php setup.php

# 5. Vérification
php verify.php --verbose
```

**Mise à jour serveur** :
```bash
# 1. Pull + update
git pull origin presentation
composer install --no-dev --optimize-autoloader

# 2. Migrations
php artisan migrate

# 3. Vérifier état
php verify.php --fix

# 4. Si problèmes, réexécuter étapes manquantes
php setup.php --only=storage  # Exemple

# 5. Clear caches
php artisan config:clear && php artisan cache:clear
```

**CI/CD Integration** :
```yaml
- name: Setup KLASSCI
  run: |
    php setup.php
    php verify.php --json > setup-status.json

- name: Verify Setup
  run: |
    if [ $(jq -r '.ready' setup-status.json) != "true" ]; then
      echo "Setup verification failed"
      exit 1
    fi
```

---

#### Avantages de la Solution

**1. Idempotence garantie** :
- ✅ Exécution multiple sans effet de bord
- ✅ Skip automatique des étapes déjà réussies
- ✅ Option `--force` pour override si nécessaire

**2. Gestion des erreurs robuste** :
- ✅ Tracking détaillé de chaque erreur
- ✅ Suggestions de correction automatiques
- ✅ Pas de corruption de l'état global

**3. UX développeur optimale** :
- ✅ Interface CLI moderne avec couleurs
- ✅ Progress indicators clairs
- ✅ Messages d'erreur explicites
- ✅ Mode interactif pour apprentissage

**4. Intégration CI/CD** :
- ✅ Export JSON structuré
- ✅ Codes de retour standard (0/1)
- ✅ Vérification automatisable

**5. Documentation complète** :
- ✅ README dédié : [docs/SETUP_INITIALISATION.md](docs/SETUP_INITIALISATION.md) (8 pages)
- ✅ Exemples d'utilisation
- ✅ Guide résolution problèmes

---

#### Fichiers Créés/Modifiés

| Fichier | Type | Lignes | Description |
|---------|------|--------|-------------|
| `setup.php` | Script PHP | 684 | Orchestrateur principal |
| `verify.php` | Script PHP | 553 | Vérification système |
| [docs/SETUP_INITIALISATION.md](docs/SETUP_INITIALISATION.md) | Documentation | ~400 | Guide complet |
| `.gitignore` | Config | +1 | Ajout `.setup.lock` |
| `.setup.lock` | JSON | Auto | Fichier de tracking (non commité) |

**Total** : 3 nouveaux fichiers, 2 modifiés, ~1650 lignes de code + documentation

---

#### Dépendances Externes

**Scripts PHP inclus** (déjà existants) :
- `init_storage.php` - Initialisation stockage
- `fix_permissions.php` - Configuration permissions
- `deploy_settings.php` - Déploiement paramètres
- `create_storage_link.php` - Lien symbolique (fallback)

**Seeders Laravel** :
- `ChatbotSeeder` - Prompts IA + templates
- `ServiceTechniqueSeeder` - Compte African Digit Consulting
- `SettingsSeeder` - Paramètres système

---

#### Tests Effectués

**Test 1 - verify.php sur système non initialisé** :
```bash
php verify.php
```
**Résultat** : ✅ Détecte correctement 5 problèmes (lock, storage, permissions, settings, seeders)

**Test 2 - Syntaxe setup.php** :
```bash
php -l setup.php
```
**Résultat** : ✅ Aucune erreur de syntaxe

**Test 3 - Permissions exécutables** :
```bash
chmod +x setup.php verify.php
./setup.php --help
```
**Résultat** : ✅ Scripts exécutables

---

#### Améliorations Futures Possibles

**Phase 2** :
- ⏭️ Option `--rollback` pour désinstaller complètement
- ⏭️ Mode `--dry-run` pour simulation sans exécution
- ⏭️ Webhook Slack/Discord pour notifications déploiement
- ⏭️ Interface web pour monitoring (Filament panel)
- ⏭️ Support multi-environnements (dev, staging, prod)

**Phase 3** :
- ⏭️ Script `update.php` pour mises à jour automatiques
- ⏭️ Backup automatique avant chaque setup
- ⏭️ Health checks périodiques (cron job)

---

### 📱 Page Étudiants - UX Responsive + AJAX (7 novembre)

**Page** : `/esbtp/etudiants` (index - Gestion Étudiants)

**Contexte** : Refonte complète de l'interface pour optimiser l'expérience mobile et desktop avec un système de design cohérent.

**Problème** : Design inconsistant, espacements aléatoires, drawer mobile avec page refresh, aucun feedback visuel sur les filtres actifs.

**Solution implémentée** : 5 améliorations majeures suivant les meilleures pratiques UX 2025.

---

#### 1. Système 8px Grid - Spacing Standard

**Fichier** : `resources/views/esbtp/etudiants/index.blade.php` (lignes 49-126)

**Problème** : Espacements incohérents (padding: 15px, margin: 20px, gap: 18px, etc.) rendant le design imprévisible.

**Solution** : Application d'un système 8px Grid (standard industrie).

**Valeurs standardisées** :
- 8px × 1 = **8px** (micro-spacing)
- 8px × 2 = **16px** (padding interne, gaps serrés)
- 8px × 3 = **24px** (padding cartes, margins sections)
- 8px × 4 = **32px** (séparation claire entre sections)
- 8px × 6 = **48px** (grands espacements)
- 8px × 7 = **56px** (touch targets mobile)
- 8px × 8 = **64px** (espacements XXL)

**Code appliqué** :
```css
/* ========================================
   SYSTÈME 8PX GRID - SPACING STANDARD
   8, 16, 24, 32, 40, 48, 56, 64px
   ======================================== */

.main-content {
    max-width: 100%;
    overflow-x: hidden;
    width: 100%;
    margin: 0 auto;
    padding-left: 16px;  /* 8px grid × 2 */
    padding-right: 16px;
    box-sizing: border-box;
}

.card-moderne {
    margin-bottom: 32px;  /* 8px grid × 4 - Séparation claire entre sections */
    padding: 24px;  /* 8px grid × 3 - Internal padding cohérent */
}

.dashboard-header {
    margin-bottom: 32px;  /* 8px grid × 4 */
}

.student-card-grid {
    gap: 16px;  /* 8px grid × 2 - Espacement entre cartes */
}

.student-card {
    padding: 16px;  /* 8px grid × 2 - Internal padding */
}
```

**Principe Gestalt appliqué** :
- **Internal ≤ External** : Padding (16-24px) toujours ≤ Margin (32px)
- **Proximité visuelle** : Éléments liés regroupés par espacement cohérent
- **Hiérarchie claire** : Petits espacements (8-16px) = liens forts, grands espacements (32-48px) = séparation sections

**Impact** :
- ✅ Design cohérent et prévisible
- ✅ Facile à maintenir (toujours multiples de 8)
- ✅ Groupements visuels clairs (principe Gestalt)

---

#### 2. Border-radius Main-Content Mobile

**Fichier** : `resources/views/esbtp/etudiants/index.blade.php` (lignes 59-66)

**Problème** : Sur mobile, le contenu principal avait des bords carrés, donnant un aspect daté.

**Solution** : Border-radius de 16px sur `.main-content` uniquement sur mobile (< 992px).

**Code** :
```css
/* Border-radius sur main-content quand cards visibles (mobile) */
@media (max-width: 992px) {
    .main-content {
        border-radius: 16px;  /* 8px grid × 2 */
        background: transparent;
    }
}
```

**Impact** :
- ✅ Design moderne et doux
- ✅ Cohérence avec les cartes (toutes avec border-radius)
- ✅ Transition visuelle naturelle entre desktop et mobile

---

#### 3. Séparation Icône/Titre - Visual Hierarchy

**Fichier** : `resources/views/esbtp/etudiants/index.blade.php` (lignes 155-172)

**Problème** : Icône et titre "Liste des étudiants" collés, icône pouvait se compresser, mauvaise lisibilité.

**Solution** : Conteneur flexbox avec gap standardisé et icône non-compressible.

**Code** :
```css
/* ========================================
   SÉPARATION ICÔNE/TITRE - Visual Hierarchy
   ======================================== */
.section-title {
    display: flex;
    align-items: center;
    gap: 12px;  /* 8px + 4px pour équilibre visuel */
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 24px;  /* 8px grid × 3 */
}

.section-title i {
    flex-shrink: 0;  /* Empêcher l'icône de se compresser */
    font-size: 1.1em;  /* Légèrement plus petite que le texte */
    color: var(--primary, #0453cb);
}
```

**HTML** :
```html
<h5 class="section-title">
    <i class="fas fa-users"></i>
    <span>Liste des étudiants</span>
</h5>
```

**Impact** :
- ✅ Hiérarchie visuelle claire
- ✅ Icône stable (ne se compresse jamais)
- ✅ Espacement visuel équilibré (12px = sweet spot)

---

#### 4. AJAX Drawer Mobile - Pas de Refresh Page

**Fichier** : `resources/views/esbtp/etudiants/index.blade.php` (lignes 2372-2416)

**Problème** : Drawer mobile rafraîchissait toute la page à chaque soumission/reset, expérience utilisateur lente et désagréable.

**Solution** : Conversion complète en AJAX avec `fetch()` API.

**Soumission formulaire (AJAX)** :
```javascript
// ========================================
// AJAX SUBMISSION DU DRAWER (PAS DE REFRESH PAGE)
// ========================================
const mobileForm = document.getElementById('mobile-search-form');
if (mobileForm) {
    mobileForm.addEventListener('submit', function(e) {
        e.preventDefault();  // Empêcher la soumission normale (refresh page)
        console.log('📤 Soumission AJAX du drawer mobile');

        // Construire les paramètres depuis le formulaire mobile
        const formData = new FormData(mobileForm);
        const params = new URLSearchParams();

        for (const [key, value] of formData.entries()) {
            if (value) {  // Ignorer les valeurs vides
                params.append(key, value);
            }
        }

        const url = mobileForm.action + '?' + params.toString();
        console.log('📍 URL AJAX:', url);

        // Utiliser la fonction fetchResults existante
        if (typeof window.fetchResultsGlobal === 'function') {
            window.fetchResultsGlobal(url, { pushState: true });
            // Fermer le drawer après la soumission
            setTimeout(closeDrawer, 300);  // Petit délai pour UX smooth
        }
    });
}
```

**Bouton Reset (AJAX)** :
```javascript
// Bouton réinitialiser dans le drawer (AJAX - pas de refresh)
resetBtn.addEventListener('click', function() {
    console.log('🔄 Réinitialisation des filtres');

    // Utiliser fetchResults pour recharger la page sans filtres (AJAX)
    if (typeof window.fetchResultsGlobal === 'function') {
        window.fetchResultsGlobal('{{ route('esbtp.etudiants.index') }}', { pushState: true });

        // Réinitialiser les champs du formulaire
        mobileForm.reset();

        // Fermer le drawer
        setTimeout(closeDrawer, 300);
    }
});
```

**Exposition fonction globale** (lignes 2832-2833) :
```javascript
// Exposer fetchResults globalement pour le drawer mobile
window.fetchResultsGlobal = fetchResults;
```

**Impact** :
- ✅ Expérience fluide sans refresh page
- ✅ Drawer se ferme automatiquement après soumission (300ms delay UX)
- ✅ URL mise à jour (history.pushState)
- ✅ État formulaire préservé

---

#### 5. Indicateur Filtres Actifs - Feedback Visuel

**Fichier** : `resources/views/esbtp/etudiants/index.blade.php` (lignes 174-287, 2277-2280, 2970-3111)

**Problème** : Aucun feedback visuel sur les filtres actuellement appliqués, utilisateur perdu après avoir filtré.

**Solution** : Conteneur dynamique affichant tous les filtres actifs avec possibilité de suppression individuelle ou globale.

**CSS Design** :
```css
/* ========================================
   INDICATEUR FILTRES ACTIFS
   Affiche les filtres actuellement appliqués avec option de suppression
   ======================================== */
.active-filters-container {
    margin-bottom: 24px;  /* 8px grid × 3 */
    padding: 16px;  /* 8px grid × 2 */
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 1px solid #bae6fd;
    border-radius: 12px;  /* 8px + 4px */
    display: flex;
    flex-wrap: wrap;
    gap: 8px;  /* 8px grid × 1 */
    align-items: center;
}

.filter-tag {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: #ffffff;
    border: 1px solid #0ea5e9;
    border-radius: 8px;
    font-size: 14px;
    color: #0c4a6e;
    font-weight: 500;
    transition: all 0.2s ease;
}

.filter-tag:hover {
    background: #f0f9ff;
    border-color: #0284c7;
}

.filter-tag-remove {
    background: none;
    border: none;
    color: #0ea5e9;
    font-size: 16px;
    cursor: pointer;
    padding: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.filter-tag-remove:hover {
    background: #fee2e2;
    color: #dc2626;
}

.clear-all-filters {
    padding: 8px 16px;
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border: 1px solid #fca5a5;
    border-radius: 8px;
    color: #991b1b;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.clear-all-filters:hover {
    background: linear-gradient(135deg, #fca5a5 0%, #fee2e2 100%);
    border-color: #dc2626;
}
```

**HTML Conteneur** :
```html
<!-- Indicateur filtres actifs -->
<div id="active-filters-container" class="active-filters-container" style="display: none;">
    <!-- Sera rempli dynamiquement via JavaScript -->
</div>
```

**JavaScript Logique** :
```javascript
function updateActiveFiltersIndicator() {
    const container = document.getElementById('active-filters-container');
    if (!container) return;

    // Récupérer les paramètres de l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const activeFilters = [];

    // Mapping des paramètres vers des labels lisibles
    const filterLabels = {
        'search': 'Recherche',
        'filiere': 'Filière',
        'niveau': 'Niveau',
        'classe': 'Classe',
        'annee': 'Année universitaire',
        'statut': 'Statut',
        'affectation_status': 'Statut affectation',
        'inscrit_annee_courante': 'Inscription validée',
        'est_transfert': 'Transfert'
    };

    // Récupérer les options de select pour avoir les labels
    const getSelectLabel = (name, value) => {
        const select = document.querySelector(`select[name="${name}"], #mobile-${name}`);
        if (select) {
            const option = select.querySelector(`option[value="${value}"]`);
            return option ? option.textContent.trim() : value;
        }
        return value;
    };

    // Parcourir les paramètres
    for (const [key, value] of urlParams) {
        if (value && filterLabels[key]) {
            activeFilters.push({
                key: key,
                label: filterLabels[key],
                value: value,
                displayValue: getSelectLabel(key, value)
            });
        }
    }

    // Afficher ou masquer le conteneur
    if (activeFilters.length === 0) {
        container.style.display = 'none';
        return;
    }

    container.style.display = 'flex';

    // Générer le HTML
    let html = '';
    activeFilters.forEach(filter => {
        html += `
            <div class="filter-tag">
                <strong>${filter.label}:</strong>
                <span>${filter.displayValue}</span>
                <button type="button"
                        class="filter-tag-remove"
                        onclick="removeFilter('${filter.key}')"
                        title="Supprimer ce filtre">
                    ×
                </button>
            </div>
        `;
    });

    // Ajouter le bouton "Clear All"
    html += `
        <button type="button"
                class="clear-all-filters"
                onclick="clearAllFilters()"
                title="Supprimer tous les filtres">
            <i class="fas fa-times-circle"></i> Tout effacer
        </button>
    `;

    container.innerHTML = html;
}

function removeFilter(key) {
    console.log('🗑️ Suppression filtre:', key);
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.delete(key);
    const newUrl = `${window.location.pathname}?${urlParams.toString()}`;

    if (typeof window.fetchResultsGlobal === 'function') {
        window.fetchResultsGlobal(newUrl, { pushState: true });
        setTimeout(updateActiveFiltersIndicator, 500);
    }
}

function clearAllFilters() {
    console.log('🧹 Suppression de tous les filtres');

    if (typeof window.fetchResultsGlobal === 'function') {
        window.fetchResultsGlobal(window.location.pathname, { pushState: true });
        setTimeout(updateActiveFiltersIndicator, 500);
    }
}

// Mettre à jour l'indicateur au chargement initial et après chaque AJAX
updateActiveFiltersIndicator();
```

**Impact** :
- ✅ Feedback visuel clair sur les filtres actifs
- ✅ Suppression individuelle par filtre (bouton ×)
- ✅ Suppression globale (bouton "Tout effacer")
- ✅ Mise à jour automatique après AJAX
- ✅ Masqué si aucun filtre actif

---

#### Fichiers Modifiés (Récapitulatif)

| Fichier | Type | Lignes modifiées | Changements |
|---------|------|------------------|-------------|
| `resources/views/esbtp/etudiants/index.blade.php` | View | +1692 / -78 | 8px grid, border-radius mobile, icon/title, AJAX drawer, filters indicator |
| `resources/views/esbtp/etudiants/partials/results.blade.php` | Partial | - | Intégration AJAX (partiel déjà existant) |

**Total** : 1 fichier principal modifié, +1692 insertions, -78 suppressions

---

#### Commit Git

**Hash** : `f992ff1`
**Branch** : `presentation`
**Message** :
```
feat(etudiants): amélioration UX responsive + système 8px grid + AJAX drawer

Refonte complète de la page index étudiants avec design moderne et UX optimale.

### Améliorations Design
1. Système 8px Grid appliqué partout
2. Border-radius main-content mobile
3. Séparation icône/titre améliorée

### Fonctionnalités AJAX
4. Drawer mobile 100% AJAX
5. Indicateur filtres actifs

### Responsive Mobile
- Card grid instead of table
- Typography scalable
- Touch targets optimized
- Perfect centering tested on 390x844

🤖 Generated with Claude Code
Co-Authored-By: Claude <noreply@anthropic.com>
```

---

#### Avantages UX

✅ **Design cohérent** : Système 8px Grid appliqué partout
✅ **Expérience fluide** : AJAX sans refresh page
✅ **Feedback visuel** : Filtres actifs clairement affichés
✅ **Mobile moderne** : Border-radius, touch targets optimisés
✅ **Hiérarchie claire** : Séparation icône/titre, espacements standardisés
✅ **Performance** : Pas de rechargement complet, seulement mise à jour partielle
✅ **Accessibilité** : Labels lisibles, boutons clairs, couleurs contrastées

---

#### Fix Indicateur Filtres Actifs - Update Automatique (7 novembre)

**Contexte** : Suite à l'implémentation initiale de l'indicateur des filtres actifs, deux bugs critiques ont été identifiés.

**Bug #1 : Filtres actifs ne se mettent pas à jour automatiquement**

**Problème** :
- Quand l'utilisateur change un filtre (ex: Filière "Génie Civil" → "BTP"), l'indicateur continuait d'afficher l'ancien filtre
- Résultat : Accumulation de filtres (ancien + nouveau) au lieu d'un remplacement
- Cause : Les event listeners appelaient `fetchResults()` directement, contournant le wrapper qui met à jour l'indicateur

**Solution** :
```javascript
// ❌ AVANT - Bypasse le wrapper
filterInputs.forEach((input) => {
    input.addEventListener('change', () => {
        fetchResults(targetUrl, { pushState: true });  // Pas d'update indicateur
    });
});

// ✅ APRÈS - Utilise le wrapper global
filterInputs.forEach((input) => {
    input.addEventListener('change', () => {
        window.fetchResultsGlobal(targetUrl, { pushState: true });  // Update automatique
    });
});

// Le wrapper déclenche l'update après chaque requête AJAX
window.fetchResultsGlobal = function(url, options) {
    originalFetchResults(url, options);
    setTimeout(updateActiveFiltersIndicator, 500);  // ← Update automatique
};
```

**Emplacements corrigés** (5 points d'entrée) :
1. Form submit desktop (ligne 3126)
2. FilterInputs change (ligne 3139)
3. Popstate navigation (ligne 3150)
4. removeFilter() (ligne 3099)
5. clearAllFilters() (ligne 3105)

**Impact** :
- ✅ Indicateur se met à jour après CHAQUE changement AJAX
- ✅ Pas de doublons de filtres
- ✅ Toujours synchronisé avec l'URL

**⚠️ Hotfix : Problème de timing (7 novembre - 30min après)**

**Problème persistant** :
- Malgré la correction initiale, l'indicateur ne se mettait toujours pas à jour
- L'utilisateur devait faire Ctrl+R (refresh page complète) pour voir les changements
- Cause : **Race condition** dans le timing d'exécution

**Diagnostic** :
```javascript
// ❌ ANCIEN CODE - Race condition
window.fetchResultsGlobal = function(url, options) {
    originalFetchResults(url, options);  // Lance le fetch (asynchrone)
    setTimeout(updateActiveFiltersIndicator, 500);  // Lit l'URL après 500ms
};

// Mais :
// - Le fetch prend 200-1000ms pour se terminer
// - pushState() est exécuté DANS le .then() du fetch
// - setTimeout() lit l'URL AVANT que pushState() soit exécuté
// Résultat : L'indicateur lit l'ANCIENNE URL !
```

**Timeline du bug** :
```
T+0ms    : Appel fetchResultsGlobal(newUrl)
T+0ms    : Démarrage fetch AJAX
T+0ms    : setTimeout(updateIndicator, 500) démarre
T+500ms  : updateIndicator() lit window.location → ANCIENNE URL ❌
T+800ms  : Fetch terminé → pushState(newUrl) → URL mise à jour
```

**Solution finale** :
```javascript
function fetchResults(url, options = {}) {
    // ...
    return fetch(url, { /* ... */ })
        .then(data => {
            resultsContainer.innerHTML = data.html;
            if (options.pushState !== false) {
                window.history.pushState({ url: data.url }, '', data.url);
            }
            bindPagination();
            initTableSorting(resultsContainer);

            // ✅ Appeler APRÈS pushState, dans le .then()
            updateActiveFiltersIndicator();
        });
}

// Supprimer le wrapper inutile
// window.fetchResultsGlobal pointe directement vers fetchResults
```

**Modifications** :
- Ligne 2934 : `fetchResults()` retourne maintenant la promesse (`return fetch(...)`)
- Ligne 2961 : `updateActiveFiltersIndicator()` appelé dans le `.then()` après `pushState`
- Lignes 3115-3120 : Wrapper setTimeout supprimé (obsolète)

**Impact** :
- ✅ L'indicateur lit l'URL au BON moment (après pushState)
- ✅ Plus besoin de Ctrl+R
- ✅ Update instantané et synchronisé

---

**Bug #2 : Classe affiche l'ID au lieu du nom**

**Problème** :
- Dans l'indicateur, le filtre classe affichait `Classe: 15` (l'ID) au lieu de `Classe: L3 GC (Génie Civil - Licence 3)`
- Cause : Le select classe utilise un composant Alpine.js (pas de `<option>` classique), la fonction `getSelectLabel()` ne trouvait pas le label

**Solution** :
```javascript
// Mapping ID → Label généré depuis les data Laravel (ligne 2975)
const classesMapping = {
    @foreach($classes as $classeOption)
    '{{ $classeOption->id }}': '{{ $classeOption->name }}@if($classeOption->filiere || $classeOption->niveauEtude) ({{ $classeOption->filiere->name ?? "Filière N/A" }} - {{ $classeOption->niveauEtude->name ?? "Niveau N/A" }})@endif',
    @endforeach
};

// Fonction getSelectLabel() améliorée (ligne 3010)
const getSelectLabel = (name, value) => {
    if (name === 'classe') {
        // Utiliser le mapping créé depuis les data Laravel
        return classesMapping[value] || value;
    }
    // ... autres cas
};
```

**Exemple de mapping généré** :
```javascript
const classesMapping = {
    '15': 'L3 GC (Génie Civil - Licence 3)',
    '22': 'L2 BTP (BTP - Licence 2)',
    '7': 'M1 Archi (Architecture - Master 1)',
    // ...
};
```

**Impact** :
- ✅ Classe affiche le nom complet : `"L3 GC (Génie Civil - Licence 3)"`
- ✅ Cohérent avec les autres filtres (tous affichent des labels lisibles)
- ✅ Pas de dépendance DOM (mapping généré côté serveur)

---

#### Fichiers Modifiés

| Fichier | Lignes modifiées | Type |
|---------|------------------|------|
| `resources/views/esbtp/etudiants/index.blade.php` | 2934, 2939, 2961, 2975-2979, 3010-3013, 3099, 3105, 3115-3120 (supprimés) | Fix timing + mapping classes |

**Modifications détaillées** :
- **2934** : `fetchResults()` retourne la promesse (`return fetch(...)`)
- **2939** : Retour de promesse vide si URL manquante (`return Promise.resolve()`)
- **2961** : Appel `updateActiveFiltersIndicator()` dans `.then()` après `pushState`
- **2975-2979** : Mapping `classesMapping` ID → Label
- **3010-3013** : Fonction `getSelectLabel()` utilise le mapping pour classe
- **3115-3120** : Wrapper `setTimeout` supprimé (obsolète)

**Total** : 1 fichier, 6 sections modifiées, 1 section supprimée

---

### 🔀 Tri Colonnes - Server-Side au lieu de Client-Side (7 novembre)

**Page** : `/esbtp/etudiants` (index - Gestion Étudiants)

**Problème** : Tri limité à la page actuelle

**Situation avant** :
- Tri des colonnes (Nom, Prénom, Matricule, etc.) fait en JavaScript côté client
- Triait SEULEMENT les étudiants de la page actuelle (ex: 10 étudiants sur la page 1)
- Les étudiants des autres pages n'étaient PAS inclus dans le tri

**Exemple concret** :
```
Page 1 avant tri : Kouassi, Bamba, Traoré
Page 2 (non visible) : Adou, Yao

Clic sur "Nom" (tri A-Z) :
❌ Résultat ancien : Bamba, Kouassi, Traoré (tri page 1 seulement)
✅ Résultat attendu : Adou devrait être en premier (A < B)
```

**Cause** :
```javascript
// ❌ Ancien code - Tri client-side
const rows = Array.from(table.querySelectorAll('tbody tr'));  // ← Seulement les lignes dans le DOM
rows.sort((a, b) => { /* ... */ });  // ← Trie seulement ces lignes
```

---

#### Solution : Tri Server-Side avec AJAX

**Backend - Controller** : `app/Http/Controllers/ESBTPStudentController.php` (lignes 235-258)

**Ajout gestion paramètres `sort` et `order`** :
```php
// Gestion du tri
$sortColumn = $request->input('sort', 'created_at');
$sortOrder = $request->input('order', 'desc');

// Mapping des colonnes frontend vers les colonnes DB
$columnMapping = [
    'nom' => 'nom',
    'prenom' => 'prenom',
    'matricule' => 'matricule',
    'email' => 'email',
    'telephone' => 'telephone',
    'date' => 'date_naissance',
    'statut' => 'statut',
    'created_at' => 'created_at',
];

// Valider la colonne de tri
$sortColumn = $columnMapping[$sortColumn] ?? 'created_at';
$sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? strtolower($sortOrder) : 'desc';

// Appliquer le tri
$baseQuery->orderBy($sortColumn, $sortOrder);

$etudiants = $baseQuery->paginate($perPage)->appends($request->query());
```

**Sécurité** :
- ✅ Whitelist des colonnes autorisées (mapping)
- ✅ Validation `asc` ou `desc` uniquement
- ✅ Fallback sur `created_at` + `desc` si paramètres invalides

---

**Frontend - Vue** : `resources/views/esbtp/etudiants/index.blade.php` (lignes 2675-2728)

**Remplacement tri client-side par AJAX** :
```javascript
function initTableSorting(scope = document) {
    scope.querySelectorAll('.table-sort').forEach((button) => {
        button.addEventListener('click', function () {
            const column = this.dataset.column;  // Ex: "nom", "matricule"

            // Alterner la direction du tri
            const currentDirection = this.dataset.sortDirection || 'desc';
            const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
            this.dataset.sortDirection = newDirection;

            // Retirer les indicateurs visuels sur les autres colonnes
            scope.querySelectorAll('.table-sort').forEach((other) => {
                if (other !== this) {
                    delete other.dataset.sortDirection;
                    other.classList.remove('sorted-asc', 'sorted-desc');
                }
            });

            // Ajouter classe CSS pour indiquer le tri actif (flèche ▲/▼)
            this.classList.add(`sorted-${newDirection}`);

            // Construire l'URL avec les paramètres de tri
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('sort', column);
            urlParams.set('order', newDirection);
            urlParams.set('page', '1');  // Reset à la page 1

            const newUrl = `${window.location.pathname}?${urlParams.toString()}`;

            // Faire l'appel AJAX pour récupérer les résultats triés
            window.fetchResultsGlobal(newUrl, { pushState: true });
        });
    });
}
```

**Workflow** :
1. Clic sur header colonne (ex: "Nom")
2. JavaScript construit URL : `?sort=nom&order=asc&page=1`
3. Appel AJAX au serveur via `fetchResultsGlobal()`
4. Controller Laravel trie TOUS les étudiants par nom ASC
5. Retourne la page 1 des résultats triés
6. Affichage avec "Adou" en premier

---

**CSS - Indicateurs visuels** : `index.blade.php` (lignes 1248-1259)

**Flèches de tri** :
```css
/* Indicateurs de tri actif avec flèches */
#etudiants-table th button.table-sort.sorted-asc::after {
    content: ' ▲';
    font-size: 10px;
    color: var(--primary, #0453cb);
}

#etudiants-table th button.table-sort.sorted-desc::after {
    content: ' ▼';
    font-size: 10px;
    color: var(--primary, #0453cb);
}
```

**Rendu visuel** :
- Colonne triée en ordre croissant : `Nom ▲`
- Colonne triée en ordre décroissant : `Nom ▼`
- Autres colonnes : Pas de flèche

---

#### Fichiers Modifiés

| Fichier | Lignes modifiées | Type |
|---------|------------------|------|
| `app/Http/Controllers/ESBTPStudentController.php` | 235-258 | Gestion paramètres `sort` et `order` |
| `resources/views/esbtp/etudiants/index.blade.php` | 1248-1259, 2675-2728 | AJAX tri + CSS flèches |

**Total** : 2 fichiers, 3 sections modifiées

---

#### Impact

**Avant** :
- ❌ Tri limité à 10 étudiants (page actuelle)
- ❌ Navigation entre pages perdait le tri
- ❌ Pas de feedback visuel sur la colonne triée

**Après** :
- ✅ Tri sur TOUS les étudiants (toutes pages confondues)
- ✅ Pagination respecte le tri (page 2 continue le tri de la page 1)
- ✅ Flèches ▲/▼ indiquent la colonne et direction du tri actif
- ✅ URL mise à jour : `?sort=nom&order=asc` (bookmarkable)
- ✅ Indicateur filtres actifs synchronisé (grâce au fix précédent)

**Exemple réel** :
```
1000 étudiants répartis sur 100 pages (10 par page)

Avant : Clic sur "Nom" → Trie seulement les 10 de la page actuelle
Après : Clic sur "Nom" → Trie les 1000, affiche les 10 premiers alphabétiquement
```

---

## 🌐 Vision Future : Réseau Social KLASSCI

**Concept** : Plateforme sociale éducative **CROSS-TENANT** pour tous les étudiants KLASSCI (tous établissements confondus), inspirée de Reddit/Twitter, mais adaptée au contexte académique africain.

**Objectif** : Créer une **grande communauté élitiste panafricaine** des établissements utilisant KLASSCI - Un "LinkedIn académique africain" où les étudiants de l'ESBTP Abidjan peuvent échanger avec ceux de l'ESBTP Yakro, créer du networking inter-établissements, et construire une marque forte "étudiant KLASSCI".

**Vision stratégique** : Transformer KLASSCI d'un simple ERP éducatif en un **écosystème académique complet** avec réseau social fédérateur - similaire à la relation entre GitHub (outil) et GitHub Social (communauté).

---

**📄 Documentation complète** : [docs/api/SOCIAL_NETWORK_ARCHITECTURE.md](docs/api/SOCIAL_NETWORK_ARCHITECTURE.md)

### 📊 Analyse de Faisabilité (Novembre 2025)

#### 1. État des Lieux - Architecture Actuelle & Vision Cross-Tenant

**🎯 CHANGEMENT MAJEUR : Architecture Cross-Tenant Obligatoire**

**Pourquoi une application séparée est OBLIGATOIRE ?**

1. **Données centralisées** :
   - Tous les étudiants (esbtp-abidjan, esbtp-yakro, etc.) sur la **même plateforme sociale**
   - Impossible dans KLASSCI actuel (chaque tenant = BDD isolée)
   - Besoin d'une **BDD centrale unique** pour le social

2. **Networking inter-établissements** :
   - Étudiant ESBTP Abidjan suit étudiant ESBTP Yakro ✅
   - Posts visibles cross-tenant (ex: "Offres de stage BTP Côte d'Ivoire") ✅
   - Communautés globales (ex: "Ingénieurs Génie Civil KLASSCI") ✅

3. **Marque KLASSCI unifiée** :
   - "Je suis étudiant KLASSCI" (comme "Je suis étudiant 42")
   - Effet réseau : Plus il y a de tenants, plus la valeur augmente
   - Élitisme : Accessible seulement aux étudiants d'établissements KLASSCI

**Architecture SaaS Multi-Tenant Actuelle (rappel)** :
```
klassci_master (DB)          ← Gestion tenants
├── Tables: tenants, tenant_deployments, tenant_health_checks,
│   tenant_backups, tenant_features, tenant_activity_logs
├── Application: klassci-master (~/workspace/klassciMaster)
│
├── tenant: esbtp-abidjan    → esbtp_abidjan (DB isolée)
├── tenant: esbtp-yakro      → esbtp_yakro (DB isolée)
├── tenant: presentation     → presentation (DB isolée)
└── tenant: test-local       → test-local (DB isolée)
```

**PROBLÈME** : Les étudiants de `esbtp_abidjan` ne peuvent PAS voir/interagir avec ceux de `esbtp_yakro` (BDD séparées).

**SOLUTION** : Application sociale séparée avec BDD centrale.

---

**Forces de KLASSCI (pour intégration sociale)** :
- ✅ **Base utilisateurs multi-établissements** : ~4000 étudiants (3000 Abidjan + 700 Yakro + 50 test)
- ✅ **API Master existante** : `klassci-master` peut servir d'auth provider
- ✅ **Sanctum déjà en place** : Tokens API réutilisables
- ✅ **Profils étudiants riches** : Nom, photo, classe, filière, établissement
- ✅ **Infrastructure SaaS mature** : Déploiement tenant automatisé

**Données à synchroniser depuis tenants** :
- `users` + `esbtp_etudiants` : Profils (sync vers social central)
- `esbtp_classes` + `esbtp_filieres` : Contexte académique
- `tenants` (master DB) : Liste établissements KLASSCI

---

#### 1.1. Recherches & Tendances 2025 (2 novembre 2025)

**Sources** : Web search réseau sociaux éducatifs + Laravel social networks best practices

**📊 Tendances Réseaux Sociaux Étudiants 2025** :

- **76% des 16-25 ans utilisent Instagram** (plateforme #1 devant Snapchat 63% et TikTok 60%)
- **Formats courts et immersifs dominants** : Reels, Shorts, TikTok (préférence génération Z)
- **IA et personnalisation** : Algorithmes de recommandation feed (Gemini/OpenAI)
- **Contenu interactif** : Polls, Q&A, Live events (engagement x2-3)
- **Mobile-first obligatoire** : 85%+ trafic mobile (PWA ou app native indispensable)
- **Formats éducatifs** : Tutoriels vidéo, webinaires, témoignages étudiants, calendriers événements

**🏗️ Laravel Social Network Best Practices 2025** :

**Architecture Moderne** :
- **Domain-Driven Design (DDD)** : Organiser code par business logic, pas couches techniques
- **API-First** : Séparer APIs web/mobile/third-party (REST + GraphQL)
- **Microservices** : Auth, Payments, Notifications services indépendants (Laravel Modules)
- **Real-time** : Laravel Broadcasting + WebSockets (Pusher/Redis/Reverb)

**Performance & Scalabilité** :
- **Redis cache** : Feeds pré-calculés (TTL 5min) → Performance x10
- **CDN** : Offload assets statiques vers S3/CloudFront (EC2 = PHP only)
- **Database** : Indexes sur colonnes fréquentes, Read replicas, Query optimization
- **Queues** : Laravel Horizon pour jobs async (emails, notifications, indexation)

**Sécurité** :
- **Rate limiting** : API throttling (60 req/min user, 10 req/min guest)
- **Input sanitization** : XSS, SQL injection protection (Laravel validation built-in)
- **CSRF tokens** : Tous les forms protégés
- **Content moderation** : Filtre mots-clés + système reports + modérateurs humains

**Packages Recommandés** :
- `spatie/laravel-medialibrary` - Gestion fichiers/images (conversions auto)
- `intervention/image` - Traitement images (resize, crop, watermark)
- `cybercog/laravel-love` - Système likes/reactions avancé
- `spatie/laravel-activitylog` - Audit trail (qui a fait quoi quand)
- `laravel/scout` + `meilisearch` - Full-text search ultra-rapide
- `laravel/horizon` - Monitoring queues Redis (dashboard temps réel)

**Projets Reddit-like Laravel Open Source** :
- `geosem42/laravel-reddit` : Login, Subreddits, Posts, Comments threadés, Upvote/Downvote, User profiles
- `ivanmmarkovic/reddit-laravel` : Posts (link/text), Ajax voting, User profiles
- Laracasts "Let's Build A Forum with Laravel and TDD" : TDD approach, threaded comments

---

#### 2. Fonctionnalités Proposées (Inspirées Reddit + Twitter)

**Phase 1 : Fondations (3-4 mois)** 🟢 Faisable

| Fonctionnalité | Description | Complexité | Modèle inspiré |
|----------------|-------------|------------|-----------------|
| **Posts/Threads** | Publications texte/image/lien | Moyenne | Reddit posts + Twitter tweets |
| **Commentaires** | Discussions threadées (3 niveaux max) | Moyenne | Reddit comments |
| **Upvotes/Downvotes** | Système de vote +/- | Faible | Reddit karma |
| **Communautés** | Basées sur Classes/Filières | Faible | Reddit subreddits |
| **Hashtags** | Tags pour catégorisation | Faible | Twitter hashtags |
| **Fil d'actualité** | Timeline chronologique/populaire | Moyenne | Twitter feed |
| **Profils étudiants** | Extension profil existant | Faible | Reddit/Twitter profiles |

**Phase 2 : Engagement (2-3 mois)** 🟡 Moyennement faisable

| Fonctionnalité | Description | Complexité | Modèle inspiré |
|----------------|-------------|------------|-----------------|
| **Notifications temps réel** | Laravel Echo + Pusher/WebSocket | Moyenne | Reddit notifications |
| **Suiveurs/Abonnements** | Follow users/communautés | Faible | Twitter follow |
| **Mentions** | @username dans posts/comments | Moyenne | Twitter mentions |
| **Recherche avancée** | Full-text search (Scout + Meilisearch) | Élevée | Reddit search |
| **Modération** | Rapports, suppression, ban | Moyenne | Reddit moderation |
| **Badges/Achievements** | Gamification (Top contributeur, etc.) | Moyenne | Reddit flair |

**Phase 3 : Avancées (3-4 mois)** 🟠 Complexe

| Fonctionnalité | Description | Complexité | Modèle inspiré |
|----------------|-------------|------------|-----------------|
| **Messages privés** | Chat 1-to-1 | Élevée | Reddit chat |
| **Groupes de discussion** | Channels thématiques | Élevée | Discord-like |
| **Partage de fichiers** | Documents, PDF, notes de cours | Moyenne | Slack files |
| **Sondages** | Votes/enquêtes communautaires | Faible | Twitter polls |
| **Live events** | Sessions Q&A en direct | Très élevée | Reddit AMAs |
| **Recommandations AI** | Suggestions posts via Gemini | Élevée | Reddit suggestions |

---

#### 3. Architecture Technique Proposée - Application Séparée Cross-Tenant

**🚀 DÉCISION ARCHITECTURALE : Application Indépendante Obligatoire**

**Architecture Production (sous-domaines)** :

```
┌─────────────────────────────────────────────────────────────────┐
│                    KLASSCI ECOSYSTEM                             │
└─────────────────────────────────────────────────────────────────┘

┌──────────────────────┐     ┌──────────────────────┐     ┌──────────────────┐
│  admin.klassci.com   │     │  social.klassci.com  │     │  Mobile App      │
│  (Master Admin)      │────▶│  (Réseau Social)     │◀────│  (Future)        │
│                      │     │                      │     │                  │
│  Laravel 12.x        │     │  Laravel 12.x        │     │  Flutter/RN      │
│  DB: klassci_master  │     │  DB: klassci_social  │     │  API REST        │
│  API: /api/*         │     │  API: /api/v1/*      │     │                  │
└──────────────────────┘     └──────────────────────┘     └──────────────────┘
         │                            │
         │                            │
         ▼                            ▼
┌─────────────────────────────────────────────────────────────────┐
│              Tenants KLASSCI (isolés par BDD)                   │
├─────────────────────────────────────────────────────────────────┤
│  • esbtp-abidjan.klassci.com    (DB: esbtp_abidjan)           │
│  • esbtp-yakro.klassci.com      (DB: esbtp_yakro)             │
│  • presentation.klassci.com     (DB: presentation)             │
│  • [futurs tenants...]                                          │
└─────────────────────────────────────────────────────────────────┘
```

**Note** : En développement local, remplacer sous-domaines par ports :
- `localhost:8000` → Tenant
- `localhost:8001` → admin.klassci.com
- `localhost:8002` → social.klassci.com

---

**Flow d'authentification Single Sign-On (SSO)** :

```
1. Étudiant connecté sur esbtp-abidjan.klassci.com
   ↓
2. Clique "Accéder au Réseau Social KLASSCI" (menu)
   ↓
3. Tenant génère JWT token signé (payload: user_id, tenant_code, expire: 5min)
   ↓
4. Redirect → https://social.klassci.com/auth/sso?token=xxx
   ↓
5. social.klassci.com décode JWT + valide signature
   ↓
6. API call → admin.klassci.com/api/students/verify (avec token)
   ↓
7. Master API retourne profil complet étudiant
   ↓
8. Sync/Update profil dans klassci_social DB
   ↓
9. Génère session social.klassci.com (cookie/Sanctum)
   ↓
10. Redirect → https://social.klassci.com/feed
```

**Avantages SSO** :
- ✅ Pas de double login (seamless)
- ✅ Sécurisé (JWT short-lived + signature vérifiée)
- ✅ Sync profil automatique (nom, photo, établissement)

---

**Phase 1 : Application Laravel Indépendante (MVP)**

**Nouveau repo Git** : `klassci-social` (séparé de `KLASSCIv2`)

```
klassci-social/                   ← Nouveau repo
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── SSOController.php       ← Login via tenant
│   │   │   ├── PostController.php
│   │   │   ├── CommentController.php
│   │   │   ├── VoteController.php
│   │   │   ├── CommunityController.php
│   │   │   └── FeedController.php
│   │   └── Middleware/
│   │       └── VerifyStudentStatus.php     ← Check actif via Master API
│   │
│   ├── Models/
│   │   ├── Student.php                     ← Sync depuis tenants
│   │   ├── Institution.php                 ← Sync depuis master.tenants
│   │   ├── Post.php
│   │   ├── Comment.php (nested set)
│   │   ├── Vote.php
│   │   ├── Community.php
│   │   ├── Hashtag.php
│   │   └── Follow.php
│   │
│   ├── Services/
│   │   ├── MasterAPIService.php            ← Appels admin.klassci.com/api
│   │   ├── StudentSyncService.php          ← Sync profils
│   │   ├── FeedGeneratorService.php
│   │   ├── VoteCalculatorService.php
│   │   └── NotificationService.php
│   │
│   ├── Jobs/
│   │   ├── SyncStudentFromTenant.php       ← Queue job
│   │   ├── ProcessVoteJob.php
│   │   └── GenerateFeedJob.php
│   │
│   └── Events/
│       ├── PostCreated.php
│       └── CommentAdded.php
│
├── database/
│   └── migrations/
│       ├── 2026_01_01_create_students_table.php
│       ├── 2026_01_02_create_institutions_table.php
│       ├── 2026_01_03_create_posts_table.php
│       ├── 2026_01_04_create_comments_table.php
│       ├── 2026_01_05_create_votes_table.php
│       └── ...
│
├── routes/
│   ├── web.php                             ← Interface Blade
│   └── api.php                             ← API mobile v1
│
├── .env
│   ├── DB_DATABASE=klassci_social
│   ├── MASTER_API_URL=https://admin.klassci.com/api
│   └── MASTER_API_TOKEN=xxx
│
└── composer.json
```

**Base de données dédiée** : `klassci_social` (nouvelle BDD)

**Principe clé** :
- `klassci-social` ne JAMAIS accéder directement aux BDD tenants
- TOUJOURS passer par Master API pour vérification/sync

---

**Phase 2 : API REST pour Mobile (6-12 mois après)**

```
routes/api.php
└── /api/v1/
    ├── /auth/sso            ← Login depuis tenant
    ├── /posts               ← CRUD posts
    ├── /comments            ← CRUD commentaires
    ├── /votes               ← Upvote/downvote
    ├── /communities         ← Liste communautés
    ├── /feed                ← Timeline personnalisée
    ├── /notifications       ← Notifs temps réel
    └── /students/{id}       ← Profils publics
```

**Documentation API** : OpenAPI/Swagger auto-généré

---

**Phase 3 : Microservices (si > 50k users actifs)**

```
┌──────────────────────┐
│  API Gateway         │  ← Reverse proxy (Nginx/Traefik)
│  (social.klassci.com)│
└──────────────────────┘
         │
         ├──▶ klassci-social-api      (Laravel - Posts/Comments/Votes)
         ├──▶ klassci-notification    (Node.js + Socket.io - Notifs temps réel)
         ├──▶ klassci-search          (Meilisearch - Full-text search)
         └──▶ klassci-media           (S3/CDN - Images/Vidéos)
```

**Communication inter-services** : RabbitMQ ou Redis Pub/Sub

---

#### 3.1. Fonctionnalités Sociales Déjà Présentes dans KLASSCI (Analyse 2 novembre 2025)

**🔍 Exploration Codebase** : Analyse complète des features sociales existantes dans les tenants KLASSCI

**✅ Infrastructure Solide Déjà En Place** :

**1. Système d'Annonces Avancé**
- **Modèle** : `ESBTPAnnonce` (`app/Models/ESBTPAnnonce.php`)
- **Table** : `esbtp_annonces`
- **Relations** : belongsToMany classes, belongsToMany etudiants
- **Pivot Table** : `esbtp_annonce_etudiant` avec tracking (`is_read`, `read_at`)
- **Features** :
  - Destinataires : Classes spécifiques ou tous étudiants
  - Types : info, warning, success, error
  - Priorité : haute, normale, basse
  - Marquer comme lu/non lu (AJAX)
  - Bouton "Marquer tout comme lu"
  - Filtres : Non lues, lues, par type, par priorité
  - Design moderne avec badges priorité

**2. Notifications Système**
- **Modèle** : `Notification` (`app/Models/Notification.php`)
- **Table** : `custom_notifications`
- **Scopes** : unread(), read(), ofType()
- **Features** :
  - Badge compteur sur dashboard
  - Actions avec liens
  - Page dédiée `/mes-notifications`

**3. Messages & Threads**
- **Modèle** : `Message` (`app/Models/Message.php`)
- **Support Threads** : via `parent_id` (réponses imbriquées)
- **Statuts** : is_read, read_at
- **Soft Deletes** : Archivage messages

**4. Dashboard Étudiant Moderne**
- **Vue** : `resources/views/dashboard/etudiant.blade.php`
- **Design** : ACASI Design System 2025
- **Sections** :
  - Header personnalisé ("Bienvenue, {nom}")
  - 6 Stat Cards (Matricule, Taux présence, Classe, Filière, Niveau, Notifications)
  - Cours d'Aujourd'hui (timeline)
  - Dernières Notes
  - Annonces pour la classe
- **Style** : Modern cards-based, responsive, icônes Font Awesome 6.4

**5. Pages "mes-*" Étudiants (11 pages)**
- `/mes-notes` - Notes avec filtres matière/période
- `/mon-emploi-temps` - Planning hebdomadaire interactif
- `/mes-absences` - Absences avec justification possible
- `/mes-evaluations` - Évaluations à venir/passées
- `/mon-bulletin` - Bulletins PDF downloadables
- `/mes-paiements` - Historique paiements scolarité
- `/mes-notifications` - Centre notifications
- `/mes-messages` - Annonces reçues (filtres avancés)
- Toutes avec middleware `auth, role:etudiant`

**6. Permissions Granulaires**
- **Rôle** : `etudiant` (Spatie Permission)
- **11 permissions** : view_own_profile, view_own_notes, view_own_grades, view_own_exams, view_own_evaluations, view_own_bulletin, view_own_attendances, view_own_attendance, view_own_timetable, view_own_schedule
- **Middleware** : Protection routes sensibles

**7. Profils Étudiants Riches**
- **Modèle** : `ESBTPEtudiant` (`app/Models/ESBTPEtudiant.php`)
- **Table** : `esbtp_etudiants`
- **Attributs** :
  - Matricule (auto-généré)
  - Nom, prénom, email, téléphone
  - Photo (`storage/photos/etudiants/`)
  - Date naissance, lieu naissance, nationalité
  - Statut (actif/inactif/abandon)
  - Tracking abandon (motif, date)
- **Relations** :
  - user(), inscriptions(), notes(), absences(), paiements()
  - classe(), parents(), evaluations(), bulletins()
- **Accesseurs** :
  - inscription_active, classe_active, nom_complet, age, photo_url

**8. Architecture Frontend Adaptée**
- **Stack** : Blade + Alpine.js (réactivité)
- **CSS** : Bootstrap 5.3 + Design System ACASI
- **JS** : Chart.js (statistiques), DataTables (tableaux)
- **Modals** : Bootstrap Modals
- **Design Variables CSS** :
  ```css
  --space-sm: 0.5rem;
  --space-md: 1rem;
  --space-lg: 1.5rem;
  --primary: #0453cb;
  --secondary: #5e91de;
  --success: #10b981;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  ```

**9. Stack Backend**
- Laravel 12.x (excellent pour social features)
- MySQL 8.x (relations complexes supportées)
- Redis (cache, queues) - déjà configuré
- AWS S3 (stockage photos étudiants)
- Laravel Sanctum (API tokens)

**📊 Évaluation Réutilisabilité** :

| Feature KLASSCI Actuelle | Réutilisable pour Social ? | Adaptations Nécessaires |
|--------------------------|----------------------------|-------------------------|
| Système annonces | ✅ OUI (90%) | Ajouter reactions/likes |
| Notifications | ✅ OUI (80%) | Temps réel (WebSockets) |
| Messages/Threads | ✅ OUI (70%) | Mentions @username |
| Profils étudiants | ✅ OUI (100%) | Sync cross-tenant |
| Permissions | ✅ OUI (100%) | Ajouter moderation roles |
| Dashboard design | ✅ OUI (90%) | Adapter feed layout |
| Frontend stack | ✅ OUI (100%) | Aucune |
| Backend stack | ✅ OUI (100%) | Aucune |

**💡 Avantages Compétitifs** :

1. **80% de l'infrastructure déjà construite** :
   - Annonces = Posts (structure identique)
   - Notifications = Notifications (même système)
   - Messages threads = Comments (déjà nested)
   - Profils riches = User profiles (complets)

2. **Design System mature** :
   - ACASI 2025 cohérent avec toutes les pages
   - Components réutilisables (cards, badges, buttons)
   - Responsive mobile-first

3. **Stack technique éprouvée** :
   - Laravel 12.x production-ready
   - MySQL 8.x scalable (4000+ étudiants actuels)
   - Redis déjà configuré pour cache/queues
   - S3 pour media (photos étudiants fonctionne)

4. **Permissions & Sécurité** :
   - Spatie Permission déjà en place
   - Middleware auth robuste
   - Audit trail (created_by, updated_by)
   - Soft deletes partout

**🎯 Conclusion** : Le réseau social ne sera **PAS une réécriture complète** mais une **évolution naturelle** des fonctionnalités existantes. Estimation : **60-70% du code réutilisable**.

---

#### 4. Modèle de Données Proposé

**Tables principales** :

```sql
-- Posts (publications)
CREATE TABLE social_posts (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,               -- Lien vers users
    community_id BIGINT,          -- Lien vers social_communities
    post_type ENUM('text', 'link', 'image', 'poll'),
    title VARCHAR(300),
    content TEXT,
    media_url VARCHAR(500),
    vote_score INT DEFAULT 0,     -- Cache pour performance
    comment_count INT DEFAULT 0,  -- Cache pour performance
    is_pinned BOOLEAN DEFAULT 0,
    is_locked BOOLEAN DEFAULT 0,
    deleted_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_community (community_id, created_at),
    INDEX idx_user (user_id),
    INDEX idx_vote_score (vote_score DESC)
);

-- Comments (commentaires threadés)
CREATE TABLE social_comments (
    id BIGINT PRIMARY KEY,
    post_id BIGINT,
    parent_id BIGINT NULL,        -- Pour threading
    user_id BIGINT,
    content TEXT,
    vote_score INT DEFAULT 0,
    depth INT DEFAULT 0,          -- 0 = top-level, max 3
    path VARCHAR(500),            -- Ex: "1/5/12" pour nested set
    deleted_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_post (post_id, path),
    INDEX idx_parent (parent_id)
);

-- Votes (upvotes/downvotes)
CREATE TABLE social_votes (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    votable_type VARCHAR(50),     -- Post ou Comment
    votable_id BIGINT,
    vote_type TINYINT,            -- 1 = upvote, -1 = downvote
    created_at TIMESTAMP,
    UNIQUE KEY unique_vote (user_id, votable_type, votable_id)
);

-- Communities (communautés basées sur classes/filières)
CREATE TABLE social_communities (
    id BIGINT PRIMARY KEY,
    name VARCHAR(100),
    slug VARCHAR(100) UNIQUE,
    description TEXT,
    community_type ENUM('classe', 'filiere', 'general', 'custom'),
    linked_id BIGINT NULL,        -- classe_id ou filiere_id
    icon VARCHAR(500),
    member_count INT DEFAULT 0,
    post_count INT DEFAULT 0,
    is_private BOOLEAN DEFAULT 0,
    created_by BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_type (community_type, linked_id)
);

-- Follows (abonnements)
CREATE TABLE social_follows (
    id BIGINT PRIMARY KEY,
    follower_id BIGINT,           -- Qui suit
    followable_type VARCHAR(50),  -- User ou Community
    followable_id BIGINT,         -- ID de l'entité suivie
    created_at TIMESTAMP,
    UNIQUE KEY unique_follow (follower_id, followable_type, followable_id)
);

-- Hashtags
CREATE TABLE social_hashtags (
    id BIGINT PRIMARY KEY,
    name VARCHAR(100) UNIQUE,
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP
);

CREATE TABLE social_post_hashtag (
    post_id BIGINT,
    hashtag_id BIGINT,
    PRIMARY KEY (post_id, hashtag_id)
);

-- Notifications sociales
CREATE TABLE social_notifications (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    type VARCHAR(50),             -- 'new_comment', 'upvote', 'mention', 'follow'
    data JSON,                    -- Détails de la notification
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    INDEX idx_user_unread (user_id, read_at)
);
```

**Relations avec tables existantes** :
- `users.id` ← `social_posts.user_id`
- `esbtp_classes.id` ← `social_communities.linked_id` (si type='classe')
- `esbtp_filieres.id` ← `social_communities.linked_id` (si type='filiere')

---

#### 5. Stack Technique Recommandée

**Backend** :
- ✅ **Laravel 12.x** (déjà en place)
- ✅ **Sanctum** (API tokens pour mobile)
- ✅ **Laravel Echo + Pusher** (notifications temps réel)
- ✅ **Spatie Media Library** (gestion médias)
- 🆕 **Laravel Scout + Meilisearch** (recherche full-text)
- 🆕 **Laravel Horizon** (gestion queues pour votes/notifications)
- 🆕 **Redis** (cache feed, vote counts)

**Frontend Web** :
- ✅ **Blade + Alpine.js** (déjà en place)
- 🆕 **Livewire 3** (réactivité sans SPA)
- 🆕 **Tailwind CSS** (déjà partiellement présent)

**Frontend Mobile** (future) :
- 🆕 **Flutter** ou **React Native**
- API REST via Laravel Sanctum

**Infrastructure** :
- ✅ **MySQL 8.x** (tables existantes)
- 🆕 **Redis** (cache + queues)
- 🆕 **Meilisearch** (search engine)
- ✅ **S3/DigitalOcean Spaces** (stockage médias)

---

#### 6. Estimations & Ressources

**Effort de développement** :

| Phase | Durée | Développeurs | Sprints | Coût estimé |
|-------|-------|--------------|---------|-------------|
| **Phase 1** : Posts + Comments + Votes | 3-4 mois | 2 devs | 6-8 sprints | ~40-50k€ |
| **Phase 2** : Notifications + Modération | 2-3 mois | 2 devs | 4-6 sprints | ~25-35k€ |
| **Phase 3** : Features avancées | 3-4 mois | 2-3 devs | 6-8 sprints | ~50-60k€ |
| **Total MVP** (Phase 1+2) | **5-7 mois** | **2 devs** | **10-14 sprints** | **~65-85k€** |

**Infrastructure supplémentaire** :

| Service | Coût mensuel | Usage |
|---------|--------------|-------|
| **Redis Cloud** (4GB) | ~30€/mois | Cache + queues |
| **Meilisearch Cloud** | ~50€/mois | Search engine |
| **Pusher** (10k connections) | ~50€/mois | Real-time notifications |
| **S3/Spaces** (500GB) | ~10€/mois | Stockage médias |
| **Total infra** | **~140€/mois** | Pour 1000 users actifs |

**Scalabilité** :
- 1000 utilisateurs actifs : **~140€/mois**
- 10 000 utilisateurs actifs : **~500€/mois**
- 100 000 utilisateurs actifs : **~2000€/mois** (microservices requis)

---

#### 7. Risques & Challenges

**🔴 Risques Majeurs** :

1. **Modération de contenu**
   - Risque : Contenu inapproprié, harcèlement, fake news
   - Mitigation : Système de rapports, modérateurs communautaires, filtres IA (Gemini)

2. **Performance à l'échelle**
   - Risque : Feed lent avec 10k+ posts, votes lents
   - Mitigation : Cache Redis agressif, queues asynchrones, denormalization

3. **Engagement utilisateur**
   - Risque : Adoption faible, contenu de mauvaise qualité
   - Mitigation : Gamification (badges), modérateurs actifs, contenu seed initial

4. **Coût infrastructure**
   - Risque : Explosion des coûts avec usage massif
   - Mitigation : Auto-scaling, CDN pour médias, archivage posts anciens

**🟡 Challenges Techniques** :

- **Nested comments** : Complexe à afficher efficacement (max 3 niveaux)
- **Vote spam** : Prévention via throttling + détection patterns
- **Real-time à l'échelle** : WebSocket coûteux, fallback polling
- **Recherche pertinente** : Ranking algorithm complexe

---

#### 8. Roadmap Proposée

**Q1 2026 : Phase 1 - MVP Social** 🟢
- ✅ Modèle de données (posts, comments, votes, communities)
- ✅ CRUD posts + commentaires
- ✅ Système de votes (upvote/downvote)
- ✅ Communautés auto (1 par classe + filière)
- ✅ Feed chronologique simple
- ✅ Interface web Blade + Alpine

**Q2 2026 : Phase 2 - Engagement** 🟡
- ✅ Notifications temps réel (Laravel Echo)
- ✅ Système de follow (users + communities)
- ✅ Mentions @username
- ✅ Hashtags #topic
- ✅ Modération (reports, admin panel)
- ✅ Recherche basique (titres + contenu)

**Q3 2026 : Phase 3 - Avancées** 🟠
- ✅ Messages privés (chat 1-to-1)
- ✅ Sondages intégrés
- ✅ Partage de fichiers (notes de cours)
- ✅ API mobile v1 (Sanctum)
- ✅ Badges & gamification
- ✅ Recherche full-text (Meilisearch)

**Q4 2026 : Phase 4 - Mobile & Scale** 🔴
- ✅ Application mobile (Flutter/RN)
- ✅ Recommandations IA (Gemini)
- ✅ Analytics & insights
- ✅ Microservices (si nécessaire)
- ✅ Monétisation (premium features ?)

---

#### 9. Recommandations Stratégiques

**✅ FAISABLE - Recommandé de démarrer si :**
1. Budget disponible : **~70k€** (Phase 1+2)
2. Équipe technique : **2 développeurs Laravel senior** (6-12 mois)
3. Base utilisateurs : **500+ étudiants actifs** sur au moins 3 tenants
4. Engagement communautaire : **Modérateurs volontaires** identifiés
5. Vision long terme : **Plan de monétisation** (premium, pub, partenariats)

**🚀 Points de Départ Immédiats** :

1. **Prototype léger (1 mois)** :
   - Réutiliser `esbtp_annonces` comme base
   - Ajouter système de commentaires simple
   - Tester engagement sur 1 tenant pilote

2. **Validation produit** :
   - Interviews étudiants : Quels besoins réels ?
   - Benchmark concurrents : Edmodo, Piazza, Discord éducatif
   - Mesurer usage actuel des annonces existantes

3. **Architecture progressive** :
   - Phase 1 : Module Laravel monolithique (rapide)
   - Phase 2 : API-first (préparation mobile)
   - Phase 3 : Microservices (si > 10k users actifs)

**⚠️ Risques à mitiger AVANT de démarrer** :

- 📊 **Étude marché** : Y a-t-il vraiment un besoin ? (enquête étudiants)
- 💰 **Business model** : Comment financer l'infrastructure long terme ?
- 👥 **Modération** : Qui va modérer 24/7 ? (coût humain)
- 🔒 **Légal** : CGU, RGPD, responsabilité contenu

---

#### 10. Benchmarks & Inspirations

**Plateformes similaires existantes** :

| Plateforme | Points forts | À adapter pour KLASSCI |
|------------|--------------|------------------------|
| **Piazza** | Q&A académique, endorsements profs | Intégrer enseignants comme modérateurs |
| **Edmodo** | Classes privées, devoirs intégrés | Lien avec évaluations KLASSCI |
| **Discord (éducatif)** | Channels par matière, voix/vidéo | Trop complexe, garder text-first |
| **Reddit** | Voting, threading, modération communautaire | Modèle principal |
| **Twitter** | Rapidité, hashtags, mentions | Pour annonces courtes |
| **Slack** | Partage fichiers, intégrations | Pour groupes de travail |

**Features différenciatrices KLASSCI** :

- 🎓 **Contexte académique** : Posts liés à matières/cours
- 📊 **Analytics profs** : Qui pose quoi ? (insights pédagogiques)
- 🤖 **IA intégrée** : Gemini pour suggestions, résumés threads
- 🌍 **Multilingue** : Français + langues locales (Côte d'Ivoire)
- 💼 **Emploi** : Section offres stages/jobs (future)

---

### 📝 Conclusion - Verdict de Faisabilité

**🟢 VERDICT : FAISABLE avec conditions**

Le réseau social KLASSCI est **techniquement et économiquement faisable** sur une timeline de **6-12 mois** pour un MVP complet (Phase 1+2).

**Facteurs de succès critiques** :
1. ✅ **Architecture Laravel solide** déjà en place
2. ✅ **Base utilisateurs existante** (étudiants + enseignants)
3. ✅ **Infrastructure SaaS mature** (multi-tenant)
4. ⚠️ **Budget nécessaire** : ~70k€ (MVP)
5. ⚠️ **Équipe technique** : 2 devs senior x 6 mois
6. ⚠️ **Engagement communautaire** : Modérateurs + contenu seed

**Prochaines étapes recommandées** :
1. **Validation produit** : Enquête étudiants (besoins réels ?)
2. **Prototype 1 mois** : Posts + comments sur 1 tenant pilote
3. **Go/No-Go** : Décision basée sur engagement prototype
4. **Phase 1 si Go** : Développement MVP 3-4 mois

---

## 📝 TODO & Prochaines Étapes

### 🔴 Priorité Haute

#### Blocage Classes Pleines - Inscriptions

**Page à modifier** : `/esbtp/inscriptions/create`

**À implémenter** :
- Affichage capacité "Places disponibles: X / Y"
- Blocage bouton submit quand `available_places <= 0`
- Tooltip au survol du bouton désactivé
- Seuils d'alerte : Vert (> 5), Jaune (≤ 5), Rouge (0)

**Backend déjà prêt** :
- Endpoint : `GET /esbtp/classes/{id}/available-places`
- Service : `ClasseManagementService::getAvailablePlaces()`

**Pattern à copier** : Page réinscription (`/esbtp/reinscription/{id}/finaliser`)

**Estimation** : 1-2h

---

### 🟡 Priorité Moyenne

#### Refactoring Controllers Volumineux

**Phase 2 - Internal Refactoring** :
- Extraire `BulletinGenerationService` (ESBTPBulletinController : 6852 lignes)
- Extraire `BulletinPdfService`
- Refactorer ESBTPComptabiliteController (4150 lignes)
- Refactorer ESBTPInscriptionController (3275 lignes)

**Garantie** : Routes, JSON API, variables vues restent identiques

**Estimation** : 1 mois

---

#### Chatbot - Compléter Intents

**À implémenter** :
- `get_etudiants` : Liste étudiants avec filtres
- `get_classes` : Stats classes

**Améliorations** :
- Contexte conversationnel : "Et pour la Deuxième Année ?"
- Validation sémantique : Vérifier existence filtres
- Suggestions proactives

**Estimation** : 1-2 semaines

---

### 🟢 Backlog

#### Répartition CM/TD/TP dans Planning Général

**Tables** : `esbtp_planifications_academiques` a déjà `volume_horaire_cm`, `volume_horaire_td`, `volume_horaire_tp`

**À faire** :
- Ajouter champs dans modal configuration volumes horaires
- Validation : CM + TD + TP = volume total
- Affichage dans `matieres.show`

**Estimation** : 4-6h

---

## 📦 Archive

**Historique complet avant Octobre 2025** : Voir [CLAUDE_ARCHIVE.md](CLAUDE_ARCHIVE.md)

Contenu archivé :
- Édition groupée résultats classe (16 octobre 2025)
- Marquage manuel attendance enseignants (17 octobre 2025)
- Calcul heures effectuées (17 octobre 2025)
- Système AJAX marquage présences étudiants (17 octobre 2025)
- Fixes terminologie attendances.index (17 octobre 2025)
- API LMS Integration (19 octobre 2025)
- Pattern AJAX Chargement Matières (19 octobre 2025)
- Support Visioconférences LMS (19 octobre 2025)
- Soumission notes évaluations en ligne (19 octobre 2025)
- Et bien d'autres...

---

## 🚦 Commandes Utiles

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
```

---

## 📝 Configuration Essentielle

### SMTP
```env
MAIL_MAILER=smtp
MAIL_HOST=mail.klassci.com
MAIL_PORT=465
MAIL_USERNAME=support@klassci.com
MAIL_ENCRYPTION=ssl
```

### Gemini API
```env
GEMINI_API_KEY=your_key_here
GEMINI_MODEL=gemini-2.0-flash-exp
```

### Master API
```env
MASTER_API_URL=http://localhost:8001/api
MASTER_API_TOKEN=your_token_here
TENANT_CODE=presentation
```

---

## 🎨 Design System

**Dashboard** : `dashboard-acasi`
- Cartes : `main-card`, `stat-card`
- Badges : `status-badge-success/danger/warning`
- Boutons : `btn-acasi primary/secondary`
- Tables : `table-modern`

**Couleurs KLASSCI** :
- Gradient principal : `#0453cb → #5e91de`
- Texte : `#1e293b` (dark), `#64748b` (gray)

---

*Dernière mise à jour: 6 novembre 2025 - 18h30*

---

## 📚 Références & Ressources

**Réseaux sociaux éducatifs 2025** :
- Tendances réseaux sociaux : https://www.ekole.fr/blog/
- Plateformes étudiantes : Piazza, Edmodo, Discord Éducatif

**Architecture Laravel** :
- Laravel Best Practices 2025 : https://benjamincrozat.com/laravel-architecture-best-practices
- Building Social Networks with Laravel : https://www.surfsidemedia.in/
- Reddit Clone Laravel : https://github.com/geosem42/laravel-reddit

**Stack technique** :
- Laravel Scout + Meilisearch : https://laravel.com/docs/scout
- Laravel Echo + Pusher : https://laravel.com/docs/broadcasting
- Laravel Horizon : https://laravel.com/docs/horizon
