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

{{-- CSS lht-* : source unique dans partials/lmd-tree-styles.blade.php (avec @once
     interne). Poussé en <head> pour les pages server-side. Pour classes/index
     (cards rechargées en AJAX), le même partial est aussi inclus directement dans
     le <head> de la vue — cf rule embedded-styles-pattern. --}}
@push('styles')
    @include('partials.lmd-tree-styles')
@endpush
@endonce
