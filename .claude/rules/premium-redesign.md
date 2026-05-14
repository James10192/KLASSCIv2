# Premium Redesign — Conventions KLASSCI

## Quand s'active

Cette rule s'active quand on redesign une page Blade (vue) avec du CSS premium.

## Design System KLASSCI

**Palette monochrome bleu** — une seule couleur, jamais multicolore :
```css
--primary:    #0453cb     /* Bleu KLASSCI */
--primary-d:  #033a8e     /* Hover/dark */
--secondary:  #5e91de     /* Gradient end */
--accent:     #3b7ddb     /* Intermédiaire */
--dark:       #0f172a     /* Texte principal */
--text:       #1e293b     /* Texte body */
--muted:      #64748b     /* Labels, hints */
--surface:    #f8fafc     /* Fond léger */
```

### Couleurs sémantiques autorisées (exception à la règle monochrome)

Les couleurs sémantiques **success/warning/danger** sont autorisées **quand elles portent du sens fonctionnel** — ne JAMAIS les utiliser comme simple décoration ou pour différencier des catégories neutres.

```css
--success: #10b981    /* Succès : paiement validé, action réussie, statut OK */
--warning: #f59e0b    /* Alerte : paiement en attente, délai approchant, à surveiller */
--danger:  #dc2626    /* Danger : suppression, erreur critique, paiement rejeté, delete confirm */
```

**Exemples d'usage correct (sens fonctionnel) :**
- Badge "Payé" → vert success, "En attente" → orange warning, "Annulé" → rouge danger
- Bouton "Supprimer" / "Archiver" → `btn-danger` (a11y + convention universelle)
- Alert "Paiement échoué" → alert-danger, "Paiement imminent" → alert-warning
- Modal header destructif ("Annuler inscription") → gradient rouge

**Exemples d'usage INCORRECT (décoration, différenciation neutre) :**
- Badges Types de séances CM/TD/TP → multicolore (bleu/vert/orange) → **FAUX** — utiliser monochrome tonal (opacity + icône + border-style)
- KPIs dashboard "Total étudiants" → couleur différente par KPI → **FAUX** — tous en bleu
- Boutons d'action secondaires colorés → **FAUX** — ghost/outline bleu

**Principe** : si la couleur porte une information sémantique que l'œil doit capter en <1 seconde (statut, niveau d'alerte, action destructive), elle est autorisée. Sinon, monochrome bleu.

**INTERDIT** : purple `#7c3aed`, multicolore décoratif, AI slop (dark mode, gradient orbs, bento grids, glassmorphism, animated counters), `--esbtp-green` (variable morte), couleurs arbitraires pour différencier des catégories neutres.

## Modèle de référence : Planning Général (`planning-header`)

**TOUJOURS copier le pattern du composant `planning-header.blade.php` (`ph-*`) pour les headers de page.** Ne pas inventer un nouveau design de header à chaque page.

### Structure du hero (2 rangées)
```
Rangée 1 (sm-hero-top / ph-hero-top) :
  GAUCHE : icône carré 52px + titre h1 + sous-titre
  DROITE : boutons d'action / filtres

Rangée 2 (stats-grid / ph-kpis) :
  KPIs en cards semi-transparentes, pleine largeur
```

### CSS du hero (copier tel quel, adapter le préfixe)
```css
.XX-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
}

.XX-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

.XX-hero-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.XX-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}

/* Titre : BLANC sur fond bleu, jamais bleu sur bleu */
.XX-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.XX-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }
```

### KPIs dans le hero (rangée 2)
```css
.XX-kpis {
    display: flex;
    gap: .75rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}

.XX-kpi {
    flex: 1; min-width: 140px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px;
    padding: .9rem 1rem;
    display: flex; align-items: center; gap: .75rem;
}

.XX-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; }
.XX-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }
```

### Boutons dans le hero
```css
/* Glass (fond bleu) */
.XX-btn--glass {
    background: rgba(255,255,255,.15);
    color: #fff;
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 10px;
    padding: .5rem 1rem;
    font-size: .82rem;
    font-weight: 600;
}

/* Action principale (blanc) */
.XX-btn--white {
    background: #fff;
    color: #0453cb;
    border-color: transparent;
}
```

## Pages SANS hero (formulaires)

Les pages de formulaire (create, edit) utilisent le **`dashboard-header` standard** de `dashboard-moderne.css` — pas de hero gradient. Le hero est réservé aux pages de listing/dashboard.

```blade
<div class="dashboard-header">
    <div class="header-left">
        <h1><i class="fas fa-plus-circle me-2"></i>Titre</h1>
        <p class="header-subtitle">Sous-titre</p>
    </div>
    <div class="header-actions">
        <a href="..." class="btn-acasi secondary"><i class="fas fa-arrow-left"></i>Retour</a>
    </div>
</div>
```

## Namespace CSS (OBLIGATOIRE)

Chaque page a son propre namespace pour éviter les conflits avec les classes globales :

| Page | Namespace | Exemple |
|------|-----------|---------|
| inscriptions.show | `is-*` | `is-hero`, `is-card`, `is-info-grid` |
| inscriptions.edit | `ie-*` | `ie-hero`, `ie-card` |
| matieres.create | `mc-*` | `mc-hero`, `mc-card` |
| relances.index | `rl-*` | `rl-hero`, `rl-kpi`, `rl-filters` |
| frais.configure | `fc-*` | `fc-hero`, `fc-card`, `fc-grid` |
| notes.index | `nm-*` | `nm-hero`, `nm-card` |
| situation financière | `sf-*` | `sf-hero` |
| student-messages | `sm-*` | `sm-hero-top`, `sm-hero-icon` |
| planning-header | `ph-*` | `ph-hero`, `ph-kpi`, `ph-tab` (composant réutilisable) |
| custom-roles (Lot 8) | `cr-*` | `cr-modal`, `cr-picker`, `cr-perm`, `cr-role-card`, `cr-section-bar` |

Pour une nouvelle page : choisir un préfixe 2-3 lettres unique, documenter ici.

## Card premium (sous le hero)
```css
.XX-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
}
.XX-card:hover {
    box-shadow: 0 8px 30px rgba(4,83,203,.08), 0 2px 8px rgba(15,23,42,.04);
    transform: translateY(-2px);
}
```

## Section header (icône + titre)
```css
.XX-section-header {
    display: flex; align-items: center; gap: .75rem;
}
.XX-section-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .95rem;
}
```

## Ombres multicouches (3 niveaux)
```css
--shadow-sm: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
--shadow-md: 0 4px 16px rgba(4,83,203,.06), 0 1px 3px rgba(15,23,42,.04);
--shadow-lg: 0 8px 30px rgba(4,83,203,.08), 0 2px 8px rgba(15,23,42,.04);
```

## Tree hiérarchique IDE-style (Domaine → Mention → Parcours, parent → enfant, etc.)

**Quand l'utiliser** : pour afficher une hiérarchie 2-N niveaux dans un panneau (page show d'une entité, fiche détail, modal info). Référence canonique : `classes.show` section Informations générales (`/esbtp/classes/{id}`) qui affiche Domaine → Mention → Parcours d'une classe LMD.

**Le pattern** : indentation progressive + L-connectors VSCode-style. Chaque enfant est décalé à droite de son parent et un L (`└──`) connecte le centre horizontal de l'icône parent au centre vertical de l'icône enfant.

### Anti-pattern (à éviter)

```css
/* MAUVAIS — ligne verticale globale + 3 nodes alignés à gauche */
.tree { padding-left: 1.65rem; }
.tree::before { /* ligne verticale UNIQUE qui traverse */ }
.tree-node::after { /* tiret horizontal de 0.85rem */ }
```
→ Donne 3 nodes empilés avec une ligne unique à gauche. Ce n'est PAS un tree IDE.

### Pattern correct (utilisé sur classes.show)

```blade
<div class="xx-tree">
    <div class="xx-tree-node xx-tree-node--lvl0">
        <div class="xx-tree-icon"><i class="fas fa-folder-open"></i></div>
        <div class="xx-tree-body">
            <div class="xx-tree-label">Domaine</div>
            <div class="xx-tree-name">Sciences Juridiques</div>
            <span class="xx-tree-code">DROIT</span>
        </div>
    </div>
    <div class="xx-tree-node xx-tree-node--lvl1">
        <div class="xx-tree-icon"><i class="fas fa-graduation-cap"></i></div>
        <div class="xx-tree-body">...</div>
    </div>
    <div class="xx-tree-node xx-tree-node--lvl2">
        <div class="xx-tree-icon"><i class="fas fa-route"></i></div>
        <div class="xx-tree-body">...</div>
    </div>
</div>
```

```css
.xx-tree {
    background: linear-gradient(135deg, rgba(4,83,203,.04), rgba(59,125,219,.06));
    border: 1px solid rgba(4,83,203,.18);
    border-radius: 12px;
    padding: .85rem;
}
.xx-tree-node {
    position: relative;
    display: flex; align-items: center;
    gap: .7rem;
    padding: 0 .65rem;
    border-radius: 7px;
    height: 44px;                 /* HEIGHT FIXE OBLIGATOIRE pour calculs L précis */
    transition: background .15s;
}
.xx-tree-node + .xx-tree-node { margin-top: .25rem; }
.xx-tree-node:hover { background: rgba(4,83,203,.06); }

/* INDENTATION TREE — chaque enfant décalé à droite de son parent */
.xx-tree-node--lvl1 { margin-left: 1.6rem; }
.xx-tree-node--lvl2 { margin-left: 3.2rem; }

/* L-CONNECTOR : trait vertical PILE sous le centre horizontal de l'icône parent,
   tournant au centre vertical de l'icône enfant.
   Calcul : centre icône parent = padding-left .65rem + 32px/2 (mi-icône) = 1.65rem
   du parent left. Le node enfant a margin-left 1.6rem → centre parent en coord
   enfant = 1.65 - 1.6 = .05rem ≈ left:0 (offset ~0.8px, invisible). */
.xx-tree-node--lvl1::before,
.xx-tree-node--lvl2::before {
    content: '';
    position: absolute;
    left: 0;                      /* trait vertical aligné sur centre icône parent */
    top: calc(-50% - .25rem);     /* part du milieu vertical du parent (height 44px + margin .25rem) */
    bottom: calc(50% - 1px);      /* arrive au milieu vertical de l'icône courante */
    width: .65rem;                /* segment horizontal jusqu'au bord gauche icône enfant */
    border-left: 2px solid rgba(4,83,203,.42);
    border-bottom: 2px solid rgba(4,83,203,.42);
    border-bottom-left-radius: 7px;
    pointer-events: none;
}

.xx-tree-icon {
    width: 32px; height: 32px;     /* TAILLE FIXE 32px obligatoire pour le calcul .65rem du L */
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .82rem; flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(4,83,203,.25);
    position: relative; z-index: 1;
}
/* Gradient progressif par niveau pour une hiérarchie visuelle */
.xx-tree-node--lvl0 .xx-tree-icon { background: linear-gradient(135deg, #033a8e, #0453cb); }
.xx-tree-node--lvl1 .xx-tree-icon { background: linear-gradient(135deg, #0453cb, #3b7ddb); }
.xx-tree-node--lvl2 .xx-tree-icon { background: linear-gradient(135deg, #3b7ddb, #5e91de); }

.xx-tree-body {
    flex: 1; min-width: 0;
    display: grid;
    grid-template-columns: 1fr auto;
    align-items: center;
    gap: .15rem .65rem;
}
.xx-tree-label { grid-column: 1; font-size: .62rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; }
.xx-tree-name { grid-column: 1; font-size: .92rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
.xx-tree-code {
    grid-column: 2; grid-row: 1 / span 2;
    align-self: center;
    font-size: .64rem; color: #0453cb;
    background: rgba(4,83,203,.08);
    padding: .15rem .5rem; border-radius: 5px;
    font-weight: 700; letter-spacing: .3px;
    font-family: 'Courier New', monospace;
    white-space: nowrap;
}
```

### Règles d'extension

- **Pour N niveaux** : ajouter `margin-left: N * 1.6rem` à chaque `--lvl{N}` et la même règle `::before` (le calcul `left:0` reste valide car chaque enfant a son parent à 1.6rem à gauche).
- **Pour des icônes plus grandes/petites** : ajuster `width/height` des icons ET le `width` du L horizontal proportionnellement (icon left edge = padding-left + 0, donc largeur L = padding-left).
- **Pour des indentations plus larges** (ex: tree profond) : changer `1.6rem` partout (margin-left ET formule). Le pattern reste valide car le L démarre toujours à `left:0` (= bord gauche enfant = centre parent moins .05rem).
- **Pour rendre le tree foldable** (Alpine x-show), wrapper chaque sous-branche dans `<div x-data="{ open: true }">` — voir Planning LMD pour un exemple de tree expandable.

### Anti-patterns à BLOQUER en review

1. ❌ Ligne verticale globale via `.tree::before` qui traverse tous les nodes (= pas un tree IDE)
2. ❌ Nodes empilés sans `margin-left` progressif (= pas d'indentation hiérarchique)
3. ❌ L-connector avec `left: -X` calculé approximativement (le `left: 0` exact est mathématiquement aligné sur le centre icône parent)
4. ❌ `height: auto` sur les nodes (les calculs `top/bottom: calc(-50%...)` ne marchent que si height est fixe)
5. ❌ Réinventer le pattern dans chaque page — copier le bloc CSS et changer juste le namespace (xx → cs/pl/etc.)

### Référence canonique

- `resources/views/esbtp/classes/show.blade.php` (namespace `cs-lmd-tree-*`) — implémentation de référence depuis commit `6d242434` (14 mai 2026)
- Calcul de l'alignement L-connector : commit message `6d242434` détaille la formule complète

## Sélecteurs / dropdowns premium

**JAMAIS de `<select>` natif visible dans une page premium.** Utiliser les composants Blade `<x-au-select>` (générique) ou `<x-au-user-picker>` (utilisateurs avec groupement par rôle), ou cloner ces composants pour un cas particulier (picker classes, matières, évaluations…). Détails complets, props, anti-patterns et checklist : voir [`.claude/rules/premium-selects.md`](premium-selects.md).

### Piège critique de superposition (dropdown coupé / texte au-dessus)

Quand un dropdown premium s'ouvre, il doit pouvoir **déborder visuellement** de sa card.

- Parent card d'un select ouvert : éviter `overflow: hidden`
- Éviter `transform` sur hover/focus des cards qui contiennent des dropdowns (crée un nouveau stacking context)
- Prévoir `position: relative` + `z-index` sur la card active (`:focus-within`) et sur le field du select
- Vérifier que le menu (`.au-select-menu` / Select2 dropdown) passe au-dessus des sections suivantes

Sans ça, on obtient le bug classique : menu tronqué, labels de la section d'en bas qui passent au-dessus, UX cassée.

## Règles absolues

1. **Copier le pattern planning-header** pour les headers de page — ne pas inventer
2. **Hero = pages listing/dashboard SEULEMENT** — pas sur les formulaires (create/edit)
3. **Titre BLANC sur hero bleu** — jamais bleu sur bleu
4. **Garder TOUS les éléments fonctionnels** — ne jamais supprimer un élément HTML qui a du JS attaché
5. **Ne jamais casser le JS** — conserver les IDs, data-attributes, class names référencés par JS
6. **CSS inline dans `<style>` block** avec `@push('styles')` — pas de fichier CSS séparé
7. **Mobile-first** — `@media` breakpoints pour 992px, 768px, 576px
8. **Spacing 8px grid** — `0.5rem`, `1rem`, `1.5rem`, `2rem`
9. **Transitions** : `all .2s ease` — jamais > .3s
10. **Border-radius** : 8-10px (petits), 12-14px (cards), 18px (hero)
11. **Sélecteurs** — voir [`premium-selects.md`](premium-selects.md). Jamais `<select>` natif visible.
12. **Dropdowns non tronqués** — aucune card contenant des select premium ne doit couper le menu (`overflow/stacking context` à contrôler explicitement).
