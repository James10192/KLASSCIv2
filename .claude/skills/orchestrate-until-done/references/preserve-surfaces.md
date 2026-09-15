# Préserver les surfaces (desktop / mobile / print)

## Le défaut

Sous pression de tokens ou de « refactor propre », le modèle :

- réécrit `show.blade.php` et **oublie** `partials/_show-mobile.blade.php` ;
- fusionne deux layouts en un seul « responsive » qui casse le reçu caisse au téléphone ;
- retire des classes `d-md-none` / Alpine mobile parce que « dupliqué » ;
- régénère un PDF d'après le HTML desktop et perd le gabarit DomPDF.

Ça s'est produit sur les reçus paiements (actionbar mobile vs desktop). Le correctif a dû **revenir** ajouter le bouton mobile, pas l'inverse.

## Règle

Toute tâche qui touche `resources/views/` :

1. Glob le voisinage : `*mobile*`, `*print*`, `pdf/**`, `partials/_*-mobile*`.
2. Si deux fichiers existent pour le même écran, le lot les **met à jour tous les deux** ou justifie dans « Non fait » pourquoi l'un est hors sujet.
3. Interdit `delete_file` sur une variante mobile/print sans phrase utilisateur « supprime le mobile ».
4. Un diff qui diminue le nombre de `@include` mobile ou de media queries est suspect : relis avant de committer.
5. CSS « premium » desktop n'est pas une excuse pour retirer l'actionbar mobile.

## Refactoring

Extraire un partial **partagé** est autorisé si desktop et mobile l'incluent encore. Remplacer deux surfaces par une seule n'est autorisé que si tu as **vu** les deux viewports (capture ou markup explicite sm/md) et que le comportement est identique.

## Tests

S'il existe un test HTTP/Dusk de la vue, le lancer. S'il n'existe que du markup, grep le sélecteur mobile après édition (`actionbar`, `d-lg-none`, `_show-mobile`).
