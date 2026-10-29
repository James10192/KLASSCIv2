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

## ⚠ Variante critique — Hero premium contenant un `<x-export-modal>` ou dropdown

Pattern KLASSCI fréquent : la page commence par un hero gradient bleu (`.xx-hero` namespace) avec à droite un bouton `<x-export-modal>` qui ouvre un dropdown vers le bas. Ce dropdown DOIT pouvoir déborder sous le hero.

### Référence canonique : `recouvrement/index.blade.php` `.re-hero`

```css
.re-hero {
    background: linear-gradient(135deg, ...);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.75rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
    /* AUCUN overflow:hidden, AUCUN position:relative, AUCUN ::before/::after */
}
```

→ Le dropdown export sort librement du hero, aucun stacking context concurrent. **Ce pattern marche, à utiliser en référence.**

### Anti-pattern observé (incident analytics 9 mai 2026)

```css
/* ❌ MAUVAIS — c'est ce qui était sur .an-hero */
.an-hero {
    position: relative;       /* ← crée stacking context */
    overflow: hidden;         /* ← clippe le dropdown qui dépasse */
}
.an-hero::before {            /* ← décoration radiale qui */
    position: absolute;       /*   nécessite overflow:hidden parent */
    top: -120px; right: -80px;
    /* ... */
}
.an-kpi:hover {
    transform: translateY(-1px);   /* ← stacking context concurrent
                                       qui passe AU-DESSUS du dropdown */
}
```

Symptômes : dropdown clippé en bas du hero, KPIs hovered passent au-dessus du menu, items inférieurs du dropdown non cliquables.

### Solution canonique pour hero + dropdown

```css
/* 1. Hero minimaliste (matche .re-hero) */
.an-hero {
    background: linear-gradient(...);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.75rem;
    /* PAS d'overflow:hidden, PAS de position:relative */
}

/* 2. Si décorations radiales souhaitées, dans un wrapper enfant clippé */
.an-hero {
    position: relative;       /* OK car wrapper deco contient lui-même overflow:hidden */
}
.an-hero-deco {
    position: absolute; inset: 0;
    border-radius: 18px;
    overflow: hidden;          /* ← clippe SEULEMENT les decorations, pas le dropdown qui est ailleurs */
    pointer-events: none;
    z-index: 0;
}
.an-hero > *:not(.an-hero-deco) { position: relative; z-index: 1; }
/* HTML : <div class="an-hero"><div class="an-hero-deco"><span class="deco-1"></span><span class="deco-2"></span></div>...content...</div> */

/* 3. Désactive le transform sur KPIs hovered tant qu'un dropdown export est ouvert */
body:has(.export-menu:not([style*="display: none"])) .an-kpi:hover {
    transform: none;
}

/* 4. .export-menu z-index: 1100 (déjà fait dans le composant global) */
```

### Anti-patterns spécifiques aux heros à BLOQUER en review

1. ❌ `overflow: hidden` sur un hero contenant `<x-export-modal>` ou tout dropdown qui s'ouvre vers le bas
2. ❌ Décorations `::before`/`::after` avec position absolute negative sans wrapper clippé séparé — préférer un `<div class="hero-deco">` enfant
3. ❌ `transform` sur `:hover` des KPIs internes au hero sans la garde `body:has(.export-menu...)` désactivant
4. ❌ Pages premium qui copient `.an-hero` ancien (avec overflow:hidden + ::before/::after) au lieu de `.re-hero` (minimaliste)

## Voir aussi

- Mémoire projet : `feedback_dropdown_stacking_card_hover.md` (PR #310 #312 #313 — recette complète)
- Mémoire projet : `modal-backdrop-stacking-fix.md` (modal × transform parent)
- Mémoire projet : `select2-modal-pattern.md` (Select2 × Bootstrap modal)
- MDN : [The stacking context](https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_positioning/Understanding_z-index/Stacking_context)
