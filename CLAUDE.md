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
- **Développement local** : Puppeteer local (si disponible)
- **Production (web44)** : Browserless.io API (Chrome headless cloud)

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

*Dernière mise à jour: 1er novembre 2025*
