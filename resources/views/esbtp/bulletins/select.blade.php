@extends('layouts.app')

@section('title', 'Générer des bulletins — KLASSCI')

@push('styles')
<style>
:root {
    --bus-primary: #0453cb;
    --bus-primary-d: #033a8e;
    --bus-secondary: #5e91de;
    --bus-accent: #3b7ddb;
    --bus-text: #1e293b;
    --bus-muted: #64748b;
    --bus-surface: #f8fafc;
    --bus-border: #e2e8f0;
    --bus-success: #10b981;
    --bus-warning: #0f766e;
    --bus-danger: #dc2626;
}

/* ── HERO ───────────────────────────────────────────── */
.bus-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.75rem;
    color: #fff;
    margin-bottom: 1.5rem;
    box-shadow: 0 8px 30px rgba(4, 83, 203, .18);
}
.bus-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}
.bus-hero-left { display: flex; align-items: center; gap: 1rem; }
.bus-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255, 255, 255, .12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, .15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.bus-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.bus-hero p { color: rgba(255, 255, 255, .72); font-size: .88rem; margin: .15rem 0 0; }

.bus-btn {
    display: inline-flex; align-items: center; gap: .45rem;
    border: 1px solid transparent;
    border-radius: 10px;
    padding: .5rem 1rem;
    font-size: .82rem; font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: background .15s, border-color .15s, color .15s;
}
.bus-btn--glass {
    background: rgba(255, 255, 255, .15);
    color: #fff;
    border-color: rgba(255, 255, 255, .2);
}
.bus-btn--glass:hover { background: rgba(255, 255, 255, .22); color: #fff; }

/* ── ACTIONS GRID ───────────────────────────────────── */
.bus-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 1rem;
}

.bus-action-card {
    background: #fff;
    border: 1px solid var(--bus-border);
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .04), 0 1px 2px rgba(15, 23, 42, .06);
    /* PAS de overflow:hidden : sinon le dropdown des selects premium est clippé
       (rule css-stacking-pitfalls). Le radius est reporté sur le head via radius
       top + le body via radius bottom. */
    display: flex;
    flex-direction: column;
    transition: box-shadow .2s;
    position: relative;
}
.bus-action-card:hover {
    box-shadow: 0 8px 30px rgba(4, 83, 203, .08), 0 2px 8px rgba(15, 23, 42, .04);
}
/* Empile la card avec le dropdown ouvert AU-DESSUS des cards voisines */
.bus-action-card:focus-within { z-index: 50; }
.bus-action-card:has(.au-select-trigger--open) { z-index: 100; }
.bus-action-card__head {
    padding: 1.1rem 1.25rem .85rem;
    border-bottom: 1px solid var(--bus-border);
    background: linear-gradient(135deg, rgba(4, 83, 203, .03), rgba(59, 125, 219, .05));
    border-radius: 14px 14px 0 0;
}
.bus-action-card__body { border-radius: 0 0 14px 14px; }
.bus-action-card__head-row { display: flex; align-items: center; gap: .75rem; }
.bus-action-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--bus-primary), var(--bus-accent));
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(4, 83, 203, .22);
}
.bus-action-title { font-size: 1rem; font-weight: 700; color: var(--bus-text); margin: 0; }
.bus-action-sub { font-size: .76rem; color: var(--bus-muted); margin-top: .25rem; line-height: 1.4; }
.bus-action-card__body {
    padding: 1.1rem 1.25rem 1.25rem;
    display: flex; flex-direction: column; gap: .85rem;
    flex: 1;
}
.bus-field { display: flex; flex-direction: column; gap: .35rem; }
.bus-field-label {
    font-size: .68rem; font-weight: 700;
    color: var(--bus-muted);
    text-transform: uppercase;
    letter-spacing: .4px;
    display: flex; align-items: center; gap: .35rem;
}
.bus-field-step {
    display: inline-flex; align-items: center; justify-content: center;
    width: 18px; height: 18px;
    border-radius: 50%;
    background: rgba(4, 83, 203, .12); color: var(--bus-primary);
    font-size: .65rem; font-weight: 700;
}
.bus-field--disabled { opacity: .45; pointer-events: none; }
.bus-helper { font-size: .72rem; color: var(--bus-muted); line-height: 1.4; }
.bus-checkbox-row {
    display: flex; align-items: center; gap: .55rem;
    padding: .6rem .75rem;
    background: var(--bus-surface);
    border: 1px solid var(--bus-border);
    border-radius: 8px;
}
.bus-checkbox-row label { font-size: .82rem; color: var(--bus-text); cursor: pointer; margin: 0; }

.bus-submit {
    display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
    width: 100%;
    padding: .65rem 1rem;
    border-radius: 10px;
    border: none;
    font-size: .85rem; font-weight: 600;
    color: #fff;
    background: linear-gradient(135deg, var(--bus-primary), var(--bus-accent));
    cursor: pointer;
    transition: filter .15s;
    box-shadow: 0 4px 14px rgba(4, 83, 203, .25);
}
.bus-submit:hover { filter: brightness(1.08); }
.bus-submit:disabled { opacity: .55; cursor: wait; filter: grayscale(.3); }
.bus-submit--info { background: linear-gradient(135deg, #0ea5e9, #3b7ddb); }
.bus-submit--success { background: linear-gradient(135deg, #10b981, #0ea5e9); }
.bus-pilotage-link {
    display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
    width: 100%; min-height: 44px; margin-top: .7rem; padding: .55rem .85rem;
    border: 1px solid #dbe5f2; border-radius: 8px; background: #fff;
    color: #0453cb; font-size: .82rem; font-weight: 700; text-decoration: none;
}
.bus-pilotage-link:hover { background: #eff6ff; border-color: #bfdbfe; color: #0347b0; }
.bus-pilotage-link.is-disabled { opacity: .5; pointer-events: none; }

.bus-tag {
    display: inline-flex; align-items: center; gap: .25rem;
    padding: .15rem .5rem;
    border-radius: 5px;
    font-size: .65rem; font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .3px;
}
.bus-tag--read   { background: rgba(4, 83, 203, .1); color: var(--bus-primary); }
.bus-tag--preview { background: rgba(14, 165, 233, .1); color: #0369a1; }
.bus-tag--gen { background: rgba(16, 185, 129, .1); color: var(--bus-success); }

.bus-status {
    display: flex; align-items: center; gap: .35rem;
    font-size: .72rem;
    color: var(--bus-muted);
    margin-top: -.35rem;
}
.bus-status i { font-size: .65rem; }

.bus-inline-panel {
    display: flex;
    flex-direction: column;
    gap: .55rem;
    padding: .75rem;
    border: 1px solid #dbe5f2;
    border-radius: 8px;
    background: #f8fafc;
    color: var(--bus-text);
}
.bus-inline-panel--danger {
    border-color: rgba(220, 38, 38, .28);
    background: rgba(220, 38, 38, .04);
}
.bus-inline-panel--ok {
    border-color: rgba(16, 185, 129, .28);
    background: rgba(16, 185, 129, .05);
}
.bus-inline-panel--warn {
    border-color: rgba(245, 158, 11, .30);
    background: rgba(245, 158, 11, .05);
}
.bus-inline-panel--info {
    border-color: rgba(4, 83, 203, .22);
    background: rgba(4, 83, 203, .05);
}
.bus-inline-panel__title {
    display: flex;
    align-items: center;
    gap: .45rem;
    font-size: .78rem;
    font-weight: 800;
    color: var(--bus-text);
}
.bus-inline-panel__body,
.bus-inline-panel__list {
    margin: 0;
    color: var(--bus-muted);
    font-size: .76rem;
    line-height: 1.45;
}
.bus-inline-panel__list {
    padding-left: 1rem;
}
.bus-inline-panel__link {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    width: fit-content;
    color: #0453cb;
    font-size: .76rem;
    font-weight: 800;
    text-decoration: none;
}
.bus-inline-panel__link:hover { color: #033a8e; text-decoration: underline; }
.bus-inline-panel__button {
    border: 0;
    background: transparent;
    cursor: pointer;
    padding: 0;
}
.bus-textarea {
    width: 100%;
    min-height: 86px;
    padding: .65rem .75rem;
    border: 1px solid #dbe5f2;
    border-radius: 8px;
    color: var(--bus-text);
    font-size: .82rem;
    resize: vertical;
}
.bus-textarea:focus {
    outline: none;
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4, 83, 203, .1);
}
.bus-field-hint {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
    margin: .25rem 0 0;
    font-size: .7rem;
    color: var(--bus-muted);
}
.bus-field-hint--warn { color: #b45309; font-weight: 700; }
.bus-submit--blocked {
    background: #64748b;
    box-shadow: none;
}

/* Toast container */
.bus-toast-stack {
    position: fixed;
    bottom: 1.25rem; right: 1.25rem;
    display: flex; flex-direction: column; gap: .5rem;
    z-index: 99999;
    max-width: 380px;
    pointer-events: none;
}
.bus-toast {
    pointer-events: auto;
    display: flex; align-items: center; gap: .55rem;
    background: #fff;
    border: 1px solid var(--bus-border);
    border-radius: 10px;
    padding: .65rem .85rem;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .12);
    font-size: .85rem;
    color: var(--bus-text);
}
.bus-toast--success { border-left: 4px solid var(--bus-success); }
.bus-toast--success > i { color: var(--bus-success); }
.bus-toast--error { border-left: 4px solid var(--bus-danger); }
.bus-toast--error > i { color: var(--bus-danger); }
.bus-toast--info { border-left: 4px solid var(--bus-primary); }
.bus-toast--info > i { color: var(--bus-primary); }
.bus-toast-close {
    background: transparent; border: none; cursor: pointer;
    color: var(--bus-muted); padding: 0; margin-left: auto;
}

.bus-config-backdrop {
    position: fixed;
    inset: 0;
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: rgba(15, 23, 42, .58);
}
.bus-config-modal {
    width: min(980px, 100%);
    max-height: min(86vh, 760px);
    display: flex;
    flex-direction: column;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 24px 70px rgba(15, 23, 42, .28);
    overflow: hidden;
}
.bus-config-modal__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.2rem;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
}
.bus-config-modal__title {
    margin: 0;
    font-size: 1rem;
    font-weight: 800;
    color: #fff;
}
.bus-config-modal__subtitle {
    margin: .2rem 0 0;
    color: rgba(255, 255, 255, .76);
    font-size: .78rem;
}
.bus-config-modal__close {
    width: 34px;
    height: 34px;
    border: 1px solid rgba(255, 255, 255, .25);
    border-radius: 8px;
    color: #fff;
    background: rgba(255, 255, 255, .12);
    cursor: pointer;
}
.bus-config-modal__body {
    padding: 1rem 1.2rem;
    overflow: auto;
}
.bus-config-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: .65rem;
    margin-bottom: 1rem;
}
.bus-config-summary__item {
    border: 1px solid var(--bus-border);
    border-radius: 8px;
    background: #f8fafc;
    padding: .7rem .8rem;
}
.bus-config-summary__label {
    display: block;
    color: var(--bus-muted);
    font-size: .68rem;
    font-weight: 800;
    text-transform: uppercase;
}
.bus-config-summary__value {
    display: block;
    margin-top: .18rem;
    color: var(--bus-text);
    font-size: .95rem;
    font-weight: 800;
}
.bus-config-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .82rem;
}
.bus-config-table th,
.bus-config-table td {
    padding: .65rem;
    border-bottom: 1px solid var(--bus-border);
    vertical-align: middle;
}
.bus-config-table th {
    color: var(--bus-muted);
    font-size: .68rem;
    text-transform: uppercase;
    text-align: left;
    background: #f8fafc;
}
.bus-config-table input,
.bus-config-table select {
    width: 100%;
    min-height: 36px;
    border: 1px solid #dbe5f2;
    border-radius: 8px;
    padding: .35rem .5rem;
    font-size: .8rem;
}
.bus-config-source {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: .15rem .45rem;
    color: #0453cb;
    background: rgba(4, 83, 203, .09);
    font-size: .68rem;
    font-weight: 800;
}
.bus-config-empty,
.bus-config-error {
    border: 1px solid var(--bus-border);
    border-radius: 8px;
    padding: .9rem;
    color: var(--bus-muted);
    background: #f8fafc;
    font-size: .85rem;
}
.bus-config-error {
    border-color: rgba(220, 38, 38, .28);
    color: #991b1b;
    background: rgba(220, 38, 38, .04);
}
.bus-config-modal__head-left {
    display: flex;
    align-items: center;
    gap: .75rem;
    min-width: 0;
}
.bus-config-modal__head-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(255, 255, 255, .14);
    border: 1px solid rgba(255, 255, 255, .2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: .95rem;
    flex-shrink: 0;
}
.bus-config-toolbar {
    display: flex;
    align-items: center;
    gap: .75rem;
    flex-wrap: wrap;
    margin-bottom: .8rem;
}
.bus-config-copy-btn {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    min-height: 38px;
    padding: .45rem .85rem;
    border: 1px dashed rgba(4, 83, 203, .4);
    border-radius: 9px;
    background: rgba(4, 83, 203, .05);
    color: #0453cb;
    font-size: .8rem;
    font-weight: 800;
    cursor: pointer;
    transition: background .15s, border-color .15s;
}
.bus-config-copy-btn:hover:not(:disabled) { background: rgba(4, 83, 203, .1); border-color: #0453cb; }
.bus-config-copy-btn:disabled { opacity: .6; cursor: wait; }
.bus-config-toolbar__hint { color: var(--bus-muted); font-size: .74rem; flex: 1; min-width: 220px; }
.bus-config-copy-panel {
    border: 1px solid rgba(4, 83, 203, .22);
    border-radius: 10px;
    background: linear-gradient(135deg, rgba(4, 83, 203, .04), rgba(59, 125, 219, .06));
    padding: .85rem .95rem;
    margin-bottom: .9rem;
}
.bus-config-copy-panel__title {
    display: flex;
    align-items: center;
    gap: .45rem;
    color: var(--bus-text);
    font-size: .82rem;
    font-weight: 800;
    margin-bottom: .65rem;
}
.bus-config-copy-panel__choices {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: .6rem;
}
.bus-config-copy-choice {
    display: flex;
    flex-direction: column;
    gap: .25rem;
    text-align: left;
    border: 1px solid #dbe5f2;
    border-radius: 9px;
    background: #fff;
    padding: .65rem .8rem;
    cursor: pointer;
    transition: border-color .15s, box-shadow .15s;
}
.bus-config-copy-choice:hover { border-color: #0453cb; box-shadow: 0 2px 10px rgba(4, 83, 203, .12); }
.bus-config-copy-choice__name { color: #0453cb; font-size: .82rem; font-weight: 800; }
.bus-config-copy-choice__desc { color: var(--bus-muted); font-size: .73rem; line-height: 1.4; }
.bus-config-copy-panel__cancel {
    margin-top: .6rem;
    border: 0;
    background: transparent;
    color: var(--bus-muted);
    font-size: .74rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: underline;
}
.bus-config-table-wrap { overflow: auto; max-height: 46vh; }
.bus-config-table thead th { position: sticky; top: 0; z-index: 1; }
.bus-config-table tbody tr:nth-child(even) td { background: #fbfdff; }
.bus-config-cell--copied { animation: busCopiedFlash 2.2s ease-out; }
@@keyframes busCopiedFlash {
    0% { background: rgba(4, 83, 203, .18); }
    70% { background: rgba(4, 83, 203, .10); }
    100% { background: transparent; }
}
.bus-config-scope {
    display: flex;
    align-items: center;
    gap: .5rem;
    flex-wrap: wrap;
    margin-right: auto;
}
.bus-config-scope__label { color: var(--bus-muted); font-size: .74rem; font-weight: 800; }
.bus-config-scope__opt {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    border: 1px solid #dbe5f2;
    border-radius: 999px;
    padding: .3rem .7rem;
    font-size: .75rem;
    font-weight: 700;
    color: var(--bus-muted);
    cursor: pointer;
    transition: border-color .15s, color .15s, background .15s;
}
.bus-config-scope__opt input { position: absolute; opacity: 0; pointer-events: none; }
.bus-config-scope__opt.is-active { border-color: #0453cb; color: #0453cb; background: rgba(4, 83, 203, .07); }
.bus-config-modal__footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: .6rem;
    flex-wrap: wrap;
    padding: .9rem 1.2rem;
    border-top: 1px solid var(--bus-border);
    background: #fff;
}
.bus-config-modal__footer-actions { display: flex; gap: .6rem; }
.bus-config-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .4rem;
    min-height: 40px;
    padding: .5rem .85rem;
    border-radius: 8px;
    border: 1px solid #dbe5f2;
    background: #fff;
    color: #0453cb;
    font-size: .82rem;
    font-weight: 800;
    cursor: pointer;
}
.bus-config-action--primary {
    color: #fff;
    border-color: #0453cb;
    background: #0453cb;
}
.bus-config-action:disabled {
    opacity: .55;
    cursor: wait;
}

@media (max-width: 768px) {
    .bus-hero { padding: 1.5rem 1.25rem 1.25rem; }
    .bus-hero h1 { font-size: 1.2rem; }
    .bus-config-table th:nth-child(2),
    .bus-config-table td:nth-child(2) { display: none; }
}

    /* Progression d une generation par tranches */
    .bus-progress {
        display: flex; align-items: center; gap: .6rem;
        margin-top: .85rem; padding: .7rem .9rem;
        background: rgba(4, 83, 203, .06);
        border: 1px solid rgba(4, 83, 203, .18);
        border-radius: 10px;
        font-size: .84rem; font-weight: 600; color: #0453cb;
    }
</style>
@endpush

@section('content')
@php
    $anneeOptions = $anneesUniversitaires->mapWithKeys(fn ($annee) => [$annee->id => $annee->display_name])->toArray();
@endphp

<div class="container-fluid" x-data="busSelect()" x-init="init()">

    {{-- ══ HERO ═══════════════════════════════════════════ --}}
    <div class="bus-hero">
        <div class="bus-hero-top">
            <div class="bus-hero-left">
                <div class="bus-hero-icon"><i class="fas fa-magic-wand-sparkles"></i></div>
                <div>
                    <h1>Générer des bulletins</h1>
                    <p>Choisissez d'abord la classe, puis l'année universitaire, puis l'action à effectuer</p>
                </div>
            </div>
            <div>
                <a href="{{ route('esbtp.bulletins.index') }}" class="bus-btn bus-btn--glass">
                    <i class="fas fa-arrow-left"></i> Retour aux bulletins
                </a>
            </div>
        </div>
    </div>

    <div class="bus-grid">
        {{-- ── CARD 1 : Consulter ─────────────────────── --}}
        <div class="bus-action-card" x-data="busCard({ kind: 'consult' })">
            <div class="bus-action-card__head">
                <div class="bus-action-card__head-row">
                    <div class="bus-action-icon" style="background:linear-gradient(135deg, #0453cb, #3b7ddb);">
                        <i class="fas fa-magnifying-glass"></i>
                    </div>
                    <div>
                        <h3 class="bus-action-title">Consulter</h3>
                        <span class="bus-tag bus-tag--read">Lecture</span>
                    </div>
                </div>
                <p class="bus-action-sub">Accéder à la page Résultats pour voir les bulletins existants d'une classe.</p>
            </div>
            <form class="bus-action-card__body" @submit.prevent="submit()">
                <div class="bus-field">
                    <label class="bus-field-label"><span class="bus-field-step">1</span>Classe</label>
                    <x-au-select
                        :options="$classes->mapWithKeys(fn($c) => [$c->id => $c->name])->toArray()"
                        label="Classe a consulter"
                        placeholder="Choisir la classe…"
                        icon="fa-school"
                        :searchable="$classes->count() > 8"
                        x-model="form.classe_id" />
                </div>
                <div class="bus-field" :class="!form.classe_id ? 'bus-field--disabled' : ''">
                    <label class="bus-field-label"><span class="bus-field-step">2</span>Année universitaire</label>
                    <x-au-select
                        :options="$anneeOptions"
                        label="Annee universitaire a consulter"
                        x-bind:disabled="!form.classe_id"
                        placeholder="Choisir l'année…"
                        icon="fa-calendar"
                        x-model="form.annee_universitaire_id" />
                </div>
                <div class="bus-field" :class="(!form.classe_id || !form.annee_universitaire_id) ? 'bus-field--disabled' : ''">
                    <label class="bus-field-label"><span class="bus-field-step">3</span>Période</label>
                    <x-au-select
                        :options="['1' => 'Semestre 1', '2' => 'Semestre 2']"
                        label="Periode a consulter"
                        x-bind:disabled="!form.classe_id || !form.annee_universitaire_id"
                        placeholder="Choisir la période…"
                        icon="fa-layer-group"
                        x-model="form.semestre" />
                </div>
                <button type="submit" class="bus-submit" :disabled="busy || !canSubmit()">
                    <i class="fas" :class="busy ? 'fa-spinner fa-spin' : 'fa-magnifying-glass'"></i>
                    <span x-text="busy ? 'Chargement…' : 'Consulter les bulletins'"></span>
                </button>
            </form>
        </div>

        {{-- ── CARD 2 : Prévisualiser ───────────────────── --}}
        <div class="bus-action-card" x-data="busCard({ kind: 'preview' })">
            <div class="bus-action-card__head">
                <div class="bus-action-card__head-row">
                    <div class="bus-action-icon" style="background:linear-gradient(135deg, #0ea5e9, #3b7ddb);">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div>
                        <h3 class="bus-action-title">Prévisualiser</h3>
                        <span class="bus-tag bus-tag--preview">PDF live</span>
                    </div>
                </div>
                <p class="bus-action-sub">Aperçu PDF d'un bulletin individuel pour vérification avant publication.</p>
            </div>
            <form class="bus-action-card__body" @submit.prevent="submit()">
                <div class="bus-field">
                    <label class="bus-field-label"><span class="bus-field-step">1</span>Classe</label>
                    <x-au-select
                        :options="$classes->mapWithKeys(fn($c) => [$c->id => $c->name])->toArray()"
                        label="Classe de l'apercu"
                        placeholder="Choisir la classe…"
                        icon="fa-school"
                        :searchable="$classes->count() > 8"
                        x-model="form.classe_id" />
                </div>
                <div class="bus-field" :class="!form.classe_id ? 'bus-field--disabled' : ''">
                    <label class="bus-field-label"><span class="bus-field-step">2</span>Année universitaire</label>
                    <x-au-select
                        :options="$anneeOptions"
                        label="Annee universitaire de l'apercu"
                        x-bind:disabled="!form.classe_id"
                        placeholder="Choisir l'année…"
                        icon="fa-calendar"
                        x-model="form.annee_universitaire_id" />
                </div>
                <div class="bus-field" :class="(!form.classe_id || !form.annee_universitaire_id) ? 'bus-field--disabled' : ''">
                    <label class="bus-field-label">
                        <span class="bus-field-step">3</span>Étudiant
                        <span x-show="loadingStudents" x-cloak style="margin-left:auto; font-size:.7rem; color:var(--bus-primary);">
                            <i class="fas fa-spinner fa-spin"></i> Chargement…
                        </span>
                    </label>
                    <x-au-select
                        x-model="form.etudiant_id"
                        label="Etudiant de l'apercu"
                        x-bind:disabled="!form.classe_id || !form.annee_universitaire_id || loadingStudents"
                        :searchable="true"
                        placeholder="Choisir l'étudiant…"
                        icon="fa-user-graduate"
                        :options="[]" />
                    <p class="bus-status" x-show="form.classe_id && form.annee_universitaire_id && !loadingStudents">
                        <i class="fas fa-users"></i>
                        <span x-text="students.length + ' étudiant' + (students.length > 1 ? 's' : '') + ' inscrit' + (students.length > 1 ? 's' : '') + ' cette année dans cette classe'"></span>
                    </p>
                </div>
                <div class="bus-field" :class="!form.etudiant_id ? 'bus-field--disabled' : ''">
                    <label class="bus-field-label"><span class="bus-field-step">4</span>Période</label>
                    <x-au-select
                        x-model="form.periode"
                        label="Periode de l'apercu"
                        x-bind:disabled="!form.etudiant_id"
                        placeholder="Choisir la période…"
                        icon="fa-layer-group"
                        :options="['semestre1' => 'Semestre 1', 'semestre2' => 'Semestre 2 (contient l\'annuel)']" />
                </div>
                <div class="bus-inline-panel bus-inline-panel--danger" x-show="previewIssue" x-cloak>
                    <div class="bus-inline-panel__title">
                        <i class="fas fa-circle-exclamation"></i>
                        <span>Configuration a completer</span>
                    </div>
                    <p class="bus-inline-panel__body" x-text="previewIssue?.message"></p>
                    <template x-if="previewIssue">
                        <button type="button" class="bus-inline-panel__link bus-inline-panel__button" @click="openInlineConfig(previewIssue)">
                            <i class="fas fa-sliders"></i>
                            Completer ici la configuration requise
                        </button>
                    </template>
                </div>
                <button type="submit" class="bus-submit bus-submit--info" :disabled="busy || !canSubmit()">
                    <i class="fas" :class="busy ? 'fa-spinner fa-spin' : 'fa-eye'"></i>
                    <span x-text="busy ? 'Ouverture…' : 'Prévisualiser le bulletin'"></span>
                </button>
            </form>
        </div>

        {{-- ── CARD 3 : Générer ─────────────────────────── --}}
        <div class="bus-action-card" x-data="busCard({ kind: 'generate' })">
            <div class="bus-action-card__head">
                <div class="bus-action-card__head-row">
                    <div class="bus-action-icon" style="background:linear-gradient(135deg, #10b981, #0ea5e9);">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div>
                        <h3 class="bus-action-title">Générer en masse</h3>
                        <span class="bus-tag bus-tag--gen">Toute la classe</span>
                    </div>
                </div>
                <p class="bus-action-sub">Créer ou recalculer les bulletins de tous les étudiants d'une classe pour une période donnée.</p>
            </div>
            <form class="bus-action-card__body" @submit.prevent="submit()">
                <div class="bus-field">
                    <label class="bus-field-label"><span class="bus-field-step">1</span>Classe</label>
                    <x-au-select
                        :options="$classes->mapWithKeys(fn($c) => [$c->id => $c->name])->toArray()"
                        label="Classe a generer"
                        placeholder="Choisir la classe…"
                        icon="fa-school"
                        :searchable="$classes->count() > 8"
                        x-model="form.classe_id" />
                </div>
                <div class="bus-field" :class="!form.classe_id ? 'bus-field--disabled' : ''">
                    <label class="bus-field-label">
                        <span class="bus-field-step">2</span>Année universitaire
                        <span x-show="loadingStudents" x-cloak style="margin-left:auto; font-size:.7rem; color:var(--bus-primary);">
                            <i class="fas fa-spinner fa-spin"></i>
                        </span>
                    </label>
                    <x-au-select
                        :options="$anneeOptions"
                        label="Annee universitaire a generer"
                        x-bind:disabled="!form.classe_id"
                        placeholder="Choisir l'année…"
                        icon="fa-calendar"
                        x-model="form.annee_universitaire_id" />
                    <p class="bus-status" x-show="form.classe_id && form.annee_universitaire_id && !loadingStudents">
                        <i class="fas fa-users"></i>
                        <span x-text="generationStudentsLabel()"></span>
                    </p>
                </div>
                <div class="bus-field" :class="(!form.classe_id || !form.annee_universitaire_id) ? 'bus-field--disabled' : ''">
                    <label class="bus-field-label"><span class="bus-field-step">3</span>Période</label>
                    <x-au-select
                        :options="['semestre1' => 'Semestre 1', 'semestre2' => 'Semestre 2']"
                        label="Periode a generer"
                        x-bind:disabled="!form.classe_id || !form.annee_universitaire_id"
                        placeholder="Choisir la période…"
                        icon="fa-layer-group"
                        x-model="form.periode" />
                </div>
                <div class="bus-checkbox-row">
                    <input type="checkbox" id="bus-recalc" x-model="form.recalculer" :value="1">
                    <label for="bus-recalc">Recalculer si déjà existants</label>
                </div>
                <div class="bus-inline-panel" x-show="preflightBusy" x-cloak>
                    <div class="bus-inline-panel__title">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Pre-controle en cours</span>
                    </div>
                    <p class="bus-inline-panel__body">Verification des inscriptions actives, coefficients et donnees academiques.</p>
                </div>
                <div class="bus-inline-panel"
                     :class="panelClass()"
                     x-show="preflight && !preflightBusy"
                     x-cloak>
                    <div class="bus-inline-panel__title">
                        <i class="fas" :class="panelIcon()"></i>
                        <span>Pre-controle generation</span>
                    </div>
                    <p class="bus-inline-panel__body" x-text="preflight?.message"></p>
                    <template x-if="preflight?.missing_coefficients?.length">
                        <ul class="bus-inline-panel__list">
                            <template x-for="item in preflight.missing_coefficients" :key="item.matiere_id">
                                <li>
                                    <span x-text="item.matiere"></span>
                                    <span x-text="' - ' + item.students_count + ' etudiant' + (item.students_count > 1 ? 's' : '')"></span>
                                </li>
                            </template>
                        </ul>
                    </template>
                    <template x-if="preflight?.missing_professeurs?.length">
                        <ul class="bus-inline-panel__list">
                            <template x-for="item in preflight.missing_professeurs" :key="'prof-' + item.matiere_id">
                                <li>
                                    <span x-text="item.matiere"></span>
                                    <span> - professeur manquant</span>
                                </li>
                            </template>
                        </ul>
                    </template>
                    <template x-if="preflight?.blocking_errors?.length && !preflight?.missing_coefficients?.length && !preflight?.missing_professeurs?.length">
                        <p class="bus-inline-panel__body" x-text="preflight.blocking_errors.length + ' blocage(s) detecte(s).'"></p>
                    </template>
                    <template x-if="preflight?.existing_empty_count > 0 && !preflight?.recalculer">
                        <p class="bus-inline-panel__body">
                            <span x-text="preflight.existing_empty_count"></span>
                            bulletin(s) existant(s) sans moyenne — cochez « Recalculer » pour les regenerer.
                        </p>
                    </template>
                    <template x-if="preflight?.has_hard_blocks">
                        <button type="button" class="bus-inline-panel__link bus-inline-panel__button" @click="openInlineConfig(preflight)">
                            <i class="fas fa-sliders"></i>
                            Completer matieres, coefficients et professeurs
                        </button>
                    </template>
                    <div class="bus-field" x-show="preflight?.requires_incomplete_reason" x-cloak>
                        <label class="bus-field-label" for="bus-incomplete-reason">Motif bulletin incomplet</label>
                        <textarea id="bus-incomplete-reason"
                                  class="bus-textarea"
                                  x-model="form.incomplete_reason"
                                  minlength="8"
                                  maxlength="1000"
                                  placeholder="Expliquez pourquoi la generation incomplete est autorisee (8 caracteres minimum)."></textarea>
                        <div class="bus-field-hint" :class="hasIncompleteReason() ? '' : 'bus-field-hint--warn'">
                            <span x-text="hasIncompleteReason() ? 'Motif valide.' : 'Minimum 8 caracteres requis pour debloquer.'"></span>
                            <span x-text="(form.incomplete_reason || '').trim().length + ' / 1000'"></span>
                        </div>
                    </div>
                </div>
                {{-- Progression d'une generation decoupee en tranches --}}
                <div class="bus-progress" x-show="progression" x-cloak>
                    <i class="fas fa-spinner fa-spin"></i>
                    <span x-text="progression"></span>
                </div>
                <div class="bus-inline-panel"
                     :class="lastGeneration?.ok ? 'bus-inline-panel--ok' : 'bus-inline-panel--danger'"
                     x-show="lastGeneration"
                     x-cloak>
                    <div class="bus-inline-panel__title">
                        <i class="fas" :class="lastGeneration?.ok ? 'fa-circle-check' : 'fa-circle-exclamation'"></i>
                        <span>Resultat generation</span>
                    </div>
                    <p class="bus-inline-panel__body" x-text="lastGeneration?.message"></p>
                    <p class="bus-inline-panel__body" x-text="generationSummary()"></p>
                </div>
                <button type="submit" class="bus-submit bus-submit--success" :class="{ 'bus-submit--blocked': isGenerationBlocked() }" :disabled="busy || !canSubmit()">
                    <i class="fas" :class="busy ? 'fa-spinner fa-spin' : 'fa-file-pdf'"></i>
                    <span x-text="busy ? 'Génération en cours…' : 'Générer les bulletins'"></span>
                </button>
                @if(auth()->user()->can('module.academic_pilotage.access') && auth()->user()->can('academic_pilotage.view'))
                    <a :href="pilotageUrl()" class="bus-pilotage-link" :class="{ 'is-disabled': !canOpenPilotage() }" title="Consulter les alertes avant la génération">
                        <i class="fas fa-chart-line"></i> Voir les alertes de pilotage
                    </a>
                @endif
            </form>
        </div>
    </div>

    @include('esbtp.bulletins.partials.select-config-modal')

    {{-- Toast stack --}}
    <div class="bus-toast-stack" aria-live="polite">
        <template x-for="t in toasts" :key="t.id">
            <div class="bus-toast" :class="'bus-toast--' + t.type" x-transition.opacity>
                <i :class="t.type === 'success' ? 'fas fa-circle-check' : (t.type === 'error' ? 'fas fa-circle-exclamation' : 'fas fa-circle-info')"></i>
                <span x-text="t.message"></span>
                <button class="bus-toast-close" @click="removeToast(t.id)"><i class="fas fa-xmark"></i></button>
            </div>
        </template>
    </div>
</div>

@include('esbtp.bulletins.partials.select-scripts')
@endsection
