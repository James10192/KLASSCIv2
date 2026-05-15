{{--
    <x-lmd-hierarchy-tree>

    Affiche la hiérarchie LMD UEMOA Domaine → Mention → Parcours [→ Classe]
    en tree premium IDE-style (L-connectors VSCode).

    Référence pattern : .claude/rules/premium-redesign.md section "Tree hiérarchique IDE-style".
    Source canonique du look : classes/show.blade.php (cs-lmd-tree-*) avant extraction.

    Props :
    - $parcours (ESBTPLMDParcours|null) : avec mention.domaine eager-loaded
    - $mention  (ESBTPLMDMention|null)  : avec domaine eager-loaded
                                          (utilisé si pas de parcours = tronc commun)
    - $classe   (ESBTPClasse|null)      : ajoute un 4e node Classe
    - $compact  (bool default false)    : version compact pour cards listing
                                          (heights/font/padding réduits)

    Comportements :
    - Si $parcours fourni  → render Domaine → Mention → Parcours [→ Classe]
    - Si $parcours absent mais $mention fourni → render Domaine → Mention [→ Classe]
      avec petit label "Tronc commun mention" sur le node Mention
    - Si rien fourni        → ne render rien (sécurité)

    CSS push-once via @once @push('styles') — namespace `lht-*` (lmd-hierarchy-tree)
    pour éviter collision avec namespaces page (cs-, ci-, is-, et-, etc.).
--}}
@props([
    'parcours' => null,
    'mention' => null,
    'classe' => null,
    'compact' => false,
])

@php
    // Source de vérité de la mention/domaine
    $mentionResolved = $parcours?->mention ?? $mention;
    $domaineResolved = $mentionResolved?->domaine;

    // Défensif : rien à afficher si aucune donnée hiérarchique
    $hasAnything = $mentionResolved || $domaineResolved || $parcours || $classe;
    $isTroncCommun = !$parcours && $mentionResolved;
    $compactClass = $compact ? ' lht--compact' : '';
@endphp

@if($hasAnything)
<div class="lht{{ $compactClass }}">
    @if($domaineResolved)
        <div class="lht-node lht-node--lvl0">
            <div class="lht-icon"><i class="fas fa-folder-open"></i></div>
            <div class="lht-body">
                <div class="lht-label">Domaine</div>
                <div class="lht-name">{{ $domaineResolved->name }}</div>
                @if($domaineResolved->code)
                    <span class="lht-code">{{ $domaineResolved->code }}</span>
                @endif
            </div>
        </div>
    @endif

    @if($mentionResolved)
        <div class="lht-node lht-node--lvl1">
            <div class="lht-icon"><i class="fas fa-graduation-cap"></i></div>
            <div class="lht-body">
                <div class="lht-label">Mention{{ $isTroncCommun ? ' · tronc commun' : '' }}</div>
                <div class="lht-name">{{ $mentionResolved->name }}</div>
                @if($mentionResolved->code)
                    <span class="lht-code">{{ $mentionResolved->code }}</span>
                @endif
            </div>
        </div>
    @endif

    @if($parcours)
        <div class="lht-node lht-node--lvl2">
            <div class="lht-icon"><i class="fas fa-route"></i></div>
            <div class="lht-body">
                <div class="lht-label">Parcours</div>
                <div class="lht-name">{{ $parcours->name }}</div>
                @if($parcours->code)
                    <span class="lht-code">{{ $parcours->code }}</span>
                @endif
            </div>
        </div>
    @endif

    @if($classe)
        <div class="lht-node lht-node--lvl{{ $parcours ? 3 : 2 }}">
            <div class="lht-icon"><i class="fas fa-chalkboard"></i></div>
            <div class="lht-body">
                <div class="lht-label">Classe</div>
                <div class="lht-name">{{ $classe->name }}</div>
                @if($classe->code)
                    <span class="lht-code">{{ $classe->code }}</span>
                @endif
            </div>
        </div>
    @endif
</div>
@endif

@once
@push('styles')
<style>
/* =====================================================================
   Composant lmd-hierarchy-tree — Tree premium IDE-style namespace lht-*
   Pattern canonique : .claude/rules/premium-redesign.md section
   "Tree hiérarchique IDE-style". height/icon-size FIXES obligatoires
   pour calculs L-connectors précis.
   ===================================================================== */
.lht {
    background: linear-gradient(135deg, rgba(4,83,203,.04), rgba(59,125,219,.06));
    border: 1px solid rgba(4,83,203,.18);
    border-radius: 12px;
    padding: .85rem;
}
.lht-node {
    position: relative;
    display: flex; align-items: center;
    gap: .7rem;
    padding: 0 .65rem;
    border-radius: 7px;
    height: 44px;
    transition: background .15s;
}
.lht-node + .lht-node { margin-top: .25rem; }
.lht-node:hover { background: rgba(4,83,203,.06); }

/* Indentation progressive parent → enfant */
.lht-node--lvl1 { margin-left: 1.6rem; }
.lht-node--lvl2 { margin-left: 3.2rem; }
.lht-node--lvl3 { margin-left: 4.8rem; }

/* L-CONNECTOR : trait vertical pile sous centre horizontal icône parent
   + segment horizontal jusqu'au bord gauche icône enfant.
   left:0 = centre icône parent (à .05rem près, invisible).
   bottom:calc(50% - 1px) = milieu vertical icône courante. */
.lht-node--lvl1::before,
.lht-node--lvl2::before,
.lht-node--lvl3::before {
    content: '';
    position: absolute;
    left: 0;
    top: calc(-50% - .25rem);
    bottom: calc(50% - 1px);
    width: .65rem;
    border-left: 2px solid rgba(4,83,203,.42);
    border-bottom: 2px solid rgba(4,83,203,.42);
    border-bottom-left-radius: 7px;
    pointer-events: none;
}

.lht-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .82rem;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(4,83,203,.25);
    position: relative; z-index: 1;
}
.lht-node--lvl0 .lht-icon { background: linear-gradient(135deg, #033a8e, #0453cb); }
.lht-node--lvl1 .lht-icon { background: linear-gradient(135deg, #0453cb, #3b7ddb); }
.lht-node--lvl2 .lht-icon { background: linear-gradient(135deg, #3b7ddb, #5e91de); }
.lht-node--lvl3 .lht-icon { background: linear-gradient(135deg, #5e91de, #93b8e8); }

.lht-body {
    flex: 1; min-width: 0;
    display: grid;
    grid-template-columns: 1fr auto;
    align-items: center;
    gap: .15rem .65rem;
}
.lht-label {
    grid-column: 1; font-size: .62rem;
    color: #64748b; font-weight: 700;
    text-transform: uppercase; letter-spacing: .6px;
}
.lht-name {
    grid-column: 1; font-size: .92rem; font-weight: 700;
    color: #1e293b; line-height: 1.2;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.lht-code {
    grid-column: 2; grid-row: 1 / span 2;
    align-self: center;
    font-size: .64rem; color: #0453cb;
    background: rgba(4,83,203,.08);
    padding: .15rem .5rem; border-radius: 5px;
    font-weight: 700; letter-spacing: .3px;
    font-family: 'Courier New', monospace;
    white-space: nowrap;
}

/* ============================ COMPACT (cards) ============================
   Heights / paddings / fonts réduits pour intégration dans une card listing.
   Indentations gardées à 1.6rem pour préserver le L-connector. */
.lht--compact {
    padding: .6rem;
    border-radius: 10px;
}
.lht--compact .lht-node {
    height: 36px;
    padding: 0 .5rem;
    gap: .55rem;
}
.lht--compact .lht-node + .lht-node { margin-top: .2rem; }
.lht--compact .lht-icon {
    width: 26px; height: 26px;
    border-radius: 7px;
    font-size: .68rem;
}
.lht--compact .lht-label {
    font-size: .55rem;
    letter-spacing: .5px;
}
.lht--compact .lht-name {
    font-size: .8rem;
}
.lht--compact .lht-code {
    font-size: .58rem;
    padding: .1rem .4rem;
}
/* L-connector en compact : recalculer width = padding-left node = .5rem */
.lht--compact .lht-node--lvl1,
.lht--compact .lht-node--lvl2,
.lht--compact .lht-node--lvl3 { margin-left: 1.3rem; }
.lht--compact .lht-node--lvl1::before,
.lht--compact .lht-node--lvl2::before,
.lht--compact .lht-node--lvl3::before {
    width: .5rem;
    top: calc(-50% - .2rem);
}
</style>
@endpush
@endonce
