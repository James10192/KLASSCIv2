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

## Hiérarchie collapse/expand parent → enfant (UE → ECUE, catégorie → items, etc.)

**Quand l'utiliser** : pour afficher une liste de N parents qui contiennent chacun M enfants, où l'utilisateur veut un scan rapide des parents + drill-down sélectif. Référence canonique : `classes.show` tab "Suivi des heures" LMD (`/esbtp/classes/{id}` namespace `sh-*`) qui groupe les ECUEs par UE avec collapse/expand, chip catégorie, et agrégats CM/TD/TP au niveau parent.

**Différence avec le tree IDE-style** : le tree est pour une hiérarchie LINÉAIRE 2-3 niveaux où chaque niveau a UNE seule entrée (Domaine → Mention → Parcours). Le collapse/expand est pour une liste de PLUSIEURS parents avec PLUSIEURS enfants chacun.

### Pattern Alpine — un seul `x-data` parent avec `openIds: []` array

**Anti-pattern à éviter** : `x-data="{ open: false }"` sur CHAQUE carte parent.

```blade
{{-- ❌ MAUVAIS — N instances Alpine, mobile lag, listeners multipliés --}}
@foreach ($parents as $idx => $parent)
    <div class="card" x-data="{ open: false }">
        <button @click="open = !open">...</button>
        <div x-show="open">...</div>
    </div>
@endforeach
```

**Pattern correct** : un seul `x-data` au container parent qui maintient un Array `openIds`.

```blade
{{-- ✅ BON — 1 instance Alpine, scaleable à N parents --}}
<div class="xx-list" x-data="{ openIds: [] }">
    <button @click="openIds = openIds.length === {{ count($parents) }} ? [] : Array.from({length: {{ count($parents) }}}, (_, i) => i)">
        <span x-show="openIds.length !== {{ count($parents) }}">Tout déplier</span>
        <span x-show="openIds.length === {{ count($parents) }}" x-cloak>Tout replier</span>
    </button>

    @foreach ($parents as $idx => $parent)
        <div class="xx-card" :class="openIds.includes({{ $idx }}) ? 'xx-card--open' : ''">
            <button @click="openIds.includes({{ $idx }}) ? openIds = openIds.filter(i => i !== {{ $idx }}) : openIds.push({{ $idx }})">
                <i class="fas fa-chevron-right xx-caret" :class="openIds.includes({{ $idx }}) ? 'xx-caret--open' : ''"></i>
                {{ $parent['name'] }}
            </button>
            <div class="xx-card-body" x-show="openIds.includes({{ $idx }})" x-cloak x-transition.opacity>
                {{-- enfants détail --}}
            </div>
        </div>
    @endforeach
</div>
```

### CSS du caret animé

```css
.xx-caret {
    color: #94a3b8; font-size: .75rem;
    width: 14px; text-align: center;
    transition: transform .2s ease;
}
.xx-caret--open {
    transform: rotate(90deg);
    color: #0453cb;
}
```

### Chip catégorie monochrome bleu (3 tones max)

Pour différencier les catégories visuellement SANS casser la palette monochrome (rule globale : pas de multicolore décoratif), grouper sémantiquement en 3 tones par opacity :

```blade
@php
    $toneClass = match ($parent['categorie']) {
        'important_A', 'important_B' => 'xx-chip--primary',   // catégories importantes
        'normal_A', 'normal_B'       => 'xx-chip--accent',    // catégories normales
        default                      => 'xx-chip--muted',     // catégories complémentaires
    };
@endphp
<span class="xx-chip {{ $toneClass }}">{{ $parent['categorie_label'] }}</span>
```

```css
.xx-chip { display: inline-flex; align-items: center; padding: .22rem .55rem; border-radius: 6px; font-size: .68rem; font-weight: 700; text-transform: uppercase; }
.xx-chip--primary { background: rgba(4,83,203,.10); color: #0453cb; border: 1px solid rgba(4,83,203,.25); }
.xx-chip--accent  { background: rgba(59,125,219,.10); color: #3b7ddb; border: 1px solid rgba(59,125,219,.25); }
.xx-chip--muted   { background: rgba(94,145,222,.08); color: #5e91de; border: 1px solid rgba(94,145,222,.20); }
.xx-chip--orphan  { background: rgba(245,158,11,.10); color: #b45309; border: 1px solid rgba(245,158,11,.25); } /* exception sémantique fallback */
```

### Agrégats parent — chips totaux dans le header

Pour qu'un coup d'œil sur la liste suffise sans déplier, le header parent affiche les agrégats clés sous forme de chips :

```blade
<div class="xx-card-totaux">
    <div class="xx-progress-wrap" title="{{ $pct }}% réalisé">
        <div class="xx-progress" style="width:{{ $pct }}%;background:{{ $progressColor }};"></div>
    </div>
    <div class="xx-totaux-row">
        <span class="xx-tot-chip"><i class="fas fa-chalkboard-user"></i>CM <strong>{{ $cm_r }}h</strong>/{{ $cm_p }}h</span>
        <span class="xx-tot-chip"><i class="fas fa-pen-ruler"></i>TD <strong>{{ $td_r }}h</strong>/{{ $td_p }}h</span>
        <span class="xx-tot-chip"><i class="fas fa-flask-vial"></i>TP <strong>{{ $tp_r }}h</strong>/{{ $tp_p }}h</span>
        <span class="xx-tot-chip xx-tot-chip--strong"><i class="fas fa-clock"></i>Total <strong>{{ $total_r }}h</strong>/{{ $total_p }}h</span>
    </div>
</div>
```

**Progress bar avec couleur sémantique** : `≥80% = success #10b981`, `≥40% = warning #f59e0b`, `>0% = primary #0453cb`, `0% = muted #94a3b8`. Ces couleurs sont autorisées car elles portent un sens fonctionnel (statut de réalisation), pas décoratif.

### Bouton "Tout déplier / Tout replier" bulk toggle

Top-right de la section, toggle l'array `openIds` :

```blade
<button @click="openIds = openIds.length === {{ $count }} ? [] : Array.from({length: {{ $count }}}, (_, i) => i)">
    <i class="fas fa-arrows-up-down"></i>
    <span x-show="openIds.length !== {{ $count }}">Tout déplier</span>
    <span x-show="openIds.length === {{ $count }}" x-cloak>Tout replier</span>
</button>
```

### Empty state gracieux

Si aucun parent à afficher, ne PAS afficher le container vide. Afficher un warn-box avec lien d'action :

```blade
@if ($parents->isEmpty())
    <div class="xx-warn">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            Aucun élément configuré pour ce scope. Configurez-le dans <a href="{{ route('xxx.config') }}">la page Configuration</a>.
        </div>
    </div>
@else
    {{-- liste --}}
@endif
```

### Bucket "Hors X" pour les enfants orphelins (FK nullable)

Si certains enfants n'ont pas de parent assigné (FK nullable), grouper dans un bucket spécial `Hors UE` / `Hors catégorie` avec chip `--orphan` pour signaler visuellement le cas.

```php
$rows = $children
    ->groupBy(fn ($row) => optional($row['matiere']->uniteEnseignement)->id ?? 0)
    ->map(function ($items, $parentId) {
        $isOrphan = $parentId === 0;
        return [
            'is_orphan' => $isOrphan,
            'name' => $isOrphan ? 'Hors UE' : $items->first()['matiere']->uniteEnseignement->name,
            // ...
        ];
    });
```

### Anti-patterns à BLOQUER en review

1. ❌ `x-data="{ open: false }"` sur chaque carte parent (N instances Alpine, mobile lag, premortem incident)
2. ❌ Persistance localStorage du collapse state — état session-only suffit, cohérent avec rest of codebase
3. ❌ Couleurs multicolores pour différencier les catégories — utiliser 3 tones bleu par opacity (monochrome strict)
4. ❌ Pas de progress bar de pourcentage dans le header parent — l'utilisateur doit pouvoir scanner sans déplier
5. ❌ Ignorer les enfants orphelins (FK nullable) — toujours grouper dans bucket `Hors X` avec chip warning
6. ❌ Vanilla JS pour le toggle quand le reste du codebase est en Alpine — dette technique gratuite
7. ❌ État initial expanded sur N parents — préférer collapsed par défaut + bouton "Tout déplier" si vue d'ensemble souhaitée

### Référence canonique

- `resources/views/esbtp/classes/partials/_suivi_heures_lmd.blade.php` (orchestrateur, namespace `sh-*`)
- `resources/views/esbtp/classes/partials/_suivi_heures_lmd_ue_card.blade.php` (carte parent pliable)
- `app/Http/Controllers/ESBTPClasseController.php::buildLmdUesAvecEcues()` (méthode privée groupby + agrégats)
- Commit `359929a8` (14 mai 2026) — implémentation de référence
- Pattern Alpine `openIds` validé suite à premortem session 14/05/2026 : ~30 instances Alpine sur mobile = lag scroll, le pattern Array unique scale à 100+ parents sans dégradation

## Pré-remplir un filtre UI depuis query string (cross-page navigation)

**Quand l'utiliser** : quand on navigue d'une page A vers une page B avec contexte (ex: bouton "Gérer UE/ECUE" sur classes.show → /esbtp/lmd/ue pré-filtré sur le parcours de la classe).

### Pattern Alpine — init filters depuis `request()`

```blade
{{-- ❌ MAUVAIS — filters init hardcoded, l'utilisateur arrive sur la page B et voit les selects vides --}}
function pageManager() {
    return {
        filters: { search: '', parcours_id: '', type_ue: '' },
        // ...
    };
}

{{-- ✅ BON — init depuis query string, l'utilisateur voit les selects PRÉ-REMPLIS --}}
function pageManager() {
    return {
        filters: {
            search: @json(request('search', '')),
            parcours_id: @json((string) request('parcours_id', '')),
            type_ue: @json(request('type_ue', '')),
        },
        // ...
    };
}
```

### Pourquoi `@json()` et pas interpolation directe

- `@json()` génère du JSON valide, donc safe contre les caractères spéciaux (apostrophes, accents)
- Cohérent avec rule `premium-selects.md` section AJAX-safe
- Le `(string)` cast assure que les ID numériques DB ne créent pas un mismatch type entre `<option value="123">` (string) et `filters.parcours_id` (number) qui empêcherait Alpine de pré-sélectionner l'option

### Côté navigation source

Toujours utiliser `array_filter()` pour ne pas envoyer de params vides ni `null` :

```blade
<a href="{{ route('target.index', array_filter([
    'parcours_id' => $classe->parcours_id,    // null pour tronc commun
    'niveau_id' => $classe->niveau_etude_id,
    'filiere_id' => $classe->filiere_id,
])) }}" class="cs-btn--glass">
    Filtrer cette ressource
</a>
```

### Côté backend controller

Le controller doit appliquer les filtres conditionnellement :

```php
if ($request->filled('parcours_id')) {
    $query->where('parcours_id', $request->parcours_id);
}
if ($request->filled('niveau_id')) {
    $query->where('niveau_id', $request->niveau_id);
}
```

### Auto-load au mount

Si le `x-init="loadData()"` existe déjà, il va automatiquement utiliser les filters pré-remplis pour le premier fetch — aucune action supplémentaire nécessaire.

### Anti-patterns à BLOQUER en review

1. ❌ Bouton de navigation contextuelle qui pointe vers la page cible SANS query string contextuelle
2. ❌ Page cible avec filters init hardcoded `''` quand un bouton externe envoie déjà le contexte
3. ❌ Oublier le cast `(string)` sur les ID numériques (mismatch type Alpine vs `<option value="">` strict)
4. ❌ `<a href="?param=null">` au lieu d'utiliser `array_filter()` côté navigation

### Référence canonique

- Source : `resources/views/esbtp/classes/show.blade.php:567` (bouton "Gérer UE/ECUE" hero LMD)
- Cible : `resources/views/esbtp/lmd/ue/index.blade.php:638-642` (filters init `@json(request(...))`)
- Commits : `fed1a3bf` (navigation source) + `fe9bac13` (cible Alpine prefill) — 14-15 mai 2026

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
