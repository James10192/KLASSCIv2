# Plan: Amélioration PDF Suivi Paiements par Catégorie

## Ce que j'ai compris

### Problème 1 : Pretty-print au refresh
La route `/esbtp/paiements/suivi-categories/refresh` (`ESBTPPaiementController@suiviCategoriesRefresh`) retourne du **JSON** (ligne 1365). Quand on accède directement à cette URL dans le navigateur (refresh), le navigateur affiche le JSON brut en pretty-print. C'est un endpoint AJAX, pas une page HTML.

### Problème 2 : Le PDF prend toute la feuille
Le PDF actuel (`resources/views/esbtp/paiements/pdf/suivi-liste-etudiants.blade.php`) a des marges `@page` de 18mm/15mm, mais le contenu (tables) s'étend sur 100% de la largeur disponible sans aucune marge intérieure ou containeur limitant. Cela donne un effet "bord-à-bord" peu professionnel.

### Problème 3 : Design pas premium
Le template actuel est fonctionnel mais basique. Le header est un simple bandeau plat, les KPI sont des boîtes simples, la table manque de raffinement. Il faut un design premium inspiré de `liste-complete-pdf.blade.php`.

### Problème 4 : Couleurs et school info
Le template utilise déjà `$pdfSettings` et `$schoolInfo` depuis le controller, mais certaines couleurs sont hardcodées et le design du header pourrait mieux mettre en valeur les infos de l'établissement.

---

## Ce que je vais faire

### 1. Fix Pretty-print (route refresh)
**Fichier** : `app/Http/Controllers/ESBTPPaiementController.php` (~ligne 1271)

Ajouter une détection : si la requête n'est PAS AJAX (`!$request->ajax() && !$request->wantsJson()`), rediriger vers la page principale `suiviCategories` avec les mêmes paramètres de filtre.

### 2. Refonte complète du template PDF
**Fichier** : `resources/views/esbtp/paiements/pdf/suivi-liste-etudiants.blade.php`

Redesign complet inspiré de `liste-complete-pdf.blade.php` avec :

**a) Marges et containeur — LA VRAIE CAUSE du "content prend toute la page"**
- `@page` margins augmentées à 20mm top/bottom, 15mm left/right (standards ISO pour A4 professionnel — actuellement 18mm/15mm ce qui est trop serré)
- Un `<div class="container">` avec `padding: 10px` pour créer de l'espace intérieur supplémentaire
- Le vrai problème : la combinaison de marges `@page` insuffisantes + absence de padding intérieur + tables pleine largeur donne l'impression "bord-à-bord"

**b) Header premium (inspiré liste-complete-pdf)**
- Layout 2 colonnes : Logo à gauche (18%) | Infos école + titre document à droite (82%)
- Fond `header_bg_color` dynamique depuis settings
- Nom établissement en gras 15px
- Coordonnées (adresse | tél | email) en 8.5px avec opacité 0.85
- Séparateur horizontal semi-transparent
- Titre document "SUIVI DES PAIEMENTS" en 12px bold
- Sous-infos : Catégorie, Statut, Date sur une ligne

**c) KPI Cards premium**
- 4 cellules sur fond `primary_color` (comme liste-complete-pdf)
- Label uppercase 7.5px en blanc opacité 0.8
- Valeur en gros (18px pour chiffres, 11px pour montants) blanc bold
- Sous-label explicatif 7px blanc opacité 0.65

**d) Table des étudiants**
- Header `primary_color` avec texte blanc, uppercase, letter-spacing
- Alternance de couleurs (zebra striping) appliquée sur `td` (pas `tr` — bug DomPDF)
- Numéros dans des badges circulaires `primary_color`
- Montants colorés (vert pour payé, rouge pour solde)
- Footer avec totaux sur fond bleu clair `#eff6ff`
- Police 9px pour le contenu, 8.5px pour le header

**e) Footer premium**
- Section 2 colonnes : Résumé filtres à gauche, Infos document à droite
- Cards avec fond `#f8f9fa` et bordure `#e5e7eb`
- Ligne de génération centrée en bas avec border-top

**f) Couleurs dynamiques depuis settings**
- Toutes les couleurs lues depuis `$pdfSettings` (primary, header_bg, header_text, text_color)
- `@include('pdf.partials.theme')` conservé
- `$schoolInfo` bien récupéré pour : nom, adresse, téléphone, email, logo

### 3. Ce qui NE changera PAS
- Le controller `exportStudentsPdf()` : les données passées restent identiques
- Les routes PDF : aucun changement
- Les variables Blade attendues : `$etudiants`, `$category`, `$statutLabel`, `$schoolInfo`, `$pdfSettings`, `$stats`

---

## Risques & points d'attention

1. **DomPDF limitations** : Pas de flexbox/grid, tout en `<table>`. Le template actuel utilise déjà des tables — on reste sur cette approche.
2. **Couleurs sur `<tr>` vs `<td>`** : DomPDF ne supporte pas `background-color` sur `<tr>`. On applique toujours sur `<td>`.
3. **Logo base64** : Le template actuel gère déjà la conversion — on conserve la même logique.
4. **Pretty-print fix** : La redirection ne casse pas le fonctionnement AJAX car les appels AJAX passent toujours par `XMLHttpRequest` (détecté par `$request->ajax()`).
5. **Aucune migration DB** ni changement de route PDF nécessaire.
