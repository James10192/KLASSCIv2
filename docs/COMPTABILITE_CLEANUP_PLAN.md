# Plan de Nettoyage - Module Comptabilité KLASSCI

**Date de création** : 7 novembre 2025
**Dernière mise à jour** : 7 novembre 2025 - 21h30 (avec clarifications utilisateur)
**Branche** : presentation
**Contexte** : Suppression des fonctionnalités obsolètes du module comptabilité pour préparer une refonte avec architecture multi-tenant appropriée.

---

## ⚠️ MISE À JOUR MAJEURE (7 novembre 2025 - 21h30)

**IMPORTANT** : Ce document a été mis à jour avec les clarifications définitives de l'utilisateur. Toutes les sections marquées "À VÉRIFIER" ont maintenant été confirmées.

**Nouveautés** :
- ✅ **Documentation complète** du module créée : `COMPTABILITE_MODULE_DOCUMENTATION.md`
- ✅ **Tous les modules obsolètes confirmés** par l'utilisateur
- ✅ **Routes actives identifiées** via analyse de la sidebar
- ✅ **Exports clarifiés** : Seuls les exports paiements sont utilisés

**Référence complète** : Voir `docs/COMPTABILITE_MODULE_DOCUMENTATION.md` pour la vue d'ensemble complète du module.

---

## 📋 Table des Matières

1. [Résumé Exécutif](#résumé-exécutif)
2. [Clarifications Utilisateur](#clarifications-utilisateur)
3. [Code à Supprimer](#code-à-supprimer)
4. [Code à Préserver](#code-à-préserver)
5. [Fonctionnalités à Recréer](#fonctionnalités-à-recréer)
6. [Impact & Breaking Changes](#impact--breaking-changes)
7. [Plan d'Exécution](#plan-dexécution)
8. [Validation Finale](#validation-finale)

---

## Résumé Exécutif

**Problème identifié** :
Le contrôleur `ESBTPComptabiliteController.php` contient **4150 lignes** (8.3x la limite recommandée de 500 lignes) avec de nombreuses fonctionnalités obsolètes jamais mises en production.

**Objectif** :
Nettoyer le code obsolète pour réduire la dette technique et préparer la recréation de fonctionnalités avec une architecture multi-tenant robuste.

**Modules concernés** (TOUS CONFIRMÉS OBSOLÈTES) :
- ❌ **Salaires** (CRUD complet) - Obsolète, jamais finalisé, à recréer
- ❌ **Fournisseurs** (CRUD complet) - Obsolète, à supprimer
- ❌ **Factures** (CRUD complet) - Obsolète, à supprimer et recréer avec multi-tenant
- ❌ **Dépenses** (CRUD complet) - Obsolète, mal implémenté pour honoraires, à recréer
- ❌ **Frais scolarité hard-coded** - Obsolète, remplacé par système flexible
- ❌ **Bourses** - Obsolète, à recréer plus tard
- ❌ **Bons de sortie rapide** - Obsolète
- ❌ **Relances paiements** - Obsolète, à recréer
- ❌ **Dashboard comptabilité** - Obsolète, à recréer
- ❌ **Configuration comptabilité** - Obsolète, remplacé par frais.configure
- ❌ **Exports comptables** (sauf exports paiements) - Obsolètes

**Modules ACTIFS (À PRÉSERVER)** :
- ✅ **Gestion des Frais** (ESBTPFraisController) - 37 routes actives
- ✅ **Gestion des Paiements** (ESBTPPaiementController) - 36 routes actives
- ✅ **Exports Paiements** (Excel, CSV, PDF) - BIEN IMPLÉMENTÉS

**Estimation réduction** :
~3200+ lignes de code à supprimer (70-75% du module).

---

## Clarifications Utilisateur

Cette section documente toutes les confirmations définitives de l'utilisateur concernant les modules obsolètes.

### 📍 Modules Confirmés Obsolètes

#### 1. Salaires ❌
**Citation utilisateur** :
> "déjà tout cela n'est pas utilisé j'avais commencé mais ce n'était pas au point donc j'ai arrêté le developpement et ce n'est pas bon donc je n'utilise plus"

**Décision** : Supprimer complètement, recréer plus tard avec bonnes fondations.

---

#### 2. Fournisseurs ❌
**Citation utilisateur** :
> "Fournisseurs : Pas utilisé donc obsolete"

**Décision** : Supprimer complètement (controller, modèle, routes, vues).

---

#### 3. Factures ❌
**Citation utilisateur** :
> "Factures : des features test à supprimer, je dois récréer cela avec de bonnes bases de reflexion et une bonne configuration pour s'adapter à tous les tenants"

**Décision** : Supprimer le CRUD actuel, garder le modèle comme référence pour future recréation avec architecture multi-tenant.

---

#### 4. Frais Scolarité Hard-Coded ❌
**Citation utilisateur** :
> "Frais Scolarité : à supprimer j'ai vu que les frais de scolarité codé en dure posait un soucis de flexibilité donc j'ai permis de tout créer de zéro et de les configurer chaque catégorie de frais (http://localhost:8000/esbtp/frais)"

**Remplacement** : Système flexible de catégories de frais via `ESBTPFraisController`.

**Décision** : Supprimer toutes les routes/méthodes avec montants hard-codés.

---

#### 5. Bourses ❌
**Citation utilisateur** :
> "Bourses : Obsolète après je vais m'y pencher pour faire un truc bien plus optimal"

**Décision** : Supprimer, recréer plus tard avec architecture optimale.

---

#### 6. Bons de Sortie Rapide ❌
**Citation utilisateur** : Marqué comme obsolète (implicite).

**Décision** : Supprimer complètement.

---

#### 7. Dépenses ❌
**Citation utilisateur** :
> "Dépenses : ça je pensais à faire l'honoraire des ensiegnants donc je fois supprimer et bien le refaire pour qu'il soit autant flexible que categorie de frais j'ai documenté les honoraires dans les docs CLAUDE.md"

**Raison** : Mal implémenté pour honoraires enseignants, manque de flexibilité.

**Décision** : Supprimer CRUD complet, recréer avec architecture similaire à `ESBTPFraisCategory`.

---

#### 8. Relances Paiements ❌
**Citation utilisateur** :
> "Relances Paiements : pareil aussi ici" (needs recreation)

**Décision** : Supprimer, recréer avec système de notifications automatiques.

**Note** : Ne pas confondre avec les routes actives de relances dans `ESBTPFraisController` :
```php
GET  /esbtp/frais/{category}/overdue-students     -> getStudentsWithOverduePayments()
POST /esbtp/frais/{category}/schedule-reminders   -> scheduleAutomaticReminders()
```

---

#### 9. Dashboard Comptabilité ❌
**Citation utilisateur** :
> "Dashboard Comptabilité : et ici" (needs recreation)

**Fichier confirmé obsolète** : `dashboard-test.blade.php`

**Décision** : Supprimer dashboard actuel, recréer avec KPIs pertinents.

---

#### 10. Configuration Comptabilité ❌
**Citation utilisateur** :
> "Configuration : les pages que j'utilise pour la configuration au niveau de la comptabilité sont uniquement ceux des configurations de catégories de frais [...] http://localhost:8000/esbtp/frais/configure et http://localhost:8000/esbtp/frais/optional-config"

**Preuve dans la sidebar** (ligne 1822-1825 `app.blade.php`) :
```blade
<!--<a href="{{ route('esbtp.comptabilite.configuration') }}" class="menu-sublink">
    <span>Configuration</span>
</a>-->
```

**Décision** : Supprimer route `comptabilite.configuration`, utiliser uniquement `frais.configure` et `frais.optional-config`.

---

#### 11. Exports Comptables (Partiels) ❌
**Citation utilisateur** :
> "Export Comptable : le seul export comptable que j'utilise et j'ai bien travaillé c'est l'export des paiement fait ici 'http://localhost:8000/esbtp/paiements'"

**Exports ACTIFS** (à conserver) :
```php
GET /esbtp/paiements/export/excel  -> exportExcel()  ✅
GET /esbtp/paiements/export/csv    -> exportCsv()    ✅
GET /esbtp/paiements/export/pdf    -> exportPdf()    ✅
```

**Exports OBSOLÈTES** (à supprimer) :
- Export salaires PDF
- Export factures
- Export dépenses
- Export dashboard
- Tous autres exports dans `ESBTPComptabiliteController`

---

### 📊 Sidebar Navigation (Source de Vérité)

**Fichier** : `resources/views/layouts/app.blade.php` (lignes 1797-1828)

**Liens ACTIFS** exposés en production :
```blade
<a href="{{ route('esbtp.frais.index') }}">Gestion des Frais</a>
<a href="{{ route('esbtp.frais.configure') }}">Configuration Frais</a>
<a href="{{ route('esbtp.paiements.index') }}">Liste des Paiements</a>
<a href="{{ route('esbtp.paiements.suivi-categories') }}">Suivi par Catégorie</a>
```

**Liens COMMENTÉS** (obsolètes) :
```blade
<!--<a href="{{ route('esbtp.comptabilite.configuration') }}">Configuration</a>-->
```

**Verdict** : La sidebar ne montre QUE ce qui est utilisé. Tout le reste est obsolète.

---

## Code à Supprimer

### 1. Module Salaires (COMPLET) ❌

**Raison** : Développement commencé mais jamais finalisé. Pas utilisé en production.

#### Controller Methods
**Fichier** : `app/Http/Controllers/ESBTPComptabiliteController.php`

```php
// Lignes à supprimer
Line 1698: public function salaires()
Line 1716: public function createSalaire()
Line 1729: public function storeSalaire(Request $request)
Line 1788: public function showSalaire($id)
Line 1799: public function editSalaire($id)
Line 1814: public function updateSalaire(Request $request, $id)
Line 1874: public function destroySalaire($id)
Line 1886: public function bulletinSalaire($id)
Line 1916: public function updateStatusSalaire($id, $status)
```

**Total** : ~250 lignes de code

#### Routes
**Fichier** : `routes/web.php` (lignes 1397-1406)

```php
Route::get('/salaires', [ESBTPComptabiliteController::class, 'salaires'])->name('salaires');
Route::get('/salaires/create', [ESBTPComptabiliteController::class, 'createSalaire'])->name('salaires.create');
Route::post('/salaires', [ESBTPComptabiliteController::class, 'storeSalaire'])->name('salaires.store');
Route::get('/salaires/{id}', [ESBTPComptabiliteController::class, 'showSalaire'])->name('salaires.show');
Route::get('/salaires/{id}/edit', [ESBTPComptabiliteController::class, 'editSalaire'])->name('salaires.edit');
Route::put('/salaires/{id}', [ESBTPComptabiliteController::class, 'updateSalaire'])->name('salaires.update');
Route::delete('/salaires/{id}', [ESBTPComptabiliteController::class, 'destroySalaire'])->name('salaires.destroy');
Route::get('/salaires/{id}/bulletin', [ESBTPComptabiliteController::class, 'bulletinSalaire'])->name('salaires.bulletin');
Route::post('/salaires/{id}/status', [ESBTPComptabiliteController::class, 'updateStatusSalaire'])->name('salaires.updateStatus');
```

**Total** : 9 routes

#### Views
**Fichier** : `resources/views/esbtp/comptabilite/salaires/`

```
index.blade.php       (~200 lignes) - Liste des salaires
create.blade.php      (~150 lignes) - Formulaire création
edit.blade.php        (~150 lignes) - Formulaire édition
show.blade.php        (~180 lignes) - Détails salaire
bulletin.blade.php    (~120 lignes) - Bulletin de paie PDF
```

**Total** : 5 fichiers, ~800 lignes

---

### 2. Module Fournisseurs (COMPLET) ❌

**Raison** : Obsolète, pas utilisé en production.

#### Controller Methods
**Fichier** : `app/Http/Controllers/ESBTPComptabiliteController.php`

```php
// Lignes à supprimer
Line 1634: public function fournisseurs()
Line 1644: public function createFournisseur()
Line 1652: public function storeFournisseur(Request $request)
Line 1678: public function editFournisseur($id)
Line 2795: public function destroyFournisseur($id)
Line 2073: public function storeFournisseurAjax(Request $request)
```

**Total** : ~180 lignes de code

**Note** : La méthode `showFournisseur($id)` n'existe PAS dans le controller mais une route y fait référence (orpheline).

#### Routes
**Fichier** : `routes/web.php` (lignes 1386, 1407-1414)

```php
Route::post('/fournisseurs/ajax', [ESBTPComptabiliteController::class, 'storeFournisseurAjax'])->name('fournisseurs.ajax.store');
Route::get('/fournisseurs', [ESBTPComptabiliteController::class, 'fournisseurs'])->name('fournisseurs');
Route::get('/fournisseurs/create', [ESBTPComptabiliteController::class, 'createFournisseur'])->name('fournisseurs.create');
Route::post('/fournisseurs', [ESBTPComptabiliteController::class, 'storeFournisseur'])->name('fournisseurs.store');
Route::get('/fournisseurs/{id}', [ESBTPComptabiliteController::class, 'showFournisseur'])->name('fournisseurs.show'); // ⚠️ MÉTHODE MANQUANTE
Route::get('/fournisseurs/{id}/edit', [ESBTPComptabiliteController::class, 'editFournisseur'])->name('fournisseurs.edit');
Route::put('/fournisseurs/{id}', [ESBTPComptabiliteController::class, 'updateFournisseur'])->name('fournisseurs.update'); // ⚠️ MÉTHODE MANQUANTE
Route::delete('/fournisseurs/{id}', [ESBTPComptabiliteController::class, 'destroyFournisseur'])->name('fournisseurs.destroy');
```

**Total** : 8 routes (dont 2 orphelines)

#### Views
**Fichier** : `resources/views/esbtp/comptabilite/fournisseurs/`

```
index.blade.php    (~150 lignes) - Liste fournisseurs
create.blade.php   (~120 lignes) - Formulaire création
edit.blade.php     (~120 lignes) - Formulaire édition
```

**Total** : 3 fichiers, ~390 lignes

---

### 3. Module Factures (COMPLET) ❌

**Raison** : À supprimer et recréer avec architecture multi-tenant appropriée.

**Décision utilisateur** : "je dois récréer cela avec de bonnes bases de reflexion et une bonne configuration pour s'adapter à tous les tenants"

#### Controller Methods
**Fichier** : `app/Http/Controllers/ESBTPComptabiliteController.php`

```php
// Lignes à supprimer
Line 1687: public function factures()
Line 4063: public function createFacture()
Line 4074: public function showFacture($id)
Line 4083: public function storeFacture(Request $request)
Line 4093: public function editFacture($id)
```

**Méthodes manquantes** (routes orphelines) :
- `updateFacture(Request $request, $id)` - Route ligne 1422
- `destroyFacture($id)` - Route ligne 1423
- `pdfFacture($id)` - Route ligne 1424

**Total** : ~200 lignes de code

#### Routes
**Fichier** : `routes/web.php` (lignes 1416-1424)

```php
Route::get('/factures', [ESBTPComptabiliteController::class, 'factures'])->name('factures');
Route::get('/factures/create', [ESBTPComptabiliteController::class, 'createFacture'])->name('factures.create');
Route::post('/factures', [ESBTPComptabiliteController::class, 'storeFacture'])->name('factures.store');
Route::get('/factures/{id}', [ESBTPComptabiliteController::class, 'showFacture'])->name('factures.show');
Route::get('/factures/{id}/edit', [ESBTPComptabiliteController::class, 'editFacture'])->name('factures.edit');
Route::put('/factures/{id}', [ESBTPComptabiliteController::class, 'updateFacture'])->name('factures.update'); // ⚠️ MÉTHODE MANQUANTE
Route::delete('/factures/{id}', [ESBTPComptabiliteController::class, 'destroyFacture'])->name('factures.destroy'); // ⚠️ MÉTHODE MANQUANTE
Route::get('/factures/{id}/pdf', [ESBTPComptabiliteController::class, 'pdfFacture'])->name('factures.pdf'); // ⚠️ MÉTHODE MANQUANTE
```

**Total** : 8 routes (dont 3 orphelines)

#### Views
**Fichier** : `resources/views/esbtp/comptabilite/factures/`

```
index.blade.php    (~180 lignes) - Liste factures
create.blade.php   (~250 lignes) - Formulaire création
edit.blade.php     (~240 lignes) - Formulaire édition
show.blade.php     (~200 lignes) - Détails facture
```

**Total** : 4 fichiers, ~870 lignes

#### Model
**Fichier** : `app/Models/ESBTPFacture.php` (208 lignes)

⚠️ **ATTENTION** : Le modèle sera supprimé mais devra être recréé avec :
- Configuration audit correcte
- Colonnes workflow multi-tenant
- Relations polymorphes pour multi-tenant
- Validation des montants

---

### 4. Configuration Comptabilité (Doublon) ❌

**Raison** : Configuration active dans `ESBTPFraisController`. Méthodes dans `ESBTPComptabiliteController` sont obsolètes.

#### Controller Methods
**Fichier** : `app/Http/Controllers/ESBTPComptabiliteController.php`

```php
// Lignes à supprimer
Line 1609: public function configuration()
Line 3110: public function configurationComptabilite()
```

**Total** : ~150 lignes de code (estimé avec logique de sauvegarde)

#### Configuration Active (À PRÉSERVER)
**Fichier** : `app/Http/Controllers/ESBTPFraisController.php`

```php
// Méthodes actives - NE PAS TOUCHER
public function configure() // Ligne ~??
public function optionalConfig() // Ligne ~??
```

**Routes actives** : `routes/web.php` (lignes 267-268)
```php
Route::get('frais/configure', [\App\Http\Controllers\ESBTPFraisController::class, 'configure'])->name('frais.configure');
Route::get('frais/optional-config', [\App\Http\Controllers\ESBTPFraisController::class, 'optionalConfig'])->name('frais.optional-config');
```

---

### 5. Dashboard Test ❌

**Raison** : Fichier de test obsolète.

#### View
**Fichier** : `resources/views/esbtp/comptabilite/dashboard-test.blade.php`

**Total** : 1 fichier, ~200 lignes (estimé)

---

### 6. Frais Scolarité (À VÉRIFIER) ⚠️

**Statut** : Incertain, nécessite validation utilisateur.

#### Controller Methods
**Fichier** : `app/Http/Controllers/ESBTPComptabiliteController.php`

```php
// Méthodes à vérifier
Line 1214: public function fraisScolarite()
Line 1244: public function createFraisScolarite()
Line 1256: public function storeFraisScolarite(Request $request)
Line 1313: public function showFraisScolarite($id)
Line 1324: public function editFraisScolarite($id)
Line 1337: public function updateFraisScolarite(Request $request, $id)
Line 1397: public function destroyFraisScolarite($id)
```

**Total** : ~200 lignes de code

#### Routes
**Fichier** : `routes/web.php` (lignes 1371-1378)

```php
Route::get('/frais-scolarite', [ESBTPComptabiliteController::class, 'fraisScolarite'])->name('frais-scolarite');
Route::get('/frais-scolarite/create', [ESBTPComptabiliteController::class, 'createFraisScolarite'])->name('frais-scolarite.create');
Route::post('/frais-scolarite', [ESBTPComptabiliteController::class, 'storeFraisScolarite'])->name('frais-scolarite.store');
Route::get('/frais-scolarite/{id}', [ESBTPComptabiliteController::class, 'showFraisScolarite'])->name('frais-scolarite.show');
Route::get('/frais-scolarite/{id}/edit', [ESBTPComptabiliteController::class, 'editFraisScolarite'])->name('frais-scolarite.edit');
Route::put('/frais-scolarite/{id}', [ESBTPComptabiliteController::class, 'updateFraisScolarite'])->name('frais-scolarite.update');
Route::delete('/frais-scolarite/{id}', [ESBTPComptabiliteController::class, 'destroyFraisScolarite'])->name('frais-scolarite.destroy');
```

**Total** : 7 routes

**Question utilisateur** : Ce module est-il utilisé en production ou est-il obsolète comme les autres ?

---

### 7. Bourses (À VÉRIFIER) ⚠️

**Statut** : Incertain, nécessite validation utilisateur.

#### Controller Methods
**Fichier** : `app/Http/Controllers/ESBTPComptabiliteController.php`

```php
// Méthodes à vérifier
Line 1421: public function bourses()
Line 1437: public function createBourse()
Line 1452: public function storeBourse(Request $request)
Line 1509: public function showBourse($id)
Line 1523: public function editBourse($id)
Line 1539: public function updateBourse(Request $request, $id)
Line 1594: public function destroyBourse($id)
```

**Total** : ~180 lignes de code

#### Routes
**Fichier** : `routes/web.php` (lignes 1379-1385)

```php
Route::get('/bourses', [ESBTPComptabiliteController::class, 'bourses'])->name('bourses');
Route::get('/bourses/create', [ESBTPComptabiliteController::class, 'createBourse'])->name('bourses.create');
Route::post('/bourses', [ESBTPComptabiliteController::class, 'storeBourse'])->name('bourses.store');
Route::get('/bourses/{id}', [ESBTPComptabiliteController::class, 'showBourse'])->name('bourses.show');
Route::get('/bourses/{id}/edit', [ESBTPComptabiliteController::class, 'editBourse'])->name('bourses.edit');
Route::put('/bourses/{id}', [ESBTPComptabiliteController::class, 'updateBourse'])->name('bourses.update');
Route::delete('/bourses/{id}', [ESBTPComptabiliteController::class, 'destroyBourse'])->name('bourses.destroy');
```

**Total** : 7 routes

**Question utilisateur** : Ce module est-il utilisé en production ou est-il obsolète comme les autres ?

---

### 8. Bons de Sortie Rapide (À VÉRIFIER) ⚠️

**Statut** : Incertain, utilisateur ne sait pas quel module est utilisé.

#### Controller Method
**Fichier** : `app/Http/Controllers/ESBTPComptabiliteController.php`

```php
// Méthode à vérifier
Line 4103: public function createBonRapide(Request $request)
```

**Total** : ~50 lignes de code (estimé)

**Question utilisateur** :
- Le module de bons de sortie rapide est-il utilisé ?
- Ou utilisez-vous le workflow complet des bons de sortie (ESBTPBonSortieController) ?
- Les deux sont-ils utilisés pour des cas différents ?

---

## Code à Préserver

### ✅ Fonctionnalités Actives & Utilisées

**Modules à NE PAS TOUCHER** :

#### 1. Paiements (CRUD complet)
- **Controller** : `ESBTPComptabiliteController` - Méthodes paiements() → destroyPaiement()
- **Model** : `ESBTPPaiement` (380 lignes) - COMPLET ET FONCTIONNEL
- **Routes** : `/esbtp/comptabilite/paiements/*`
- **Views** : `resources/views/esbtp/comptabilite/paiements/*`

**Caractéristiques** :
- Génération automatique numéros de reçu
- Audit logging complet
- Relations avec inscriptions, étudiants, années universitaires
- Support JSON metadata
- Workflow validation robuste

---

#### 2. Dépenses (CRUD complet)
- **Controller** : `ESBTPComptabiliteController` - Méthodes depenses() → destroyDepense()
- **Model** : `ESBTPDepense` (299 lignes) - COMPLET ET FONCTIONNEL
- **Routes** : `/esbtp/comptabilite/depenses/*`
- **Views** : `resources/views/esbtp/comptabilite/depenses/*`

**Caractéristiques** :
- Workflow approbation (statut_workflow, workflow_data JSON)
- Relation avec bons de sortie (bon_sortie_id)
- Audit logging complet
- Numéro bon unique

---

#### 3. Bons de Sortie (Workflow complet)
- **Controller** : `ESBTPBonSortieController` (séparé) - À VÉRIFIER SI UTILISÉ
- **Model** : `ESBTPBonSortie` - À VÉRIFIER
- **Routes** : `/esbtp/bons-sortie/*` - À VÉRIFIER

**⚠️ À CLARIFIER** : Confusion entre deux modules possibles :
- Module bons de sortie complet (ESBTPBonSortieController)
- Création rapide dans comptabilité (createBonRapide)

---

#### 4. Configuration Frais
- **Controller** : `ESBTPFraisController::configure()` et `::optionalConfig()`
- **Routes** : `/esbtp/frais/configure` et `/esbtp/frais/optional-config`
- **Models** : `ESBTPFraisCategory`, `ESBTPFraisSubscription`
- **Views** : `resources/views/esbtp/frais/*`

**Usage confirmé par utilisateur** :
> "les pages que j'utilise pour la configuration au niveau de la comptabilité sont uniquement ceux des configurations de catégories de frais"

---

#### 5. Relances Paiements
- **Controller** : `ESBTPComptabiliteController` - Méthodes relances() → exportRelances()
- **Model** : `ESBTPRelance` - À VÉRIFIER SI EXISTE
- **Routes** : `/esbtp/comptabilite/relances/*`
- **Views** : `resources/views/esbtp/comptabilite/relances/*` - À VÉRIFIER

**Méthodes identifiées** :
```php
public function relances()
public function createRelance()
public function storeRelance(Request $request)
public function showRelance($id)
public function editRelance($id)
public function updateRelance(Request $request, $id)
public function destroyRelance($id)
public function exportRelances()
```

**⚠️ À CONFIRMER** : Module utilisé en production ?

---

#### 6. Dashboard Comptabilité
- **Controller** : `ESBTPComptabiliteController::index()` (ligne ~40)
- **Route** : `/esbtp/comptabilite` (route principale)
- **View** : `resources/views/esbtp/comptabilite/index.blade.php`

**KPI affichés** :
- Total encaissements du mois
- Total dépenses du mois
- Solde
- Graphiques évolution (via AnalyticsPredictifService)

**Services associés** :
- `ComptabiliteService`
- `AIAnalyticsService`
- `AnalyticsPredictifService`

---

#### 7. Export Comptable
- **Controller** : `ESBTPComptabiliteController::export()` - À VÉRIFIER SI COMPLET
- **Route** : `/esbtp/comptabilite/export`

**⚠️ Audit précédent** : Signalé comme incomplet dans CLAUDE.md ligne 167-184

---

## Fonctionnalités à Recréer

### 🔄 Modules à Refaire Avec Architecture Multi-Tenant

#### 1. Module Salaires ⏭️

**Raison de la refonte** :
- Code actuel non finalisé
- Aucune considération multi-tenant
- Workflow approbation manquant
- Pas de gestion des cotisations sociales

**Nouvelles exigences** :
- Support multi-tenant (configuration par établissement)
- Workflow approbation (Comptable → Directeur → Validé)
- Calcul automatique cotisations (CNPS, impôts)
- Export bulletins PDF avec logo tenant
- Audit logging complet (auditInclude + auditEvents)
- Intégration avec dépenses (auto-création dépense après validation)

**Tables à créer** :
```sql
esbtp_salaires
  - id, tenant_id, employe_id, periode (mois/année)
  - salaire_base, primes, retenues
  - salaire_net, cotisations_employeur
  - statut_workflow (brouillon, en_attente, approuve, paye)
  - workflow_data (JSON)
  - approved_by, date_approbation
  - numero_bulletin (auto-généré)
  - audit columns (created_by, updated_by, deleted_at)

esbtp_salaire_lignes
  - id, salaire_id, type (prime/retenue)
  - libelle, montant, is_taxable

esbtp_employes (si n'existe pas déjà)
  - id, tenant_id, user_id
  - matricule, poste, date_embauche
  - salaire_base, type_contrat
```

**Estimation** : 3-4 jours de développement

---

#### 2. Module Factures ⏭️

**Raison de la refonte** :
- Code actuel incomplet (méthodes update/destroy/pdf manquantes)
- Aucune configuration tenant-specific
- Workflow approbation manquant

**Nouvelles exigences** :
- Support multi-tenant (numérotation par tenant)
- Workflow approbation (Demandeur → Validateur → Comptable → Payé)
- Support TVA configurable par tenant
- Génération PDF avec logo et coordonnées tenant
- Rappels automatiques avant échéance
- Intégration avec dépenses (auto-création après paiement)

**Tables à créer** :
```sql
esbtp_factures
  - id, tenant_id, fournisseur_id
  - numero (auto-généré par tenant)
  - date_emission, date_echeance, date_paiement
  - montant_ht, taux_tva, montant_tva, montant_ttc
  - montant_paye, montant_restant
  - statut_workflow (brouillon, en_attente, approuve, paye, en_retard)
  - workflow_data (JSON)
  - approved_by, date_approbation
  - path_fichier (PDF facture fournisseur)
  - audit columns

esbtp_facture_lignes
  - id, facture_id
  - designation, quantite, prix_unitaire, montant
  - is_taxable, taux_tva
```

**Estimation** : 2-3 jours de développement

---

#### 3. Module Fournisseurs ⏭️

**Raison de la refonte** :
- Code actuel incomplet (méthodes show/update manquantes)
- Aucune gestion des contacts multiples
- Pas de catégorisation

**Nouvelles exigences** :
- Support multi-tenant (fournisseurs partagés entre tenants ?)
- Catégories fournisseurs (Fournitures, Services, Maintenance, etc.)
- Contacts multiples par fournisseur
- Historique des factures par fournisseur
- Statistiques (montant total facturé, délai moyen paiement)

**Tables à créer** :
```sql
esbtp_fournisseurs
  - id, tenant_id (ou null si partagé)
  - raison_sociale, numero_contribuable
  - adresse, ville, pays
  - telephone, email, site_web
  - categorie_id
  - is_actif, notes
  - audit columns

esbtp_fournisseur_contacts
  - id, fournisseur_id
  - nom, prenom, fonction
  - telephone, email, is_principal

esbtp_fournisseur_categories
  - id, tenant_id, name, description
```

**Estimation** : 2 jours de développement

---

## Impact & Breaking Changes

### 🚨 Breaking Changes

#### 1. Routes Supprimées

**Impact** : Liens morts si utilisés dans menus/navigation

**Routes concernées** :
```
/esbtp/comptabilite/salaires/*          (9 routes)
/esbtp/comptabilite/fournisseurs/*      (8 routes)
/esbtp/comptabilite/factures/*          (8 routes)
/esbtp/comptabilite/configuration       (1 route)
/esbtp/comptabilite/frais-scolarite/*   (7 routes - si confirmé obsolète)
/esbtp/comptabilite/bourses/*           (7 routes - si confirmé obsolète)
```

**Total** : 40+ routes supprimées

**Migration nécessaire** :
- Audit des menus de navigation (sidebar, header)
- Vérification des permissions Spatie associées
- Suppression des éléments de menu correspondants
- Mise à jour documentation utilisateur

---

#### 2. Modèle ESBTPFacture Supprimé

**Impact** : Relations cassées si utilisées ailleurs

**Vérifications nécessaires** :
- Vérifier relations polymorphes (si utilisées)
- Vérifier mentions dans seeders
- Vérifier migrations (foreign keys)

**Commandes à exécuter AVANT suppression** :
```bash
# Chercher toutes les utilisations du modèle
grep -r "ESBTPFacture" app/
grep -r "esbtp_factures" database/
grep -r "factures()" app/Models/
```

---

#### 3. Tables Orphelines Potentielles

**Tables à supprimer** (si existent) :
```sql
esbtp_salaires
esbtp_salaire_details (ou similaire)
esbtp_factures
esbtp_facture_details (ou similaire)
esbtp_frais_scolarite (à confirmer)
esbtp_bourses (à confirmer)
```

**⚠️ ATTENTION** : Vérifier s'il existe des données en production avant suppression

**Commandes à exécuter** :
```bash
# Vérifier si tables existent
php artisan db:table esbtp_salaires
php artisan db:table esbtp_factures
php artisan db:table esbtp_frais_scolarite
php artisan db:table esbtp_bourses

# Compter les enregistrements
mysql -e "SELECT COUNT(*) FROM esbtp_salaires" klassci_presentation
mysql -e "SELECT COUNT(*) FROM esbtp_factures" klassci_presentation
```

---

#### 4. Permissions Spatie Orphelines

**Permissions à supprimer** (si existent) :
```
view_salaires, create_salaires, edit_salaires, delete_salaires
view_fournisseurs, create_fournisseurs, edit_fournisseurs, delete_fournisseurs
view_factures, create_factures, edit_factures, delete_factures
view_frais_scolarite, create_frais_scolarite, ... (si confirmé obsolète)
view_bourses, create_bourses, ... (si confirmé obsolète)
```

**Commande à exécuter APRÈS suppression** :
```bash
php artisan permission:cache-reset
```

---

### 📊 Métriques de Nettoyage Estimées

| Catégorie | Avant | Après | Réduction |
|-----------|-------|-------|-----------|
| **ESBTPComptabiliteController** | 4150 lignes | ~2800 lignes | -33% (1350 lignes) |
| **Routes** | ~100 routes comptabilité | ~60 routes | -40% (40 routes) |
| **Views** | ~25 fichiers | ~12 fichiers | -52% (13 fichiers) |
| **Lignes code vues** | ~3500 lignes | ~1700 lignes | -51% (1800 lignes) |
| **Models** | 4 models | 3 models | -1 model (ESBTPFacture) |

**Total économisé** : ~3200 lignes de code obsolète

---

## Plan d'Exécution

### Phase 1 : Validation & Préparation ⏱️ 1-2 heures

#### Étape 1.1 : Validation Utilisateur
- [ ] Confirmer suppression modules Salaires, Fournisseurs, Factures
- [ ] Statut définitif Frais Scolarité (utilisé ou obsolète ?)
- [ ] Statut définitif Bourses (utilisé ou obsolète ?)
- [ ] Statut définitif Bons de Sortie Rapide (utilisé ou doublon ?)

#### Étape 1.2 : Audit Dépendances
```bash
# Vérifier utilisations dans menus/navigation
grep -r "salaires\|fournisseurs\|factures" resources/views/layouts/
grep -r "salaires\|fournisseurs\|factures" resources/views/components/

# Vérifier tables en BDD
mysql -e "SHOW TABLES LIKE 'esbtp_%'" klassci_presentation

# Compter enregistrements par table
mysql -e "SELECT 'salaires', COUNT(*) FROM esbtp_salaires UNION ALL SELECT 'factures', COUNT(*) FROM esbtp_factures" klassci_presentation
```

#### Étape 1.3 : Backup
```bash
# Backup BDD avant modifications
mysqldump klassci_presentation > backup_before_cleanup_$(date +%Y%m%d).sql

# Backup fichiers code
cd /home/levraimd/workspace/KLASSCIv2
git checkout -b cleanup-comptabilite-obsolete
git add -A
git commit -m "chore: backup avant nettoyage module comptabilité"
```

---

### Phase 2 : Suppression Routes & Controller ⏱️ 2-3 heures

#### Étape 2.1 : Suppression Routes (routes/web.php)
```bash
# Éditer routes/web.php
# Supprimer lignes 1386-1424 (Salaires, Fournisseurs, Factures)
# + lignes frais-scolarite, bourses si confirmé obsolète

# Tester routes restantes
php artisan route:list --path=comptabilite
```

#### Étape 2.2 : Suppression Méthodes Controller
```bash
# Éditer app/Http/Controllers/ESBTPComptabiliteController.php
# Supprimer méthodes identifiées (lignes 1634-1916, 4063-4103, etc.)

# Vérifier syntaxe
php -l app/Http/Controllers/ESBTPComptabiliteController.php

# Tester application
php artisan optimize:clear
php artisan config:cache
```

---

### Phase 3 : Suppression Views ⏱️ 30 minutes

```bash
# Supprimer dossiers vues obsolètes
rm -rf resources/views/esbtp/comptabilite/salaires/
rm -rf resources/views/esbtp/comptabilite/fournisseurs/
rm -rf resources/views/esbtp/comptabilite/factures/
rm -f resources/views/esbtp/comptabilite/dashboard-test.blade.php

# + frais-scolarite, bourses si confirmé obsolète
```

---

### Phase 4 : Suppression Modèle ESBTPFacture ⏱️ 1 heure

#### Étape 4.1 : Vérification Dépendances
```bash
# Chercher toutes utilisations
grep -r "ESBTPFacture" app/
grep -r "esbtp_factures" app/
grep -r "factures()" app/Models/

# Vérifier migrations
grep -r "esbtp_factures" database/migrations/
```

#### Étape 4.2 : Suppression
```bash
# Supprimer modèle
rm app/Models/ESBTPFacture.php

# Supprimer migration (si existe en tant que fichier séparé)
# À identifier d'abord avec grep
```

---

### Phase 5 : Nettoyage BDD ⏱️ 1 heure

⚠️ **DANGER** : Suppression irréversible de données

```sql
-- VÉRIFIER D'ABORD s'il y a des données
SELECT COUNT(*) FROM esbtp_salaires;
SELECT COUNT(*) FROM esbtp_factures;

-- Si COUNT = 0, safe à supprimer
-- Si COUNT > 0, DEMANDER CONFIRMATION UTILISATEUR AVANT

-- Supprimer tables (SEULEMENT SI CONFIRMÉ)
DROP TABLE IF EXISTS esbtp_salaires;
DROP TABLE IF EXISTS esbtp_salaire_details; -- si existe
DROP TABLE IF EXISTS esbtp_factures;
DROP TABLE IF EXISTS esbtp_facture_details; -- si existe
-- + tables frais_scolarite, bourses si confirmé obsolète
```

---

### Phase 6 : Nettoyage Permissions ⏱️ 30 minutes

```bash
# Identifier permissions obsolètes
php artisan tinker
>>> \Spatie\Permission\Models\Permission::where('name', 'like', '%salaires%')->get();
>>> \Spatie\Permission\Models\Permission::where('name', 'like', '%fournisseurs%')->get();
>>> \Spatie\Permission\Models\Permission::where('name', 'like', '%factures%')->get();

# Supprimer permissions (si existent)
>>> \Spatie\Permission\Models\Permission::where('name', 'like', '%salaires%')->delete();
>>> \Spatie\Permission\Models\Permission::where('name', 'like', '%fournisseurs%')->delete();
>>> \Spatie\Permission\Models\Permission::where('name', 'like', '%factures%')->delete();

# Réinitialiser cache permissions
php artisan permission:cache-reset
```

---

### Phase 7 : Tests & Validation ⏱️ 2 heures

#### Étape 7.1 : Tests Automatisés
```bash
# Tests unitaires (si existent)
php artisan test --filter Comptabilite

# Vérifier pages principales
curl http://localhost:8000/esbtp/comptabilite
curl http://localhost:8000/esbtp/comptabilite/paiements
curl http://localhost:8000/esbtp/comptabilite/depenses
```

#### Étape 7.2 : Tests Manuels
- [ ] Dashboard comptabilité charge sans erreur
- [ ] Liste paiements accessible
- [ ] Liste dépenses accessible
- [ ] Configuration frais accessible (/esbtp/frais/configure)
- [ ] Aucun lien mort dans menus navigation
- [ ] Aucune erreur 404 au clic sur liens sidebar

---

### Phase 8 : Documentation & Commit ⏱️ 1 heure

#### Étape 8.1 : Mise à Jour CLAUDE.md

Ajouter section dans **Développements Novembre 2025** :

```markdown
### 🧹 Nettoyage Module Comptabilité - Suppression Code Obsolète (7 novembre)

**Contexte** : Suppression de 3200+ lignes de code obsolète jamais utilisé en production.

**Modules supprimés** :
- ❌ Salaires (9 routes, 250 lignes controller, 5 vues, ~800 lignes)
- ❌ Fournisseurs (8 routes, 180 lignes controller, 3 vues, ~390 lignes)
- ❌ Factures (8 routes, 200 lignes controller, 4 vues, ~870 lignes, 1 modèle)
- ❌ Configuration comptabilité doublon (2 méthodes, ~150 lignes)
- ❌ Dashboard test (1 vue, ~200 lignes)

**Controller ESBTPComptabiliteController** :
- Avant : 4150 lignes (8.3x limite recommandée)
- Après : ~2800 lignes (5.6x limite recommandée)
- Réduction : -33% (1350 lignes)

**Fichiers supprimés** :
- 13 vues Blade (~2060 lignes)
- 1 modèle (ESBTPFacture.php - 208 lignes)
- 40 routes obsolètes

**Modules préservés** :
- ✅ Paiements (CRUD complet, génération reçus, audit)
- ✅ Dépenses (CRUD complet, workflow approbation)
- ✅ Configuration Frais (ESBTPFraisController)
- ✅ Relances paiements
- ✅ Dashboard comptabilité

**À recréer avec architecture multi-tenant** :
- ⏭️ Salaires (workflow approbation, cotisations sociales)
- ⏭️ Factures (workflow approbation, rappels automatiques)
- ⏭️ Fournisseurs (catégories, contacts multiples)
```

#### Étape 8.2 : Git Commit

```bash
git add -A
git commit -m "chore(comptabilite): supprimer code obsolète salaires/fournisseurs/factures

Suppression de 3200+ lignes de code obsolète jamais utilisé en production.

BREAKING CHANGES:
- Suppression modules Salaires (9 routes, 5 vues)
- Suppression modules Fournisseurs (8 routes, 3 vues)
- Suppression modules Factures (8 routes, 4 vues, modèle ESBTPFacture)
- Suppression configuration doublon
- 40 routes obsolètes supprimées

Modules préservés:
- Paiements, Dépenses, Configuration Frais, Relances

ESBTPComptabiliteController réduit de 4150 → 2800 lignes (-33%)

À recréer: Salaires, Factures, Fournisseurs avec architecture multi-tenant

🤖 Generated with Claude Code
Co-Authored-By: Claude <noreply@anthropic.com>"
```

---

## Validation Finale

### Questions en Suspens

Avant d'exécuter la suppression, validation utilisateur requise sur :

#### 1. Frais Scolarité ❓
**Question** : Le module Frais Scolarité (7 routes, 7 méthodes controller, ~200 lignes) est-il utilisé en production ou est-il obsolète comme Salaires/Fournisseurs/Factures ?

**Routes concernées** :
- `/esbtp/comptabilite/frais-scolarite` (index)
- `/esbtp/comptabilite/frais-scolarite/create`
- `/esbtp/comptabilite/frais-scolarite/{id}` (show, edit, delete)

**Impact si supprimé** : -200 lignes controller, -7 routes

---

#### 2. Bourses ❓
**Question** : Le module Bourses (7 routes, 7 méthodes controller, ~180 lignes) est-il utilisé en production ou est-il obsolète ?

**Routes concernées** :
- `/esbtp/comptabilite/bourses` (index)
- `/esbtp/comptabilite/bourses/create`
- `/esbtp/comptabilite/bourses/{id}` (show, edit, delete)

**Impact si supprimé** : -180 lignes controller, -7 routes

---

#### 3. Bons de Sortie Rapide ❓
**Question** : La méthode `createBonRapide()` est-elle utilisée ou est-ce un doublon du module complet `ESBTPBonSortieController` ?

**Contexte** :
- Méthode rapide dans ESBTPComptabiliteController (ligne 4103, ~50 lignes)
- Existe un controller séparé ESBTPBonSortieController (à vérifier)

**Hypothèses** :
- Soit les deux sont utilisés pour des cas différents (rapide vs complet)
- Soit l'un est obsolète

**Impact si supprimé** : -50 lignes controller

---

#### 4. Tables BDD avec Données ❓
**Question** : Y a-t-il des enregistrements dans les tables suivantes ?
- `esbtp_salaires` : ??? enregistrements
- `esbtp_factures` : ??? enregistrements
- `esbtp_frais_scolarite` : ??? enregistrements (si existe)
- `esbtp_bourses` : ??? enregistrements (si existe)

**Action requise** :
```bash
# Exécuter ces commandes et fournir résultats
mysql -e "SELECT COUNT(*) FROM esbtp_salaires" klassci_presentation
mysql -e "SELECT COUNT(*) FROM esbtp_factures" klassci_presentation
mysql -e "SELECT COUNT(*) FROM esbtp_frais_scolarite" klassci_presentation
mysql -e "SELECT COUNT(*) FROM esbtp_bourses" klassci_presentation
```

**Décision** : Si COUNT > 0, BACKUP des données avant suppression ou migration vers nouvelles tables.

---

### Checklist de Validation

Avant d'exécuter le plan, cocher :

- [ ] **Utilisateur confirme** : Modules Salaires, Fournisseurs, Factures sont obsolètes
- [ ] **Utilisateur répond** : Statut Frais Scolarité (utilisé ou obsolète ?)
- [ ] **Utilisateur répond** : Statut Bourses (utilisé ou obsolète ?)
- [ ] **Utilisateur répond** : Statut Bons de Sortie Rapide (utilisé ou doublon ?)
- [ ] **Vérification BDD** : COUNT enregistrements tables obsolètes
- [ ] **Backup créé** : Dump SQL + commit Git avant modifications
- [ ] **Tests prévus** : Plan de tests manuels défini
- [ ] **Documentation prête** : Section CLAUDE.md rédigée

---

### Approbation Finale

**Date** : ___ / ___ / 2025
**Validé par** : ___________________
**Risques acceptés** : ☐ Oui ☐ Non
**Backup vérifié** : ☐ Oui ☐ Non

**Commentaires** :
```
[Espace pour notes utilisateur]
```

---

## Annexes

### A. Liste Complète des Méthodes ESBTPComptabiliteController

**Méthodes à SUPPRIMER** (confirmé obsolète) :
```php
// Salaires
salaires()                          // Ligne 1698
createSalaire()                     // Ligne 1716
storeSalaire()                      // Ligne 1729
showSalaire($id)                    // Ligne 1788
editSalaire($id)                    // Ligne 1799
updateSalaire($id)                  // Ligne 1814
destroySalaire($id)                 // Ligne 1874
bulletinSalaire($id)                // Ligne 1886
updateStatusSalaire($id, $status)   // Ligne 1916

// Fournisseurs
fournisseurs()                      // Ligne 1634
createFournisseur()                 // Ligne 1644
storeFournisseur()                  // Ligne 1652
editFournisseur($id)                // Ligne 1678
destroyFournisseur($id)             // Ligne 2795
storeFournisseurAjax()              // Ligne 2073

// Factures
factures()                          // Ligne 1687
createFacture()                     // Ligne 4063
showFacture($id)                    // Ligne 4074
storeFacture()                      // Ligne 4083
editFacture($id)                    // Ligne 4093

// Configuration doublon
configuration()                     // Ligne 1609
configurationComptabilite()         // Ligne 3110
```

**Méthodes à VÉRIFIER** :
```php
// Frais Scolarité
fraisScolarite()                    // Ligne 1214
createFraisScolarite()              // Ligne 1244
storeFraisScolarite()               // Ligne 1256
showFraisScolarite($id)             // Ligne 1313
editFraisScolarite($id)             // Ligne 1324
updateFraisScolarite($id)           // Ligne 1337
destroyFraisScolarite($id)          // Ligne 1397

// Bourses
bourses()                           // Ligne 1421
createBourse()                      // Ligne 1437
storeBourse()                       // Ligne 1452
showBourse($id)                     // Ligne 1509
editBourse($id)                     // Ligne 1523
updateBourse($id)                   // Ligne 1539
destroyBourse($id)                  // Ligne 1594

// Bons de Sortie Rapide
createBonRapide()                   // Ligne 4103
```

**Méthodes à PRÉSERVER** (actives) :
```php
// Dashboard
index()                             // Ligne ~40

// Paiements
paiements()                         // Ligne ???
createPaiement()                    // Ligne ???
storePaiement()                     // Ligne ???
showPaiement($id)                   // Ligne ???
editPaiement($id)                   // Ligne ???
updatePaiement($id)                 // Ligne ???
destroyPaiement($id)                // Ligne ???

// Dépenses
depenses()                          // Ligne ???
createDepense()                     // Ligne ???
storeDepense()                      // Ligne ???
showDepense($id)                    // Ligne ???
editDepense($id)                    // Ligne ???
updateDepense($id)                  // Ligne ???
destroyDepense($id)                 // Ligne ???

// Relances
relances()                          // Ligne ???
createRelance()                     // Ligne ???
storeRelance()                      // Ligne ???
showRelance($id)                    // Ligne ???
editRelance($id)                    // Ligne ???
updateRelance($id)                  // Ligne ???
destroyRelance($id)                 // Ligne ???
exportRelances()                    // Ligne ???

// Export
export()                            // Ligne ???
```

**Note** : Lignes `???` = À identifier via grep si nécessaire pour documentation complète.

---

### B. Exemple Migration Nouvelle Table Factures

**Fichier** : `database/migrations/2025_11_08_000001_create_factures_v2_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('esbtp_factures', function (Blueprint $table) {
            $table->id();

            // Multi-tenant
            $table->string('tenant_code')->nullable(); // 'esbtp-abidjan', 'esbtp-yakro'

            // Fournisseur
            $table->foreignId('fournisseur_id')->constrained('esbtp_fournisseurs')->onDelete('restrict');

            // Numérotation
            $table->string('numero', 50)->unique(); // FAC-ESBTP25-00001

            // Dates
            $table->date('date_emission');
            $table->date('date_echeance');
            $table->date('date_paiement')->nullable();

            // Montants
            $table->decimal('montant_ht', 15, 2)->default(0);
            $table->decimal('taux_tva', 5, 2)->default(0); // Configurable par tenant
            $table->decimal('montant_tva', 15, 2)->default(0);
            $table->decimal('montant_ttc', 15, 2)->default(0);
            $table->decimal('montant_paye', 15, 2)->default(0);
            $table->decimal('montant_restant', 15, 2)->default(0);

            // Workflow
            $table->enum('statut_workflow', ['brouillon', 'en_attente', 'approuve', 'paye', 'en_retard'])->default('brouillon');
            $table->json('workflow_data')->nullable(); // Historique approbations
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('date_approbation')->nullable();

            // Fichiers
            $table->string('path_fichier')->nullable(); // PDF facture fournisseur uploadé

            // Notes
            $table->text('notes')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index('tenant_code');
            $table->index('statut_workflow');
            $table->index('date_echeance');
        });

        Schema::create('esbtp_facture_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facture_id')->constrained('esbtp_factures')->onDelete('cascade');
            $table->string('designation');
            $table->decimal('quantite', 10, 2)->default(1);
            $table->decimal('prix_unitaire', 15, 2);
            $table->decimal('montant', 15, 2);
            $table->boolean('is_taxable')->default(true);
            $table->decimal('taux_tva', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_facture_lignes');
        Schema::dropIfExists('esbtp_factures');
    }
};
```

---

### C. Exemple Modèle Facture V2

**Fichier** : `app/Models/ESBTPFacture.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class ESBTPFacture extends Model implements Auditable
{
    use SoftDeletes, AuditableTrait;

    protected $table = 'esbtp_factures';

    protected $fillable = [
        'tenant_code',
        'fournisseur_id',
        'numero',
        'date_emission',
        'date_echeance',
        'date_paiement',
        'montant_ht',
        'taux_tva',
        'montant_tva',
        'montant_ttc',
        'montant_paye',
        'montant_restant',
        'statut_workflow',
        'workflow_data',
        'approved_by',
        'date_approbation',
        'path_fichier',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_emission' => 'date',
        'date_echeance' => 'date',
        'date_paiement' => 'date',
        'date_approbation' => 'datetime',
        'montant_ht' => 'decimal:2',
        'taux_tva' => 'decimal:2',
        'montant_tva' => 'decimal:2',
        'montant_ttc' => 'decimal:2',
        'montant_paye' => 'decimal:2',
        'montant_restant' => 'decimal:2',
        'workflow_data' => 'json',
    ];

    /**
     * Configuration audit
     */
    protected $auditInclude = [
        'numero',
        'montant_ht',
        'montant_tva',
        'montant_ttc',
        'montant_paye',
        'statut_workflow',
        'approved_by',
        'date_approbation',
        'date_echeance',
    ];

    protected $auditExclude = [
        'path_fichier',
    ];

    protected $auditTimestamps = true;

    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
        'restored',
    ];

    /**
     * Relations
     */
    public function fournisseur()
    {
        return $this->belongsTo(ESBTPFournisseur::class, 'fournisseur_id');
    }

    public function lignes()
    {
        return $this->hasMany(ESBTPFactureLigne::class, 'facture_id');
    }

    public function createur()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approbateur()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scopes
     */
    public function scopeTenant($query, $tenantCode)
    {
        return $query->where('tenant_code', $tenantCode);
    }

    public function scopeEnRetard($query)
    {
        return $query->where('statut_workflow', '!=', 'paye')
                     ->where('date_echeance', '<', now());
    }

    public function scopeApprouves($query)
    {
        return $query->where('statut_workflow', 'approuve');
    }

    /**
     * Accesseurs
     */
    public function getIsEnRetardAttribute()
    {
        return $this->statut_workflow !== 'paye' && $this->date_echeance < now();
    }

    /**
     * Génération numéro automatique
     */
    public static function genererNumero($tenantCode)
    {
        $annee = date('y');
        $prefix = "FAC-" . strtoupper($tenantCode) . $annee . "-";

        $lastFacture = self::where('numero', 'like', $prefix . '%')
                           ->orderByRaw('CAST(SUBSTRING_INDEX(numero, "-", -1) AS UNSIGNED) DESC')
                           ->first();

        $seq = 1;
        if ($lastFacture) {
            $parts = explode('-', $lastFacture->numero);
            $seq = intval(end($parts)) + 1;
        }

        return $prefix . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }
}
```

---

*Document créé le 7 novembre 2025*
*Validé par : [En attente validation utilisateur]*
