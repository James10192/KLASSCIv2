# Rule: CSS stacking context pitfalls — dropdowns, modals, cards hover

## Quand s'active

Cette rule s'active quand tu travailles sur une UI avec :
- Liste de cards qui ont `transform`, `filter`, `backdrop-filter` ou `will-change` sur `:hover`
- Dropdowns / popovers / tooltips qui doivent sortir d'une card ou d'un container
- Modal Bootstrap dans une page avec animations CSS (`transform`, `animation`)
- Tout endroit où un menu Alpine.js (`x-data`, `x-show`) coexiste avec des hovers

## Le piège

CSS spec : **`transform`, `filter`, `perspective`, `will-change`, `opacity < 1`, `position: fixed/sticky` créent tous un nouveau stacking context.** Cela isole leur z-index par rapport au parent et écrase silencieusement les z-index des siblings, MÊME si ces siblings ont un z-index numérique plus élevé.

Conséquence concrète : une card hovered avec `transform: translateY(-1px)` peut rendre AU-DESSUS du dropdown ouvert d'une autre card, même si la card-avec-dropdown a `z-index: 1000`.

## Solution canonique (pattern KLASSCI)

Quand un dropdown sort d'une card dans une liste où les cards ont un hover avec transform, applique ces 4 règles ENSEMBLE :

### 1. Parent sans `overflow: hidden`

```css
/* MAUVAIS */
.card { overflow: hidden; border-radius: 14px; }

/* BON */
.card { /* pas d'overflow hidden */ border-radius: 14px; }
.card__stripe { border-radius: 14px 0 0 14px; }  /* radius reporté sur l'enfant si nécessaire */
```

### 2. Marquer la card avec dropdown ouvert via Alpine

```html
<div class="card__menu" x-data="{ open: false }" @click.outside="open = false"
     :class="{ 'card__menu--open': open }">
    <button @click="open = !open">...</button>
    <div class="card__menu-pop" x-show="open" x-transition.opacity>...</div>
</div>
```

### 3. CSS z-index + désactivation du transform concurrent

```css
.card { z-index: 1; }
.card:has(.card__menu--open) { z-index: 1000; }
.card__menu-pop { z-index: 1060; }

/* La clé : tant qu'un dropdown est ouvert n'importe où, désactive le transform
   sur toutes les cards hovered. Sans transform, plus de stacking context concurrent. */
body:has(.card__menu--open) .card:hover { transform: none; }
```

### 4. Vérifier le résultat

Hover la card du dessous quand le dropdown du dessus est ouvert. Le dropdown doit rester intégralement visible.

## Anti-patterns à bloquer en review

1. ❌ `overflow: hidden` sur une card qui contient un dropdown qui doit dépasser ses limites
2. ❌ Compter sur `z-index: 9999` seul sans neutraliser le stacking context du transform
3. ❌ `isolation: isolate` sur la card-avec-dropdown : ça crée un stacking context piège qui peut clipper le dropdown
4. ❌ Augmenter le z-index du dropdown-menu sans toucher la card parent
5. ❌ Append le dropdown au body via JS : fonctionne mais lourd, casse `@click.outside` Alpine
6. ❌ Modifier la spécificité du sélecteur sans comprendre que le problème est le stacking context, pas la spécificité

## Variantes du pattern

### Pour un menu Bootstrap dropdown (Popper.js auto-positionné)

Bootstrap utilise déjà `data-bs-toggle="dropdown"` avec Popper qui peut détacher le menu via `data-bs-strategy="fixed"`. Si tu utilises Bootstrap, préfère Popper plutôt que le pattern Alpine ci-dessus.

### Pour un modal Bootstrap dans une page avec animations

Voir mémoire `modal-backdrop-stacking-fix.md` : modal derrière backdrop quand parent a `transform/animation` → `appendTo: 'body'` via JS.

### Pour Select2 dans modal Bootstrap

Voir mémoire `select2-modal-pattern.md` : `dropdownParent: $('body')` + `z-index: 1075`.

## Voir aussi

- Mémoire projet : `feedback_dropdown_stacking_card_hover.md` (PR #310 #312 #313 — recette complète)
- Mémoire projet : `modal-backdrop-stacking-fix.md` (modal × transform parent)
- Mémoire projet : `select2-modal-pattern.md` (Select2 × Bootstrap modal)
- MDN : [The stacking context](https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_positioning/Understanding_z-index/Stacking_context)
