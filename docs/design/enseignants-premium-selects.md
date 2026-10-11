# Enseignants — migration des filtres vers les sélecteurs premium

## Constat de revue, 11 octobre 2026
Sur `/esbtp/enseignants`, les six sélecteurs « statut », « spécialisation », « document PDF », « année des bulletins », « classe » et « semestre » sont restés des `<select>` natifs visibles après la refonte de l'annuaire pédagogique. Cela enfreint `.claude/rules/premium-selects.md`.

## Solution retenue
- Réutiliser le composant Blade institutionnel `<x-au-select>`, **sans nouvelle librairie, CSS de dropdown ou composant parallèle**.
- Chaque picker conserve son `name`, une `value` issue de la requête et le `<select>` natif **caché** de `x-au-select` comme source de vérité.
- La lecture des filtres par `new FormData(form)` du composant `x-export-modal` doit rester inchangée et refléter immédiatement la dernière sélection, sans rechargement pour les choix d'export.
- Liste des spécialités et classes : menu recherchable dès que le nombre d'options dépasse huit.
- Année des bulletins et document PDF : conserver une valeur sélectionnée même sans option « Toutes », en utilisant `placeholderIsFirstOption=false`.
- Responsivité : wrapper `.te-filter-group` flex colonne, `.au-select` largeur 100 %, pas de `overflow:hidden` sur le conteneur des filtres.
- Tests statiques du template : aucun `<select>` natif visible, six composants premium et leurs champs GET persistants.
- Les contrôles CI ne remplacent pas la capture d'écran réelle sur `presentation` avant propagation vers les écoles.

Aucune migration ou modification de données métier.
