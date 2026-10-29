# Correction du débordement du chart "Évolution Financière Mensuelle" (ESBTP)

## Problème

Le chart "Évolution Financière Mensuelle" sur la page `dashboard-avance.blade.php` s’allongeait indéfiniment, provoquant un allongement de la page et un ralentissement de l’interface, même avec la limitation à 12 points côté contrôleur.

## Analyse

-   Les méthodes du contrôleur limitaient déjà à 12 points (mois).
-   Le parent du `<canvas>` (div.chart-container) n’avait aucune contrainte CSS de largeur ou d’overflow.
-   Si les labels sont longs ou la fenêtre étroite, Chart.js force le parent à s’étirer horizontalement, ce qui allonge la page.

## Solution appliquée

Ajout d’une règle CSS robuste dans `public/css/dashboard-moderne.css` :

```css
.chart-container {
    max-width: 100%;
    overflow-x: auto;
    min-width: 0;
}
```

-   Cette règle force le chart à rester dans la largeur de son parent et à scroller horizontalement si besoin, sans allonger la page.
-   Aucun changement du contrôleur ou du Blade n’a été nécessaire.

## Validation

-   Testé en session authentifiée : la page dashboard-avance s’affiche correctement, le chart ne déborde plus, même avec des labels longs.
-   Aucun ParseError, InvalidArgumentException ou effet de bord constaté.

---

_Correction indexée et documentée pour traçabilité et audit (2025)._
