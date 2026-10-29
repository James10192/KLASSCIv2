@extends('layouts.app')

@section('title', 'Résultats des étudiants — KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}?v={{ @filemtime(public_path('css/dashboard-moderne.css')) ?: '1' }}">
<link rel="stylesheet" href="{{ asset('css/student-results.css') }}?v={{ @filemtime(public_path('css/student-results.css')) ?: '1' }}">
@endsection

@php
    $_rslClasseOptions = collect($classes ?? [])->mapWithKeys(function ($c) {
        return [$c->id => $c->name . ($c->filiere ? ' (' . $c->filiere->name . ')' : '')];
    })->all();
    $_rslAnneeOptions = collect($annees_universitaires ?? [])->mapWithKeys(function ($annee) {
        return [$annee->id => ($annee->name ?? ($annee->annee_debut . '-' . $annee->annee_fin)) . ($annee->is_current ? ' *' : '')];
    })->all();
    $_rslPeriodeOptions = $periodes ?? [];
    $_rslInitialFilters = [
        'classe_id' => isset($classe_id) && $classe_id ? (string) $classe_id : null,
        'annee_universitaire_id' => isset($annee_universitaire_id) && $annee_universitaire_id ? (string) $annee_universitaire_id : null,
        'semestre' => isset($semestre) && $semestre !== null && $semestre !== '' ? (string) $semestre : null,
        'include_all_statuses' => (bool) (isset($include_all_statuses) && $include_all_statuses),
    ];
    $_rslAutoLoad = isset($classe_id) || isset($annee_universitaire_id);
@endphp

@push('styles')
<style>
/* ===== Résultats des étudiants — namespace rsl-* ===== */
.rsl-page { --rsl-primary:#0453cb; --rsl-primary-d:#033a8e; --rsl-accent:#3b7ddb; --rsl-text:#1e293b; --rsl-muted:#64748b; --rsl-border:#e2e8f0; --rsl-surface:#f8fafc; }

/* Hero (pattern planning-header : 2 rangées, KPI dans le hero, sans overflow ni position) */
.rsl-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%); border-radius: 18px; padding: 2rem 2.5rem 1.5rem; color: #fff; margin-bottom: 1.25rem; box-shadow: 0 8px 30px rgba(4,83,203,.18); }
.rsl-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.rsl-hero-left { display: flex; align-items: center; gap: 1rem; min-width: 0; }
.rsl-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; color: #fff; }
.rsl-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.rsl-hero p { color: rgba(255,255,255,.75); font-size: .88rem; margin: .15rem 0 0; }
.rsl-hero-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
.rsl-btn { display: inline-flex; align-items: center; gap: .45rem; border-radius: 10px; padding: .5rem 1rem; font-size: .82rem; font-weight: 600; text-decoration: none; border: 1px solid transparent; cursor: pointer; transition: background .2s ease, color .2s ease, border-color .2s ease; white-space: nowrap; }
.rsl-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.2); }
.rsl-btn--glass:hover { background: rgba(255,255,255,.25); color: #fff; }
.rsl-btn--white { background: #fff; color: var(--rsl-primary); }
.rsl-btn--white:hover { background: #eef4ff; color: var(--rsl-primary-d); }
.rsl-btn--primary { background: var(--rsl-primary); color: #fff; }
.rsl-btn--primary:hover { background: var(--rsl-primary-d); color: #fff; }
.rsl-btn--ghost { background: #fff; color: var(--rsl-primary); border-color: rgba(4,83,203,.25); }
.rsl-btn--ghost:hover { background: rgba(4,83,203,.06); }

.rsl-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: .75rem; margin-top: 1.5rem; }
.rsl-kpi { background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15); border-radius: 12px; padding: .9rem 1rem; display: flex; align-items: center; gap: .75rem; min-width: 0; }
.rsl-kpi-icon { width: 38px; height: 38px; border-radius: 10px; background: rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.rsl-kpi-body { min-width: 0; }
.rsl-kpi-value { font-size: clamp(1.1rem, 2.2vw, 1.35rem); font-weight: 700; color: #fff; white-space: nowrap; line-height: 1.15; }
.rsl-kpi-value small { font-size: .7rem; font-weight: 600; opacity: .75; margin-left: .15rem; }
.rsl-kpi-label { font-size: .72rem; color: rgba(255,255,255,.7); margin-top: .15rem; display: flex; flex-wrap: wrap; align-items: center; gap: .2rem .4rem; }
.rsl-kpi-ref { font-size: .68rem; color: rgba(255,255,255,.6); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rsl-kpi { transition: box-shadow .2s ease; }
/* Etat semantique (vert / orange / rouge) : la couleur porte un sens, pas un decor. */
.rsl-kpi[data-etat="bon"] { box-shadow: inset 4px 0 0 #10b981; }
.rsl-kpi[data-etat="a_surveiller"] { box-shadow: inset 4px 0 0 #f59e0b; }
.rsl-kpi[data-etat="alerte"] { box-shadow: inset 4px 0 0 #dc2626; }
.rsl-kpi[data-etat="bon"] .rsl-kpi-icon { background: #10b981; }
.rsl-kpi[data-etat="a_surveiller"] .rsl-kpi-icon { background: #f59e0b; }
.rsl-kpi[data-etat="alerte"] .rsl-kpi-icon { background: #dc2626; }
.rsl-kpi-icon { position: relative; }
/* Un signe en plus de la couleur (WCAG 1.4.1) : coche, point d'exclamation, croix. */
.rsl-kpi[data-etat] .rsl-kpi-icon::after { position: absolute; right: -5px; bottom: -5px; width: 16px; height: 16px; border-radius: 50%; background: #fff; font-size: .62rem; font-weight: 800; line-height: 16px; text-align: center; box-shadow: 0 1px 3px rgba(15,23,42,.25); }
.rsl-kpi[data-etat="bon"] .rsl-kpi-icon::after { content: '\2713'; color: #047857; }
.rsl-kpi[data-etat="a_surveiller"] .rsl-kpi-icon::after { content: '!'; color: #b45309; }
.rsl-kpi[data-etat="alerte"] .rsl-kpi-icon::after { content: '\00D7'; color: #b91c1c; font-size: .8rem; }
.rsl-kpi-etat { display: inline-flex; align-items: center; gap: .3rem; padding: .12rem .45rem; border-radius: 999px; background: #fff; font-size: .62rem; font-weight: 700; line-height: 1.4; white-space: nowrap; }
.rsl-kpi-etat::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.rsl-kpi-etat[hidden] { display: none; }
.rsl-kpi[data-etat="bon"] .rsl-kpi-etat { color: #047857; }
.rsl-kpi[data-etat="a_surveiller"] .rsl-kpi-etat { color: #b45309; }
.rsl-kpi[data-etat="alerte"] .rsl-kpi-etat { color: #b91c1c; }

/* Filtres */
.rsl-card { background: #fff; border: 1px solid var(--rsl-border); border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); }
.rsl-filters { padding: 1.1rem 1.25rem; margin-bottom: 1.25rem; position: relative; z-index: 3; }
.rsl-filters:focus-within { z-index: 5; }
.rsl-filter-row { display: grid; grid-template-columns: minmax(0, 2.2fr) minmax(0, 1fr) minmax(0, 1fr) auto; gap: .85rem; align-items: end; }
.rsl-field { display: flex; flex-direction: column; gap: .35rem; min-width: 0; }
.rsl-field-label { font-size: .68rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--rsl-muted); margin: 0; }
.rsl-field .au-select { display: flex; width: 100%; }
.rsl-field .au-select .au-select-trigger { width: 100%; }
.rsl-filter-foot { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .5rem; margin-top: .85rem; }
.rsl-switch { display: inline-flex; align-items: center; gap: .55rem; cursor: pointer; font-size: .82rem; color: var(--rsl-text); margin: 0; user-select: none; }
.rsl-switch input { position: absolute; opacity: 0; width: 1px; height: 1px; }
.rsl-switch-track { width: 36px; height: 20px; border-radius: 999px; background: #cbd5e1; position: relative; transition: background .2s ease; flex-shrink: 0; }
.rsl-switch-track::after { content: ''; position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 2px rgba(15,23,42,.2); transition: left .2s ease; }
.rsl-switch input:checked + .rsl-switch-track { background: var(--rsl-primary); }
.rsl-switch input:checked + .rsl-switch-track::after { left: 18px; }
.rsl-switch input:focus-visible + .rsl-switch-track { outline: 2px solid var(--rsl-accent); outline-offset: 2px; }
.rsl-filter-hint { font-size: .75rem; color: var(--rsl-muted); }

/* Carte liste */
.rsl-list { overflow: visible; }
.rsl-list-head { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .85rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--rsl-border); }
.rsl-list-title { display: flex; align-items: center; gap: .75rem; min-width: 0; }
.rsl-section-icon { width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #0453cb, #3b7ddb); display: flex; align-items: center; justify-content: center; color: #fff; font-size: .95rem; flex-shrink: 0; }
.rsl-list-title h3 { font-size: 1rem; font-weight: 700; color: var(--rsl-text); margin: 0; }
.rsl-count { font-size: .75rem; color: var(--rsl-muted); }
.rsl-list-tools { display: flex; align-items: center; gap: .65rem; flex-wrap: wrap; }
.rsl-chip-mode { display: inline-flex; align-items: center; gap: .5rem; padding: .4rem .75rem; border-radius: 10px; font-size: .75rem; border: 1px solid; max-width: 100%; }
.rsl-chip-mode--on { background: rgba(4,83,203,.06); border-color: rgba(4,83,203,.2); color: var(--rsl-primary-d); }
.rsl-chip-mode--off { background: var(--rsl-surface); border-color: var(--rsl-border); color: var(--rsl-muted); }
.rsl-chip-mode-eyebrow { font-weight: 700; text-transform: uppercase; letter-spacing: .05em; font-size: .62rem; opacity: .8; display: block; }
.rsl-search { position: relative; }
.rsl-search i { position: absolute; left: .75rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .8rem; pointer-events: none; }
.rsl-search input { width: 240px; max-width: 100%; height: 40px; padding: .45rem .75rem .45rem 2.1rem; border: 1px solid var(--rsl-border); border-radius: 10px; font-size: .82rem; color: var(--rsl-text); background: #fff; transition: border-color .2s ease, box-shadow .2s ease; }
.rsl-search input:focus { outline: none; border-color: var(--rsl-accent); box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
.rsl-selection { display: none; align-items: center; gap: .5rem; padding: .55rem 1.25rem; background: rgba(4,83,203,.05); border-bottom: 1px solid var(--rsl-border); font-size: .8rem; color: var(--rsl-primary-d); }
.rsl-selection.is-visible { display: flex; }
.rsl-selection button { background: none; border: none; color: var(--rsl-primary); font-weight: 600; font-size: .78rem; padding: 0; cursor: pointer; }

/* Tableau */
.rsl-table-wrap { margin: 0; }
.rsl-table { width: 100%; }
.rsl-table thead th { background: var(--rsl-surface); color: var(--rsl-muted); font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--rsl-border); padding: .75rem 1rem; white-space: nowrap; }
.rsl-th-check { width: 44px; }
.rsl-th-actions { width: 1%; text-align: right; }
.rsl-table tbody td { padding: .8rem 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: .85rem; color: var(--rsl-text); }
.rsl-table tbody tr { transition: background .15s ease; }
.rsl-table tbody tr:hover { background: rgba(4,83,203,.03); }
.rsl-table tbody tr:last-child td { border-bottom: none; }
.rsl-matricule { font-family: 'Courier New', monospace; font-weight: 600; font-size: .8rem; color: var(--rsl-muted); white-space: nowrap; }
.rsl-identity { display: flex; align-items: center; gap: .7rem; min-width: 0; }
.rsl-avatar { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; font-weight: 700; font-size: .78rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; letter-spacing: .02em; }
.rsl-identity-text { min-width: 0; }
.rsl-name { color: var(--rsl-text); line-height: 1.25; }
.rsl-email { color: var(--rsl-muted); font-size: .74rem; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 260px; }
.rsl-chip { display: inline-block; padding: .2rem .55rem; border-radius: 6px; background: rgba(4,83,203,.06); border: 1px solid rgba(4,83,203,.15); color: var(--rsl-primary-d); font-size: .74rem; font-weight: 600; white-space: nowrap; }
.rsl-moyenne { font-size: 1rem; font-weight: 700; color: var(--rsl-primary-d); white-space: nowrap; font-variant-numeric: tabular-nums; }
.rsl-moyenne small { font-size: .7rem; font-weight: 600; color: var(--rsl-muted); margin-left: .1rem; }
.rsl-moyenne--faible { color: #dc2626; }
.rsl-moyenne-alerte { color: #b45309; font-size: .7rem; margin-left: .25rem; }
.rsl-hint { font-size: .7rem; line-height: 1.2; margin-top: .25rem; }
.rsl-hint--warning { color: #b45309; }
.rsl-rang { display: flex; align-items: center; gap: .4rem; white-space: nowrap; }
.rsl-rang small { color: var(--rsl-muted); }
.rsl-rang-icon { color: #94a3b8; font-size: .8rem; }
.rsl-rang-icon--podium { color: var(--rsl-primary); font-size: .85rem; }
.rsl-badge { display: inline-flex; align-items: center; gap: .3rem; padding: .22rem .55rem; border-radius: 6px; font-size: .72rem; font-weight: 700; white-space: nowrap; border: 1px solid transparent; }
.rsl-badge--success { background: rgba(16,185,129,.1); color: #047857; border-color: rgba(16,185,129,.25); }
.rsl-badge--danger { background: rgba(220,38,38,.08); color: #b91c1c; border-color: rgba(220,38,38,.22); }
.rsl-badge--warning { background: rgba(245,158,11,.12); color: #92400e; border-color: rgba(245,158,11,.3); }
.rsl-badge--muted { background: var(--rsl-surface); color: var(--rsl-muted); border-color: var(--rsl-border); }

/* Actions par ligne : une action principale + menu (pas d'arc-en-ciel) */
.rsl-actions { display: flex; align-items: center; justify-content: flex-end; gap: .35rem; }
.rsl-action-main { display: inline-flex; align-items: center; gap: .4rem; height: 34px; padding: 0 .8rem; border-radius: 8px; background: rgba(4,83,203,.08); color: var(--rsl-primary); font-size: .78rem; font-weight: 600; text-decoration: none; white-space: nowrap; transition: background .2s ease, color .2s ease; }
.rsl-action-main:hover { background: var(--rsl-primary); color: #fff; }
.rsl-action-more { width: 34px; height: 34px; border-radius: 8px; border: 1px solid var(--rsl-border); background: #fff; color: var(--rsl-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: border-color .2s ease, color .2s ease; }
.rsl-action-more:hover, .rsl-action-more[aria-expanded="true"] { border-color: var(--rsl-accent); color: var(--rsl-primary); }
.rsl-menu { border-radius: 10px; border: 1px solid var(--rsl-border); box-shadow: 0 8px 30px rgba(4,83,203,.12); padding: .35rem; min-width: 230px; }
.rsl-menu .dropdown-item { border-radius: 7px; font-size: .82rem; padding: .5rem .7rem; display: flex; align-items: center; gap: .55rem; color: var(--rsl-text); }
.rsl-menu .dropdown-item i { color: var(--rsl-primary); width: 16px; text-align: center; }
.rsl-menu .dropdown-item small { color: var(--rsl-muted); }
.rsl-menu .dropdown-item:hover { background: rgba(4,83,203,.06); }

/* États */
.rsl-state { text-align: center; padding: 3rem 1.5rem; }
.rsl-state-icon { width: 56px; height: 56px; border-radius: 16px; background: rgba(4,83,203,.08); color: var(--rsl-primary); display: inline-flex; align-items: center; justify-content: center; font-size: 1.35rem; margin-bottom: .85rem; }
.rsl-state-icon--danger { background: rgba(220,38,38,.08); color: #dc2626; }
.rsl-state h3 { font-size: 1.05rem; font-weight: 700; color: var(--rsl-text); margin: 0 0 .35rem; }
.rsl-state p { font-size: .85rem; color: var(--rsl-muted); margin: 0 auto; max-width: 440px; }
.rsl-skeleton { padding: .5rem 1.25rem 1.25rem; }
.rsl-skeleton-row { display: grid; grid-template-columns: 24px 90px 1fr 80px 70px 80px 110px; gap: 1rem; align-items: center; padding: .9rem 0; border-bottom: 1px solid #f1f5f9; }
.rsl-skeleton-bar { height: 12px; border-radius: 6px; background: linear-gradient(90deg, #eef2f7 0%, #f8fafc 50%, #eef2f7 100%); background-size: 200% 100%; animation: rsl-shimmer 1.2s ease-in-out infinite; }
.rsl-skeleton-bar--tall { height: 30px; border-radius: 8px; }
@keyframes rsl-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
.rsl-sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }

/* Bas de liste : composant x-liste-infinie (public/js/liste-infinie.js) */
.rsl-bas { border-top: 1px solid var(--rsl-border); }
.rsl-search-empty { display: none; }

@media (max-width: 992px) {
    .rsl-filter-row { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
    .rsl-field--classe { grid-column: 1 / -1; }
}
@media (max-width: 768px) {
    .rsl-hero { padding: 1.5rem 1.25rem 1.25rem; border-radius: 14px; }
    .rsl-hero h1 { font-size: 1.2rem; }
    .rsl-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; margin-top: 1.1rem; }
    .rsl-kpi { padding: .7rem .75rem; gap: .55rem; }
    .rsl-kpi-icon { width: 32px; height: 32px; }
    /* Sur telephone, l'icone coloree et son signe portent l'etat ; le mot reste lu
       par les lecteurs d'ecran. */
    .rsl-kpi-etat { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; padding: 0; margin: 0; }
    .rsl-filters { padding: 1rem; }
    .rsl-filter-row { grid-template-columns: 1fr; }
    .rsl-filter-row .rsl-btn { width: 100%; justify-content: center; }
    .rsl-list-head { padding: .9rem 1rem; }
    .rsl-list-tools, .rsl-search, .rsl-search input { width: 100%; }

    /* Téléphone : chaque ligne devient une carte élève */
    .rsl-table thead { display: none; }
    .rsl-table, .rsl-table tbody { display: block; }
    .rsl-table tbody { padding: .75rem; }
    .rsl-table tbody tr.rsl-row { display: grid; grid-template-columns: auto 1fr auto; grid-template-areas: "check nom nom" "moy moy rang" "statut statut statut" "mat mat mat" "classe classe classe" "act act act"; gap: .35rem .75rem; padding: .9rem; margin-bottom: .75rem; border: 1px solid var(--rsl-border); border-radius: 12px; background: #fff; box-shadow: 0 1px 3px rgba(15,23,42,.04); }
    .rsl-table tbody tr.rsl-row:hover { background: #fff; }
    .rsl-table tbody td { display: block; padding: 0; border: none; }
    .rsl-cell--check { grid-area: check; align-self: center; }
    .rsl-cell--nom { grid-area: nom; }
    .rsl-cell--moyenne { grid-area: moy; padding-top: .35rem !important; }
    .rsl-cell--rang { grid-area: rang; padding-top: .35rem !important; text-align: right; }
    .rsl-cell--rang .rsl-rang { justify-content: flex-end; }
    .rsl-cell--statut { grid-area: statut; }
    .rsl-cell--matricule { grid-area: mat; }
    .rsl-cell--classe { grid-area: classe; }
    .rsl-cell--matricule::before, .rsl-cell--classe::before { content: attr(data-label) " · "; font-size: .7rem; color: var(--rsl-muted); font-weight: 600; }
    .rsl-cell--actions { grid-area: act; padding-top: .5rem !important; border-top: 1px solid #f1f5f9 !important; margin-top: .25rem; }
    .rsl-actions { justify-content: stretch; }
    .rsl-action-main { flex: 1; justify-content: center; height: 40px; }
    .rsl-action-more { width: 40px; height: 40px; }
    .rsl-email { max-width: 100%; }
    .rsl-skeleton-row { grid-template-columns: 24px 1fr 70px; }
    .rsl-skeleton-row .rsl-skeleton-bar:nth-child(n+4) { display: none; }
}
@media (max-width: 576px) {
    .rsl-hero-actions { width: 100%; }
    .rsl-hero-actions .rsl-btn { flex: 1; justify-content: center; }
}
</style>
@endpush

@section('content')
<div class="dashboard-acasi rsl-page">
    <div class="main-content">

        {{-- Hero : titre, accès rapides et KPI de la sélection --}}
        <div class="rsl-hero">
            <div class="rsl-hero-top">
                <div class="rsl-hero-left">
                    <div class="rsl-hero-icon"><i class="fas fa-chart-bar"></i></div>
                    <div>
                        <h1>Résultats des étudiants</h1>
                        <p>Consultez et gérez les résultats scolaires de l'établissement</p>
                    </div>
                </div>
                <div class="rsl-hero-actions">
                    <a href="{{ route('esbtp.resultats.classes') }}" class="rsl-btn rsl-btn--glass">
                        <i class="fas fa-layer-group"></i>Classes
                    </a>
                    @can('bulletins.configure')
                        <a href="{{ route('esbtp.bulletins.configuration') }}" class="rsl-btn rsl-btn--white">
                            <i class="fas fa-cog"></i>Configuration
                        </a>
                    @endcan
                </div>
            </div>

            @php $_seuilReussite = rtrim(rtrim(number_format(\App\Domain\Bulletins\EtatDesResultats::SEUIL_REUSSITE, 2, ',', ''), '0'), ','); @endphp
            <div class="rsl-kpis" aria-live="polite">
                <div class="rsl-kpi">
                    <div class="rsl-kpi-icon"><i class="fas fa-users"></i></div>
                    <div class="rsl-kpi-body">
                        <div class="rsl-kpi-value" id="kpi-total-etudiants">{{ $totalEtudiants }}</div>
                        <div class="rsl-kpi-label">Étudiants</div>
                        <div class="rsl-kpi-ref">dans la sélection</div>
                    </div>
                </div>
                <div class="rsl-kpi" id="kpi-carte-moyenne">
                    <div class="rsl-kpi-icon"><i class="fas fa-calculator"></i></div>
                    <div class="rsl-kpi-body">
                        <div class="rsl-kpi-value"><span id="kpi-moyenne-generale">N/A</span><small id="kpi-moyenne-suffixe" hidden>/20</small></div>
                        <div class="rsl-kpi-label">Moy. générale<span class="rsl-kpi-etat" id="kpi-moyenne-etat" hidden></span></div>
                        <div class="rsl-kpi-ref">seuil de réussite : {{ $_seuilReussite }}</div>
                    </div>
                </div>
                <div class="rsl-kpi" id="kpi-carte-reussite">
                    <div class="rsl-kpi-icon"><i class="fas fa-percentage"></i></div>
                    <div class="rsl-kpi-body">
                        <div class="rsl-kpi-value" id="kpi-taux-reussite">N/A</div>
                        <div class="rsl-kpi-label">Réussite<span class="rsl-kpi-etat" id="kpi-reussite-etat" hidden></span></div>
                        <div class="rsl-kpi-ref">part des moyennes ≥ {{ $_seuilReussite }}</div>
                    </div>
                </div>
                <div class="rsl-kpi">
                    <div class="rsl-kpi-icon"><i class="fas fa-file-alt"></i></div>
                    <div class="rsl-kpi-body">
                        <div class="rsl-kpi-value"><span id="kpi-bulletins">0</span><small id="kpi-bulletins-ref"></small></div>
                        <div class="rsl-kpi-label">Bulletins</div>
                        <div class="rsl-kpi-ref">générés pour la sélection</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtres --}}
        <div class="rsl-card rsl-filters">
            <form class="filter-form" autocomplete="off">
                <div class="rsl-filter-row">
                    <div class="rsl-field rsl-field--classe">
                        <label class="rsl-field-label" for="classe_id">Classe</label>
                        <x-au-select id="classe_id" name="classe_id" icon="fa-layer-group"
                            :options="$_rslClasseOptions" :value="$classe_id ?? ''"
                            placeholder="Toutes les classes" :searchable="true" />
                    </div>
                    <div class="rsl-field">
                        <label class="rsl-field-label" for="annee_universitaire_id">Année</label>
                        <x-au-select id="annee_universitaire_id" name="annee_universitaire_id" icon="fa-calendar"
                            :options="$_rslAnneeOptions" :value="$annee_universitaire_id ?? ''"
                            placeholder="Toutes" :searchable="count($_rslAnneeOptions) > 8" />
                    </div>
                    <div class="rsl-field">
                        <label class="rsl-field-label" for="semestre">Période</label>
                        <x-au-select id="semestre" name="semestre" icon="fa-clock"
                            :options="$_rslPeriodeOptions" :value="$semestre ?? ''"
                            :placeholder-is-first-option="false" placeholder="Annuel" />
                    </div>
                    <div class="rsl-field">
                        <button type="submit" class="rsl-btn rsl-btn--primary" style="height:44px;">
                            <i class="fas fa-search"></i>Filtrer
                        </button>
                    </div>
                </div>
                <div class="rsl-filter-foot">
                    <label class="rsl-switch" for="include_all_statuses">
                        <input type="checkbox" id="include_all_statuses" name="include_all_statuses" value="1"
                               {{ isset($include_all_statuses) && $include_all_statuses ? 'checked' : '' }}>
                        <span class="rsl-switch-track" aria-hidden="true"></span>
                        <span>Inclure inscriptions inactives</span>
                    </label>
                    <span class="rsl-filter-hint">La liste se met à jour dès qu'un filtre change.</span>
                </div>
            </form>
        </div>

        {{-- Contenu principal --}}
        <div class="rsl-card rsl-list">
            <div class="rsl-list-head">
                <div class="rsl-list-title">
                    <div class="rsl-section-icon"><i class="fas fa-list-ol"></i></div>
                    <div>
                        <h3>Liste des résultats</h3>
                        {{-- Le compteur visible est celui du bas de liste ; celui-ci reste tenu à jour, masqué. --}}
                        <div class="rsl-count" id="rsl-count" hidden></div>
                    </div>
                </div>
                <div class="rsl-list-tools">
                    <div class="rsl-chip-mode {{ !empty($attendanceNoteEnabled) ? 'rsl-chip-mode--on' : 'rsl-chip-mode--off' }}">
                        <i class="fas {{ !empty($attendanceNoteEnabled) ? 'fa-wave-square' : 'fa-slash' }}"></i>
                        <span>
                            <span class="rsl-chip-mode-eyebrow">Mode Moyenne</span>
                            @if(!empty($attendanceNoteEnabled))
                                <strong>Assiduité activée</strong>, la liste inclut le bonus/malus.
                            @else
                                <strong>Assiduité désactivée</strong>, liste en moyenne brute.
                            @endif
                        </span>
                    </div>
                    <div class="rsl-search">
                        <i class="fas fa-search"></i>
                        <input type="search" class="search-bar" placeholder="Rechercher un nom ou un matricule…" aria-label="Rechercher un étudiant">
                    </div>
                </div>
            </div>

            <div class="rsl-selection" id="rsl-selection" aria-live="polite">
                <i class="fas fa-check-square"></i>
                <span id="rsl-selection-count">0 étudiant sélectionné</span>
                <button type="button" id="rsl-selection-clear">Tout désélectionner</button>
            </div>

            {{-- Instructions initiales --}}
            <div id="initial-instructions" class="{{ $_rslAutoLoad ? 'd-none' : '' }}">
                <div class="rsl-state">
                    <div class="rsl-state-icon"><i class="fas fa-filter"></i></div>
                    <h3>Sélectionnez des filtres</h3>
                    <p>Choisissez une classe et/ou une année universitaire pour afficher les résultats.</p>
                </div>
            </div>

            {{-- Chargement : squelette de la liste --}}
            <div id="initial-spinner" style="display: none;" aria-busy="true">
                <span class="rsl-sr-only">Chargement des résultats...</span>
                <div class="rsl-skeleton" aria-hidden="true">
                    @for($i = 0; $i < 6; $i++)
                        <div class="rsl-skeleton-row">
                            <div class="rsl-skeleton-bar"></div>
                            <div class="rsl-skeleton-bar"></div>
                            <div class="rsl-skeleton-bar rsl-skeleton-bar--tall"></div>
                            <div class="rsl-skeleton-bar"></div>
                            <div class="rsl-skeleton-bar"></div>
                            <div class="rsl-skeleton-bar"></div>
                            <div class="rsl-skeleton-bar rsl-skeleton-bar--tall"></div>
                        </div>
                    @endfor
                </div>
            </div>

            {{-- Erreur --}}
            <div id="error-state" style="display: none;">
                <div class="rsl-state">
                    <div class="rsl-state-icon rsl-state-icon--danger"><i class="fas fa-exclamation-triangle"></i></div>
                    <h3>Erreur de chargement</h3>
                    <p>Impossible de charger les résultats.</p>
                    <button type="button" onclick="reloadResults()" class="rsl-btn rsl-btn--primary" style="margin-top: 1rem;">
                        <i class="fas fa-redo"></i>Réessayer
                    </button>
                </div>
            </div>

            {{-- Résultats (rempli par AJAX) --}}
            <div id="results-container" style="display: none;"></div>

            <div class="rsl-state rsl-search-empty" id="rsl-search-empty">
                <div class="rsl-state-icon"><i class="fas fa-search"></i></div>
                <h3>Aucun étudiant chargé ne correspond</h3>
                <p>La recherche porte sur les lignes déjà affichées ; la suite de la liste se charge en descendant.</p>
            </div>

        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var ajaxUrl = @json(route('esbtp.resultats.load-etudiants'));
    var initialFilters = @json($_rslInitialFilters);
    var isLoading = false;
    var currentRequest = null;
    var requestSeq = 0;
    var filterTimer = null;
    var applyingHistory = false;
    var remplissageAnnee = false;
    var anneeRequest = null;

    var currentFilters = $.extend({}, initialFilters);

    function shouldLoadResults() {
        return Boolean(currentFilters.classe_id) || Boolean(currentFilters.annee_universitaire_id);
    }

    function readFilters() {
        return {
            classe_id: $('#classe_id').val() || null,
            annee_universitaire_id: $('#annee_universitaire_id').val() || null,
            semestre: $('#semestre').val() || null,
            include_all_statuses: $('#include_all_statuses').is(':checked')
        };
    }

    function setNativeValue(id, value) {
        var el = document.getElementById(id);
        if (!el) return;
        var v = value === null || value === undefined ? '' : String(value);
        if (el.value === v) return;
        el.value = v;
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // Un geste = une entrée d'historique et un rechargement de la page 1.
    function applyFilters() {
        clearTimeout(filterTimer);
        currentFilters = readFilters();
        updateQueryString(true);
        loadEtudiants({ reset: true });
    }

    // Soumission du formulaire : AJAX, aucun rechargement
    $('.filter-form').on('submit', function(e) {
        e.preventDefault();
        applyFilters();
    });

    // Un filtre qui change recharge la liste (la classe a son propre gestionnaire, plus bas)
    $('#annee_universitaire_id, #semestre, #include_all_statuses').on('change', function() {
        if (applyingHistory || remplissageAnnee) return;
        // Une proposition d'année est en vol : sa fin rechargera, avec ce filtre-ci.
        // Seule l'année de l'utilisateur passe outre (la proposition est alors abandonnée).
        if (anneeRequest && this.id !== 'annee_universitaire_id') return;
        clearTimeout(filterTimer);
        filterTimer = setTimeout(applyFilters, 300);
    });

    // L'utilisateur touche l'année : une proposition encore en vol ne l'écrasera pas,
    // et c'est son geste qui recharge la liste.
    $('#annee_universitaire_id').on('change', function() {
        if (remplissageAnnee || !anneeRequest) return;
        anneeRequest.abort();
        anneeRequest = null;
    });

    // Fin de la proposition d'année (reçue ou non) : un seul rechargement, avec
    // l'année affichée, si la classe demandée est toujours celle du sélecteur.
    function rechargerApresAnnee(classeId) {
        if ($('#classe_id').val() !== classeId) return;
        applyFilters();
    }

    // Sélection d'une classe : propose son année, seulement si aucune n'est choisie,
    // sans jamais remplacer l'année de l'utilisateur. La liste attend la réponse :
    // le filtre appliqué est toujours celui que le sélecteur affiche.
    $('#classe_id').on('change', function() {
        if (applyingHistory) return;
        if (anneeRequest) { anneeRequest.abort(); anneeRequest = null; }
        clearTimeout(filterTimer);
        var classeId = $(this).val();
        if (!classeId || $('#annee_universitaire_id').val()) {
            filterTimer = setTimeout(applyFilters, 300);
            return;
        }
        anneeRequest = $.ajax({
            url: '/esbtp/api/classes/' + classeId,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (!data || !data.annee_universitaire_id) return;
                if ($('#annee_universitaire_id').val() || $('#classe_id').val() !== classeId) return;
                remplissageAnnee = true;
                setNativeValue('annee_universitaire_id', data.annee_universitaire_id);
                remplissageAnnee = false;
            },
            complete: function(xhr, status) {
                if (status === 'abort') return;
                anneeRequest = null;
                rechargerApresAnnee(classeId);
            }
        });
    });

    function showInitialSpinner() {
        $('#error-state').hide();
        $('#results-container').hide().empty();
        $('#rsl-search-empty').hide();
        $('#initial-instructions').addClass('d-none');
        $('#initial-spinner').show();
    }

    function hideInitialSpinner() {
        $('#initial-spinner').hide();
    }

    var LIBELLES_ETAT_MOYENNE = { bon: 'Satisfaisante', a_surveiller: 'À surveiller', alerte: 'Sous le seuil' };
    var LIBELLES_ETAT_REUSSITE = { bon: 'Bon niveau', a_surveiller: 'À surveiller', alerte: 'Alerte' };

    // Sans etat (aucune moyenne encore), la carte reste neutre : ni rassurante ni alarmante.
    function poserEtat(carte, pastille, etat, libelles) {
        var libelle = etat ? libelles[etat] : null;
        $(carte).attr('data-etat', libelle ? etat : null);
        $(pastille).text(libelle || '').prop('hidden', !libelle);
    }

    function updateKpis(kpis) {
        if (!kpis) return;
        if (kpis.hasOwnProperty('total_etudiants')) $('#kpi-total-etudiants').text(kpis.total_etudiants ?? 0);
        if (kpis.hasOwnProperty('moyenne_generale')) {
            var m = kpis.moyenne_generale;
            var numerique = m !== null && m !== '' && !isNaN(Number(m));
            $('#kpi-moyenne-generale').text(numerique ? m : 'N/A');
            $('#kpi-moyenne-suffixe').prop('hidden', !numerique);
        }
        if (kpis.hasOwnProperty('taux_reussite')) $('#kpi-taux-reussite').text(kpis.taux_reussite !== null ? kpis.taux_reussite + '%' : 'N/A');
        // L'etat suit sa valeur : une valeur remise a vide efface aussi sa couleur.
        var etats = kpis.etats || {};
        if (kpis.hasOwnProperty('moyenne_generale')) poserEtat('#kpi-carte-moyenne', '#kpi-moyenne-etat', etats.moyenne_generale, LIBELLES_ETAT_MOYENNE);
        if (kpis.hasOwnProperty('taux_reussite')) poserEtat('#kpi-carte-reussite', '#kpi-reussite-etat', etats.taux_reussite, LIBELLES_ETAT_REUSSITE);
        if (kpis.hasOwnProperty('bulletins_count')) {
            $('#kpi-bulletins').text(kpis.bulletins_count ?? 0);
            var total = kpis.total_etudiants || 0;
            $('#kpi-bulletins-ref').text(total ? ' / ' + total : '');
        }
    }

    function updateQueryString(push) {
        var params = new URLSearchParams();
        if (currentFilters.classe_id) params.set('classe_id', currentFilters.classe_id);
        if (currentFilters.annee_universitaire_id) params.set('annee_universitaire_id', currentFilters.annee_universitaire_id);
        if (currentFilters.semestre) params.set('semestre', currentFilters.semestre);
        if (currentFilters.include_all_statuses) params.set('include_all_statuses', 1);
        var newUrl = params.toString() ? window.location.pathname + '?' + params.toString() : window.location.pathname;
        if (newUrl === window.location.pathname + window.location.search) return;
        var state = { rslFilters: $.extend({}, currentFilters) };
        if (push) {
            window.history.pushState(state, '', newUrl);
        } else {
            window.history.replaceState(state, '', newUrl);
        }
    }

    // Retour / avance du navigateur : on remet les filtres de l'entrée d'historique
    window.history.replaceState({ rslFilters: $.extend({}, currentFilters) }, '', window.location.href);
    window.addEventListener('popstate', function(ev) {
        var f = ev.state && ev.state.rslFilters;
        if (!f) return;
        applyingHistory = true;
        setNativeValue('classe_id', f.classe_id);
        setNativeValue('annee_universitaire_id', f.annee_universitaire_id);
        setNativeValue('semestre', f.semestre);
        $('#include_all_statuses').prop('checked', !!f.include_all_statuses);
        applyingHistory = false;
        clearTimeout(filterTimer);
        currentFilters = $.extend({}, f);
        loadEtudiants({ reset: true });
    });

    function showEmptyState() {
        $('#results-container').html(
            '<div class="rsl-state"><div class="rsl-state-icon"><i class="fas fa-inbox"></i></div>' +
            '<h3>Aucun étudiant trouvé</h3><p>Aucun étudiant ne correspond aux critères. Essayez une autre classe, une autre année ou incluez les inscriptions inactives.</p></div>'
        ).show();
        $('#error-state').hide();
    }

    function updateCount(affiches, total) {
        if (!total) {
            $('#rsl-count').text('');
            return;
        }
        $('#rsl-count').text(affiches + ' affiché' + (affiches > 1 ? 's' : '') + ' sur ' + total);
    }

    // Première page (et rechargement quand un filtre change). La suite de la liste
    // est chargée par le bas de liste x-liste-infinie rendu dans cette page 1.
    function loadEtudiants(options) {
        options = options || {};
        if (options.reset) {
            // Un nouveau filtre l'emporte sur un chargement encore en vol
            if (currentRequest) currentRequest.abort();
            currentRequest = null;
            isLoading = false;
        }
        if (isLoading) return;

        if (!shouldLoadResults()) {
            hideInitialSpinner();
            $('#results-container').hide().empty();
            $('#error-state').hide();
            $('#initial-instructions').removeClass('d-none');
            updateCount(0, 0);
            updateSelection();
            updateKpis({ total_etudiants: 0, moyenne_generale: null, taux_reussite: null, bulletins_count: 0 });
            return;
        }

        $('#initial-instructions').addClass('d-none');
        showInitialSpinner();
        updateSelection();

        isLoading = true;
        var seq = ++requestSeq;

        currentRequest = $.ajax({
            url: ajaxUrl,
            method: 'GET',
            data: {
                page: 1,
                per_page: 50,
                classe_id: currentFilters.classe_id,
                semestre: currentFilters.semestre,
                annee_universitaire_id: currentFilters.annee_universitaire_id,
                include_all_statuses: currentFilters.include_all_statuses ? 1 : 0
            },
            success: function(response) {
                if (seq !== requestSeq) return;
                hideInitialSpinner();
                $('#error-state').hide();

                if (response.total === 0) {
                    showEmptyState();
                } else {
                    $('#results-container').html(response.html);
                }
                updateKpis(response.kpis || null);

                $('#results-container').show();
                isLoading = false;
                currentRequest = null;

                updateCount(response.loaded_count || 0, response.total || 0);
                applySearch();
                updateSelection();
            },
            error: function(xhr, status) {
                if (status === 'abort' || seq !== requestSeq) return;
                isLoading = false;
                currentRequest = null;
                showErrorState();
            }
        });
    }

    // Repli conservé : demande la suite au bas de liste, comme son bouton « Charger la suite ».
    window.loadMore = function() {
        var bas = document.querySelector('#results-container [data-liste-infinie]');
        if (bas && window.ListeInfinie) window.ListeInfinie.charger(bas);
    };

    function showErrorState() {
        hideInitialSpinner();
        $('#results-container').hide().empty();
        $('#initial-instructions').addClass('d-none');
        updateSelection();
        $('#error-state').show();
    }

    window.reloadResults = function() {
        $('#error-state').hide();
        loadEtudiants({ reset: true });
    };

    // Lignes ajoutées par le défilement infini
    document.getElementById('results-container').addEventListener('liste-infinie:ajout', function(ev) {
        var p = (ev.detail && ev.detail.pagination) || {};
        if (p.affiches !== undefined) updateCount(Number(p.affiches), Number(p.total || 0));
        applySearch();
        updateSelection();
    });

    // ===== Recherche (porte aussi sur les lignes ajoutées) =====
    function applySearch() {
        var term = ($('.search-bar').val() || '').toLowerCase().trim();
        var rows = $('#results-container tbody tr.rsl-row');
        if (!term) {
            rows.show();
            $('#rsl-search-empty').hide();
            return;
        }
        var visibles = 0;
        rows.each(function() {
            var match = (this.getAttribute('data-search') || '').indexOf(term) !== -1;
            this.style.display = match ? '' : 'none';
            if (match) visibles++;
        });
        $('#rsl-search-empty').toggle(rows.length > 0 && visibles === 0);
    }
    $('.search-bar').on('input', applySearch);

    // ===== Sélection (délégation : vaut pour les lignes ajoutées) =====
    function updateSelection() {
        var checked = $('#results-container .student-checkbox:checked').length;
        var total = $('#results-container .student-checkbox').length;
        $('#select-all').prop('checked', total > 0 && total === checked);
        $('#rsl-selection').toggleClass('is-visible', checked > 0);
        $('#rsl-selection-count').text(checked + ' étudiant' + (checked > 1 ? 's' : '') + ' sélectionné' + (checked > 1 ? 's' : ''));
    }
    $(document).on('change', '#select-all', function() {
        $('#results-container .student-checkbox').prop('checked', $(this).prop('checked'));
        updateSelection();
    });
    $(document).on('change', '#results-container .student-checkbox', updateSelection);
    $('#rsl-selection-clear').on('click', function() {
        $('#results-container .student-checkbox, #select-all').prop('checked', false);
        updateSelection();
    });

    // Chargement initial
    @if($_rslAutoLoad)
        loadEtudiants({ reset: true });
    @else
        hideInitialSpinner();
        $('#initial-instructions').removeClass('d-none');
    @endif
});
</script>
@endpush
