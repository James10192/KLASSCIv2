# ESBTP-yAKRO Documentation

## Corrections récentes

### Feature: Filtrage AJAX sans rechargement pour suivi-categories

**Date:** 10 octobre 2025
**Branche:** presentation

#### Fonctionnalités ajoutées

Implémentation d'un système de filtrage AJAX complet sur la page de suivi des paiements par catégorie, similaire à celui déjà implémenté sur `paiements.index`.

#### Problème résolu

Sur la page `/esbtp/paiements/suivi-categories`, les filtres (filière, niveau, catégorie) et les clics sur les cartes de catégories déclenchaient un rechargement complet de la page, causant une expérience utilisateur lourde.

#### Solution implémentée

**1. Création de partiels Blade** :
- `partials/suivi-metrics.blade.php` - KPI cards (étudiants en règle, partiels, impayés, taux de recouvrement)
- `partials/suivi-content.blade.php` - Contenu (répartition, catégories grid, détails catégorie)

**2. Backend - Nouvelle route et méthode** :
- Route: `GET /paiements/suivi-categories/refresh` → `esbtp.paiements.suivi-categories.refresh`
- Méthode: `ESBTPPaiementController@suiviCategoriesRefresh()`
- Réutilise la même logique que `suiviCategories()` (filtrage, pré-chargement optimisé)
- Retourne JSON: `{metrics, content, url, last_updated_at}`

**3. Frontend - AJAX avec fetch()** :
- Interception du formulaire de filtres
- Interception des clics sur les cartes de catégories (via classe `.category-card-ajax`)
- Fonction `buildRefreshUrl()` - construit l'URL avec les filtres actuels
- Fonction `fetchSuiviData()` - effectue la requête AJAX et met à jour le DOM
- Fonction `bindCategoryCardClicks()` - re-bind les événements après mise à jour du DOM
- Support de `pushState` pour mettre à jour l'URL sans rechargement
- Support du bouton retour du navigateur (event `popstate`)

**4. Auto-submission des filtres** :
- Les selects (filière, niveau, catégorie) soumettent automatiquement le formulaire via AJAX au changement
- Pas besoin de cliquer sur le bouton "Filtrer"

#### Architecture technique

**Pattern utilisé** : Similaire à `paiements.index` avec quelques adaptations

- **Partiels** au lieu de HTML monolithique
- **AJAX complet** sans jQuery (vanilla JavaScript + fetch)
- **Event delegation** pour les cartes de catégories dynamiques
- **History API** pour navigation navigateur fonctionnelle
- **Réutilisation du code backend** (pas de duplication de logique)

#### Fichiers créés

- [resources/views/esbtp/paiements/partials/suivi-metrics.blade.php](resources/views/esbtp/paiements/partials/suivi-metrics.blade.php) - KPI cards
- [resources/views/esbtp/paiements/partials/suivi-content.blade.php](resources/views/esbtp/paiements/partials/suivi-content.blade.php) - Contenu principal

#### Fichiers modifiés

- [routes/web.php:679](routes/web.php:679) - Ajout route `paiements.suivi-categories.refresh`
- [app/Http/Controllers/ESBTPPaiementController.php:1060-1168](app/Http/Controllers/ESBTPPaiementController.php:1060) - Méthode `suiviCategoriesRefresh()`
- [resources/views/esbtp/paiements/suivi-categories.blade.php](resources/views/esbtp/paiements/suivi-categories.blade.php):
  - Ligne 446 : Suppression `onchange="this.form.submit()"` des selects (remplacé par AJAX)
  - Lignes 492-494 : Remplacement KPI section par `@include('suivi-metrics')`
  - Lignes 497-499 : Remplacement contenu par `@include('suivi-content')`
  - Lignes 506-645 : Ajout système AJAX complet (145 lignes de JavaScript)
  - Modification de la classe des cartes : `category-card` → `category-card category-card-ajax`
  - Suppression de l'attribut `onclick` sur les cartes (remplacé par event listener)

#### Différences clés avec paiements.index

| Aspect | paiements.index | suivi-categories |
|--------|-----------------|------------------|
| **Partiels** | `partials/metrics.blade.php`<br>`partials/table.blade.php` | `partials/suivi-metrics.blade.php`<br>`partials/suivi-content.blade.php` |
| **Route refresh** | `paiements.refresh` | `paiements.suivi-categories.refresh` |
| **Polling** | ✅ Auto-refresh toutes les 30s | ❌ Pas implémenté |
| **Élément cliquable** | Pagination | Cartes de catégories |
| **Event binding** | jQuery `.on('click')` | Vanilla JS `addEventListener` |

#### Résultat

**Avant** :
- Changement de filtre → Rechargement complet de la page
- Clic sur une carte de catégorie → Rechargement complet de la page
- Expérience lente et non fluide

**Après** :
- ✅ Changement de filtre → Mise à jour instantanée sans rechargement
- ✅ Clic sur une carte → Mise à jour instantanée sans rechargement
- ✅ URL mise à jour automatiquement (partage du lien possible)
- ✅ Bouton retour du navigateur fonctionnel
- ✅ Expérience utilisateur fluide et moderne

#### Tests recommandés

- [ ] Changer le filtre "Filière" → Vérifier mise à jour AJAX sans rechargement
- [ ] Changer le filtre "Niveau" → Vérifier mise à jour AJAX
- [ ] Changer le filtre "Catégorie détaillée" → Vérifier mise à jour AJAX
- [ ] Cliquer sur une carte de catégorie → Vérifier passage au mode détails sans rechargement
- [ ] Vérifier que l'URL change (inspect network tab: XHR request, pas de navigation)

---

### Fix: Résolution affichage onglets étudiants et ajout loading states sur suivi-categories

**Date:** 10 octobre 2025
**Branche:** presentation

#### Problèmes résolus

1. **Aucun feedback visuel pendant les requêtes AJAX**
   - Les sections ne montraient pas d'état de chargement
   - Utilisateur ne savait pas si le filtre était en cours de traitement

2. **Onglets étudiants affichaient aucun contenu**
   - Après avoir cliqué sur une catégorie, les onglets "Aucun paiement (94)", "Paiements partiels (120)", "À jour (66)" restaient vides
   - Le système de lazy loading des onglets était incompatible avec le refresh AJAX

#### Solutions implémentées

**1. Loading states visuels (suivi-categories.blade.php)**

Ajout d'un effet de "grisement" pendant les requêtes AJAX :

```javascript
// Avant le fetch (lignes 536-541)
metricsContainer.style.opacity = '0.5';
metricsContainer.style.pointerEvents = 'none';
metricsContainer.style.transition = 'opacity 0.2s ease';
contentContainer.style.opacity = '0.5';
contentContainer.style.pointerEvents = 'none';
contentContainer.style.transition = 'opacity 0.2s ease';

// Après le fetch - dans finally (lignes 586-590)
metricsContainer.style.opacity = '1';
metricsContainer.style.pointerEvents = 'auto';
contentContainer.style.opacity = '1';
contentContainer.style.pointerEvents = 'auto';
```

**Effet obtenu :**
- Sections grises (opacity 0.5) pendant le chargement
- Interactions désactivées (pointer-events none)
- Transition smooth de 0.2s
- Restauration automatique après réception des données

**2. Fix affichage onglets étudiants (suivi-content.blade.php)**

**Problème identifié :** Le système de lazy loading avec `data-statut` et spinners ne fonctionnait pas avec le refresh AJAX.

**Solution :** Remplacement complet par Bootstrap native tabs avec rendu immédiat :

```blade
{{-- AVANT (lazy loading) --}}
<a class="nav-link" data-statut="non_payes">...</a>
<div id="students-list-container">
    <div class="spinner">Chargement...</div>
</div>

{{-- APRÈS (rendu direct) --}}
<a class="nav-link active" data-bs-toggle="tab" href="#non_payes_{{ $detailsCategorie['category']->id }}">
    Aucun paiement ({{ $detailsCategorie['etudiants_non_payes']->count() }})
</a>

<div class="tab-pane fade show active" id="non_payes_{{ $detailsCategorie['category']->id }}">
    @if($detailsCategorie['etudiants_non_payes']->count() > 0)
        @include('esbtp.paiements.partials.liste-etudiants', [
            'etudiants' => $detailsCategorie['etudiants_non_payes'],
            'statut' => 'non_payes',
            'category' => $detailsCategorie['category']
        ])
    @else
        <div>Aucun étudiant sans paiement</div>
    @endif
</div>
```

**Modifications clés (lignes 182-258) :**
- Changement de `data-statut` vers `data-bs-toggle="tab"`
- Ajout de `href="#non_payes_{{ $detailsCategorie['category']->id }}"` pour cibler le bon onglet
- IDs uniques avec `{{ $detailsCategorie['category']->id }}` pour éviter les conflits
- Utilisation de `@include('liste-etudiants')` avec les collections directement :
  - `$detailsCategorie['etudiants_non_payes']`
  - `$detailsCategorie['etudiants_en_retard']`
  - `$detailsCategorie['etudiants_a_jour']`
- Ajout d'états vides avec messages appropriés
- Premier onglet (Aucun paiement) actif par défaut (`show active`)

#### Fichiers modifiés

- [resources/views/esbtp/paiements/suivi-categories.blade.php](resources/views/esbtp/paiements/suivi-categories.blade.php):
  - Lignes 536-541 : Ajout loading state (opacity + pointer-events)
  - Lignes 586-590 : Restauration état normal dans finally

- [resources/views/esbtp/paiements/partials/suivi-content.blade.php](resources/views/esbtp/paiements/partials/suivi-content.blade.php):
  - Lignes 182-258 : Refonte complète des onglets étudiants
  - Suppression du système de lazy loading
  - Ajout de Bootstrap native tabs avec `data-bs-toggle`
  - Rendu immédiat via `@include` au lieu de chargement différé

#### Caractéristiques techniques

- **Loading feedback visuel** : Opacity 0.5 + pointer-events none pendant AJAX
- **Transition CSS smooth** : 0.2s ease pour effet de grisement progressif
- **Tabs Bootstrap 5** : Utilisation de `data-bs-toggle="tab"` natif
- **Rendu immédiat** : Les étudiants sont déjà présents dans les collections, pas besoin de lazy load
- **IDs uniques** : Utilisation de l'ID de catégorie pour éviter les conflits entre catégories
- **État vide géré** : Messages d'information quand aucun étudiant dans une catégorie

#### Résultat

**Avant** :
- ❌ Pas de feedback visuel pendant le chargement AJAX
- ❌ Onglets étudiants vides après clic sur catégorie
- ❌ Lazy loading incompatible avec refresh AJAX

**Après** :
- ✅ Sections grisées pendant le chargement (opacity 0.5)
- ✅ Transitions smooth et professionnelles
- ✅ Onglets étudiants affichent immédiatement le contenu
- ✅ Bootstrap native tabs fonctionnelles
- ✅ Compteurs corrects sur chaque onglet
- ✅ États vides gérés avec messages appropriés

#### Tests effectués

- ✅ Effet de grisement visible lors du changement de filtre
- ✅ Restauration de l'opacité après chargement des données
- ✅ Onglets étudiants affichent les cartes correctement
- ✅ Navigation entre les 3 onglets fonctionnelle
- ✅ Compteurs d'étudiants corrects sur chaque onglet
- ✅ Messages d'état vide affichés quand applicable

---

```bash
# Vérifier les routes
php artisan route:list --name=suivi-categories

# Vider le cache si nécessaire
php artisan view:clear
php artisan cache:clear
```

#### Notes techniques

- **fetch() API** utilisée au lieu de XMLHttpRequest ou jQuery.ajax
- **ImmediatelyInvokedFunctionExpression (IIFE)** pour isoler le scope JavaScript
- **Event delegation** avec `bindCategoryCardClicks()` appelé après chaque mise à jour
- **pushState** préserve l'état de navigation (URL, filtres)
- **Promise chain** pour gestion asynchrone propre

---

### Feature: Accès en lecture seule aux classes pour les coordinateurs

**Date:** 10 octobre 2025
**Branche:** presentation

#### Fonctionnalités ajoutées

Ajout du menu "Classes" dans la sidebar des coordinateurs avec permissions en lecture seule uniquement, sans possibilité de créer ou modifier des classes.

#### Modifications des permissions

**Rôle coordinateur** (`fix_permissions.php` ligne 292):
- ✅ **Conservé**: `view_classes` - Peut consulter les classes
- ❌ **Supprimé**: `edit_classes` - Ne peut plus modifier les classes
- ❌ **Supprimé**: `create_classes` - Ne peut plus créer de classes

**Justification**: Les coordinateurs doivent pouvoir consulter les classes pour la coordination pédagogique (gestion étudiants, emplois du temps) mais la création/modification reste réservée aux superAdmin et secrétaires.

#### Ajout dans la sidebar

**Fichier**: `resources/views/layouts/app.blade.php` (lignes 1567-1573)

**Nouveau menu item** dans la section "Coordination pédagogique":
```blade
<!-- Classes Management -->
<div class="menu-item">
    <a href="{{ route('esbtp.classes.index') }}" class="menu-link">
        <div class="menu-icon"><i class="fas fa-chalkboard"></i></div>
        <div class="menu-text">Classes</div>
    </a>
</div>
```

**Placement**: Entre "Gestion étudiants" et "Gestion du personnel"

#### Protections existantes vérifiées

Les vues sont déjà correctement protégées avec `@if(auth()->user()->hasRole('superAdmin'))`:

- ✅ `classes/index.blade.php` (ligne 19) - Bouton "Nouvelle Classe"
- ✅ `classes/partials/results.blade.php` (ligne 22) - Bouton "Créer une classe"
- ✅ `classes/partials/items.blade.php` (ligne 70) - Bouton "Modifier"

**Résultat**: Les coordinateurs ne voient aucun bouton de création/édition.

#### Routes protégées

Les routes utilisent déjà le middleware `permission:view_classes`:
```php
Route::get('classes', [ESBTPClasseController::class, 'index'])
    ->middleware(['permission:view_classes|view classes']);

Route::get('classes/{classe}', [ESBTPClasseController::class, 'show'])
    ->middleware(['permission:view_classes|view classes']);
```

#### Résultat final

**Pour les coordinateurs:**
- ✅ **PEUT**: Voir le menu Classes, accéder à la liste, consulter les détails, voir listes d'appel
- ❌ **NE PEUT PAS**: Créer, modifier ou supprimer des classes

**Pour les superAdmin:**
- ✅ Aucun changement - conserve tous les accès

#### Fichiers modifiés

- [fix_permissions.php:292](fix_permissions.php:292) - Suppression permissions create/edit
- [resources/views/layouts/app.blade.php:1567-1573](resources/views/layouts/app.blade.php:1567) - Ajout menu Classes

#### Tests effectués

- ✅ Script permissions exécuté (61 permissions accordées au coordinateur)
- ✅ Vérification Spatie: `view_classes` = OUI, `create_classes` = NON, `edit_classes` = NON
- ✅ Caches vidés (cache, config, permissions)

---

### Feature: Colonne statut d'affectation et filtre inscription validée dans etudiants.index

**Date:** 10 octobre 2025
**Branche:** presentation

#### Fonctionnalités ajoutées

Implémentation d'un système de filtrage avancé et affichage du statut d'affectation pour les étudiants basé sur le workflow d'inscription.

#### 1. Nouvelle colonne "Statut d'affectation"

**Affichage basé sur le workflow_step :**

**Si workflow terminé (`workflow_step = 'etudiant_cree')` :**
- Badge simple du statut d'affectation :
  - ✅ Badge vert "Affecté"
  - 🔄 Badge bleu "Réaffecté"
  - ❌ Badge rouge "Non affecté"

**Si workflow en cours (autre étape) :**
- Badge jaune avec étape du workflow : "📋 Prospect", "Documents complets", "En validation", "Validé"
- **+** Badge du statut d'affectation en dessous (si défini)

**Si pas d'inscription dans l'année courante :**
- Texte grisé : "Pas d'inscription (2025-2026)"

#### 2. Colonne "Classe actuelle" améliorée

**Affichage des icônes basé sur workflow_step :**
- ✅ **Check vert** : Si `workflow_step = 'etudiant_cree'` (inscription validée - workflow terminé)
- ⏳ **Sablier orange** : Si `workflow_step != 'etudiant_cree'` (inscription en cours - workflow pas terminé)

**Tooltip au survol :**
- "Inscription validée - Workflow terminé"
- "Inscription en cours - Workflow : prospect/documents_complets/en_validation/valide"

#### 3. Nouveaux filtres

**Filtre "Statut d'affectation (2025-2026)" :**
- Tous les statuts d'affectation
- Affecté
- Réaffecté
- Non affecté

**Logique :** Filtre uniquement les étudiants avec `workflow_step = 'etudiant_cree'` (inscription validée)

**Filtre "Inscription validée (2025-2026)" - 3 options distinctes :**

**Option "Oui (Validée)"** :
- Affiche les étudiants avec `workflow_step = 'etudiant_cree'`
- Inscription complètement validée, prêts à suivre les cours

**Option "En attente"** :
- Affiche les étudiants avec `workflow_step != 'etudiant_cree'`
- Inscription en cours (étapes: prospect, documents_complets, en_validation, valide)
- Nécessitent un suivi pour terminer leur processus

**Option "Absente"** :
- Affiche les étudiants sans inscription dans l'année courante
- Candidats potentiels à la réinscription ou anciens étudiants

#### 4. Labels des étapes du workflow

- `prospect` → "Prospect"
- `documents_complets` → "Documents complets"
- `en_validation` → "En validation"
- `valide` → "Validé"
- `etudiant_cree` → "Étudiant créé" (dernière étape)

#### Fichiers modifiés

**Backend :**
- [app/Http/Controllers/ESBTPStudentController.php](app/Http/Controllers/ESBTPStudentController.php)
  - Lignes 43-52 : Récupération année courante et filtres (affectation_status, inscrit_annee_courante)
  - Lignes 59-65 : Eager loading inscriptions année courante
  - Lignes 85-92 : Filtre par statut d'affectation (workflow terminé uniquement)
  - Lignes 94-114 : Filtre inscription validée (3 options: validee, en_attente, absente)
  - Ligne 250-263 : Passage variables à la vue

**Frontend - Vues :**
- [resources/views/esbtp/etudiants/index.blade.php](resources/views/esbtp/etudiants/index.blade.php)
  - Lignes 98-115 : Ajout selects filtres (Statut d'affectation + Inscription validée)
  - Ligne 152 : Intégration Select2 pour les nouveaux filtres

- [resources/views/esbtp/etudiants/partials/results.blade.php](resources/views/esbtp/etudiants/partials/results.blade.php)
  - Lignes 3-15 : Ajout colonne "Statut d'affectation" dans thead
  - Lignes 49-88 : Colonne "Classe actuelle" avec icône basée sur workflow_step
  - Lignes 89-137 : Colonne "Statut d'affectation" avec logique workflow
  - Ligne 122 : Colspan mis à jour (10 colonnes au lieu de 9)

#### Différence clé avec l'ancien système

**Avant :**
- Utilisation de `status` (active, pending, en_attente)
- Filtrage binaire : inscrit ou pas inscrit

**Après :**
- Utilisation de `workflow_step` (5 étapes du processus d'inscription)
- Filtrage tripartite : validée, en attente, absente
- Affichage du statut d'affectation uniquement pour inscriptions validées
- Labels explicites de l'étape du workflow pour inscriptions en cours

#### Cas d'usage

**"Inscription validée = Oui (Validée)" + "Statut d'affectation = Non affecté"** :
- Liste des étudiants validés mais qui n'ont pas encore de classe assignée
- Action requise : Affecter une classe

**"Inscription validée = En attente"** :
- Liste des étudiants en cours d'inscription
- Action requise : Suivre et compléter le workflow

**"Inscription validée = Absente"** :
- Liste des anciens étudiants sans inscription pour l'année courante
- Action potentielle : Campagne de réinscription

#### Tests recommandés

- [ ] Filtrer par "Inscription validée = Oui (Validée)" → Vérifier uniquement étudiants avec check vert
- [ ] Filtrer par "Inscription validée = En attente" → Vérifier uniquement étudiants avec sablier orange
- [ ] Filtrer par "Inscription validée = Absente" → Vérifier "Pas d'inscription (2025-2026)"
- [ ] Filtrer par "Statut d'affectation = Non affecté" → Vérifier badge rouge
- [ ] Combiner filtres : "Validée + Affecté" → Vérifier cohérence des résultats
- [ ] Vérifier tooltips au survol des icônes (check/sablier)
- [ ] Tester AJAX : Les filtres doivent fonctionner sans rechargement de page

---

### Fix: Calcul incorrect du reliquat dans reinscriptions.show

**Date:** 10 octobre 2025
**Branche:** presentation

#### Problème résolu

Dans la page `reinscriptions.show`, la carte de validation affichait un reliquat erroné pour l'étudiant MESBTP22-0545 :
- **Affiché** : "150 000 FCFA à régulariser"
- **Attendu** : "Aucun reliquat en attente"

**Cause racine :**
Le calcul utilisait `$etudiant->solde_restant` qui représente le solde de l'inscription actuelle (année courante 2025-2026), incluant les frais non payés de l'année en cours. Pour cet étudiant :
- Solde inscription actuelle = 150 000 FCFA (frais 2025-2026 non payés)
- Reliquats entrants (années précédentes) = 0 FCFA

**Définition correcte du reliquat :**
Un reliquat = uniquement les dettes reportées des années précédentes via `ESBTPReliquatDetail`, PAS les frais impayés de l'année courante.

#### Solution implémentée

**1. Backend - ESBTPReinscriptionController@show (lignes 292-307)**

Ajout du calcul du vrai reliquat basé sur `ESBTPReliquatDetail` :

```php
// Calculer le VRAI reliquat (uniquement les dettes des années précédentes via ESBTPReliquatDetail)
// comme dans inscriptions.show
$reliquatsEntrants = \App\Models\ESBTPReliquatDetail::where('inscription_destination_id', $inscription->id)
    ->actifs()
    ->get();

$reliquatMontant = $reliquatsEntrants->sum('solde_restant');

// ... autres calculs ...

// IMPORTANT: Le reliquat affiché dans la carte de validation = uniquement années précédentes
$etudiant->reliquat_reel = $reliquatMontant;
```

**2. Vue - reinscriptions/show.blade.php (ligne 493-495)**

Remplacement de `solde_restant` par `reliquat_reel` :

```blade
// CORRECTION: Utiliser reliquat_reel (uniquement années précédentes) au lieu de solde_restant (année courante)
$reliquatRestant = $etudiant->reliquat_reel ?? 0;
$reliquatGere = $reliquatRestant <= 0;
```

#### Cohérence avec inscriptions.show

Cette correction aligne la logique de `reinscriptions.show` avec celle de `inscriptions.show` (lignes 953-967) où le même calcul est appliqué :

```php
// Reliquats entrants (provenant d'inscriptions précédentes)
$reliquatsEntrants = \App\Models\ESBTPReliquatDetail::where('inscription_destination_id', $inscription->id)
    ->with([...])
    ->actifs()
    ->get();

// Statistiques reliquats
$statistiquesReliquats = [
    'total_reliquats_entrants' => $reliquatsEntrants->sum('solde_restant'),
    ...
];
```

#### Résultat

Pour l'étudiant MESBTP22-0545 :
- ✅ **Avant** : "Reliquat : 150 000 FCFA à régulariser" (incorrect)
- ✅ **Après** : "Reliquat : Aucun reliquat en attente" (correct)

Le montant de 150 000 FCFA reste visible dans la section "Situation Financière & Réinscription" en tant que "Reste à Payer" pour l'année courante, ce qui est correct.

#### Fichiers modifiés

- [app/Http/Controllers/ESBTP/ESBTPReinscriptionController.php](app/Http/Controllers/ESBTP/ESBTPReinscriptionController.php:292-307) - Ajout calcul reliquat_reel
- [resources/views/esbtp/reinscription/show.blade.php](resources/views/esbtp/reinscription/show.blade.php:493-495) - Utilisation reliquat_reel

#### Différence clé

| Variable | Signification | Utilisé pour |
|----------|---------------|--------------|
| `$etudiant->solde_restant` | Frais non payés de l'inscription actuelle (année courante) | KPI "Reste à Payer" |
| `$etudiant->reliquat_reel` | Dettes reportées des années précédentes (via ESBTPReliquatDetail) | Carte "Reliquat" |

---

### Fix: Boutons et modals de rejet de paiement non fonctionnels

**Date:** 10 octobre 2025
**Branche:** presentation

#### Problèmes résolus

**1. paiements.index - Incompatibilité Bootstrap 4/5**
- **Cause :** Modal utilisait des attributs Bootstrap 4 (`data-dismiss`, `class="close"`) alors que l'app utilise Bootstrap 5
- **Impact :** Boutons "Annuler" et fermeture du modal ne fonctionnaient pas
- **Conflit :** Chargement de Bootstrap 4.6.2 en plus de Bootstrap 5 (ligne 315)

**2. paiements.show - Modal dupliqué**
- **Cause :** Deux modals de rejet avec IDs différents (`#modalRejeter` et `#rejetModal`)
- **Impact :** Bouton ligne 272 pointait vers `#rejectModal` (inexistant)
- **Erreur :** Champ `name="commentaire"` alors que contrôleur attend `name="motif_rejet"`

#### Solutions implémentées

**paiements.index :**
```blade
<!-- Avant (Bootstrap 4) -->
<button type="button" class="close" data-dismiss="modal">
    <span>&times;</span>
</button>
$('#bulkRejetModal').modal('show');

<!-- Après (Bootstrap 5) -->
<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
const modal = new bootstrap.Modal(document.getElementById('bulkRejetModal'));
modal.show();
```

**paiements.show :**
- Supprimé modal dupliqué `#rejetModal` (lignes 638-680)
- Corrigé `data-bs-target="#rejectModal"` → `data-bs-target="#modalRejeter"`
- Corrigé `name="commentaire"` → `name="motif_rejet"`
- Supprimé script jQuery obsolète `$('#rejeterBtn').click()`

#### Fichiers modifiés

- [resources/views/esbtp/paiements/index.blade.php](resources/views/esbtp/paiements/index.blade.php)
  - Lignes 266-311 : Modal Bootstrap 5
  - Ligne 313 : Suppression chargement Bootstrap 4
  - Lignes 602-604 : API Bootstrap 5 pour modal

- [resources/views/esbtp/paiements/show.blade.php](resources/views/esbtp/paiements/show.blade.php)
  - Ligne 272 : Correction target modal
  - Lignes 571-577 : Correction nom champ
  - Lignes 631-680 : Suppression modal dupliqué

#### Tests recommandés

- [ ] Ouvrir paiements.index et cliquer sur "Rejeter la sélection"
- [ ] Vérifier que le modal s'ouvre correctement
- [ ] Vérifier que le bouton "Annuler" ferme le modal
- [ ] Soumettre un rejet groupé avec motif
- [ ] Ouvrir paiements.show et cliquer sur "Rejeter"
- [ ] Vérifier que le modal s'ouvre sans erreur console
- [ ] Soumettre le rejet et vérifier que le motif est envoyé

#### Compatibilité Bootstrap

L'application utilise **Bootstrap 5** :
- Attributs modals : `data-bs-*` (pas `data-*`)
- Bouton fermeture : `btn-close` (pas `close`)
- API JavaScript : `new bootstrap.Modal(element).show()` (pas `$(element).modal('show')`)
- Classes form : `mb-3` (pas `form-group`)

---

### Feature: Affichage détaillé des informations de réinscription dans inscriptions.show

**Date:** 10 octobre 2025
**Branche:** presentation

#### Fonctionnalités ajoutées

Affichage automatique des informations de réinscription dans la section "Observations" de `inscriptions.show` pour les inscriptions de type `réinscription`.

**Informations affichées :**
- ✅ **Décision académique** : Badge coloré (Passage=vert, Redoublement=rouge, Rattrapage=orange)
- ✅ **Statut d'affectation** : Affecté/Non-affecté/Maintenant-affecté
- ✅ **Reliquat** : Calcul automatique basé sur la situation financière (solde global + reliquats entrants)
- ✅ **Notes complémentaires** : Si présentes dans les observations

#### Logique de calcul du reliquat

Pour une **réinscription**, le reliquat affiché correspond **uniquement aux reliquats entrants** de l'année précédente, PAS au solde de l'inscription actuelle :

```php
// Reliquat = Uniquement les reliquats entrants non soldés (ESBTPReliquatDetail)
$reliquatMontant = $statistiquesReliquats['total_reliquats_entrants'] ?? 0;
```

**Différence importante :**
- ❌ **PAS le solde de l'inscription actuelle** : Les frais non payés de l'année en cours (2025-2026) ne sont pas des "reliquats"
- ✅ **UNIQUEMENT les reliquats entrants** : Les dettes reportées des années précédentes (via `ESBTPReliquatDetail`)

**Exemple :**
- Étudiant avec 150 000 FCFA de frais non payés en 2025-2026 → **Reliquat = 0 FCFA** (si aucun reliquat de 2024-2025)
- Étudiant avec 50 000 FCFA de reliquat reporté de 2024-2025 → **Reliquat = 50 000 FCFA** (même si frais 2025-2026 soldés)

#### Fichiers modifiés

- [app/Http/Controllers/ESBTPInscriptionController.php:970-1003](app/Http/Controllers/ESBTPInscriptionController.php:970) - Ajout logique formatage données réinscription
- [resources/views/esbtp/inscriptions/show.blade.php:438-478](resources/views/esbtp/inscriptions/show.blade.php:438) - Affichage conditionnel détails réinscription

#### Caractéristiques techniques

- **Parsing automatique** : Extraction de la décision depuis `reinscription_observations` (format: `"passage - notes"`)
- **Compatibilité** : Support des deux orthographes (`reinscription` et `réinscription`)
- **Design moderne** : Badges Bootstrap colorés, icônes FontAwesome, séparation visuelle
- **Calcul cohérent** : Utilise la même logique que la section "Situation Financière"

#### Exemple d'affichage

Pour une réinscription avec reliquat :
```
Décision académique: [Passage au niveau supérieur]
Statut d'affectation: Affecté
Reliquat: ⚠ 500 000 FCFA à régulariser
```

Pour une réinscription sans reliquat :
```
Décision académique: [Redoublement]
Statut d'affectation: Non-affecté
Reliquat: ✓ Aucun reliquat en attente
Notes complémentaires: Étudiant autorisé à redoubler
```

#### Tests effectués

- ✅ Parsing de la décision depuis observations
- ✅ Calcul du reliquat identique à la situation financière
- ✅ Affichage conditionnel pour réinscriptions uniquement
- ✅ Compatibilité avec les deux orthographes
- ✅ Badges colorés selon décision et état reliquat

---

### Fix: Comptage dynamique des étudiants et filtrage par classe sur classes.show

**Date:** 10 octobre 2025
**Branche:** presentation

#### Problèmes résolus

1. **KPI "Étudiants Inscrits" affichait 0 alors que des étudiants étaient visibles**
   - **Cause:** La vue utilisait `$classe->nombre_etudiants` (attribut statique) au lieu de compter dynamiquement `$classe->etudiants`
   - **Impact:** Incohérence entre les KPI et le contenu du tableau
   - **Localisation:** `resources/views/esbtp/classes/show.blade.php` lignes 99, 109, 114, 263

2. **Étudiants d'autres classes apparaissaient dans la liste**
   - **Cause:** Le filtre `whereHas('inscriptions')` ne vérifiait pas le `classe_id`, donc tout étudiant avec une inscription active pour l'année courante apparaissait
   - **Exemple:** YAO KOUASSI (inscrit en "1A BTS") apparaissait aussi dans "2A BTS S Bâtiment"
   - **Localisation:** `app/Http/Controllers/ESBTPClasseController.php` ligne 246

3. **Doublons d'étudiants si plusieurs inscriptions actives**
   - **Cause:** Un étudiant avec plusieurs inscriptions actives (redoublement) apparaissait plusieurs fois
   - **Solution:** Ajout de `distinct()` à la query

#### Solutions implémentées

**Backend - ESBTPClasseController@show (lignes 244-250) :**
```php
'etudiants' => function ($query) use ($anneeCourante, $classe) {
    $query->distinct()  // ← Évite les doublons
          ->whereHas('inscriptions', function ($inscriptionQuery) use ($anneeCourante, $classe) {
              $inscriptionQuery->where('annee_universitaire_id', $anneeCourante->id)
                               ->where('status', 'active')
                               ->where('classe_id', $classe->id);  // ← Filtre crucial
          });
}
```

**Frontend - show.blade.php :**
- Ligne 99 : `{{ $classe->nombre_etudiants }}` → `{{ $classe->etudiants->count() }}`
- Lignes 109-111 : Calcul dynamique du taux d'occupation et places libres
- Ligne 263 : Subtitle avec comptage dynamique

#### Fichiers modifiés

- [app/Http/Controllers/ESBTPClasseController.php](app/Http/Controllers/ESBTPClasseController.php:244-250) - Ajout `distinct()` + filtre `classe_id`
- [resources/views/esbtp/classes/show.blade.php](resources/views/esbtp/classes/show.blade.php) - Remplacement `nombre_etudiants` par `etudiants->count()`

#### Tests effectués

- ✅ KPI affiche le bon nombre d'étudiants pour l'année courante
- ✅ Aucun étudiant d'une autre classe n'apparaît
- ✅ Pas de doublons même si plusieurs inscriptions actives
- ✅ Taux d'occupation calculé correctement
- ✅ Compteur "X étudiant(s) inscrit(s)" cohérent avec le tableau

#### Avantages

✅ **Cohérence garantie** : KPI et tableau affichent les mêmes données
✅ **Isolation par classe** : Chaque classe affiche uniquement ses propres étudiants
✅ **Pas de doublons** : Un étudiant n'apparaît qu'une fois même avec plusieurs inscriptions
✅ **Calculs dynamiques** : Taux d'occupation et places libres toujours à jour

---

### Fix: Accès étudiant et design moderne des pages étudiantes

**Date:** 10 octobre 2025
**Branche:** presentation

#### Problèmes résolus

1. **Erreur 403 sur les pages étudiantes**
   - **Cause:** Permissions manquantes (`view_own_profile`, `view_own_grades`, `view_own_exams`, `view_own_timetable`) pour le rôle `etudiant`
   - **Pages concernées:**
     - `/esbtp/mon-profil`
     - `/esbtp/mes-notes`
     - `/esbtp/mes-evaluations`
     - `/esbtp/mon-emploi-temps`
     - `/esbtp/esbtp/mes-absences`
   - **Solution:** Ajout des permissions manquantes dans `fix_permissions.php` et exécution du script

2. **Design obsolète de la page "Mes Absences"**
   - **Problème:** Interface Bootstrap basique ne correspondant pas au design moderne du dashboard étudiant
   - **Solution:** Refonte complète avec le système `dashboard-acasi` (cartes modernes, stat-cards, badges, etc.)

3. **Header "Mes Évaluations" non responsive**
   - **Problème:** Sur mobile, le titre et le badge année se chevauchaient
   - **Solution:** Ajout de media queries pour layout vertical sur mobile

#### Fichiers modifiés

**Permissions:**
- [fix_permissions.php:127-136](fix_permissions.php:127) - Ajout des permissions globales:
  - `view_own_grades`
  - `view_own_exams`
  - `view_own_profile`
- [fix_permissions.php:328-342](fix_permissions.php:328) - Mise à jour du rôle étudiant (11 permissions au total)

**Views:**
- [resources/views/esbtp/attendances/mes-absences.blade.php](resources/views/esbtp/attendances/mes-absences.blade.php) - Refonte complète:
  - Structure `dashboard-acasi` avec `main-content`
  - Header moderne avec titre et description
  - Stat cards (`stat-card-primary`, `stat-card-success`, `stat-card-danger`)
  - Tableau moderne (`table-modern`)
  - Badges de statut (`status-badge`)
  - Boutons modernes (`btn-acasi`)
  - Layout en grid (`dashboard-main-grid`)
  - Alertes stylisées
  - Modals Bootstrap 5

- [resources/views/etudiants/evaluations.blade.php:272-330](resources/views/etudiants/evaluations.blade.php:272) - Amélioration responsive:
  - Header en colonne sur mobile
  - Badge année aligné à gauche
  - Taille de police réduite
  - Badge type d'évaluation en position statique
  - Padding réduit des cartes

#### Permissions ajoutées (rôle étudiant)

Liste complète des 11 permissions du rôle `etudiant`:
1. `view_dashboard`
2. `view_own_notes`
3. `view_own_grades` ✨ (nouveau)
4. `view_own_bulletin`
5. `view_own_attendances`
6. `view_own_schedule`
7. `view_own_timetable` ✨ (nouveau)
8. `view_own_profile` ✨ (nouveau)
9. `view_own_exams` ✨ (nouveau)
10. `receive_messages`
11. `view_annonces`

#### Tests effectués

- ✅ Permissions appliquées avec succès (script `fix_permissions.php`)
- ✅ Cache nettoyé (`php artisan cache:clear && config:clear && permission:cache-reset`)
- ✅ Étudiant PRINCE MARC-ARTHUR ZEWOU a toutes les permissions
- ✅ Accès aux 5 pages étudiantes vérifié (pas d'erreur 403)
- ✅ Design moderne de "Mes Absences" cohérent avec le profil étudiant
- ✅ Header "Mes Évaluations" responsive sur mobile

#### Pages étudiantes accessibles

Toutes les pages suivantes sont maintenant accessibles pour le rôle `etudiant`:
- ✅ `/esbtp/mon-profil` (Profil étudiant)
- ✅ `/esbtp/mes-notes` (Notes et moyennes)
- ✅ `/esbtp/mes-evaluations` (Évaluations programmées)
- ✅ `/esbtp/mon-emploi-temps` (Emploi du temps)
- ✅ `/esbtp/esbtp/mes-absences` (Absences et justifications)

#### Design moderne appliqué

**Structure commune à toutes les pages étudiantes:**
- Container `dashboard-acasi`
- Header avec titre et description (`dashboard-header`)
- Cartes modernes (`main-card`)
- Grilles de statistiques (`stats-grid`, `stat-card`)
- Tableaux stylisés (`table-modern`)
- Badges de statut (`status-badge-success/danger/warning`)
- Boutons cohérents (`btn-acasi btn-acasi-primary/secondary`)
- Alertes modernes avec icônes
- Responsive design complet

---

### Fix: Calcul financier robuste et affichage logo dans emails parents

**Date:** 10 octobre 2025
**Branche:** presentation

#### Problèmes résolus

1. **Logo ne s'affichait pas dans les emails parents**
   - **Cause:** Variable `schoolLogo` utilisée au lieu de `schoolLogoPath` dans `NotificationService`
   - **Solution:** Remplacement de toutes les occurrences `'schoolLogo' => $schoolSettings['school_logo']` par `'schoolLogoPath' => $schoolSettings['schoolLogoPath']`
   - **Fichiers modifiés:** `app/Services/NotificationService.php` (7 occurrences corrigées)

2. **Calcul financier obsolète avec ESBTPReliquat (modèle inexistant)**
   - **Cause:** Utilisation de `ESBTPReliquat::where()` qui n'existe plus dans la nouvelle architecture
   - **Solution:** Remplacement par la vraie logique basée sur `ESBTPFraisSubscription` et `ESBTPReliquatDetail`
   - **Logique appliquée:**
     ```php
     // 1. Frais souscrits année courante
     $fraisSouscrits = ESBTPFraisSubscription::where('inscription_id', $inscription->id)
         ->where('is_active', true)->get();
     $totalFraisAnnee = $fraisSouscrits->sum('amount');

     // 2. Reliquats entrants années précédentes
     $reliquatsEntrants = ESBTPReliquatDetail::where('inscription_destination_id', $inscription->id)
         ->actifs()->get();
     $totalReliquats = $reliquatsEntrants->sum('solde_restant');

     // 3. Total attendu
     $totalAttendu = $totalFraisAnnee + $totalReliquats;

     // 4. Total payé
     $totalPaye = ESBTPPaiement::where('inscription_id', $inscription->id)
         ->where('status', 'validé')->sum('montant');

     // 5. Solde restant (jamais négatif)
     $soldeRestant = max(0, $totalAttendu - $totalPaye);
     ```
   - **Gestion cas étudiants ayant tout soldé:** Utilisation de `max(0, $soldeRestant)` pour éviter montants négatifs
   - **Protection division par zéro:** `$totalAttendu > 0 ? ... : 0`

3. **Variables manquantes dans emails d'inscription**
   - Ajout de toutes les variables financières (`montantTotal`, `montantPaye`, `montantDu`)
   - Les templates affichent maintenant la situation financière complète

#### Méthodes modifiées dans NotificationService

1. **notifyParentsInscriptionCreated()** (ligne ~2245)
   - Calcul complet de la situation financière
   - Passage de `schoolLogoPath` au lieu de `schoolLogo`

2. **notifyParentsPaiementValide()** (ligne ~2337)
   - Calcul robuste des montants sans `ESBTPReliquat`
   - Gestion taux de paiement avec protection division par zéro

#### Tests effectués

- ✅ Email envoyé à `djedjelipatrick@gmail.com` avec logo visible
- ✅ Calcul financier fonctionne pour étudiants sans frais (totalAttendu = 0)
- ✅ Calcul financier fonctionne pour étudiants ayant tout soldé (soldeRestant = 0)
- ✅ Pas de division par zéro quand totalAttendu = 0
- ✅ Montants jamais négatifs grâce à `max(0, $soldeRestant)`

#### Référence

Logique inspirée de `ESBTPInscriptionController@previewSituationFinanciere` (ligne 2174) qui utilise la même approche pour calculer la situation financière basée sur:
- Frais souscrits actifs (`ESBTPFraisSubscription`)
- Reliquats entrants (`ESBTPReliquatDetail`)
- Paiements validés (`ESBTPPaiement`)

---

### Feature: Système de notifications multi-canal pour les parents

**Date:** 9 octobre 2025
**Branche:** presentation

#### Fonctionnalités ajoutées

Implémentation complète d'un système de notifications en temps réel et par email pour les parents, avec support futur pour WhatsApp/SMS.

#### Architecture

**Points clés :**
- **IMPORTANT** : Les parents utilisent le même compte que leur enfant (pas de compte séparé)
  - Les notifications in-app utilisent `$etudiant->user_id`
  - Les emails sont envoyés à `$tuteur->email` (email du parent dans la table `esbtp_parents`)
  - Le parent se connecte avec les identifiants de l'étudiant pour voir les infos
- **Nom de la plateforme** : KLASSCI (et non ESBTP ou ESBTP-yAKRO)
- **Configuration dynamique** : Toutes les informations de l'établissement (nom, adresse, téléphone, email, logo) sont chargées depuis `esbtp/settings` via `SettingsHelper::get()` - aucune valeur hardcodée
- **Design** : Blanc (#ffffff) et Bleu (#007bff), sans gradients ni icônes

#### Événements notifiés

1. **Inscriptions** : Confirmation avec identifiants (nouveaux étudiants uniquement)
2. **Réinscriptions** : Confirmation sans identifiants (étudiants existants)
3. **Paiements** :
   - Création (notification aux administrateurs)
   - Validation (notification au parent avec détails financiers)
   - Rejet (notification au parent avec motif)
4. **Absences** : Notification quotidienne avec taux de présence mensuel
5. **Bulletins** : Publication avec moyenne et rang
6. **Notes faibles** : Alerte si moyenne < seuil configuré

#### Fichiers créés

##### Migrations et modèles
- `database/migrations/2025_10_09_create_parent_notification_preferences_table.php` - Table préférences notifications
- `app/Models/ParentNotificationPreference.php` - Modèle préférences

##### Templates email (Blade)
- `resources/views/esbtp/emails/parents/layout.blade.php` - Layout de base
- `resources/views/esbtp/emails/parents/inscription-confirmation.blade.php` - Avec identifiants
- `resources/views/esbtp/emails/parents/reinscription-confirmation.blade.php` - Sans identifiants
- `resources/views/esbtp/emails/parents/paiement-created.blade.php` - Paiement en attente
- `resources/views/esbtp/emails/parents/paiement-valide.blade.php` - Paiement validé
- `resources/views/esbtp/emails/parents/paiement-rejete.blade.php` - Paiement rejeté
- `resources/views/esbtp/emails/parents/paiement-relance.blade.php` - Relance paiement
- `resources/views/esbtp/emails/parents/absence-notification.blade.php` - Absence quotidienne
- `resources/views/esbtp/emails/parents/low-attendance.blade.php` - Alerte taux présence
- `resources/views/esbtp/emails/parents/bulletin-published.blade.php` - Bulletin publié
- `resources/views/esbtp/emails/parents/low-grades.blade.php` - Alerte notes faibles
- `resources/views/esbtp/emails/parents/note-published.blade.php` - Note publiée

##### Mailable classes
- `app/Mail/Parents/InscriptionConfirmationMail.php`
- `app/Mail/Parents/ReinscriptionConfirmationMail.php`
- `app/Mail/Parents/PaiementCreatedMail.php`
- `app/Mail/Parents/PaiementValideMail.php`
- `app/Mail/Parents/PaiementRejeteMail.php`
- `app/Mail/Parents/PaiementRelanceMail.php`
- `app/Mail/Parents/AbsenceNotificationMail.php`
- `app/Mail/Parents/LowAttendanceMail.php`
- `app/Mail/Parents/BulletinPublishedMail.php`
- `app/Mail/Parents/LowGradesMail.php`
- `app/Mail/Parents/NotePublishedMail.php`

#### Fichiers modifiés

##### NotificationService
- `app/Services/NotificationService.php` (lignes 2184-2650)
  - **Import ajouté** : `use App\Models\ESBTPReliquat;`
  - **Méthode helper** : `getSchoolSettings()` - Charge les paramètres depuis SettingsHelper
  - **7 nouvelles méthodes** :
    - `notifyParentsInscriptionCreated($inscription, $credentials)` - Ligne 2208
    - `notifyParentsReinscriptionCreated($inscription, $decision, $reliquatMontant)` - Ligne 2594
    - `notifyParentsPaiementValide($paiement)` - Ligne 2277
    - `notifyParentsPaiementRejete($paiement)` - Ligne 2339
    - `notifyParentsAbsence($attendance)` - Ligne 2386
    - `notifyParentsBulletinPublished($bulletin)` - Ligne 2456
    - `notifyParentsLowGrades($bulletin)` - Ligne 2513

##### Controllers (intégrations)
- `app/Http/Controllers/ESBTPInscriptionController.php` (ligne 746-753) - Notification inscription
- `app/Http/Controllers/ESBTP/ESBTPReinscriptionController.php` (ligne 878-889) - Notification réinscription
- `app/Http/Controllers/ESBTPPaiementController.php` :
  - Ligne 714 : Notification création paiement
  - Ligne 1871 : Notification validation paiement
  - Ligne 1936 : Notification rejet paiement
- `app/Http/Controllers/ESBTPAttendanceController.php` (ligne 1224) - Notification absence
- `app/Http/Controllers/ESBTPBulletinController.php` (ligne 2289-2299) - Notifications bulletin/notes

#### Configuration SMTP

**.env configuré** :
```env
MAIL_MAILER=smtp
MAIL_HOST=mail.klassci.com
MAIL_PORT=465
MAIL_USERNAME=support@klassci.com
MAIL_PASSWORD=@FV@8BWyKk3JiPb
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=support@klassci.com
MAIL_FROM_NAME="KLASSCI"
```

**Problème résolu** : Configuration SMTP dupliquée dans .env - la deuxième occurrence (lignes 80-87) écrasait la première

#### Améliorations design (9 octobre 2025 - 23h)

**Problèmes corrigés** :
1. ❌ Email envoyé depuis "ESBTP-yAKRO" au lieu de "KLASSCI" → `MAIL_FROM_NAME="KLASSCI"` + config cache
2. ❌ Design email basique et peu attrayant → Refonte complète inspirée du PDF liste-appel
3. ❌ Beaucoup de N/A affichés (classe, filière, niveau manquants) → Conditions `@if` pour masquer les N/A
4. ❌ Valeurs hardcodées dans template → Utilisation systématique des variables `$schoolName`, etc.
5. ❌ Bouton bleu invisible sur fond bleu → Bouton blanc avec bordure bleue (#007bff)
6. ❌ Manque de contraste/profondeur → Fond gris clair (#f2f2f2) + éléments blancs avec ombres

**Nouveau design email** :
- **Header bleu** (#007bff) avec section titre semi-transparente (inspiré PDF liste-appel)
- **Contenu** : Fond gris clair (#f2f2f2) pour contraste
- **Bouton** : Fond blanc, texte bleu, bordure bleue 2px, ombre portée (hover: inversé)
- **Tables** : Fond blanc, en-têtes bleus, ombres subtiles (0 2px 4px)
- **KPI cards** : Fond blanc, bordures arrondies, ombres
- **Instruction-box** : Fond blanc, bordure grise, ombre
- **Container principal** : Ombre prononcée (0 8px 16px) pour effet de profondeur
- **Footer** : Fond gris (#f8f9fa), bordure bleue supérieure 3px
- **Police monospace** (Courier New) pour identifiants (username/password)
- **Responsive design** pour mobile
- **Design 100% professionnel** - aucun emoji, design épuré et moderne
- **Masquage automatique** des champs vides (N/A) via conditions `@if`
- **Settings dynamiques** - aucune valeur hardcodée

**Fichiers modifiés** :
- `resources/views/esbtp/emails/parents/layout.blade.php` - Refonte complète (440 lignes)
- `resources/views/esbtp/emails/parents/inscription-confirmation.blade.php` - Nouveau design + masquage N/A
- `app/Services/NotificationService.php` - Encodage logo en base64 pour affichage email

**Fix logo email (9 octobre 2025 - 23h30 → 00h15)** :
- ❌ Logo ne s'affichait pas dans les emails (chemin relatif invalide)
- ❌ Base64 encodé mais **bloqué par Gmail** (politique de sécurité)
- ❌ `storage_path()` avec `$message->embed()` → Image non trouvée
- ✅ **Solution finale** : `public_path('storage/' . $logoPath)` avec `$message->embed()`
- ✅ Migration de **tous les Mailable** vers méthode `build()` au lieu de `content()/envelope()`
- ✅ Le logo est attaché comme image inline (CID - Content-ID)
- ✅ Changement dans **tous les templates** : `$schoolLogo` → `$schoolLogoPath`
- ✅ Compatible avec tous les clients email (Gmail, Outlook, Apple Mail, etc.)

**Point crucial** :
- `$message->embed()` nécessite **`public_path()`** et non `storage_path()`
- Le logo est accessible via le symlink : `public/storage/logos/xxx.png`

**Fichiers modifiés** :
- **11 Mailable classes** migrées vers `build()` method (app/Mail/Parents/*.php)
- **11 templates Blade** mis à jour avec `$schoolLogoPath` (resources/views/esbtp/emails/parents/*.blade.php)
- **NotificationService** : `getSchoolSettings()` utilise `public_path('storage/' . $logoPath)`

#### Tests effectués

✅ Notification in-app créée correctement (table `custom_notifications`)
✅ Email envoyé avec succès (test : djedjelipatrick@gmail.com)
✅ Settings dynamiques chargés depuis SettingsHelper
✅ Layout email utilise les variables `$schoolName`, `$schoolAddress`, etc.
✅ Pas de valeurs hardcodées
✅ MAIL_FROM_NAME = "KLASSCI" (vérifié via config:cache)
✅ Design moderne inspiré du PDF liste-appel
✅ Champs N/A masqués automatiquement
✅ Logo affiché correctement via `$message->embed()` (CID attachment)

#### Compte test créé

- **Étudiant** : Patrick Jean KOUAME (ID: 2775)
- **Username** : patrick.kouame
- **Password** : Patrick2025!
- **Email parent** : djedjelipatrick@gmail.com
- **Rôle** : etudiant (via Spatie Permission)

#### Phase future (non implémenté)

1. **WhatsApp** : Intégration Meta Cloud API avec templates pré-approuvés
2. **SMS** : Gateway SMS local
3. **Interface settings** : Page configuration préférences notifications par défaut
4. **Statistiques** : Dashboard historique notifications envoyées

#### Notes techniques

- Table `custom_notifications` utilisée (pas `notifications` Laravel native)
- Méthode `getOrCreateNotificationPreferences()` sur modèle `ESBTPParent`
- Canal email uniquement pour l'instant (champ `preferred_channels` JSON prévu pour multi-canal)
- Logging complet de toutes les opérations

---

### Feature: Rafraîchissement paiements & matricules tolérants aux doublons

**Date:** 6 octobre 2025  
**Branche:** presentation

#### Problèmes résolus

1. La page `paiements.index` nécessitait un rechargement complet pour afficher les nouveaux paiements ou appliquer un filtre. Les indicateurs financiers n'étaient pas synchronisés avec les résultats paginés.
2. Les détections de doublons côté inscription étaient liées à une route POST spécifique (`check-duplicates`), difficile à interroger depuis d'autres formulaires.
3. En cas de concurrence lors de la génération automatique d'un matricule, une exception SQL stoppait définitivement l'inscription.
4. Sur `reinscription.show`, lorsqu'une réinscription était déjà enregistrée, l'interface continuait d'afficher le bouton « Procéder ».

#### Solutions implémentées

- Scission de `paiements.index` en partiels `partials/metrics` et `partials/table`, exposition d'une route JSON `esbtp.paiements.refresh` et ajout d'un poll JavaScript (rafraîchissement manuel + intervalle) qui compare un `last_updated_at` et remplace le DOM sans rechargement global.
- Harmonisation des recherches fuzzy : le contrôleur `ESBTPPaiementController@index` retourne désormais du JSON (table + KPI) en mode AJAX, le front intercepte soumissions/pagination et gère l'historique via `pushState`.
- Nouvelle route GET `esbtp.inscriptions.duplicates` (toujours servie par `StudentDuplicateDetector`) utilisée par `inscriptions.create` avec `fetch` GET et conservation de l'ancien alias `check-duplicates` pour compatibilité.
- Ajout d'un helper `MatriculeGenerator` + injection dans `ESBTPInscriptionService`. Lors d'une collision SQL (`QueryException` 1062), `ESBTPInscriptionController@store` retente jusqu'à 3 fois en régénérant automatiquement le matricule avant d'abandonner.
- Détection côté `ESBTPReinscriptionController@show` d'une réinscription existante pour l'année courante : la carte affiche désormais un récapitulatif (décision, classe/filière/niveau, statut d'affectation, reliquat) et masque l'action.

#### Fichiers modifiés / ajoutés

- `app/Http/Controllers/ESBTPPaiementController.php`
- `resources/views/esbtp/paiements/index.blade.php`
- `resources/views/esbtp/paiements/partials/metrics.blade.php` *(nouveau)*
- `resources/views/esbtp/paiements/partials/table.blade.php` *(nouveau)*
- `routes/web.php` (routes `paiements.refresh`, `inscriptions.duplicates`)
- `resources/views/esbtp/inscriptions/create.blade.php`
- `app/Http/Controllers/ESBTPInscriptionController.php`
- `app/Services/ESBTPInscriptionService.php`
- `app/Support/MatriculeGenerator.php` *(nouveau)*
- `app/Http/Controllers/ESBTP/ESBTPReinscriptionController.php`
- `resources/views/esbtp/reinscription/show.blade.php`

#### Tests recommandés

- Sur `paiements.index` : appliquer un filtre, vérifier que la table et les KPI se mettent à jour sans rechargement et que le bouton « Rafraîchir » ainsi que le poll détectent un nouveau paiement (tester en validant un paiement dans un autre onglet).
- Ouvrir la console réseau : la route `paiements.refresh` doit retourner un JSON contenant `table`, `metrics`, `last_updated_at`.
- Soumettre un formulaire d'inscription sans matricule → provoquer volontairement un conflit (copier un matricule existant) et vérifier que la 2ᵉ tentative génère automatiquement un matricule unique.
- Tester la route `GET /inscriptions/duplicates` (via le formulaire ou en appel direct) et s'assurer que la modal de doublons continue de fonctionner.
- Consulter `reinscription.show` pour un étudiant déjà réinscrit : la carte doit afficher le récapitulatif et aucun bouton d'action n'apparaît.

### Maintenance: Recherche Fuzzy & rafraîchissement AJAX généralisés

**Date:** 8 octobre 2025  
**Branche:** presentation

#### Sujets traités

1. Instrumentation des listes étudiants / inscriptions pour diagnostiquer les recherches lentes (logs `start / processing / completed` avec durée, URL, utilisateur).
2. Durcissement du service `FuzzyNameMatcher` et harmonisation des contrôleurs `ESBTPStudentController@index` et `ESBTPInscriptionController@index` :
   - extraction préalable d'un lot raisonnable côté SQL (protection `%` via escape),
   - combinaison recherche exacte (matricule/nom/prénoms/concat + téléphone/email) + scoring fuzzy,
   - pagination `LengthAwarePaginator` en mémoire lorsque `search` est présent,
   - fallback automatique si une colonne optionnelle (ex. `numero_inscription`) est absente.
3. Refonte AJAX des vues `esbtp.etudiants.index` et `esbtp.inscriptions.index` :
   - interception formulaire / pagination via `fetch`,
   - rafraîchissement partiel (`partials.results`) + `pushState`,
   - restauration des filtres, gestion Select2/tooltips et reprise des sélections groupées,
   - retour JSON spécifique si `request()->ajax()`.
4. Journalisation des réponses AJAX pour suivre les requêtes côté back.

#### Tests express

- Recherche `DOSSO IBRAHIM` sur `/esbtp/etudiants` et `/esbtp/inscriptions` : vérifier apparition des logs `processing` + `completed`, absence de rechargement global, présence des résultats attendus.
- Pagination depuis une recherche fuzzy : s'assurer que l'URL se met à jour et que les filtres restent sélectionnés.
- Simuler base partielle (suppression colonne optionnelle) : la recherche retombe sur le fallback et n'explose plus en 500.
- Sur `/esbtp/paiements` : rejouer filtres, pagination et bouton « Rafraîchir » ; observer les logs `ESBTPPaiementController@index start/processing/completed` et vérifier que le poll compare bien `last_updated_at`.
- Sur `etudiants.show` / certificats : vérifier que toutes les inscriptions affichent « Année scolaire 2025-2026 » (sans doublon de préfixe) + la nouvelle colonne « Niveau d'étude » sur la prévisualisation et le PDF.


### Feature: Détection de doublons & gestion parents unifiée

**Date:** 5 octobre 2025  
**Branche:** presentation

#### Problème résolu

1. Lors d’une nouvelle inscription, un doublon potentiel (orthographe approximative, inversion nom/prénoms) pouvait être enregistré sans alerte.
2. Sur la fiche étudiant, la nationalité et la gestion des parents n’étaient pas harmonisées, et le bouton « Ajouter un parent » était instable.

#### Solution implémentée

- Création d’un service `StudentDuplicateDetector` (tokenisation + similarité + date/genre) exposé via une route AJAX `esbtp.inscriptions.check-duplicates`.
- Le formulaire `inscriptions.create` enclenche une vérification asynchrone, affiche un bandeau d’avertissement et bloque la soumission jusqu’à confirmation explicite (modal récapitulant les fiches proches, bouton « C’est la même personne » redirigeant vers `etudiants.show`).
- Facteurs front conservés via `duplicate_override` pour éviter les re-bloquages une fois l’utilisateur certain.
- Centralisation de la liste des nationalités dans `resources/views/esbtp/partials/nationality-options.blade.php` et réutilisation sur les formulaires `create` et `edit`.
- Refonte de la section Parents/Tuteurs sur `etudiants.edit` : cartes lisibles, ajout/suppression dynamique (max 2 entrées), synchronisation côté contrôleur.

#### Fichiers modifiés

- `app/Services/StudentDuplicateDetector.php` *(nouveau)* – logique fuzzy.
- `app/Http/Controllers/ESBTPInscriptionController.php` & `routes/web.php` – vérification serveur et route AJAX.
- `resources/views/esbtp/inscriptions/create.blade.php` – bandeau + modal + fetch JS.
- `resources/views/esbtp/partials/nationality-options.blade.php` *(nouveau)* – options mutualisées.
- `resources/views/esbtp/etudiants/edit.blade.php` & `resources/views/esbtp/etudiants/partials/parent-card.blade.php` *(nouveau)* – interface parents & select nationalité.

#### Tests recommandés

- Saisir un étudiant existant (prénoms/noms inversés ou faute volontaire) → le bandeau et la modal doivent apparaître.
- Cliquer sur « C’est la même personne » → redirection vers `etudiants.show`.
- Confirmer puis finaliser l’inscription → les doublons ne bloquent plus mais la création aboutit.
- Sur `etudiants.edit`, ajouter puis supprimer un parent → vérification en base que les liens pivot sont mis à jour.

### Feature: Propagation automatique des enseignants pour toute la classe

**Date:** 4 octobre 2025
**Branche:** presentation

#### Problème résolu

Lors de la configuration des noms d'enseignants pour les bulletins, il fallait remplir les noms matière par matière **pour chaque étudiant** de la classe. Avec des classes de 30+ étudiants, cela devenait très fastidieux et répétitif.

#### Solution implémentée

Ajout d'une **checkbox "Appliquer à toute la classe"** sur la page d'édition des professeurs ([edit-professeurs.blade.php](resources/views/esbtp/bulletins/edit-professeurs.blade.php)) qui permet de **copier automatiquement** les noms des enseignants configurés vers tous les autres bulletins de la même classe (même période, même année universitaire).

#### Fonctionnement

1. **Interface** : Checkbox avec switch moderne placée juste avant les boutons d'action
2. **Backend** : Logique dans [saveProfesseurs()](app/Http/Controllers/ESBTPBulletinController.php:5272-5290)
   - Si checkbox cochée : récupère tous les bulletins de la classe (même `classe_id`, `periode`, `annee_universitaire_id`)
   - Copie le JSON `professeurs` vers chaque bulletin
   - Met à jour `updated_by` avec l'utilisateur actuel
3. **Feedback** : Message indiquant combien de bulletins ont été mis à jour
   - Ex: "Les noms des professeurs ont été enregistrés avec succès. Ces enseignants ont également été appliqués à 29 autre(s) bulletin(s) de la classe."

#### Fichiers modifiés

- [resources/views/esbtp/bulletins/edit-professeurs.blade.php:283-303](resources/views/esbtp/bulletins/edit-professeurs.blade.php:283) - Ajout checkbox propagation
- [app/Http/Controllers/ESBTPBulletinController.php:5236](app/Http/Controllers/ESBTPBulletinController.php:5236) - Validation `appliquer_a_classe`
- [app/Http/Controllers/ESBTPBulletinController.php:5270-5290](app/Http/Controllers/ESBTPBulletinController.php:5270) - Logique de propagation
- [app/Http/Controllers/ESBTPBulletinController.php:5304-5308](app/Http/Controllers/ESBTPBulletinController.php:5304) - Message de feedback dynamique

#### Avantages

✅ **Gain de temps massif** : Configuration en une seule fois pour toute la classe
✅ **Cohérence garantie** : Mêmes enseignants sur tous les bulletins de la classe
✅ **Optionnel** : L'utilisateur choisit s'il veut propager ou non
✅ **Transparent** : Feedback clair sur le nombre de bulletins mis à jour
✅ **Audit trail** : Chaque mise à jour enregistre l'utilisateur (`updated_by`)

---

### Fix: Message d'erreur explicite lors de la génération de bulletin

**Date:** 4 octobre 2025
**Branche:** presentation

#### Problème résolu

Quand l'utilisateur enregistrait les absences et générait le bulletin, si une erreur survenait (ex: "Aucune matière trouvée"), le message n'était pas explicite et ne confirmait pas que les absences avaient bien été sauvegardées.

#### Solution

Modification des messages d'erreur dans [genererPDFParParamsUnified()](app/Http/Controllers/ESBTPBulletinController.php:4740-4758) pour :
1. **Confirmer** que les absences sont bien enregistrées
2. **Expliquer** pourquoi le bulletin ne peut pas être généré
3. **Rediriger** vers la page des résultats de l'étudiant (au lieu d'un simple `back()`)
4. **Indiquer** quelle action entreprendre ("Modifier les moyennes")

**Nouveau message** :
> "Les absences ont été enregistrées avec succès. Cependant, le bulletin ne peut pas être généré car aucune matière n'a été trouvée pour cette classe. Veuillez d'abord "Modifier les moyennes" pour configurer les notes."

#### Fichiers modifiés

- [app/Http/Controllers/ESBTPBulletinController.php:4740-4746](app/Http/Controllers/ESBTPBulletinController.php:4740) - Message cas "Aucune matière"
- [app/Http/Controllers/ESBTPBulletinController.php:4753-4758](app/Http/Controllers/ESBTPBulletinController.php:4753) - Message cas "Erreur récupération"

---

### Feature: Édition manuelle des absences pour les bulletins

**Date:** 4 octobre 2025
**Branche:** presentation

#### Fonctionnalités ajoutées

Implémentation d'un système d'édition manuelle des absences pour les bulletins, similaire au système de modification des moyennes.

**Flux de génération de bulletin mis à jour:**
1. Configuration des matières
2. Vérification des moyennes
3. Édition des professeurs
4. **[NOUVEAU]** Édition des absences (optionnel)
5. Génération du PDF

#### 1. Système automatique conservé

- Le système de calcul automatique des absences via le module d'émargement reste actif
- Les absences sont calculées automatiquement depuis `calculerAbsencesDetailes()`
- L'édition manuelle est **optionnelle** et vient en complément

#### 2. Interface d'édition des absences

**Page:** [resources/views/esbtp/bulletins/edit-absences.blade.php](resources/views/esbtp/bulletins/edit-absences.blade.php)

**Caractéristiques:**
- Design moderne similaire à `moyennes-preview.blade.php`
- KPI cards affichant: Étudiant, Classe, Période, Total absences
- Vue comparative: Absences calculées automatiquement vs Absences à enregistrer
- Badge indiquant la source des données (Auto/Manuel)
- Calcul en temps réel du total et de la note d'assiduité via JavaScript
- Affichage du barème de calcul de la note d'assiduité

**Champs modifiables:**
- Absences justifiées (heures, step 0.5)
- Absences non justifiées (heures, step 0.5)
- Total absences (calculé automatiquement)
- Note d'assiduité (affichée, recalculée automatiquement)

**Actions disponibles:**
- Enregistrer (reste sur la page)
- Enregistrer et retour (retourne aux résultats étudiant)
- Enregistrer et générer PDF (enregistre puis génère le bulletin)

#### 3. Backend

**Controller:** [app/Http/Controllers/ESBTPBulletinController.php](app/Http/Controllers/ESBTPBulletinController.php)

**Nouvelles méthodes:**
- `editAbsences()` (ligne 5763) - Affiche la page d'édition
  - Récupère ou crée le bulletin
  - Calcule les absences automatiques via `calculerAbsencesDetailes()`
  - Initialise avec valeurs auto si pas de données manuelles
  - Détermine la source (auto/manuelle)
  - Calcule la note d'assiduité

- `saveAbsences()` (ligne 5870) - Sauvegarde les modifications
  - Valide les données (absences_justifiees, absences_non_justifiees)
  - Calcule `total_absences` = justifiées + non justifiées
  - Calcule `note_assiduite` via `calculerNoteAssiduite()`
  - Gère 3 actions: edit, save_and_back, generate
  - Logging complet des opérations

#### 4. Routes

**Fichier:** [routes/web.php](routes/web.php#L1630-L1631)

```php
Route::get('/esbtp-special/bulletins/edit-absences', [ESBTPBulletinController::class, 'editAbsences'])
    ->name('esbtp.bulletins.edit-absences');
Route::post('/esbtp-special/bulletins/save-absences', [ESBTPBulletinController::class, 'saveAbsences'])
    ->name('esbtp.bulletins.save-absences');
```

#### 5. Bouton d'accès

**Fichier:** [resources/views/components/student-results/action-buttons.blade.php](resources/views/components/student-results/action-buttons.blade.php#L60-L63)

- Visible uniquement pour les `superAdmin`
- Placé après "Éditer professeurs"
- Icône: `fas fa-user-clock`
- Style: `btn-acasi warning`

**Guide mis à jour:**
- Étape 4 ajoutée: "Éditer les absences (optionnel)"
- Indique que c'est facultatif

#### 6. Barème de calcul de la note d'assiduité

La note d'assiduité est calculée selon les absences **non justifiées** uniquement:

| Absences non justifiées | Note d'assiduité |
|------------------------|------------------|
| 0                      | +0.13 point      |
| 1                      | 0 point          |
| 2                      | -0.13 point      |
| 3-4                    | -0.39 point      |
| 5+                     | -0.50 point      |

**Implémentation:** [app/Http/Controllers/ESBTPBulletinController.php](app/Http/Controllers/ESBTPBulletinController.php#L4060-L4096)

#### 7. Stockage des données

**Table:** `esbtp_bulletins`

**Champs concernés:**
- `absences_justifiees` (float) - Heures d'absences justifiées
- `absences_non_justifiees` (float) - Heures d'absences non justifiées
- `total_absences` (float) - Total des heures d'absences
- `note_assiduite` (float, nullable) - Note d'assiduité calculée
- `details_absences` (json, nullable) - Détails au format JSON

**Migration:** [database/migrations/2025_04_08_091936_add_absences_fields_to_esbtp_bulletins_table.php](database/migrations/2025_04_08_091936_add_absences_fields_to_esbtp_bulletins_table.php)

#### Fichiers modifiés

- [routes/web.php](routes/web.php#L1630-L1631) - Ajout des routes
- [app/Http/Controllers/ESBTPBulletinController.php](app/Http/Controllers/ESBTPBulletinController.php#L5763-L5991) - Méthodes editAbsences() et saveAbsences()
- [resources/views/components/student-results/action-buttons.blade.php](resources/views/components/student-results/action-buttons.blade.php) - Bouton et guide

#### Fichiers créés

- [resources/views/esbtp/bulletins/edit-absences.blade.php](resources/views/esbtp/bulletins/edit-absences.blade.php) - Interface d'édition

#### Tests recommandés

- [ ] Tester l'affichage des absences calculées automatiquement
- [ ] Tester la modification manuelle des absences
- [ ] Vérifier le calcul en temps réel du total et de la note
- [ ] Tester les 3 boutons d'action (enregistrer, retour, générer)
- [ ] Vérifier que les valeurs sont bien sauvegardées dans le bulletin
- [ ] Générer un PDF et vérifier que les absences apparaissent correctement
- [ ] Tester le badge Auto/Manuel selon la source des données

#### Caractéristiques techniques

- **Permissions:** Accessible uniquement aux `superAdmin`
- **Validation:** Valeurs numériques ≥ 0, step 0.5h
- **Calcul JS:** Mise à jour en temps réel sans rechargement
- **Logging:** Tous les changements sont loggés
- **Transaction-safe:** Utilisation de try-catch pour gestion d'erreurs
- **Flexibilité:** Édition optionnelle, n'impacte pas le flux de base

---

## Fix: 404 error when generating bulletin from edit-absences page

**Date:** 4 octobre 2025
**Branche:** presentation

### Problème résolu

Lorsque l'utilisateur cliquait sur "Enregistrer et générer bulletin" depuis la page d'édition des absences, il obtenait une erreur 404 avec l'URL `http://localhost:8000/esbtp/bulletins/generate?etudiant_id=1`.

### Cause racine

La méthode `saveAbsences()` dans [ESBTPBulletinController.php](app/Http/Controllers/ESBTPBulletinController.php:5937) redirigait vers la route `esbtp.bulletins.generate` qui pointe vers une méthode `generateBulletin()` qui n'est qu'un stub avec des commentaires placeholder (`// ... existing code ...`).

### Solution

Changement de la route de redirection de `esbtp.bulletins.generate` vers `esbtp.bulletins.pdf-params` qui est la vraie route de génération de PDF définie à la ligne 1596 de [routes/web.php](routes/web.php:1596).

**Avant:**
```php
return redirect()->route('esbtp.bulletins.generate', [
    'etudiant_id' => $etudiant_id
]);
```

**Après:**
```php
return redirect()->route('esbtp.bulletins.pdf-params', [
    'bulletin' => $etudiant_id,
    'classe_id' => $classe_id,
    'periode' => $periode,
    'annee_universitaire_id' => $annee_universitaire_id
]);
```

### Fichiers modifiés

- [app/Http/Controllers/ESBTPBulletinController.php:5937](app/Http/Controllers/ESBTPBulletinController.php:5937) - Correction de la route de redirection

### Notes

La route `esbtp.bulletins.pdf-params` est utilisée partout ailleurs dans l'application (notamment dans [action-buttons.blade.php:74](resources/views/components/student-results/action-buttons.blade.php:74)) pour générer les bulletins PDF.

---

### Fix: Résolution de la fonctionnalité de sélection rapide d'enseignant

**Date:** 21 septembre 2025
**Branche:** presentation

#### Problèmes résolus

1. **Erreur de validation "La valeur sélectionnée pour periode est invalide"**
   - **Localisation:** `app/Http/Controllers/ESBTPBulletinController.php:2517`
   - **Cause:** La méthode `resultatEtudiant` acceptait seulement les valeurs '1,2' mais recevait 'semestre2' lors de la redirection
   - **Solution:** Mise à jour de la validation pour accepter les formats: '1,2,semestre1,semestre2'
   - **Code ajouté:** Logique de conversion complète entre les formats entiers et string

2. **Fonctionnalité de sélection rapide d'enseignant non fonctionnelle**
   - **Localisation:** `resources/views/esbtp/bulletins/edit-professeurs.blade.php`
   - **Cause:** Erreur JavaScript "selectEnseignant is not defined" due aux attributs `onchange`
   - **Solution:** Remplacement par des `addEventListener` et placement direct du script dans le HTML
   - **Résultat:** La sélection d'un enseignant dans le dropdown remplit automatiquement l'input correspondant

3. **Interface utilisateur peu moderne**
   - **Problème:** Design des inputs/selects et boutons trop près des bords
   - **Solution:** Refonte complète avec design moderne basé sur des cartes
   - **Améliorations:**
     - Cartes modernes avec hover effects
     - Meilleur espacement et placement des boutons
     - Icônes et couleurs améliorées
     - Responsive design

#### Fichiers modifiés

- `app/Http/Controllers/ESBTPBulletinController.php`
- `app/Http/Controllers/ESBTPEvaluationController.php`
- `resources/views/components/student-results/results-overview-card.blade.php`
- `resources/views/esbtp/bulletins/edit-professeurs.blade.php`

#### Fonctionnalités ajoutées

- Support des formats de période multiples (1, 2, semestre1, semestre2)
- Logging détaillé pour le débogage des erreurs de validation
- Interface moderne avec cartes pour l'assignation des enseignants
- Sélection rapide d'enseignant fonctionnelle avec animation
- Gestion robuste des événements JavaScript

#### Tests recommandés

- [ ] Tester la sélection rapide d'enseignant sur différentes matières
- [ ] Vérifier que la validation des périodes fonctionne correctement
- [ ] Tester l'interface sur mobile (responsive design)
- [ ] Vérifier que les bulletins PDF se génèrent correctement

#### Commandes de test

```bash
# Tests de base
php artisan test

# Vérification du linting (si configuré)
npm run lint

# Build des assets (si nécessaire)
npm run build
```

---

## Structure des composants

### Teacher Assignment Interface

Le composant d'assignation des enseignants utilise maintenant une structure moderne :

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

### JavaScript Events

Les événements JavaScript sont maintenant gérés via `addEventListener` :

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

## Fix: Implémentation des actions groupées sur les paiements

**Date:** 3 octobre 2025
**Branche:** presentation

### Problème résolu

**UX pénible sur la gestion des paiements**
- Les paiements en attente devaient être validés/rejetés un par un
- Avec beaucoup de paiements répartis sur plusieurs pages de pagination, le processus était fastidieux
- Aucune possibilité de traiter plusieurs paiements simultanément

### Solution implémentée

Implémentation complète d'un système d'actions groupées (bulk actions) pour les paiements :

1. **Interface utilisateur**
   - Checkboxes de sélection pour chaque paiement en attente (visible uniquement pour superAdmin)
   - Checkbox "Tout sélectionner" dans l'en-tête du tableau
   - Barre d'actions flottante en bas de l'écran affichant le nombre de paiements sélectionnés
   - Boutons pour valider ou rejeter la sélection
   - Modal de confirmation pour le rejet groupé avec champ "motif de rejet"

2. **Backend**
   - Nouvelle méthode `bulkValider()` dans `ESBTPPaiementController`
   - Nouvelle méthode `bulkRejeter()` dans `ESBTPPaiementController`
   - Support des transactions DB pour garantir l'intégrité des données
   - Gestion intelligente des reliquats lors de la validation
   - Messages de feedback détaillés (succès/erreurs/déjà traités)

3. **Routes**
   - `POST /paiements/bulk-valider`
   - `POST /paiements/bulk-rejeter`

### Fichiers modifiés

- [resources/views/esbtp/paiements/index.blade.php](resources/views/esbtp/paiements/index.blade.php) - Interface avec checkboxes et JavaScript
- [app/Http/Controllers/ESBTPPaiementController.php](app/Http/Controllers/ESBTPPaiementController.php:1666) - Méthodes `bulkValider()` et `bulkRejeter()`
- [routes/web.php](routes/web.php:691) - Routes pour actions groupées

### Caractéristiques techniques

- Sélection limitée aux paiements en statut `en_attente`
- Vérification des permissions (superAdmin uniquement)
- Compteurs en temps réel du nombre de paiements sélectionnés
- Animation smooth de la barre d'actions
- Validation côté serveur des IDs de paiements
- Gestion des erreurs avec rollback de transaction
- Logging des erreurs pour le débogage
- Mise à jour automatique des reliquats lors de la validation

---

## Fix: Migration base de données XAMPP Windows vers MariaDB WSL2

**Date:** 3 octobre 2025
**Branche:** presentation

### Problème résolu

**Impossible de connecter Laravel (WSL2) à MySQL XAMPP (Windows)**

Erreur rencontrée :
```
SQLSTATE[HY000] [2002] No such file or directory
```

### Cause racine

1. Laravel dans WSL2 avec `DB_HOST=localhost` cherchait un socket Unix (`/tmp/mysql.sock`) inexistant
2. MySQL XAMPP configuré sur Windows avec `bind-address=127.0.0.1` n'acceptait que les connexions locales Windows
3. Pare-feu Windows bloquait les connexions depuis WSL2 malgré les règles configurées

### Solution appliquée

**Migration vers MariaDB dans WSL2** pour éviter les complications de connexion cross-système :

1. Installation et configuration de MariaDB dans WSL2
2. Création de la base de données `esbtp-abidjan-db`
3. Configuration des utilisateurs MySQL
4. Mise à jour du fichier `.env` avec `DB_HOST=localhost`

### Scripts créés

- [setup-mariadb-wsl2.sh](setup-mariadb-wsl2.sh) - Script d'installation automatique MariaDB WSL2
- [test-mysql-connection.sh](test-mysql-connection.sh) - Script de diagnostic connexion MySQL

### Documentation mise à jour

- [docs/MYSQL_TROUBLESHOOTING_XAMPP.md](docs/MYSQL_TROUBLESHOOTING_XAMPP.md) - Section "Erreur 5: Laravel dans WSL2 ne peut pas se connecter à XAMPP MySQL sur Windows"

Trois solutions documentées :
1. Utiliser `DB_HOST=127.0.0.1` au lieu de `localhost`
2. Utiliser l'IP Windows depuis WSL2 avec configuration pare-feu
3. **Installer MariaDB directement dans WSL2** (solution choisie)

---

## Fix: Message "compiled views cleared successfully" sur toutes les pages

**Date:** 3 octobre 2025
**Branche:** presentation

### Problème résolu

Message texte "compiled views cleared successfully" apparaissant sur toutes les pages de l'application, corrompant :
- L'affichage des pages HTML
- Les réponses AJAX JSON
- Le chargement des images (404)

### Cause racine

Fichier [public/index.php](public/index.php:15) contenait un code de debug :

```php
// Force clear all caches on each request during development
if (in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1', 'localhost'])) {
    if (file_exists(__DIR__.'/../artisan')) {
        passthru('php ../artisan view:clear 2>/dev/null');
    }
}
```

Ce code exécutait `view:clear` à **chaque requête HTTP**, injectant le message de succès dans toutes les réponses.

### Solution

Suppression complète du bloc de code auto-cache-clearing (lignes 10-17) de `public/index.php`.

---

## Fix: Syntaxe Blade dans fichier JavaScript

**Date:** 3 octobre 2025
**Branche:** presentation

### Problème résolu

Le fichier [public/js/navbar-diagnostics.js](public/js/navbar-diagnostics.js) contenait du code Blade (`{{ route() }}`) qui ne compile pas dans les fichiers .js.

### Solution

Remplacement par lecture des routes depuis les attributs `data-route` du DOM, avec fallback vers chemins hardcodés :

```javascript
const notifBtn = document.getElementById('notificationsDropdown');
const msgBtn = document.getElementById('messagesDropdown');
const actionBtn = document.getElementById('quickActionsDropdown');

if (notifBtn) {
    console.log('🛣️ Route notifications:', notifBtn.dataset.route || '/navbar/notifications');
}
```

### Création de répertoires manquants

Création du répertoire pour les photos de profil :
```bash
mkdir -p storage/app/public/profile-photos
```

---

## Feature: Système de notifications et rappels automatiques pour inscriptions et paiements

**Date:** 4 octobre 2025
**Branche:** presentation

### Fonctionnalités ajoutées

Implémentation complète d'un système de notifications en temps réel et de rappels automatiques pour les inscriptions et paiements en attente.

#### 1. Notifications en temps réel

**Notifications d'inscription :**
- Envoyées à tous les `superAdmin`, `coordinateur` et `secretaire` (sauf celui qui a créé l'inscription)
- Contiennent : nom étudiant, classe, statut inscription, étape workflow, état du paiement
- Lien direct vers [inscriptions.show](app/Http/Controllers/ESBTPInscriptionController.php:485)
- Icônes FontAwesome pour meilleure lisibilité

**Notifications de paiement :**
- **Création** : Notifie les `superAdmin` quand un paiement en attente est créé
- **Validation** : Notifie l'étudiant concerné avec les détails (référence, numéro de reçu)
- **Rejet** : Notifie l'étudiant avec le motif du rejet

#### 2. Système de rappels automatiques

**Table de suivi `notification_reminders` :**
- Stocke l'état des rappels pour chaque inscription/paiement
- Champs : `remindable_type`, `remindable_id`, `reminder_count`, `last_reminder_sent_at`, `next_reminder_at`, `is_active`
- Désactivation automatique après validation/rejet

**Paramètres configurables (via interface) :**
- Délai avant premier rappel (jours)
- Fréquence entre rappels (jours)
- Nombre maximum de rappels (0 = illimité)
- Activation/désactivation par type (inscriptions/paiements)

**Valeurs par défaut :**
- Inscriptions : 1er rappel après 3j, puis tous les 2j, max 5 rappels
- Paiements : 1er rappel après 2j, puis tous les 1j, max 7 rappels

#### 3. Interface de configuration

**Nouvelle page settings avec onglets :**
- Onglet "Général" : Informations établissement (inchangé)
- Onglet "Configuration PDF" : Paramètres bulletins (inchangé)
- **Nouveau** - Onglet "Notifications et Rappels" :
  - Section rappels inscriptions
  - Section rappels paiements
  - Section test et diagnostics (bouton de test en mode simulation)

**Route de test :** `POST /esbtp/settings/test-reminders`

### Fichiers créés

#### Modèles et migrations
- [database/migrations/2025_10_04_092055_create_notification_reminders_table.php](database/migrations/2025_10_04_092055_create_notification_reminders_table.php)
- [app/Models/NotificationReminder.php](app/Models/NotificationReminder.php)

#### Commande et scheduler
- [app/Console/Commands/SendInscriptionPaiementReminders.php](app/Console/Commands/SendInscriptionPaiementReminders.php)
- [app/Console/Kernel.php](app/Console/Kernel.php:102) - Ajout de la tâche planifiée quotidienne à 8h00

#### Seeder
- [database/seeders/ReminderSettingsSeeder.php](database/seeders/ReminderSettingsSeeder.php)

### Fichiers modifiés

#### Services
- [app/Services/NotificationService.php](app/Services/NotificationService.php:1847) - 6 nouvelles méthodes :
  - `notifyInscriptionCreated()` - Notification création inscription
  - `notifyPaiementCreated()` - Notification création paiement
  - `notifyPaiementValide()` - Notification validation paiement
  - `notifyPaiementRejete()` - Notification rejet paiement
  - `sendInscriptionReminder()` - Envoi rappel inscription
  - `sendPaiementReminder()` - Envoi rappel paiement

#### Controllers
- [app/Http/Controllers/ESBTPInscriptionController.php](app/Http/Controllers/ESBTPInscriptionController.php:458) - Appel `notifyInscriptionCreated()` après création
- [app/Http/Controllers/ESBTPPaiementController.php](app/Http/Controllers/ESBTPPaiementController.php:464) - 3 intégrations :
  - Ligne 464 : Notification création paiement
  - Ligne 1618 : Notification validation + désactivation rappels
  - Ligne 1680 : Notification rejet + désactivation rappels
- [app/Http/Controllers/ESBTP/ESBTPSettingsController.php](app/Http/Controllers/ESBTP/ESBTPSettingsController.php:83) - Gestion paramètres rappels + méthode `testReminders()`

#### Vues
- [resources/views/esbtp/settings/index.blade.php](resources/views/esbtp/settings/index.blade.php) - Refonte complète avec système d'onglets :
  - Lignes 285-302 : Navigation par onglets
  - Lignes 864-1042 : Nouvel onglet "Notifications et Rappels"
  - Lignes 1164-1225 : Fonction JavaScript `testReminders()`

#### Routes
- [routes/web.php](routes/web.php:1518) - Route `esbtp.settings.test-reminders`

### Commandes disponibles

```bash
# Tester les rappels (mode simulation, n'envoie rien)
php artisan reminders:send-inscription-paiement --test

# Envoyer les rappels réellement
php artisan reminders:send-inscription-paiement

# Seed des paramètres par défaut
php artisan db:seed --class=ReminderSettingsSeeder
```

### Planification automatique

La commande `reminders:send-inscription-paiement` s'exécute automatiquement **chaque jour à 8h00** (heure d'Abidjan) via le scheduler Laravel.

Pour activer le scheduler en production :
```bash
# Ajouter au crontab
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Caractéristiques techniques

- **Anti-auto-notification** : L'utilisateur qui crée une inscription/paiement ne reçoit pas la notification
- **Icônes FontAwesome** : Toutes les notifications utilisent des icônes (pas d'emojis)
- **Gestion intelligente des rappels** : Arrêt automatique après limite ou changement de statut
- **Mode test intégré** : Permet de tester sans envoyer de vraies notifications
- **Logging complet** : Toutes les opérations sont loguées pour audit
- **Transaction-safe** : Utilisation de DB::beginTransaction() pour intégrité des données

### Tests effectués

- ✅ Migration `notification_reminders` exécutée avec succès
- ✅ Seeder des paramètres par défaut exécuté avec succès
- ✅ Commande test avec 226 inscriptions et 110 paiements en attente détectés
- ✅ Interface settings avec onglets fonctionnelle
- ✅ Système anti-auto-notification vérifié

### Notes importantes

- Les notifications utilisent la table `custom_notifications` (pas la table Laravel native `notifications`)
- Les settings de rappels utilisent `ESBTPSystemSetting` (pas la table `settings`)
- Le scheduler doit être activé via crontab pour le fonctionnement automatique en production
- En développement, lancer manuellement : `php artisan schedule:work`

---

*Dernière mise à jour: 4 octobre 2025*
## Feature: Système de notifications emails pour les parents

**Date:** 9 octobre 2025  
**Branche:** presentation

### Fonctionnalités ajoutées

Implémentation complète d'un système de notifications par email pour les parents concernant tous les événements importants liés à la scolarité de leurs enfants.

#### Vue d'ensemble

Les parents reçoivent des emails automatiques pour :
1. **Inscription** - Confirmation avec identifiants de connexion
2. **Paiements** - Création, validation, rejet
3. **Absences** - Notifications quotidiennes avec statistiques mensuelles
4. **Bulletins** - Publication et alertes de notes faibles
5. **Système de préférences** - Configuration personnalisable par parent

**Points clés :**
- **IMPORTANT** : Les parents utilisent le même compte que leur enfant (pas de compte séparé)
  - Les notifications in-app utilisent `$etudiant->user_id`
  - Les emails sont envoyés à `$tuteur->email` (email du parent dans la table `esbtp_parents`)
  - Le parent se connecte avec les identifiants de l'étudiant pour voir les infos
- Design uniforme : blanc (#ffffff) et bleu (#007bff), sans gradient ni icône
- Envoi multi-canal : notification in-app + email (WhatsApp/SMS en Phase 2/3)
- Système de préférences avec opt-in/opt-out par type d'événement

### 1. Table de préférences des notifications

**Migration :** `database/migrations/2025_10_09_182704_create_parent_notification_preferences_table.php`

**Champs :**
- `parent_id` - ID du parent (FK vers esbtp_parents)
- `notify_inscriptions` - Activer notifications d'inscription (défaut: true)
- `notify_paiements` - Activer notifications de paiement (défaut: true)
- `notify_absences` - Activer notifications d'absence (défaut: true)
- `notify_notes` - Activer notifications de notes (défaut: true)
- `notify_bulletins` - Activer notifications de bulletins (défaut: true)
- `notify_annonces` - Activer notifications d'annonces (défaut: true)
- `preferred_channels` - Canaux préférés (JSON: ["app", "email"], extensible pour WhatsApp/SMS)
- `absence_threshold` - Seuil d'absences pour alerte (défaut: 3)
- `grade_threshold` - Seuil de moyenne pour alerte (défaut: 10.0)
- `attendance_rate_threshold` - Seuil de taux de présence pour alerte (défaut: 80)
- `notification_count` - Compteur de notifications envoyées
- `last_notification_sent_at` - Date de dernière notification

**Index :** `pnp_absences_paiements_idx` sur (notify_absences, notify_paiements)

### 2. Modèle ParentNotificationPreference

**Fichier :** `app/Models/ParentNotificationPreference.php`

**Méthodes principales :**
```php
hasChannel($channel)               // Vérifie si un canal est activé
isNotificationEnabled($type)       // Vérifie si un type de notification est activé
incrementNotificationCount()       // Incrémente le compteur
getOrCreateForParent($parentId)   // Récupère ou crée les préférences
```

**Relation avec ESBTPParent :**
```php
// Dans app/Models/ESBTPParent.php
public function notificationPreferences()
{
    return $this->hasOne(ParentNotificationPreference::class, 'parent_id');
}

public function getOrCreateNotificationPreferences()
{
    return ParentNotificationPreference::getOrCreateForParent($this->id);
}
```

### 3. Templates d'emails

**Layout de base :** `resources/views/esbtp/emails/parents/layout.blade.php`
- Design blanc et bleu sans gradient
- Header bleu (#007bff) avec logo et nom de l'établissement
- Corps blanc avec sections clairement délimitées
- Footer gris clair (#f8f9fa) avec informations de contact
- Responsive design pour mobile

**10 templates spécialisés :**

1. **inscription-confirmation.blade.php**
   - Confirmation d'inscription avec année universitaire, classe, filière, niveau
   - Table d'identifiants (nom d'utilisateur + mot de passe)
   - Lien vers la plateforme

2. **paiement-valide.blade.php**
   - Confirmation de validation avec montant, référence, numéro de reçu
   - KPI financiers : Total payé, Reliquat restant, Taux de paiement
   - Situation financière complète

3. **paiement-created.blade.php**
   - Notification de paiement en attente de validation
   - Détails : montant, mode, référence

4. **paiement-rejete.blade.php**
   - Notification de rejet avec motif détaillé
   - Informations du paiement rejeté

5. **paiement-relance.blade.php**
   - Rappel de paiement avec montant dû
   - Situation financière et solde restant

6. **absence-notification.blade.php**
   - Notification d'absence avec date, heure, matière
   - Statistiques mensuelles : total absences, justifiées, non justifiées, taux de présence
   - Badge coloré pour le taux (vert ≥80%, orange ≥60%, rouge <60%)

7. **low-attendance.blade.php**
   - Alerte de taux de présence faible (<80%)
   - Statistiques détaillées du mois
   - Invitation à contacter l'établissement

8. **bulletin-published.blade.php**
   - Notification de disponibilité du bulletin
   - Résumé : moyenne générale, rang, mention
   - Lien vers le bulletin PDF

9. **low-grades.blade.php**
   - Alerte de performance académique faible
   - Liste des matières avec moyenne <10
   - Encouragement au soutien scolaire

10. **note-published.blade.php**
    - Notification de publication d'une note individuelle
    - Détails : matière, note, coefficient

### 4. Classes Mailable

**Emplacement :** `app/Mail/Parents/`

**Liste des Mailables :**
- `InscriptionConfirmationMail.php`
- `PaiementValideMail.php`
- `PaiementCreatedMail.php`
- `PaiementRejeteMail.php`
- `PaiementRelanceMail.php`
- `AbsenceNotificationMail.php`
- `LowAttendanceMail.php`
- `BulletinPublishedMail.php`
- `LowGradesMail.php`
- `NotePublishedMail.php`

**Structure commune :**
```php
class [Event]Mail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject('[Sujet]')
                    ->view('esbtp.emails.parents.[template]');
    }
}
```

### 5. Extension du NotificationService

**Fichier :** `app/Services/NotificationService.php`

**6 nouvelles méthodes pour les parents :**

1. **notifyParentsInscriptionCreated($inscription, $credentials)**
   - Envoyée après création d'inscription réussie
   - Paramètres : objet inscription, array credentials (username, password)
   - Notification in-app + email avec identifiants de connexion

2. **notifyParentsPaiementValide($paiement)**
   - Envoyée après validation d'un paiement
   - Calcule situation financière complète (montants, reliquat, taux)
   - Notification in-app + email avec KPI financiers

3. **notifyParentsPaiementRejete($paiement)**
   - Envoyée après rejet d'un paiement
   - Inclut le motif du rejet
   - Notification in-app + email

4. **notifyParentsAbsence($attendance)**
   - Envoyée lors d'une nouvelle absence
   - Calcule statistiques mensuelles (absences justifiées/non justifiées, taux de présence)
   - Notification in-app + email avec stats
   - Alerte automatique si taux < seuil configuré

5. **notifyParentsBulletinPublished($bulletin)**
   - Envoyée lors de la publication d'un bulletin
   - Inclut moyenne générale, rang, mention
   - Notification in-app + email avec lien PDF

6. **notifyParentsLowGrades($bulletin)**
   - Alerte automatique si moyenne < 10 ou matières en échec
   - Liste les matières concernées
   - Notification in-app + email d'encouragement

**Méthode helper :**
```php
private function getMentionColor($mention)
{
    // Retourne couleur selon mention : vert (TB/B), orange (AB), rouge (P), gris (E)
}
```

**Logique commune à toutes les méthodes :**
1. Récupération de l'étudiant et ses parents (tuteurs)
2. **Vérification existence compte utilisateur de l'étudiant** (`$etudiant->user`)
3. Vérification existence du tuteur
4. Récupération ou création des préférences de notification du parent
5. Vérification activation du type de notification
6. Préparation des données
7. **Création notification in-app avec `$etudiant->user_id`** (le parent utilise le compte étudiant)
8. **Envoi email à `$tuteur->email`** si canal activé et adresse présente
9. Incrémentation compteur de notifications
10. Logging des erreurs éventuelles

### 6. Intégrations dans les contrôleurs

**ESBTPInscriptionController.php (ligne ~746)**
```php
// Après création de l'inscription
if ($inscription->etudiant && $inscription->etudiant->user && session('generated_password')) {
    $credentials = [
        'username' => $inscription->etudiant->user->username,
        'password' => session('generated_password'),
    ];
    $notificationService->notifyParentsInscriptionCreated($inscription, $credentials);
}
```

**ESBTPPaiementController.php (3 intégrations)**

1. **Création de paiement (ligne ~714)**
```php
// Notifier les parents de la création du paiement
$notificationService->notifyParentsPaiementValide($paiement);
```

2. **Validation de paiement (ligne ~1871)**
```php
// Envoyer notification aux parents
$notificationService->notifyParentsPaiementValide($paiement);
```

3. **Rejet de paiement (ligne ~1936)**
```php
// Envoyer notification aux parents
$notificationService->notifyParentsPaiementRejete($paiement);
```

**ESBTPAttendanceController.php (ligne ~1224)**
```php
// Dans la méthode sendAbsenceNotification()
// Après notification à l'étudiant
$this->notificationService->notifyParentsAbsence($absence);
```

**ESBTPBulletinController.php (ligne ~2289)**
```php
// Dans togglePublication(), après publication
if (!$wasPublished && $bulletin->is_published) {
    $notificationService->notifyParentsBulletinPublished($bulletin);
    $notificationService->notifyParentsLowGrades($bulletin);
}
```

### 7. Fichiers créés

**Migrations :**
- `database/migrations/2025_10_09_182704_create_parent_notification_preferences_table.php`

**Modèles :**
- `app/Models/ParentNotificationPreference.php`

**Templates Blade :**
- `resources/views/esbtp/emails/parents/layout.blade.php`
- `resources/views/esbtp/emails/parents/inscription-confirmation.blade.php`
- `resources/views/esbtp/emails/parents/paiement-valide.blade.php`
- `resources/views/esbtp/emails/parents/paiement-created.blade.php`
- `resources/views/esbtp/emails/parents/paiement-rejete.blade.php`
- `resources/views/esbtp/emails/parents/paiement-relance.blade.php`
- `resources/views/esbtp/emails/parents/absence-notification.blade.php`
- `resources/views/esbtp/emails/parents/low-attendance.blade.php`
- `resources/views/esbtp/emails/parents/bulletin-published.blade.php`
- `resources/views/esbtp/emails/parents/low-grades.blade.php`
- `resources/views/esbtp/emails/parents/note-published.blade.php`

**Mailables :**
- `app/Mail/Parents/InscriptionConfirmationMail.php`
- `app/Mail/Parents/PaiementValideMail.php`
- `app/Mail/Parents/PaiementCreatedMail.php`
- `app/Mail/Parents/PaiementRejeteMail.php`
- `app/Mail/Parents/PaiementRelanceMail.php`
- `app/Mail/Parents/AbsenceNotificationMail.php`
- `app/Mail/Parents/LowAttendanceMail.php`
- `app/Mail/Parents/BulletinPublishedMail.php`
- `app/Mail/Parents/LowGradesMail.php`
- `app/Mail/Parents/NotePublishedMail.php`

### 8. Fichiers modifiés

**Modèles :**
- `app/Models/ESBTPParent.php` - Ajout relations notificationPreferences

**Services :**
- `app/Services/NotificationService.php` - 6 méthodes parent + helper getMentionColor()

**Contrôleurs :**
- `app/Http/Controllers/ESBTPInscriptionController.php` - Notification inscription
- `app/Http/Controllers/ESBTPPaiementController.php` - Notifications paiements (création, validation, rejet)
- `app/Http/Controllers/ESBTPAttendanceController.php` - Notification absences
- `app/Http/Controllers/ESBTPBulletinController.php` - Notifications bulletins et notes faibles

### 9. Tests recommandés

**Migration et modèle :**
- [ ] Exécuter la migration : `php artisan migrate`
- [ ] Vérifier la table `parent_notification_preferences` dans la BDD
- [ ] Tester création de préférences via `ParentNotificationPreference::getOrCreateForParent()`

**Templates d'emails :**
- [ ] Tester le rendu de chaque template individuellement
- [ ] Vérifier le design : blanc/bleu, sans gradient, sans icône
- [ ] Tester le responsive sur mobile

**Envoi de notifications :**
- [ ] Créer une inscription → vérifier email inscription avec identifiants
- [ ] Valider un paiement → vérifier email paiement validé avec KPI
- [ ] Rejeter un paiement → vérifier email rejet avec motif
- [ ] Enregistrer une absence → vérifier email absence avec stats mensuelles
- [ ] Publier un bulletin → vérifier email bulletin + alerte notes faibles si applicable

**Préférences :**
- [ ] Désactiver un type de notification → vérifier que l'email n'est plus envoyé
- [ ] Retirer "email" de preferred_channels → vérifier notification in-app seulement
- [ ] Modifier les seuils (absence_threshold, grade_threshold) → vérifier alertes

**Configuration mail :**
- [ ] Vérifier configuration SMTP dans `.env` :
  ```
  MAIL_MAILER=smtp
  MAIL_HOST=smtp.gmail.com
  MAIL_PORT=587
  MAIL_USERNAME=your_email@gmail.com
  MAIL_PASSWORD=your_app_password
  MAIL_ENCRYPTION=tls
  MAIL_FROM_ADDRESS=your_email@gmail.com
  MAIL_FROM_NAME="${APP_NAME}"
  ```
- [ ] Tester connexion : `php artisan tinker` puis `Mail::raw('Test', function($msg) { $msg->to('test@example.com')->subject('Test'); });`

### 10. Commandes utiles

```bash
# Exécuter la migration
php artisan migrate

# Tester envoi d'email en console
php artisan tinker
>>> $parent = App\Models\ESBTPParent::first();
>>> $inscription = App\Models\ESBTPInscription::first();
>>> $credentials = ['username' => 'test', 'password' => 'test123'];
>>> app(\App\Services\NotificationService::class)->notifyParentsInscriptionCreated($inscription, $credentials);

# Vérifier la queue (si configurée)
php artisan queue:work

# Effacer le cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### 11. Prochaines étapes (Phase 2/3)

**Phase 2 - WhatsApp via Meta Cloud API (payant) :**
- [ ] Créer compte Meta Business Manager
- [ ] Créer application WhatsApp Business
- [ ] Obtenir token d'accès et Phone Number ID
- [ ] Créer templates WhatsApp (doivent être pré-approuvés)
- [ ] Créer service `WhatsAppService` pour gestion API
- [ ] Ajouter configuration dans `esbtp/settings`
- [ ] Tester envoi de messages WhatsApp

**Phase 3 - SMS (payant) :**
- [ ] Choisir provider SMS (Twilio, Vonage, AfricasTalking, etc.)
- [ ] Créer compte et obtenir credentials
- [ ] Créer service `SmsService` pour gestion API
- [ ] Ajouter configuration dans `esbtp/settings`
- [ ] Limiter nombre de caractères (160 chars standard)
- [ ] Tester envoi de SMS

**Améliorations futures :**
- [ ] Interface de gestion des préférences pour les parents dans leur profil
- [ ] Statistiques d'emails envoyés dans `esbtp/settings`
- [ ] Historique des notifications dans le profil parent
- [ ] Support de langues multiples (français, anglais)
- [ ] Système de templates personnalisables depuis l'interface admin
- [ ] Planification d'envoi (digest quotidien/hebdomadaire)

### 12. Notes techniques

**Architecture :**
- Toutes les notifications passent par `NotificationService` (centralisé)
- Chaque méthode vérifie les préférences avant envoi
- Les emails sont envoyés de manière synchrone (à mettre en queue si volume élevé)
- Les échecs d'envoi sont loggés mais ne bloquent pas le flux principal

**Sécurité :**
- Les mots de passe ne sont stockés qu'en session temporaire
- Les emails ne contiennent jamais de mots de passe après l'inscription initiale
- Les parents ne reçoivent que les infos de leurs propres enfants

**Performance :**
- Prévoir mise en queue si volume > 100 emails/jour
- Index sur `parent_notification_preferences` pour accès rapide
- Lazy loading des relations pour éviter N+1 queries

**Dépendances :**
- Laravel Mail (natif)
- Configuration SMTP requise
- Table `custom_notifications` pour notifications in-app
- Table `esbtp_parents` avec relation vers `esbtp_etudiants`

---

### Feature: Système AJAX "Load More" pour la liste des classes

**Date:** 10 octobre 2025
**Branche:** presentation

#### Problème résolu

La page de liste des classes (`classes.index`) utilisait une pagination traditionnelle qui :
- Désactivait les filtres lors du changement de page
- Nécessitait un rechargement complet de la page
- N'offrait pas d'expérience utilisateur fluide
- Affichait des KPI incorrects (basés uniquement sur les classes chargées au lieu de toutes les classes actives)

#### Solution implémentée

Implémentation complète d'un système AJAX avec bouton "Charger plus" qui préserve l'état des filtres et offre une expérience utilisateur fluide, similaire à celui déjà implémenté pour la liste des étudiants.

#### Architecture

**Points clés :**
- **Pagination manuelle** : Utilisation de `slice()` au lieu de `paginate()` pour un contrôle total
- **AJAX complet** : Aucun rechargement de page, tout passe par `fetch()`
- **Préservation des filtres** : Les filtres restent actifs lors du chargement de nouvelles classes
- **KPI globaux** : Statistiques calculées sur TOUTES les classes actives, pas seulement celles affichées
- **Gestion dynamique du DOM** : Utilisation de helper functions pour éviter les références obsolètes

#### 1. Backend - ESBTPClasseController

**Fichier :** [app/Http/Controllers/ESBTPClasseController.php](app/Http/Controllers/ESBTPClasseController.php)

**Modifications clés :**

- **Lignes 26-34** : Logging complet pour diagnostics
  ```php
  $startMicrotime = microtime(true);
  $baseLogContext = [
      'timestamp' => now()->toIso8601String(),
      'url' => $request->fullUrl(),
      'query' => $request->query(),
      'user_id' => optional($request->user())->id,
  ];
  \Log::info('ESBTPClasseController@index start', $baseLogContext);
  ```

- **Lignes 86-97** : Pagination manuelle avec slice()
  ```php
  $allClasses = $query->get();
  $perPage = 12;
  $page = $request->input('page', 1);
  $offset = ($page - 1) * $perPage;
  $classes = $allClasses->slice($offset, $perPage)->values();
  $hasMore = $allClasses->count() > ($offset + $perPage);
  $totalCount = $allClasses->count();
  ```

- **Lignes 103-132** : Calcul séparé des KPI sur TOUTES les classes actives
  ```php
  $kpiQuery = ESBTPClasse::where('is_active', true);

  if ($anneeCourante) {
      $kpiQuery->withCount([
          'inscriptions as nombre_etudiants_annee_courante' => function($q) use ($anneeCourante) {
              $q->where('annee_universitaire_id', $anneeCourante->id)
                ->where('status', 'active');
          }
      ]);
  }

  $allActiveClasses = $kpiQuery->get();

  $kpiStats = [
      'totalClasses' => $allActiveClasses->count(),
      'totalEtudiants' => $anneeCourante
          ? $allActiveClasses->sum('nombre_etudiants_annee_courante')
          : $allActiveClasses->sum('nombre_etudiants'),
      'totalPlaces' => $allActiveClasses->sum('places_totales'),
  ];
  ```

- **Lignes 144-151** : Réponse AJAX avec JSON
  ```php
  if ($request->ajax()) {
      $html = view('esbtp.classes.partials.items', compact('classes'))->render();
      return response()->json([
          'html' => $html,
          'hasMore' => $hasMore,
          'currentPage' => $page,
          'total' => $totalCount,
      ]);
  }
  ```

#### 2. Nouvelles vues partielles

**Fichier créé :** [resources/views/esbtp/classes/partials/results.blade.php](resources/views/esbtp/classes/partials/results.blade.php) (28 lignes)
- Conteneur principal avec grille de classes
- Bouton "Charger plus" avec visibilité conditionnelle
- Spinner de chargement
- État vide avec message et bouton de création

**Fichier créé :** [resources/views/esbtp/classes/partials/items.blade.php](resources/views/esbtp/classes/partials/items.blade.php) (145 lignes)
- Boucle foreach des cartes de classe uniquement
- Aucun wrapper ou bouton (pour permettre l'append AJAX)
- Contient les modals de suppression

#### 3. Frontend - JavaScript AJAX

**Fichier :** [resources/views/esbtp/classes/index.blade.php](resources/views/esbtp/classes/index.blade.php)

**Modifications clés :**

- **Lignes 141-143** : Bouton reset transformé de lien en bouton
  ```blade
  <button type="button" id="reset-filters-btn" class="btn-acasi secondary">
      <i class="fas fa-times me-1"></i>Réinitialiser
  </button>
  ```

- **Lignes 154-197** : KPI cards utilisant `$kpiStats`
  ```blade
  <div class="kpi-value color-primary">{{ $kpiStats['totalClasses'] }}</div>
  <div class="kpi-value color-accent">{{ $kpiStats['totalEtudiants'] }}</div>
  <div class="kpi-value color-success">{{ $kpiStats['totalPlaces'] }}</div>
  ```

- **Lignes 214-217** : Inclusion de la partial results
  ```blade
  <div id="classes-results">
      @include('esbtp.classes.partials.results', ['classes' => $classes])
  </div>
  ```

- **Lignes 328-335** : Helper functions pour références DOM dynamiques
  ```javascript
  function getLoadMoreBtn() {
      return document.getElementById('load-more-btn');
  }

  function getLoadMoreSpinner() {
      return document.getElementById('load-more-spinner');
  }
  ```

- **Lignes 364-441** : Fonction principale fetchResults() avec logique reset/append
  ```javascript
  function fetchResults(reset = true) {
      if (reset) {
          currentPage = 1;
          // Remplace tout le contenu avec nouvelle grille + bouton
          resultsContainer.innerHTML = `<div class="resultats-grid" id="classes-grid">...`;
      } else {
          // Ajoute à la grille existante
          const grid = document.getElementById('classes-grid');
          grid.insertAdjacentHTML('beforeend', data.html);
      }

      // TOUJOURS rebind après chargement
      bindLoadMore();
      updateLoadMoreButton(data.hasMore);
  }
  ```

- **Lignes 448-467** : Rebinding du bouton avec clone-and-replace
  ```javascript
  function bindLoadMore() {
      const btn = getLoadMoreBtn();
      const spinner = getLoadMoreSpinner();

      if (btn && spinner) {
          const newBtn = btn.cloneNode(true);
          btn.parentNode.replaceChild(newBtn, btn);

          newBtn.addEventListener('click', function() {
              newBtn.style.display = 'none';
              spinner.classList.remove('d-none');
              currentPage++;
              fetchResults(false); // Mode append
          });
      }
  }
  ```

- **Lignes 478-494** : Handler du bouton reset
  ```javascript
  resetBtn.addEventListener('click', function(e) {
      e.preventDefault();
      form.reset();

      if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
          $('#filiere_id, #niveau_id, #statut, #capacite').val(null).trigger('change');
      }

      fetchResults(true);
  });
  ```

#### Fichiers modifiés

- [app/Http/Controllers/ESBTPClasseController.php](app/Http/Controllers/ESBTPClasseController.php) - AJAX support, KPI calculation, manual pagination
- [resources/views/esbtp/classes/index.blade.php](resources/views/esbtp/classes/index.blade.php) - JavaScript AJAX, reset button, KPI display

#### Fichiers créés

- [resources/views/esbtp/classes/partials/results.blade.php](resources/views/esbtp/classes/partials/results.blade.php) - Container with load more button
- [resources/views/esbtp/classes/partials/items.blade.php](resources/views/esbtp/classes/partials/items.blade.php) - Class cards for AJAX append

#### Caractéristiques techniques

- **Double query strategy** : Une query pour l'affichage paginé, une query séparée pour les KPI globaux
- **Helper functions** : Accès dynamique aux éléments DOM au lieu de références cachées
- **Clone-and-replace** : Pattern propre pour le rebinding des event listeners
- **State management** : Variables `currentPage`, `hasMorePages`, `isLoading` pour tracking de pagination
- **History API** : Utilisation de `pushState` pour mise à jour d'URL sans navigation
- **Select2 integration** : Support des dropdowns Select2 avec reset
- **Loading states** : Spinners et états de chargement pour feedback utilisateur

#### Tests effectués

- ✅ Filtrage AJAX sans rechargement de page
- ✅ Bouton "Charger plus" préserve les filtres à travers les chargements
- ✅ Bouton reset efface les filtres via AJAX (sans rechargement)
- ✅ KPI cards affichent des statistiques globales précises (toutes les classes actives avec inscriptions année courante)
- ✅ Rebinding correct du bouton après multiples clics
- ✅ Gestion des états vides
- ✅ Logging complet pour diagnostics

#### Avantages

✅ **Expérience utilisateur fluide** : Pas de rechargement de page
✅ **Filtres persistants** : Les filtres restent actifs lors du chargement de nouvelles classes
✅ **KPI précis** : Statistiques calculées sur l'ensemble des classes actives
✅ **Performance optimisée** : Chargement incrémental par lots de 12
✅ **Code maintenable** : Séparation claire des concerns avec partials
✅ **Gestion robuste du DOM** : Pas de références obsolètes grâce aux helper functions

#### Problèmes résolus pendant l'implémentation

1. **HTML cassé** : HTML comment non fermé → Suppression du code commenté
2. **Reset force le reload** : Lien `<a href>` → Bouton avec handler JavaScript
3. **Bouton invisible** : `$hasMore` non passé à la vue → Ajout dans compact()
4. **Bouton disparaît après clic** : Références DOM obsolètes → Helper functions + rebinding systématique
5. **KPI incorrects** : Basés sur `$classes` (12 classes) → Query séparée sur toutes les classes actives

---

*Dernière mise à jour: 10 octobre 2025*
