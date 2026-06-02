# Rule: Universal Bootstrap Dropdowns KLASSCI — z-index 99999 + auto-flip + escape overflow

## Quand s'active

Cette rule s'active automatiquement quand tu :
- Modifies `resources/views/layouts/app.blade.php` (toucher au pattern global est dangereux)
- Ajoutes un nouveau `<div class="dropdown">` ou `[data-bs-toggle="dropdown"]` n'importe où dans KLASSCI
- Ajoutes/modifies du CSS sur un dropdown spécifique (`.xxx-dropdown-menu`, `.dropdown-menu` dans une page)
- Vois un dropdown coupé / clippé / qui ouvre vers le bas alors qu'il devrait flip up
- L'utilisateur dit « le dropdown est coupé », « overflow hidden », « dropdown ne s'ouvre pas correctement », « z-index »
- Travailles sur une page premium avec un `<div class="dropdown">` à l'intérieur d'un hero gradient, card, modal, table

## Pourquoi cette rule existe

**Incident fondateur** : Marcel a signalé en juin 2026 :
- `/esbtp/personnel/unified` : dropdown « Nouveau Personnel » clippé par section suivante + KPI « 205 » passait au-dessus du menu
- `/esbtp/matieres` : kebab 3-points dernière row tronqué bas viewport
- `/esbtp/etudiants` : popups multiples mal positionnés

Le pattern universel a été appliqué une fois pour toutes au layout global au lieu de patcher chaque page.

Marcel : « je veux que ce soit universel dans KLASSCI en ne cassant pas les autres fonctionnalités sur les autres pages aussi »

## Le pattern universel — déjà en place

**Source canonique** : `resources/views/layouts/app.blade.php` (CSS dans `<head>` + JS avant `bootstrap.bundle.min.js`).

**3 couches qui agissent ensemble** :
1. CSS `position: fixed !important; z-index: 99999 !important; background: #fff !important` — couche défensive
2. JS injection `data-bs-strategy="fixed"` + auto-flip dropup — placement intelligent
3. **Teleport-to-body à l'ouverture** — couche définitive qui extrait le menu de tout stacking context parent

### 0. Teleport-to-body (couche définitive, juin 2026)

CSS pur ne suffit pas quand Popper.js applique un `transform: translate(...)` inline sur le menu (ce qu'il fait toujours en mode `data-bs-display="dynamic"`). Le `transform` sur le menu crée un stacking context qui interagit avec les siblings positionnés du parent, malgré `position: fixed; z-index: 99999`.

**La solution canonique** : à `show.bs.dropdown`, détacher le menu de son parent et l'appendChild au `<body>`. À `hidden.bs.dropdown`, le ré-attacher à son emplacement d'origine via un placeholder commentaire.

```js
document.addEventListener('show.bs.dropdown', function(ev) {
    const trigger = ev.target;
    const menu = trigger.parentElement && trigger.parentElement.querySelector(':scope > .dropdown-menu');
    if (!menu || menu.dataset.klassciTeleported === '1') return;
    menu.dataset.klassciTeleported = '1';
    const placeholder = document.createComment('dropdown-menu-placeholder');
    menu.parentElement.insertBefore(placeholder, menu);
    menu._klassciPlaceholder = placeholder;
    document.body.appendChild(menu);
});
document.addEventListener('hidden.bs.dropdown', function(ev) {
    document.querySelectorAll('body > .dropdown-menu[data-klassci-teleported="1"]').forEach(function(menu) {
        if (menu._klassciPlaceholder && menu._klassciPlaceholder.parentNode) {
            menu._klassciPlaceholder.parentNode.insertBefore(menu, menu._klassciPlaceholder);
            menu._klassciPlaceholder.remove();
            menu._klassciPlaceholder = null;
            delete menu.dataset.klassciTeleported;
        }
    });
});
```

**Effets de bord à connaître** :
- Le menu est dans `<body>` quand ouvert — `closest('.dropdown')` depuis le menu retournera null. Si du code legacy dépend de cette navigation, le casser ou utiliser `getRootNode()`.
- Les sélecteurs CSS scopés au parent (ex: `.pu-hero .dropdown-menu`) **ne s'appliquent plus quand le menu est ouvert**. Utiliser des classes propres au menu ou des sélecteurs globaux.
- `aria-labelledby` reste valide car c'est un ID reference, pas un parent-child.

### 1. CSS global (z-index 99999 + force position fixed)

```css
/* Force z-index très haut pour TOUS les dropdown-menu ouverts. Sélecteurs multiples
   pour battre la spécificité des règles inline Bootstrap (z-index: 1100). */
.dropdown-menu.show,
.dropdown-menu[data-bs-popper],
html body .dropdown-menu.show,
html body .dropdown-menu[data-bs-popper] {
    z-index: 99999 !important;
}
/* Force position:fixed pour vraiment échapper aux parents overflow:hidden. */
html body .dropdown-menu.show[data-bs-popper],
html body .dropdown-menu[data-bs-popper] {
    position: fixed !important;
}
/* Quand un dropdown est ouvert, neutralise les transform/transition concurrents
   sur les rows hover qui créeraient un nouveau stacking context au-dessus du dropdown. */
body:has(.dropdown-menu.show) tr:hover,
body:has(.dropdown-menu.show) .card:hover,
body:has(.dropdown-menu.show) .stat-card:hover,
body:has(.dropdown-menu.show) .kpi-card:hover {
    transform: none !important;
}
```

### 2. JS injection des data-bs-* AVANT bootstrap.bundle

```js
(function() {
    function applyDropdownDefaults(scope) {
        scope.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function(trigger) {
            if (trigger.dataset.klassciDropdownInit === '1') return;
            trigger.dataset.klassciDropdownInit = '1';
            if (!trigger.hasAttribute('data-bs-strategy')) trigger.setAttribute('data-bs-strategy', 'fixed');
            if (!trigger.hasAttribute('data-bs-boundary')) trigger.setAttribute('data-bs-boundary', 'viewport');
            if (!trigger.hasAttribute('data-bs-display')) trigger.setAttribute('data-bs-display', 'dynamic');
            if (window.bootstrap && bootstrap.Dropdown && bootstrap.Dropdown.getInstance(trigger)) {
                try { bootstrap.Dropdown.getInstance(trigger).dispose(); } catch(e) {}
            }
        });
    }
    function detectAndFlipDropdown(trigger) {
        const dropdownParent = trigger.closest('.dropdown, .btn-group, .dropdown-center');
        if (!dropdownParent) return;
        const menu = dropdownParent.querySelector('.dropdown-menu');
        if (!menu) return;
        const estHeight = menu.scrollHeight || menu.offsetHeight || 200;
        const triggerRect = trigger.getBoundingClientRect();
        const spaceBelow = window.innerHeight - triggerRect.bottom;
        const spaceAbove = triggerRect.top;
        if (spaceBelow < estHeight + 20 && spaceAbove > estHeight + 20) {
            dropdownParent.classList.add('dropup');
        } else {
            dropdownParent.classList.remove('dropup');
        }
    }
    document.addEventListener('mousedown', function(ev) {
        const trigger = ev.target.closest('[data-bs-toggle="dropdown"]');
        if (trigger) detectAndFlipDropdown(trigger);
    }, true);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { applyDropdownDefaults(document); });
    } else {
        applyDropdownDefaults(document);
    }
    new MutationObserver(function(mutations) {
        mutations.forEach(function(m) {
            m.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) applyDropdownDefaults(node);
            });
        });
    }).observe(document.documentElement, { childList: true, subtree: true });
})();
```

**Ordre obligatoire** : ce script DOIT être placé AVANT `<script src=".../bootstrap.bundle.min.js"></script>` pour que les data-attrs soient présents quand Bootstrap auto-instancie les dropdowns.

## Comment écrire un dropdown sur une NOUVELLE page KLASSCI

Tu as **rien à faire de spécial**. Utilise le pattern Bootstrap natif et tout marche :

```blade
<div class="dropdown">
    <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
        Menu
    </button>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="#">Item 1</a></li>
        <li><a class="dropdown-item" href="#">Item 2</a></li>
    </ul>
</div>
```

Le layout global :
- Ajoute automatiquement `data-bs-strategy="fixed" + data-bs-boundary="viewport" + data-bs-display="dynamic"` au trigger
- Calcule l'espace dispo et ajoute `.dropup` au parent si le menu déborderait viewport
- Force z-index 99999 sur le menu ouvert (au-dessus de modals, sidebar, hero gradient)
- Force `position: fixed` pour échapper aux parents `overflow:hidden`

## Anti-patterns à BLOQUER en review

1. ❌ **Ajouter un `z-index: 10XX` local sur un `.dropdown-menu`** dans une page (ex: `.pu-hero .dropdown-menu { z-index: 1051 }`). Sabote la règle universelle. Si ton dropdown a un problème de z-index, debug pourquoi la règle globale n'applique pas (probablement spécificité). NE PAS ajouter de !important local.

2. ❌ **Hardcoder `data-bs-strategy="absolute"` ou `data-bs-display="static"`** sur un trigger. Tu casses l'auto-flip.

3. ❌ **Ajouter `overflow: hidden` à un parent direct d'un dropdown** (ex: hero card). Combiné avec `position: fixed` du menu c'est OK mais reste fragile. Préfère :
   - Soit retirer `overflow:hidden`
   - Soit isoler les décorations dans un wrapper enfant clippé (`.pu-hero-deco` pattern, voir rule `css-stacking-pitfalls.md` variante critique)

4. ❌ **Modifier le script inline de `layouts/app.blade.php`** sans comprendre la chaîne :
   - data-attrs sur le trigger AVANT bootstrap auto-init
   - mousedown capture-phase pour détecter espace
   - MutationObserver pour les dropdowns dynamiques (Alpine, AJAX)

5. ❌ **Désactiver `data-bs-toggle="dropdown"` au profit d'un Alpine custom** sans raison forte. Bootstrap dropdown gère a11y (keyboard navigation, aria, focus trap). Reste sur Bootstrap sauf si besoin métier spécifique.

6. ❌ **Réintroduire un `transform: translateY(-1px)` sur hover** d'une `tr` ou `card` qui contient un dropdown. Crée un stacking context concurrent. La règle CSS globale neutralise `transform` quand un dropdown est ouvert mais c'est un patch, pas une solution. Préférer ne pas avoir de transform sur hover de containers de dropdowns.

7. ❌ **Tester un nouveau dropdown uniquement sur desktop pleine page**. Toujours scroll au bas du viewport et vérifier que le menu :
   - Ne déborde pas du viewport (auto-flip up devrait kicker)
   - Reste au-dessus des autres éléments (z-index)
   - N'est pas clippé par un parent overflow

## Piège fondamental : z-index ne suffit pas — il faut `position: fixed`

**Le piège qui m'a couté plusieurs itérations** : un dropdown-menu avec `position: absolute`
(par défaut Bootstrap/Popper) reste **prisonnier du stacking context du parent positionné**.

Exemple concret KLASSCI : `/esbtp/personnel/unified` :
```
.pu-hero (position:relative → stacking context root)
├── .pu-hero-top (z-index:1)
│   └── .pu-hero-actions
│       └── .dropdown
│           └── ul.dropdown-menu (z-index:99999, position:absolute)
└── .pu-hero-kpis (z-index:1)
    └── .pu-hero-kpi-value "205" ← APPARAÎT PAR-DESSUS LE MENU
```

Le z-index 99999 du menu est **INTERNE au scope `.pu-hero-top` (z:1)**. Le sibling
`.pu-hero-kpis` est dans le même scope au même z-index 1. Par ordre DOM, les KPIs
paintent APRÈS → visuellement par-dessus le menu.

**Le seul vrai fix** : `position: fixed` qui extrait le menu du stacking context parent
et le place au niveau viewport. Là, z-index 99999 devient vraiment compétitif au plus
haut niveau, au-dessus de tous les parents.

```css
/* CORRECT — utilise .show (qui est TOUJOURS sur menu ouvert) */
html body .dropdown-menu.show {
    position: fixed !important;
    z-index: 99999 !important;
}

/* INSUFFISANT — [data-bs-popper] n'est pas toujours présent */
html body .dropdown-menu[data-bs-popper] {
    position: fixed !important;
}
```

**Diagnostic dev-browser** pour confirmer si un menu est prisonnier de son parent :
```js
document.elementsFromPoint(cx, cy)[0]  // → si c'est un élément AUTRE que le menu, le menu est masqué visuellement
```

Si l'élément TOP au point central du menu n'est pas le menu lui-même, c'est la preuve
que le menu est dans un stacking context perdant.

## Piège annexe : icônes FontAwesome dans les dropdown-items

KLASSCI charge FA 6.4.0 Free via CDN. Certains noms d'icônes FA5 sont absents du
subset Free :

- ❌ `fa-chalkboard-teacher` (FA5, supprimé en FA6)
- ❌ `fa-chalkboard-user` (FA6 mais content vide en 6.4.0 Free CDN)

Symptôme : l'icône apparaît comme un caractère glitchy ou un carré. Le diagnostic
dev-browser le confirme :
```js
window.getComputedStyle(icon, ':before').content  // → "" (vide = pas dans la font)
```

Icônes garantes Free 6.4.0 pour les personas KLASSCI :
- Enseignant : `fa-user-graduate` ou `fa-user-tie`
- Coordinateur : `fa-user-tie` ou `fa-users-cog`
- Secrétaire : `fa-user-shield`
- Comptable : `fa-calculator` ou `fa-coins`
- Caissier : `fa-cash-register` ou `fa-money-bill-wave`

Toujours tester une nouvelle icône via dev-browser ou Chrome devtools sur la page,
pas seulement faire confiance au nom dans la documentation FA.

## Sites historiques validés par cette rule

- `/esbtp/personnel/unified` : « Nouveau Personnel » dropdown (z-index 1051 local retiré, règle globale prend le relais)
- `/esbtp/matieres` : kebab 3-points table row (auto-flip + escape table-responsive overflow)
- `/esbtp/etudiants` : kebab table row + modal Bootstrap interactions
- Sidebar nav user menu (top-right account dropdown)
- Toute page premium avec hero gradient + dropdown dedans

## Voir aussi

- `.claude/rules/css-stacking-pitfalls.md` — pattern parent `overflow:hidden` + wrapper deco
- `.claude/rules/premium-redesign.md` — hero pattern avec dropdown dedans
- Mémoire `feedback_dropdown_stacking_card_hover.md` — historique des hotfixes per-page
- Bootstrap 5.3 docs : https://getbootstrap.com/docs/5.3/components/dropdowns/
- Source code : `resources/views/layouts/app.blade.php` (chercher « Règle universelle KLASSCI »)
