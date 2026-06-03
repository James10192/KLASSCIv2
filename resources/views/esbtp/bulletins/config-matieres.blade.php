@extends('layouts.app')

@section('title', 'Configuration des matières - KLASSCI')

@php
    // Normalisations pour accès uniforme (le controller passe parfois array, parfois Model)
    $_classeId = $classe['id'] ?? $classe->id;
    $_classeName = $classe['libelle'] ?? $classe['name'] ?? ($classe->name ?? 'N/A');
    $_classeFiliere = $classe['filiere']['name'] ?? $classe['filiere']['nom'] ?? ($classe->filiere->name ?? 'N/A');
    $_etudiantId = $etudiant['id'] ?? $etudiant->id;
    $_etudiantNom = trim(($etudiant['nom'] ?? $etudiant->nom ?? '').' '.($etudiant['prenoms'] ?? $etudiant->prenoms ?? ''));
    $_anneeId = $anneeUniversitaire['id'] ?? $anneeUniversitaire->id;
    $_anneeName = $anneeUniversitaire['libelle'] ?? $anneeUniversitaire['name'] ?? ($anneeUniversitaire->name ?? 'N/A');
    $_periode = $periode ?? 'semestre1';
    $_periodeLabel = $_periode === 'semestre1' ? 'Semestre 1' : ($_periode === 'semestre2' ? 'Semestre 2' : 'Annuel (S1 + S2)');
    $_isAnnuel = ($_periode === 'annuel');
    // Quand annuel : la copie n'a pas de sens (on applique aux 2 semestres en même temps)
    $_otherPeriode = $_periode === 'semestre1' ? 'semestre2' : ($_periode === 'semestre2' ? 'semestre1' : null);
    $_otherPeriodeLabel = $_otherPeriode === 'semestre1' ? 'Semestre 1' : ($_otherPeriode === 'semestre2' ? 'Semestre 2' : null);
    $_returnUrl = '/esbtp/resultats/etudiant/'.$_etudiantId.'?classe_id='.$_classeId.'&periode='.$_periode.'&annee_universitaire_id='.$_anneeId;
@endphp

@push('styles')
<style>
/* ═══════════════════════ NAMESPACE cm-* (Config Matières premium) ═══════════════════════ */

/* ─── Hero ─── */
.cm-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.75rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.cm-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.cm-hero-left { display: flex; align-items: center; gap: 1rem; min-width: 0; flex: 1; }
.cm-hero-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.cm-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; line-height: 1.2; }
.cm-hero p {
    color: rgba(255,255,255,.72); font-size: .88rem; margin: .25rem 0 0;
    display: inline-flex; align-items: center; gap: .5rem; flex-wrap: wrap;
}
.cm-hero-chip {
    display: inline-flex; align-items: center; gap: .4rem;
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 999px;
    padding: .15rem .65rem;
    font-size: .75rem; font-weight: 600; color: #fff;
}
.cm-btn {
    display: inline-flex; align-items: center; gap: .5rem;
    border: 1px solid transparent; border-radius: 10px;
    padding: .55rem 1rem; font-size: .82rem; font-weight: 600;
    text-decoration: none; cursor: pointer; transition: all .15s; white-space: nowrap;
}
.cm-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.2); }
.cm-btn--glass:hover { background: rgba(255,255,255,.22); color: #fff; }
.cm-btn--white { background: #fff; color: #0453cb; }
.cm-btn--white:hover { background: #f1f5fc; color: #0453cb; }
.cm-btn--ghost { background: #fff; color: #0453cb; border-color: rgba(4,83,203,.25); }
.cm-btn--ghost:hover { background: rgba(4,83,203,.05); }
.cm-btn--success { background: #10b981; color: #fff; }
.cm-btn--success:hover { background: #059669; color: #fff; }
.cm-btn--warning { background: #f59e0b; color: #fff; }
.cm-btn--warning:hover { background: #d97706; color: #fff; }
.cm-btn--danger  { background: #dc2626; color: #fff; }
.cm-btn--primary { background: #0453cb; color: #fff; }
.cm-btn--primary:hover { background: #033a8e; color: #fff; }
.cm-btn[disabled], .cm-btn.disabled { opacity: .55; cursor: not-allowed; }

/* ─── Switch S1/S2 dans le hero ─── */
.cm-period-switch {
    display: inline-flex;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 12px;
    padding: 4px;
}
.cm-period-switch a {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .45rem .9rem;
    border-radius: 9px;
    font-size: .8rem; font-weight: 700;
    color: rgba(255,255,255,.7);
    text-decoration: none;
    transition: all .15s;
}
.cm-period-switch a.active { background: #fff; color: #0453cb; box-shadow: 0 2px 6px rgba(0,0,0,.08); }
.cm-period-switch a:not(.active):hover { color: #fff; background: rgba(255,255,255,.08); }

/* ─── KPIs ─── */
.cm-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
.cm-kpi {
    flex: 1; min-width: 140px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px;
    padding: .85rem 1rem;
    display: flex; align-items: center; gap: .85rem;
}
.cm-kpi-icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: rgba(255,255,255,.15); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0;
}
.cm-kpi-value { font-size: 1.5rem; font-weight: 700; color: #fff; line-height: 1; }
.cm-kpi-label { font-size: .7rem; color: rgba(255,255,255,.7); margin-top: .25rem; text-transform: uppercase; letter-spacing: .5px; font-weight: 600; }

/* ─── Cards ─── */
.cm-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
    margin-bottom: 1.25rem;
    overflow: hidden;
}
.cm-card-header {
    padding: 1.1rem 1.4rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
}
.cm-card-body { padding: 1.4rem; }
.cm-section-header { display: flex; align-items: center; gap: .75rem; }
.cm-section-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .95rem; flex-shrink: 0;
}
.cm-section-header h3 { font-size: .95rem; font-weight: 700; color: #1e293b; margin: 0; }
.cm-section-header p { font-size: .78rem; color: #64748b; margin: 0; }

/* ─── Toolbar (boutons rapides + search) ─── */
.cm-toolbar {
    display: flex; gap: .65rem; flex-wrap: wrap; align-items: center;
    margin-bottom: 1.25rem;
}
.cm-search-wrap {
    flex: 1; min-width: 220px;
    position: relative;
    display: inline-flex; align-items: center;
}
.cm-search-wrap i { position: absolute; left: 12px; color: #94a3b8; font-size: .85rem; pointer-events: none; }
.cm-search-wrap input {
    width: 100%;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: .55rem .8rem .55rem 2.1rem;
    background: #fff;
    font-size: .88rem; color: #1e293b;
    transition: border-color .15s, box-shadow .15s;
}
.cm-search-wrap input:focus {
    outline: none; border-color: rgba(4,83,203,.5);
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}

/* ─── Jauge de complétude ─── */
.cm-completion {
    background: linear-gradient(135deg, rgba(4,83,203,.04), rgba(59,125,219,.06));
    border: 1px solid rgba(4,83,203,.12);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.25rem;
    display: flex; align-items: center; gap: 1rem;
    flex-wrap: wrap;
}
.cm-completion-text {
    flex: 1; min-width: 200px;
    display: flex; align-items: center; gap: .75rem;
}
.cm-completion-status {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .35rem .75rem;
    border-radius: 999px;
    font-size: .72rem; font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    border: 1px solid;
}
.cm-completion-status--complete { background: rgba(16,185,129,.12); color: #047857; border-color: rgba(16,185,129,.3); }
.cm-completion-status--incomplete { background: rgba(245,158,11,.12); color: #b45309; border-color: rgba(245,158,11,.3); }
.cm-completion-status--unconfigured { background: rgba(220,38,38,.1); color: #b91c1c; border-color: rgba(220,38,38,.3); }
.cm-completion-bar {
    flex: 2; min-width: 200px;
    height: 10px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
}
.cm-completion-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #0453cb, #3b7ddb);
    transition: width .4s ease;
}
.cm-completion-bar-fill--complete { background: linear-gradient(90deg, #10b981, #34d399); }
.cm-completion-bar-fill--warning { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.cm-completion-counts { font-size: .82rem; font-weight: 600; color: #475569; }
.cm-completion-counts strong { color: #0453cb; }

/* ─── Table matières ─── */
.cm-table-wrap { overflow-x: auto; border-radius: 10px; border: 1px solid #f1f5f9; }
.cm-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: .87rem;
}
.cm-table thead th {
    background: linear-gradient(180deg, #f8fafc, #f1f5f9);
    color: #475569;
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    padding: .8rem 1rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}
.cm-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background .12s;
}
.cm-table tbody tr:hover { background: rgba(4,83,203,.03); }
.cm-table tbody tr:last-child { border-bottom: none; }
.cm-table tbody td {
    padding: .85rem 1rem;
    vertical-align: middle;
    color: #1e293b;
}
.cm-matiere-cell { display: flex; align-items: center; gap: .75rem; }
.cm-matiere-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: rgba(4,83,203,.08);
    color: #0453cb;
    display: flex; align-items: center; justify-content: center;
    font-size: .9rem;
    flex-shrink: 0;
}
.cm-matiere-name { font-weight: 700; color: #1e293b; }
.cm-matiere-meta { font-size: .72rem; color: #64748b; margin-top: .1rem; }
.cm-matiere-sources { display: inline-flex; gap: .25rem; margin-top: .25rem; flex-wrap: wrap; }
.cm-source-badge {
    font-size: .64rem; font-weight: 700;
    padding: .15rem .45rem;
    border-radius: 5px;
    text-transform: uppercase;
    letter-spacing: .02em;
    border: 1px solid;
}
.cm-source-badge--classe { background: rgba(4,83,203,.08); color: #0453cb; border-color: rgba(4,83,203,.2); }
.cm-source-badge--notes  { background: rgba(59,125,219,.08); color: #3b7ddb; border-color: rgba(59,125,219,.2); }

/* ─── Type picker (radio premium) ─── */
.cm-type-picker {
    display: inline-flex;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 3px;
    gap: 3px;
}
.cm-type-picker input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.cm-type-picker label {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .4rem .75rem;
    border-radius: 7px;
    font-size: .78rem; font-weight: 600;
    color: #64748b;
    cursor: pointer;
    margin: 0;
    transition: all .12s;
    white-space: nowrap;
}
.cm-type-picker label:hover { color: #1e293b; background: rgba(0,0,0,.02); }
.cm-type-picker input[value="general"]:checked + label {
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
    box-shadow: 0 2px 6px rgba(4,83,203,.25);
}
.cm-type-picker input[value="technique"]:checked + label {
    background: linear-gradient(135deg, #10b981, #34d399);
    color: #fff;
    box-shadow: 0 2px 6px rgba(16,185,129,.25);
}
.cm-type-picker input[value="none"]:checked + label {
    background: linear-gradient(135deg, #94a3b8, #cbd5e1);
    color: #fff;
    box-shadow: 0 2px 6px rgba(148,163,184,.25);
}

/* ─── Mini stats statut ligne ─── */
.cm-row-status {
    display: inline-flex; align-items: center; gap: .35rem;
    font-size: .72rem; font-weight: 600;
    padding: .25rem .55rem;
    border-radius: 999px;
}
.cm-row-status--general { background: rgba(4,83,203,.1); color: #0453cb; }
.cm-row-status--technique { background: rgba(16,185,129,.1); color: #047857; }
.cm-row-status--excluded { background: rgba(148,163,184,.12); color: #475569; }

/* ─── Empty state ─── */
.cm-empty {
    text-align: center;
    padding: 3.5rem 1rem;
    color: #64748b;
}
.cm-empty i { font-size: 2.5rem; color: #cbd5e1; margin-bottom: .75rem; }

/* ─── Flash ─── */
.cm-flash {
    display: flex; align-items: center; gap: .75rem;
    padding: .85rem 1.1rem;
    border-radius: 12px;
    margin-bottom: 1rem;
    font-size: .9rem;
    border: 1px solid;
}
.cm-flash--success { background: rgba(16,185,129,.08); border-color: rgba(16,185,129,.3); color: #047857; }
.cm-flash--danger  { background: rgba(220,38,38,.08); border-color: rgba(220,38,38,.3); color: #b91c1c; }

/* ─── Actions footer ─── */
.cm-actions {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
}
.cm-actions-right { display: flex; gap: .65rem; flex-wrap: wrap; }

/* ─── Modal copy preview ─── */
.cm-modal-bg {
    position: fixed; inset: 0;
    background: rgba(15,23,42,.6);
    z-index: 99980;
    display: none;
    align-items: center; justify-content: center;
    padding: 1rem;
}
.cm-modal-bg.show { display: flex; }
.cm-modal {
    background: #fff;
    border-radius: 16px;
    max-width: 700px;
    width: 100%;
    max-height: 90vh;
    overflow: hidden;
    display: flex; flex-direction: column;
    box-shadow: 0 25px 60px rgba(0,0,0,.3);
}
.cm-modal-header {
    padding: 1.2rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
    display: flex; align-items: center; justify-content: space-between;
}
.cm-modal-header h4 { margin: 0; font-size: 1rem; font-weight: 700; }
.cm-modal-close {
    background: rgba(255,255,255,.2); border: none; color: #fff;
    width: 30px; height: 30px; border-radius: 8px;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer;
}
.cm-modal-body { padding: 1.25rem 1.5rem; overflow-y: auto; flex: 1; }
.cm-modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #f1f5f9;
    display: flex; justify-content: flex-end; gap: .65rem;
}
.cm-mode-picker {
    display: flex; gap: .5rem; margin-bottom: 1rem;
}
.cm-mode-picker label {
    flex: 1;
    display: block;
    padding: .85rem 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all .15s;
}
.cm-mode-picker input[type="radio"] { display: none; }
.cm-mode-picker input[type="radio"]:checked + .cm-mode-content {
    /* on n'utilise pas adjacent sibling; on toggle classe via JS */
}
.cm-mode-picker label.active {
    border-color: #0453cb;
    background: rgba(4,83,203,.04);
}
.cm-mode-title { font-weight: 700; font-size: .9rem; color: #1e293b; margin: 0; }
.cm-mode-desc { font-size: .78rem; color: #64748b; margin: .15rem 0 0; }
.cm-preview-summary {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: .85rem 1rem;
    margin-bottom: 1rem;
    display: flex; gap: 1rem; flex-wrap: wrap;
}
.cm-preview-pill {
    display: inline-flex; align-items: center; gap: .35rem;
    font-size: .78rem; font-weight: 700;
    padding: .25rem .65rem;
    border-radius: 999px;
}
.cm-preview-pill--create { background: rgba(16,185,129,.12); color: #047857; }
.cm-preview-pill--update { background: rgba(245,158,11,.12); color: #b45309; }
.cm-preview-pill--skip   { background: rgba(148,163,184,.15); color: #475569; }

.cm-preview-list { font-size: .85rem; }
.cm-preview-list-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: .5rem .65rem;
    border-bottom: 1px solid #f1f5f9;
}
.cm-preview-list-item:last-child { border-bottom: none; }
.cm-preview-list-item .name { color: #1e293b; font-weight: 600; }
.cm-preview-list-item .change { font-size: .75rem; color: #64748b; }

[x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content" x-data="cmConfigMatieres()" x-init="init()">

        {{-- ═══════════════════════ HERO ═══════════════════════ --}}
        <div class="cm-hero">
            <div class="cm-hero-top">
                <div class="cm-hero-left">
                    <div class="cm-hero-icon"><i class="fas fa-sliders-h"></i></div>
                    <div>
                        <h1>Configuration des matières</h1>
                        <p>
                            @if($_etudiantNom){{ $_etudiantNom }}@endif
                            <span class="cm-hero-chip"><i class="fas fa-chalkboard"></i>{{ $_classeName }}</span>
                            <span class="cm-hero-chip"><i class="fas fa-graduation-cap"></i>{{ $_classeFiliere }}</span>
                            <span class="cm-hero-chip"><i class="fas fa-calendar"></i>{{ $_anneeName }}</span>
                        </p>
                    </div>
                </div>
                <div class="cm-hero-actions" style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;">
                    {{-- Switch S1/S2/Annuel dans le hero --}}
                    <div class="cm-period-switch">
                        <a href="?bulletin={{ $_etudiantId }}&classe_id={{ $_classeId }}&periode=semestre1&annee_universitaire_id={{ $_anneeId }}"
                           class="{{ $_periode === 'semestre1' ? 'active' : '' }}">
                            <span style="font-weight:800;">S1</span>
                        </a>
                        <a href="?bulletin={{ $_etudiantId }}&classe_id={{ $_classeId }}&periode=semestre2&annee_universitaire_id={{ $_anneeId }}"
                           class="{{ $_periode === 'semestre2' ? 'active' : '' }}">
                            <span style="font-weight:800;">S2</span>
                        </a>
                        <a href="?bulletin={{ $_etudiantId }}&classe_id={{ $_classeId }}&periode=annuel&annee_universitaire_id={{ $_anneeId }}"
                           class="{{ $_isAnnuel ? 'active' : '' }}">
                            <i class="fas fa-calendar"></i>Annuel
                        </a>
                    </div>
                    @if(!$_isAnnuel)
                    <button type="button" class="cm-btn cm-btn--glass" @click="openCopyModal()" title="Copier la configuration de ce semestre vers {{ $_otherPeriodeLabel }}">
                        <i class="fas fa-copy"></i><span>Copier vers {{ $_otherPeriodeLabel }}</span>
                    </button>
                    @endif
                    <a href="{{ $_returnUrl }}" class="cm-btn cm-btn--glass">
                        <i class="fas fa-arrow-left"></i><span>Retour</span>
                    </a>
                </div>
            </div>

            <div class="cm-kpis">
                <div class="cm-kpi">
                    <div class="cm-kpi-icon"><i class="fas fa-book"></i></div>
                    <div>
                        <div class="cm-kpi-value" x-text="counts.total">{{ count($matieres) }}</div>
                        <div class="cm-kpi-label">Total matières</div>
                    </div>
                </div>
                <div class="cm-kpi">
                    <div class="cm-kpi-icon"><i class="fas fa-graduation-cap"></i></div>
                    <div>
                        <div class="cm-kpi-value" x-text="counts.general">0</div>
                        <div class="cm-kpi-label">Générales</div>
                    </div>
                </div>
                <div class="cm-kpi">
                    <div class="cm-kpi-icon"><i class="fas fa-tools"></i></div>
                    <div>
                        <div class="cm-kpi-value" x-text="counts.technique">0</div>
                        <div class="cm-kpi-label">Techniques</div>
                    </div>
                </div>
                <div class="cm-kpi">
                    <div class="cm-kpi-icon"><i class="fas fa-eye-slash"></i></div>
                    <div>
                        <div class="cm-kpi-value" x-text="counts.excluded">0</div>
                        <div class="cm-kpi-label">Exclues</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Flash --}}
        @if(session('success'))
            <div class="cm-flash cm-flash--success">
                <i class="fas fa-check-circle"></i><span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="cm-flash cm-flash--danger">
                <i class="fas fa-exclamation-circle"></i><span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- ═══════════════════════ JAUGE COMPLÉTUDE ═══════════════════════ --}}
        <div class="cm-completion">
            <div class="cm-completion-text">
                <span class="cm-completion-status"
                      :class="{
                        'cm-completion-status--complete': completionStatus === 'complete',
                        'cm-completion-status--incomplete': completionStatus === 'incomplete',
                        'cm-completion-status--unconfigured': completionStatus === 'unconfigured'
                      }">
                    <i class="fas fa-circle-check" x-show="completionStatus === 'complete'"></i>
                    <i class="fas fa-circle-exclamation" x-show="completionStatus === 'incomplete'"></i>
                    <i class="fas fa-circle-xmark" x-show="completionStatus === 'unconfigured'"></i>
                    <span x-text="completionStatusLabel"></span>
                </span>
                <span class="cm-completion-counts">
                    <strong x-text="counts.configured"></strong> / <strong x-text="counts.total"></strong> matières classées
                </span>
            </div>
            <div class="cm-completion-bar">
                <div class="cm-completion-bar-fill"
                     :class="{
                        'cm-completion-bar-fill--complete': completionStatus === 'complete',
                        'cm-completion-bar-fill--warning': completionStatus === 'incomplete'
                     }"
                     :style="`width: ${counts.total > 0 ? (counts.configured / counts.total * 100) : 0}%`"></div>
            </div>
        </div>

        {{-- ═══════════════════════ TABLE ═══════════════════════ --}}
        <div class="cm-card">
            <div class="cm-card-header">
                <div class="cm-section-header">
                    <div class="cm-section-icon"><i class="fas fa-list"></i></div>
                    <div>
                        <h3>Classification des matières — {{ $_periodeLabel }}</h3>
                        <p>Choisissez Général, Technique ou Exclure pour chaque matière</p>
                    </div>
                </div>
            </div>
            <div class="cm-card-body">

                <form action="{{ route('esbtp.bulletins.save-config-matieres') }}" method="POST" id="configMatieresForm" @submit="onSubmit($event)">
                    @csrf
                    <input type="hidden" name="classe_id" value="{{ $_classeId }}">
                    <input type="hidden" name="etudiant_id" value="{{ $_etudiantId }}">
                    <input type="hidden" name="annee_universitaire_id" value="{{ $_anneeId }}">
                    <input type="hidden" name="periode" value="{{ $_periode }}">
                    @if(isset($bulletin))
                        <input type="hidden" name="bulletin" value="{{ $bulletin }}">
                    @endif

                    {{-- Toolbar : recherche + boutons rapides --}}
                    <div class="cm-toolbar">
                        <div class="cm-search-wrap">
                            <i class="fas fa-search"></i>
                            <input type="search" placeholder="Filtrer une matière…" x-model="search" @input="filterMatieres()">
                        </div>
                        <button type="button" class="cm-btn cm-btn--primary" @click="setAll('general')">
                            <i class="fas fa-graduation-cap"></i><span>Toutes générales</span>
                        </button>
                        <button type="button" class="cm-btn cm-btn--success" @click="setAll('technique')">
                            <i class="fas fa-tools"></i><span>Toutes techniques</span>
                        </button>
                        <button type="button" class="cm-btn cm-btn--ghost" @click="setAll('none')">
                            <i class="fas fa-eye-slash"></i><span>Tout exclure</span>
                        </button>
                    </div>

                    <div class="cm-table-wrap">
                        <table class="cm-table">
                            <thead>
                                <tr>
                                    <th style="width:55%;">Matière</th>
                                    <th style="width:30%;">Classification</th>
                                    <th style="width:15%;">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($matieres as $matiere)
                                    @php
                                        $_matiereId = $matiere->id;
                                        $_currentType = in_array($_matiereId, $general ?? []) ? 'general'
                                            : (in_array($_matiereId, $technique ?? []) ? 'technique' : 'none');
                                    @endphp
                                    <tr data-matiere-row="{{ $_matiereId }}" data-matiere-name="{{ strtolower($matiere->name ?? $matiere->nom ?? '') }}">
                                        <td>
                                            <div class="cm-matiere-cell">
                                                <div class="cm-matiere-icon"><i class="fas fa-book"></i></div>
                                                <div>
                                                    <div class="cm-matiere-name">{{ $matiere->name ?? $matiere->nom }}</div>
                                                    @if(!empty($matiere->code))
                                                        <div class="cm-matiere-meta">Code : {{ $matiere->code }}</div>
                                                    @endif
                                                    @if(!empty($matiere->sources))
                                                        <div class="cm-matiere-sources">
                                                            @foreach($matiere->sources as $source)
                                                                @if($source === 'classe')
                                                                    <span class="cm-source-badge cm-source-badge--classe"><i class="fas fa-school"></i> Classe</span>
                                                                @elseif($source === 'notes')
                                                                    <span class="cm-source-badge cm-source-badge--notes"><i class="fas fa-clipboard-list"></i> Notes</span>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="cm-type-picker" data-matiere-type-group="{{ $_matiereId }}">
                                                <input type="radio" name="matiere_type[{{ $_matiereId }}]"
                                                       id="general_{{ $_matiereId }}" value="general"
                                                       data-matiere-id="{{ $_matiereId }}"
                                                       {{ $_currentType === 'general' ? 'checked' : '' }}
                                                       @change="onTypeChange()">
                                                <label for="general_{{ $_matiereId }}"><i class="fas fa-graduation-cap"></i>Général</label>

                                                <input type="radio" name="matiere_type[{{ $_matiereId }}]"
                                                       id="technique_{{ $_matiereId }}" value="technique"
                                                       data-matiere-id="{{ $_matiereId }}"
                                                       {{ $_currentType === 'technique' ? 'checked' : '' }}
                                                       @change="onTypeChange()">
                                                <label for="technique_{{ $_matiereId }}"><i class="fas fa-tools"></i>Technique</label>

                                                <input type="radio" name="matiere_type[{{ $_matiereId }}]"
                                                       id="none_{{ $_matiereId }}" value="none"
                                                       data-matiere-id="{{ $_matiereId }}"
                                                       {{ $_currentType === 'none' ? 'checked' : '' }}
                                                       @change="onTypeChange()">
                                                <label for="none_{{ $_matiereId }}"><i class="fas fa-eye-slash"></i>Exclure</label>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="cm-row-status cm-row-status--general" data-status-pill="{{ $_matiereId }}" data-pill-type="general" style="display: {{ $_currentType === 'general' ? 'inline-flex' : 'none' }};">
                                                <i class="fas fa-graduation-cap"></i>Général
                                            </span>
                                            <span class="cm-row-status cm-row-status--technique" data-status-pill="{{ $_matiereId }}" data-pill-type="technique" style="display: {{ $_currentType === 'technique' ? 'inline-flex' : 'none' }};">
                                                <i class="fas fa-tools"></i>Technique
                                            </span>
                                            <span class="cm-row-status cm-row-status--excluded" data-status-pill="{{ $_matiereId }}" data-pill-type="none" style="display: {{ $_currentType === 'none' ? 'inline-flex' : 'none' }};">
                                                <i class="fas fa-eye-slash"></i>Exclue
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="cm-empty">
                                            <i class="fas fa-folder-open"></i>
                                            <div>Aucune matière disponible pour cette classe / cette période.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Footer actions --}}
                    <div class="cm-actions" style="margin-top: 1.5rem;">
                        <a href="{{ $_returnUrl }}" class="cm-btn cm-btn--ghost">
                            <i class="fas fa-times"></i><span>Annuler</span>
                        </a>
                        <div class="cm-actions-right">
                            <button type="submit" name="action" value="save" class="cm-btn cm-btn--primary">
                                <i class="fas fa-save"></i><span>Enregistrer</span>
                            </button>
                            <button type="submit" name="action" value="save_and_edit_profs" class="cm-btn cm-btn--success">
                                <i class="fas fa-user-edit"></i><span>Enregistrer + éditer profs</span>
                            </button>
                            <button type="submit" name="action" value="save_and_return" class="cm-btn cm-btn--ghost">
                                <i class="fas fa-arrow-left"></i><span>Enregistrer + retour</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- ═══════════════════════ MODAL COPY ═══════════════════════ --}}
        <div class="cm-modal-bg" :class="{ show: copyOpen }" x-cloak @click.self="copyOpen = false">
            <div class="cm-modal">
                <div class="cm-modal-header">
                    <h4><i class="fas fa-copy me-2"></i>Copier {{ $_periodeLabel }} → {{ $_otherPeriodeLabel }}</h4>
                    <button class="cm-modal-close" type="button" @click="copyOpen = false"><i class="fas fa-times"></i></button>
                </div>
                <div class="cm-modal-body">
                    <p style="color:#64748b;font-size:.88rem;margin-bottom:1rem;">Choisissez la stratégie de copie puis prévisualisez les changements avant validation.</p>

                    {{-- Mode picker --}}
                    <div class="cm-mode-picker">
                        <label :class="{ active: copyMode === 'override' }" @click="copyMode = 'override'; refreshPreview()">
                            <input type="radio" name="copy_mode" value="override" :checked="copyMode === 'override'">
                            <div class="cm-mode-content">
                                <p class="cm-mode-title"><i class="fas fa-arrows-rotate me-1"></i>Override (Remplacer)</p>
                                <p class="cm-mode-desc">Les classifications existantes en {{ $_otherPeriodeLabel }} seront remplacées</p>
                            </div>
                        </label>
                        <label :class="{ active: copyMode === 'merge' }" @click="copyMode = 'merge'; refreshPreview()">
                            <input type="radio" name="copy_mode" value="merge" :checked="copyMode === 'merge'">
                            <div class="cm-mode-content">
                                <p class="cm-mode-title"><i class="fas fa-shield-halved me-1"></i>Merge (Préserver)</p>
                                <p class="cm-mode-desc">Les classifications déjà saisies en {{ $_otherPeriodeLabel }} seront préservées</p>
                            </div>
                        </label>
                    </div>

                    {{-- Preview summary --}}
                    <div class="cm-preview-summary" x-show="preview">
                        <div class="cm-preview-pill cm-preview-pill--create"><i class="fas fa-plus"></i>Créer : <strong x-text="preview?.summary?.will_create ?? 0"></strong></div>
                        <div class="cm-preview-pill cm-preview-pill--update"><i class="fas fa-arrows-rotate"></i>Mettre à jour : <strong x-text="preview?.summary?.will_update ?? 0"></strong></div>
                        <div class="cm-preview-pill cm-preview-pill--skip"><i class="fas fa-circle-pause"></i>Skip : <strong x-text="preview?.summary?.will_skip ?? 0"></strong></div>
                    </div>

                    <div x-show="previewLoading" style="text-align:center;padding:1rem;color:#64748b;">
                        <i class="fas fa-circle-notch fa-spin"></i> Calcul de l'aperçu…
                    </div>

                    {{-- Details lists --}}
                    <template x-if="preview && preview.summary.will_create > 0">
                        <div style="margin-top:.85rem;">
                            <div style="font-weight:700;color:#047857;font-size:.85rem;margin-bottom:.35rem;"><i class="fas fa-plus"></i> Création ({{ '' }}<span x-text="preview.summary.will_create"></span>)</div>
                            <div class="cm-preview-list">
                                <template x-for="row in preview.preview.create" :key="'c'+row.matiere_id">
                                    <div class="cm-preview-list-item">
                                        <span class="name" x-text="row.matiere_name"></span>
                                        <span class="change">→ <strong x-text="row.source_type"></strong></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                    <template x-if="preview && preview.summary.will_update > 0">
                        <div style="margin-top:.85rem;">
                            <div style="font-weight:700;color:#b45309;font-size:.85rem;margin-bottom:.35rem;"><i class="fas fa-arrows-rotate"></i> Mise à jour</div>
                            <div class="cm-preview-list">
                                <template x-for="row in preview.preview.update" :key="'u'+row.matiere_id">
                                    <div class="cm-preview-list-item">
                                        <span class="name" x-text="row.matiere_name"></span>
                                        <span class="change"><span x-text="row.target_existing_type"></span> → <strong x-text="row.source_type"></strong></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                    <template x-if="preview && preview.summary.will_skip > 0">
                        <div style="margin-top:.85rem;">
                            <div style="font-weight:700;color:#475569;font-size:.85rem;margin-bottom:.35rem;"><i class="fas fa-circle-pause"></i> Skip (déjà configurées)</div>
                            <div class="cm-preview-list">
                                <template x-for="row in preview.preview.skip" :key="'s'+row.matiere_id">
                                    <div class="cm-preview-list-item">
                                        <span class="name" x-text="row.matiere_name"></span>
                                        <span class="change">déjà : <strong x-text="row.target_existing_type"></strong></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="cm-modal-footer">
                    <button type="button" class="cm-btn cm-btn--ghost" @click="copyOpen = false">Annuler</button>
                    <button type="button" class="cm-btn cm-btn--primary" :disabled="copySaving" @click="executeCopy()">
                        <i class="fas fa-check"></i><span x-text="copySaving ? 'Copie en cours…' : 'Valider la copie'"></span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function cmConfigMatieres() {
    return {
        counts: { total: 0, general: 0, technique: 0, excluded: 0, configured: 0 },
        completionStatus: 'unconfigured',
        completionStatusLabel: 'Non configuré',
        search: '',
        copyOpen: false,
        copyMode: 'override',
        copySaving: false,
        preview: null,
        previewLoading: false,

        init() {
            this.recountFromDOM();
        },

        recountFromDOM() {
            const allRows = document.querySelectorAll('[data-matiere-row]');
            this.counts.total = allRows.length;
            this.counts.general = document.querySelectorAll('.cm-type-picker input[value="general"]:checked').length;
            this.counts.technique = document.querySelectorAll('.cm-type-picker input[value="technique"]:checked').length;
            this.counts.excluded = document.querySelectorAll('.cm-type-picker input[value="none"]:checked').length;
            this.counts.configured = this.counts.general + this.counts.technique;
            // Status
            if (this.counts.total === 0) {
                this.completionStatus = 'unconfigured';
                this.completionStatusLabel = 'Aucune matière';
            } else if (this.counts.configured >= this.counts.total) {
                this.completionStatus = 'complete';
                this.completionStatusLabel = 'Complet';
            } else if (this.counts.configured === 0) {
                this.completionStatus = 'unconfigured';
                this.completionStatusLabel = 'Non configuré';
            } else {
                this.completionStatus = 'incomplete';
                this.completionStatusLabel = 'Incomplet';
            }
        },

        onTypeChange() {
            // Mise à jour pills statut
            document.querySelectorAll('[data-matiere-row]').forEach(row => {
                const matiereId = row.dataset.matiereRow;
                const checked = row.querySelector('.cm-type-picker input[type="radio"]:checked');
                const type = checked ? checked.value : null;
                row.querySelectorAll(`[data-status-pill="${matiereId}"]`).forEach(p => {
                    p.style.display = (p.dataset.pillType === type) ? 'inline-flex' : 'none';
                });
            });
            this.recountFromDOM();
        },

        setAll(type) {
            // ⚠️ Ne touche QUE les rows visibles (filter actif)
            const visibleRows = Array.from(document.querySelectorAll('[data-matiere-row]'))
                .filter(r => r.style.display !== 'none');
            visibleRows.forEach(row => {
                const radio = row.querySelector(`.cm-type-picker input[value="${type}"]`);
                if (radio) radio.checked = true;
            });
            this.onTypeChange();
        },

        filterMatieres() {
            const term = (this.search || '').toLowerCase().trim();
            document.querySelectorAll('[data-matiere-row]').forEach(row => {
                const matiereName = row.dataset.matiereName || '';
                row.style.display = (!term || matiereName.includes(term)) ? '' : 'none';
            });
        },

        onSubmit(ev) {
            if (this.counts.configured === 0 && this.counts.excluded === 0) {
                ev.preventDefault();
                alert('Veuillez choisir une classification (Général/Technique/Exclure) pour au moins une matière.');
            }
        },

        async openCopyModal() {
            this.copyOpen = true;
            await this.refreshPreview();
        },

        async refreshPreview() {
            this.previewLoading = true;
            this.preview = null;
            try {
                const fd = new FormData();
                fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                fd.append('classe_id', '{{ $_classeId }}');
                fd.append('annee_universitaire_id', '{{ $_anneeId }}');
                fd.append('source_periode', '{{ $_periode }}');
                fd.append('target_periode', '{{ $_otherPeriode }}');
                fd.append('mode', this.copyMode);
                fd.append('dry_run', '1');
                const res = await fetch('{{ route('esbtp.bulletins.config-matieres.copy') }}', {
                    method: 'POST', body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                this.preview = await res.json();
            } catch (e) {
                this.preview = null;
                alert('Impossible de calculer l\'aperçu : ' + e.message);
            } finally {
                this.previewLoading = false;
            }
        },

        async executeCopy() {
            this.copySaving = true;
            try {
                const fd = new FormData();
                fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                fd.append('classe_id', '{{ $_classeId }}');
                fd.append('annee_universitaire_id', '{{ $_anneeId }}');
                fd.append('source_periode', '{{ $_periode }}');
                fd.append('target_periode', '{{ $_otherPeriode }}');
                fd.append('mode', this.copyMode);
                const res = await fetch('{{ route('esbtp.bulletins.config-matieres.copy') }}', {
                    method: 'POST', body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    alert(`Copie effectuée : ${data.summary.created} créées, ${data.summary.updated} mises à jour, ${data.summary.skipped} skippées.`);
                    this.copyOpen = false;
                } else {
                    alert('Erreur : ' + (data.message || 'inconnue'));
                }
            } catch (e) {
                alert('Erreur réseau : ' + e.message);
            } finally {
                this.copySaving = false;
            }
        }
    };
}
</script>
@endsection
