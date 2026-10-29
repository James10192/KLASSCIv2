@props([
    'systeme' => 'BTS',
    'size' => 'md',
    'showIcon' => true,
    'showText' => true,
])

@php
    $sys = strtoupper((string) $systeme);
    $isLmd = $sys === 'LMD';
    $isMixte = $sys === 'MIXTE';
    $label = $isLmd ? 'LMD' : ($isMixte ? 'MIXTE' : 'BTS');
    $iconClass = $isLmd
        ? 'fa-graduation-cap'
        : ($isMixte ? 'fa-circle-exclamation' : 'fa-screwdriver-wrench');
    $aria = $isLmd
        ? "Système académique : LMD (Licence-Master-Doctorat UEMOA)"
        : ($isMixte
            ? "Configuration invalide : examen mixte BTS et LMD"
            : "Système académique : BTS (Brevet de Technicien Supérieur)");
    $variantClass = $isLmd ? 'sys-chip--lmd' : ($isMixte ? 'sys-chip--mixte' : 'sys-chip--bts');
    $sizeClass = match ($size) {
        'sm' => 'sys-chip--sm',
        'lg' => 'sys-chip--lg',
        default => 'sys-chip--md',
    };
@endphp

<span class="sys-chip {{ $variantClass }} {{ $sizeClass }}"
      role="status"
      aria-label="{{ $aria }}"
      title="{{ $aria }}">
    @if ($showIcon)
        <i class="fas {{ $iconClass }}" aria-hidden="true"></i>
    @endif
    @if ($showText)
        <span class="sys-chip-label">{{ $label }}</span>
    @endif
</span>

@once
@push('styles')
<style>
/* ════════════════════════════════════════════════════════════════════
   SYSTEME CHIP (composant Blade `systeme-chip`)
   Namespace : sys-chip-*
   WCAG 1.4.1 triple encoding : couleur + icône + texte
   ════════════════════════════════════════════════════════════════════ */
.sys-chip {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    border-radius: 5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    line-height: 1;
    white-space: nowrap;
    border: 1px solid;
    user-select: none;
}
.sys-chip i { font-size: .68em; opacity: .85; }

/* Sizes */
.sys-chip--sm { padding: .12rem .35rem; font-size: .62rem; }
.sys-chip--md { padding: .2rem .5rem; font-size: .7rem; }
.sys-chip--lg { padding: .3rem .65rem; font-size: .8rem; }

/* LMD : bleu KLASSCI primary */
.sys-chip--lmd {
    background: rgba(4,83,203,.10);
    color: #0453cb;
    border-color: rgba(4,83,203,.30);
}

/* BTS : gris ardoise sobre */
.sys-chip--bts {
    background: rgba(100,116,139,.10);
    color: #475569;
    border-color: rgba(100,116,139,.28);
}

/* MIXTE : warning (config invalide) */
.sys-chip--mixte {
    background: rgba(245,158,11,.12);
    color: #b45309;
    border-color: rgba(245,158,11,.32);
}
</style>
@endpush
@endonce
