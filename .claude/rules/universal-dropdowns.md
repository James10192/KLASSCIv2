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
