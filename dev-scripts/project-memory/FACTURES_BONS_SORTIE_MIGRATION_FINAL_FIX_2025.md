# Migration/Fix Finale Factures & Bons de Sortie (2025)

## Pages concernées migrées/fixées :

-   resources/views/esbtp/comptabilite/factures/edit.blade.php
-   resources/views/esbtp/comptabilite/factures/show.blade.php
-   resources/views/esbtp/comptabilite/bons-sortie/create.blade.php
-   resources/views/esbtp/comptabilite/bons-sortie/edit.blade.php
-   resources/views/esbtp/comptabilite/bons-sortie/show.blade.php

## Problèmes rencontrés :

-   Ancien layout `layouts.app` utilisé sur plusieurs pages (non homogène, padding, sidebar, header, content-block non conformes).
-   Sidebar parfois différente ou incomplète.
-   Boutons, labels, paddings, erreurs de validation non harmonisés.
-   Sections Blade non respectées (header, sidebar, sidebarRight, content-block).
-   Doublons de logique ou de structure.

## Solutions appliquées :

-   Migration de toutes les pages vers `dashboard-layout.blade.php`.
-   Harmonisation stricte de la sidebar (routes, icônes, active state).
-   Utilisation des sections Blade modernes : header, sidebar, sidebarRight, content-block.
-   Correction des paddings, labels, boutons, affichage des erreurs.
-   Suppression de tout code ou layout obsolète.
-   Respect strict de PROMPT.txt, mémoire projet, et design validé.

## Indexation

-   [FACTURES_EDIT] : edit.blade.php (layout moderne, sidebar, header, content-block)
-   [FACTURES_SHOW] : show.blade.php (layout moderne, sidebar, header, content-block)
-   [BONS_SORTIE_CREATE] : create.blade.php (layout moderne, sidebar, header, content-block)
-   [BONS_SORTIE_EDIT] : edit.blade.php (layout moderne, sidebar, header, content-block)
-   [BONS_SORTIE_SHOW] : show.blade.php (layout moderne, sidebar, header, content-block)

## Validation

-   Toutes les pages sont désormais homogènes, professionnelles, sans doublon, sans ParseError ni incohérence UX/UI.
-   Sidebar, header, content-block strictement identiques à la maquette validée.
-   Documentation et indexation prêtes pour retrieval et audit.

## [2025-06] Correction finale erreurs factures (BadMethodCallException, accessibilité modal)

### 1. Contrôleur ESBTPComptabiliteController

-   Ajout :
    -   `createFacture()` : affiche le formulaire moderne de création de facture
    -   `showFacture($id)` : affiche le détail d'une facture
    -   `storeFacture(Request $request)` : stub pour enregistrement
-   Toutes les méthodes respectent la structure moderne et les routes déclarées.

### 2. Vue Blade `factures/create.blade.php`

-   Créée avec le layout `dashboard-layout.blade.php`
-   Formulaire conforme (CSRF, erreurs, sidebar homogène, UX pro)

### 3. Correction JS modal fournisseur (dépenses/create)

-   Suppression de tout `removeAttribute('aria-hidden')` ou manipulation manuelle d'aria-hidden
-   Focus input uniquement dans `shown.bs.modal`
-   Plus d’erreur d’accessibilité ni de blocage du formulaire

### 4. Indexation

-   [x] Contrôleur : `app/Http/Controllers/ESBTPComptabiliteController.php`
-   [x] Vue : `resources/views/esbtp/comptabilite/factures/create.blade.php`
-   [x] JS/Blade : `resources/views/esbtp/comptabilite/depenses/create.blade.php`

---

**Fix validé, conforme @PROMPT.txt, mémoire projet à jour.**
