@extends('layouts.app')

@section('title', 'Modification des moyennes - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@include('pdf.partials.theme')
<style>
/* =====================================================
   MOYENNES-PREVIEW - Design Premium KLASSCI
   Couleurs fidèles au système KLASSCI #0453cb → #5e91de
   ===================================================== */
:root {
    --k-primary:       #0453cb;
    --k-primary-light: #5e91de;
    --k-primary-dark:  #033a9a;
    --k-primary-muted: #e8f0fe;
    --k-success:       #10b981;
    --k-danger:        #ef4444;
    --k-warning:       #f59e0b;
    --k-gray-50:       #f8fafc;
    --k-gray-100:      #f1f5f9;
    --k-gray-200:      #e2e8f0;
    --k-gray-400:      #94a3b8;
    --k-gray-600:      #475569;
    --k-gray-800:      #1e293b;
    --k-radius:        12px;
    --k-radius-sm:     8px;
    --k-shadow:        0 4px 24px rgba(4, 83, 203, 0.10);
    --k-shadow-sm:     0 2px 8px rgba(4, 83, 203, 0.06);
}

/* ── Hero header ───────────────────────────────────── */
.mp-hero {
    background: linear-gradient(135deg, var(--k-primary) 0%, #1a6fd4 55%, var(--k-primary-light) 100%);
    border-radius: var(--k-radius);
    padding: 28px 32px;
    margin-bottom: 24px;
    color: #fff;
    position: relative;
    overflow: hidden;
}
.mp-hero::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 200px; height: 200px;
    background: rgba(255,255,255,0.06);
    border-radius: 50%;
}
.mp-hero::after {
    content: '';
    position: absolute;
    bottom: -60px; left: -20px;
    width: 160px; height: 160px;
    background: rgba(255,255,255,0.04);
    border-radius: 50%;
}
.mp-hero-inner {
    position: relative; z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
}
.mp-hero-left {}
.mp-hero-eyebrow {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.7);
    margin-bottom: 4px;
}
.mp-hero-title {
    font-size: 1.6rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 4px;
    line-height: 1.25;
}
.mp-hero-sub {
    font-size: 0.9rem;
    color: rgba(255,255,255,0.8);
    margin: 0;
}
.mp-hero-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}
.mp-btn-ghost {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border: 1.5px solid rgba(255,255,255,0.4);
    border-radius: var(--k-radius-sm);
    color: #fff;
    font-size: 0.825rem;
    font-weight: 500;
    background: rgba(255,255,255,0.1);
    text-decoration: none;
    transition: background 0.2s, border-color 0.2s;
    cursor: pointer;
}
.mp-btn-ghost:hover {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.7);
    color: #fff;
}
.mp-btn-ghost-danger {
    border-color: rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.08);
}
.mp-btn-ghost-danger:hover {
    background: rgba(239,68,68,0.35);
    border-color: rgba(239,68,68,0.7);
}

/* ── KPI Grid ──────────────────────────────────────── */
.mp-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
@media (max-width: 992px) {
    .mp-kpi-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 576px) {
    .mp-kpi-grid { grid-template-columns: 1fr; }
}
.mp-kpi-card {
    background: #fff;
    border: 1px solid var(--k-gray-200);
    border-radius: var(--k-radius);
    padding: 20px 20px 16px;
    box-shadow: var(--k-shadow-sm);
    transition: box-shadow 0.2s, transform 0.2s;
}
.mp-kpi-card:hover {
    box-shadow: var(--k-shadow);
    transform: translateY(-2px);
}
.mp-kpi-icon {
    width: 38px; height: 38px;
    border-radius: var(--k-radius-sm);
    background: var(--k-primary-muted);
    display: flex; align-items: center; justify-content: center;
    color: var(--k-primary);
    font-size: 0.9rem;
    margin-bottom: 12px;
}
.mp-kpi-label {
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--k-gray-400);
    margin-bottom: 4px;
}
.mp-kpi-value {
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--k-gray-800);
    line-height: 1.2;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.mp-kpi-sub {
    font-size: 0.775rem;
    color: var(--k-gray-400);
}

/* ── Info banner ────────────────────────────────────── */
.mp-info-banner {
    background: var(--k-primary-muted);
    border: 1px solid rgba(4,83,203,0.15);
    border-left: 4px solid var(--k-primary);
    border-radius: var(--k-radius-sm);
    padding: 14px 18px;
    margin-bottom: 24px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
.mp-info-banner-icon {
    color: var(--k-primary);
    font-size: 1.05rem;
    margin-top: 2px;
    flex-shrink: 0;
}
.mp-info-banner-body { flex: 1; }
.mp-info-banner-body p {
    margin: 0 0 4px;
    font-size: 0.85rem;
    color: var(--k-gray-600);
    line-height: 1.5;
}
.mp-info-banner-body p:last-child { margin-bottom: 0; }
.mp-info-banner-body a {
    color: var(--k-primary);
    font-weight: 600;
    text-decoration: none;
}
.mp-info-banner-body a:hover { text-decoration: underline; }

/* ── Main card ──────────────────────────────────────── */
.mp-card {
    background: #fff;
    border: 1px solid var(--k-gray-200);
    border-radius: var(--k-radius);
    box-shadow: var(--k-shadow-sm);
    overflow: hidden;
    margin-bottom: 24px;
}
.mp-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px;
    border-bottom: 1px solid var(--k-gray-200);
    background: var(--k-gray-50);
    flex-wrap: wrap;
    gap: 12px;
}
.mp-card-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--k-gray-800);
    display: flex;
    align-items: center;
    gap: 8px;
}
.mp-card-title i { color: var(--k-primary); }
.mp-card-subtitle {
    font-size: 0.8rem;
    color: var(--k-gray-400);
    margin-top: 1px;
}
.mp-card-body { padding: 0; }

/* ── Table ──────────────────────────────────────────── */
.mp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}
.mp-table thead tr {
    background: var(--k-gray-50);
    border-bottom: 2px solid var(--k-gray-200);
}
.mp-table thead th {
    padding: 12px 14px;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--k-gray-400);
    white-space: nowrap;
}
.mp-table tbody tr {
    border-bottom: 1px solid var(--k-gray-100);
    transition: background 0.15s;
}
.mp-table tbody tr:last-child { border-bottom: none; }
.mp-table tbody tr:hover { background: var(--k-gray-50); }
.mp-table td { padding: 12px 14px; vertical-align: middle; }

/* Numéro de ligne */
.mp-row-num {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: var(--k-primary-muted);
    color: var(--k-primary);
    font-size: 0.75rem;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
}

/* Cellule matière */
.mp-matiere-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}
.mp-matiere-icon {
    width: 32px; height: 32px;
    border-radius: var(--k-radius-sm);
    background: var(--k-primary-muted);
    display: flex; align-items: center; justify-content: center;
    color: var(--k-primary);
    font-size: 0.75rem;
    flex-shrink: 0;
}
.mp-matiere-name {
    font-weight: 600;
    color: var(--k-gray-800);
    line-height: 1.3;
}
.mp-matiere-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 3px;
}
.mp-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2px 7px;
    border-radius: 999px;
    font-size: 0.65rem;
    font-weight: 600;
}
.mp-badge-auto   { background: #d1fae5; color: #065f46; }
.mp-badge-manuel { background: #fef3c7; color: #92400e; }
.mp-badge-code   { background: var(--k-gray-100); color: var(--k-gray-600); font-family: monospace; }

/* Note badges */
.mp-note-badge {
    display: inline-flex;
    align-items: center;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 0.82rem;
    font-weight: 700;
    white-space: nowrap;
}
.mp-note-pass    { background: #d1fae5; color: #065f46; }
.mp-note-fail    { background: #fee2e2; color: #991b1b; }
.mp-note-empty   { font-style: italic; color: var(--k-gray-400); font-size: 0.8rem; }

/* Inputs */
.mp-input {
    border: 1.5px solid var(--k-gray-200);
    border-radius: var(--k-radius-sm);
    padding: 6px 10px;
    font-size: 0.875rem;
    color: var(--k-gray-800);
    background: #fff;
    width: 100%;
    transition: border-color 0.2s, box-shadow 0.2s;
    text-align: center;
}
.mp-input:focus {
    outline: none;
    border-color: var(--k-primary);
    box-shadow: 0 0 0 3px rgba(4,83,203,0.1);
}
.mp-input-appreciation {
    text-align: left;
}
.mp-coeff-group {
    display: flex;
    align-items: stretch;
    gap: 0;
    border: 1.5px solid var(--k-gray-200);
    border-radius: var(--k-radius-sm);
    overflow: hidden;
    transition: border-color 0.2s;
}
.mp-coeff-group:focus-within {
    border-color: var(--k-primary);
    box-shadow: 0 0 0 3px rgba(4,83,203,0.1);
}
.mp-coeff-input {
    border: none;
    padding: 6px 10px;
    font-size: 0.875rem;
    color: var(--k-gray-800);
    background: #fff;
    width: 70px;
    text-align: center;
    outline: none;
    -moz-appearance: textfield;
}
.mp-coeff-input::-webkit-outer-spin-button,
.mp-coeff-input::-webkit-inner-spin-button { -webkit-appearance: none; }
.mp-coeff-sync {
    padding: 6px 10px;
    background: var(--k-gray-50);
    border: none;
    border-left: 1.5px solid var(--k-gray-200);
    color: var(--k-gray-400);
    font-size: 0.8rem;
    cursor: pointer;
    transition: background 0.15s, color 0.15s;
}
.mp-coeff-sync:hover {
    background: var(--k-primary-muted);
    color: var(--k-primary);
}
.coeff-info {
    margin-top: 5px;
    border-radius: 6px;
    overflow: hidden;
}

/* Action bouton supprimer */
.mp-btn-del {
    width: 32px; height: 32px;
    border: 1.5px solid #fecaca;
    border-radius: var(--k-radius-sm);
    background: #fff;
    color: #ef4444;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s;
}
.mp-btn-del:hover { background: #fee2e2; border-color: #ef4444; }

/* ── Section ajout matière ──────────────────────────── */
.mp-add-section {
    padding: 20px 24px;
    border-top: 1px solid var(--k-gray-200);
    background: var(--k-gray-50);
}
.mp-add-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}
.mp-add-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--k-gray-600);
    display: flex;
    align-items: center;
    gap: 8px;
}
.mp-add-title i { color: var(--k-primary); }
.mp-btn-add {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    background: var(--k-primary-muted);
    border: 1.5px solid rgba(4,83,203,0.2);
    border-radius: var(--k-radius-sm);
    color: var(--k-primary);
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, border-color 0.2s;
}
.mp-btn-add:hover {
    background: #ccdcfa;
    border-color: var(--k-primary);
}

/* Ligne ajout dynamique */
.mp-new-matiere-row {
    background: #fff;
    border: 1.5px solid var(--k-gray-200);
    border-radius: var(--k-radius-sm);
    padding: 16px;
    margin-bottom: 10px;
    display: grid;
    grid-template-columns: 3fr 1fr 1fr 2fr auto;
    gap: 12px;
    align-items: end;
}
@media (max-width: 768px) {
    .mp-new-matiere-row { grid-template-columns: 1fr 1fr; }
}
.mp-new-matiere-row label {
    display: block;
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--k-gray-400);
    margin-bottom: 5px;
}

/* ── Footer actions ─────────────────────────────────── */
.mp-footer {
    padding: 20px 24px;
    border-top: 1px solid var(--k-gray-200);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    background: #fff;
}
.mp-btn-cancel {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 18px;
    border: 1.5px solid var(--k-gray-200);
    border-radius: var(--k-radius-sm);
    color: var(--k-gray-600);
    font-size: 0.875rem;
    font-weight: 500;
    background: #fff;
    text-decoration: none;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
}
.mp-btn-cancel:hover {
    border-color: var(--k-gray-400);
    background: var(--k-gray-50);
    color: var(--k-gray-800);
}
.mp-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 20px;
    background: var(--k-primary);
    border: none;
    border-radius: var(--k-radius-sm);
    color: #fff;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, box-shadow 0.2s;
    text-decoration: none;
}
.mp-btn-primary:hover {
    background: var(--k-primary-dark);
    box-shadow: 0 4px 12px rgba(4,83,203,0.3);
    color: #fff;
}
.mp-btn-pdf {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 20px;
    background: #ef4444;
    border: none;
    border-radius: var(--k-radius-sm);
    color: #fff;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, box-shadow 0.2s;
    text-decoration: none;
}
.mp-btn-pdf:hover {
    background: #dc2626;
    box-shadow: 0 4px 12px rgba(239,68,68,0.3);
    color: #fff;
}

/* ── Empty state ────────────────────────────────────── */
.mp-empty {
    padding: 56px 24px;
    text-align: center;
}
.mp-empty-icon {
    width: 56px; height: 56px;
    border-radius: 50%;
    background: var(--k-primary-muted);
    display: flex; align-items: center; justify-content: center;
    color: var(--k-primary);
    font-size: 1.3rem;
    margin: 0 auto 16px;
}
.mp-empty h5 { font-weight: 700; color: var(--k-gray-800); }
.mp-empty p  { font-size: 0.875rem; color: var(--k-gray-400); max-width: 380px; margin: 0 auto; }

/* ── Alerts ─────────────────────────────────────────── */
.mp-alert {
    border-radius: var(--k-radius-sm);
    padding: 12px 16px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 0.875rem;
}
.mp-alert-success { background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; }
.mp-alert-danger  { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
.mp-alert button.btn-close { margin-left: auto; }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- ██ HERO HEADER ██ --}}
        <div class="mp-hero">
            <div class="mp-hero-inner">
                <div class="mp-hero-left">
                    <div class="mp-hero-eyebrow">
                        <i class="fas fa-graduation-cap me-1"></i> Résultats académiques
                    </div>
                    <h1 class="mp-hero-title">
                        <i class="fas fa-edit me-2" style="opacity:.85"></i>Modification des moyennes
                    </h1>
                    <p class="mp-hero-sub">
                        {{ $etudiant->prenoms }} {{ $etudiant->nom }}
                        &nbsp;·&nbsp;
                        {{ $classe->name }}
                        &nbsp;·&nbsp;
                        @if($periode == 'semestre1') 1er Semestre
                        @elseif($periode == 'semestre2') 2e Semestre
                        @else Année complète
                        @endif
                    </p>
                </div>
                <div class="mp-hero-actions">
                    @if(auth()->check() && auth()->user()->hasAnyPermission(['admin.access', 'identity.school_manager', 'identity.coordinate']))
                    <a href="{{ route('esbtp.classes.matieres', ['classe' => $classe->id]) }}" class="mp-btn-ghost">
                        <i class="fas fa-sliders-h"></i>
                        <span class="d-none d-sm-inline">Matières de la classe</span>
                    </a>
                    @endif
                    @role('superAdmin')
                    <a href="{{ route('esbtp.matieres.index') }}" class="mp-btn-ghost">
                        <i class="fas fa-cog"></i>
                        <span class="d-none d-md-inline">Gestion globale</span>
                    </a>
                    @endrole
                    <a href="{{ route('esbtp.resultats.etudiant', $etudiant) }}?classe_id={{ $classe->id }}&periode={{ $periode == 'semestre1' ? '1' : ($periode == 'semestre2' ? '2' : $periode) }}&annee_universitaire_id={{ $anneeUniversitaire->id }}"
                       class="mp-btn-ghost mp-btn-ghost-danger">
                        <i class="fas fa-times"></i>
                        <span class="d-none d-sm-inline">Annuler</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- ██ KPI CARDS ██ --}}
        <div class="mp-kpi-grid">
            <div class="mp-kpi-card">
                <div class="mp-kpi-icon"><i class="fas fa-user"></i></div>
                <div class="mp-kpi-label">Étudiant</div>
                <div class="mp-kpi-value">{{ $etudiant->nom }}</div>
                <div class="mp-kpi-sub">{{ $etudiant->prenoms }}</div>
            </div>
            <div class="mp-kpi-card">
                <div class="mp-kpi-icon"><i class="fas fa-users"></i></div>
                <div class="mp-kpi-label">Classe</div>
                <div class="mp-kpi-value">{{ $classe->name }}</div>
                <div class="mp-kpi-sub">{{ $anneeUniversitaire->annee_debut }}&ndash;{{ $anneeUniversitaire->annee_fin }}</div>
            </div>
            <div class="mp-kpi-card">
                <div class="mp-kpi-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="mp-kpi-label">Période</div>
                <div class="mp-kpi-value">
                    @if($periode == 'semestre1') S1
                    @elseif($periode == 'semestre2') S2
                    @else Annuel
                    @endif
                </div>
                <div class="mp-kpi-sub">
                    @if($periode == 'semestre1') 1er Semestre
                    @elseif($periode == 'semestre2') 2e Semestre
                    @else Année complète
                    @endif
                </div>
            </div>
            <div class="mp-kpi-card">
                <div class="mp-kpi-icon"><i class="fas fa-book"></i></div>
                <div class="mp-kpi-label">Matières</div>
                <div class="mp-kpi-value" style="color: var(--k-primary);">{{ count($resultatsData) }}</div>
                <div class="mp-kpi-sub">à modifier</div>
            </div>
        </div>

        {{-- ██ ALERTS DE SESSION ██ --}}
        @if(session('success'))
        <div class="mp-alert mp-alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle" style="margin-top:2px;flex-shrink:0"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="mp-alert mp-alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle" style="margin-top:2px;flex-shrink:0"></i>
            <span>{{ session('error') }}</span>
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
        @endif
        @if($errors->any())
        <div class="mp-alert mp-alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle" style="margin-top:2px;flex-shrink:0"></i>
            <div>
                <strong>Erreurs de validation&nbsp;:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
        @endif

        {{-- ██ INFO BANNER ██ --}}
        <div class="mp-info-banner">
            <i class="fas fa-info-circle mp-info-banner-icon"></i>
            <div class="mp-info-banner-body">
                <p><strong>Attention&nbsp;:</strong> La modification des moyennes a un impact direct sur les bulletins générés. Les valeurs doivent être comprises entre <strong>0</strong> et <strong>20</strong>.</p>
                <p>
                    Les coefficients sont gérés par filière, niveau et année.
                    &nbsp;·&nbsp;
                    <a href="{{ route('esbtp.resultats.etudiant', $etudiant) }}?classe_id={{ $classe->id }}&periode={{ $periode == 'semestre1' ? '1' : ($periode == 'semestre2' ? '2' : $periode) }}&annee_universitaire_id={{ $anneeUniversitaire->id }}&open_coeff_modal=1">
                        Configurer les coefficients
                    </a>
                </p>
            </div>
        </div>

        {{-- ██ FORMULAIRE PRINCIPAL ██ --}}
        <div class="mp-card">
            <div class="mp-card-header">
                <div>
                    <div class="mp-card-title">
                        <i class="fas fa-list-ul"></i>
                        Moyennes par matière
                    </div>
                    <div class="mp-card-subtitle">Modifiez les moyennes pour chaque matière</div>
                </div>
            </div>

            <div class="mp-card-body">
                <form method="POST" action="{{ route('esbtp.bulletins.moyennes-update') }}">
                    @csrf
                    <input type="hidden" name="etudiant_id" value="{{ $etudiant->id }}">
                    <input type="hidden" name="classe_id" value="{{ $classe->id }}">
                    <input type="hidden" name="periode" value="{{ $periode }}">
                    <input type="hidden" name="annee_universitaire_id" value="{{ $anneeUniversitaire->id }}">

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="mp-table">
                            <thead>
                                <tr>
                                    <th style="width:44px; text-align:center">#</th>
                                    <th style="min-width:200px">Matière</th>
                                    <th style="width:140px; text-align:center">Moy. calculée</th>
                                    <th style="width:140px; text-align:center">Moy. à enregistrer</th>
                                    <th style="width:130px; text-align:center">Coefficient</th>
                                    <th style="min-width:160px">Appréciation</th>
                                    <th style="width:60px; text-align:center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i = 1; @endphp
                                @forelse($resultatsData as $matiereId => $resultat)
                                    @php
                                        $calculatedMoyenne = isset($notesByMatiere[$matiereId]) ? $notesByMatiere[$matiereId]['moyenne'] : null;
                                        $existingMoyenne   = $resultat['moyenne'] ?? $calculatedMoyenne;
                                        $source            = $resultat['source'] ?? 'manuelle';
                                    @endphp
                                    <tr>
                                        {{-- # --}}
                                        <td style="text-align:center">
                                            <div class="mp-row-num mx-auto">{{ $i++ }}</div>
                                        </td>

                                        {{-- Matière --}}
                                        <td>
                                            <div class="mp-matiere-cell">
                                                <div class="mp-matiere-icon">
                                                    <i class="fas fa-book-open"></i>
                                                </div>
                                                <div>
                                                    <div class="mp-matiere-name">{{ $resultat['matiere']->name }}</div>
                                                    <div class="mp-matiere-meta">
                                                        @if($resultat['matiere']->code)
                                                            <span class="mp-badge mp-badge-code">{{ $resultat['matiere']->code }}</span>
                                                        @endif
                                                        @if($source == 'calculee')
                                                            <span class="mp-badge mp-badge-auto">
                                                                <i class="fas fa-calculator"></i> Auto
                                                            </span>
                                                        @else
                                                            <span class="mp-badge mp-badge-manuel">
                                                                <i class="fas fa-pen"></i> Manuel
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Moy. calculée --}}
                                        <td style="text-align:center">
                                            @if($calculatedMoyenne !== null)
                                                <span class="mp-note-badge {{ $calculatedMoyenne >= 10 ? 'mp-note-pass' : 'mp-note-fail' }}">
                                                    {{ number_format($calculatedMoyenne, 2) }}/20
                                                </span>
                                            @else
                                                <span class="mp-note-empty">
                                                    <i class="fas fa-minus me-1"></i>Aucune éval.
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Moy. à enregistrer --}}
                                        <td>
                                            <input type="hidden" name="resultats[{{ $matiereId }}][matiere_id]" value="{{ $matiereId }}">
                                            <input type="hidden" name="resultats[{{ $matiereId }}][id]" value="{{ $resultat['id'] }}">
                                            <input type="number"
                                                   class="mp-input"
                                                   name="resultats[{{ $matiereId }}][moyenne]"
                                                   value="{{ old('resultats.' . $matiereId . '.moyenne', $existingMoyenne ? number_format($existingMoyenne, 2) : '') }}"
                                                   min="0" max="20" step="0.01"
                                                   placeholder="0.00"
                                                   required>
                                        </td>

                                        {{-- Coefficient --}}
                                        <td>
                                            <div class="mp-coeff-group">
                                                <input type="number"
                                                       class="mp-coeff-input coefficient-input"
                                                       name="resultats[{{ $matiereId }}][coefficient]"
                                                       value="{{ old('resultats.' . $matiereId . '.coefficient', $resultat['coefficient'] ?? 1) }}"
                                                       min="0" max="20" step="0.5"
                                                       data-matiere-id="{{ $matiereId }}"
                                                       placeholder="1">
                                                <button type="button"
                                                        class="mp-coeff-sync sync-coefficient-btn"
                                                        title="Synchroniser le coefficient configuré"
                                                        data-matiere-id="{{ $matiereId }}">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </div>
                                            <div id="coeff-info-{{ $matiereId }}" class="coeff-info" style="display:none;"></div>
                                        </td>

                                        {{-- Appréciation --}}
                                        <td>
                                            <input type="text"
                                                   class="mp-input mp-input-appreciation"
                                                   name="resultats[{{ $matiereId }}][appreciation]"
                                                   value="{{ old('resultats.' . $matiereId . '.appreciation', $resultat['appreciation'] ?? '') }}"
                                                   placeholder="Optionnel">
                                        </td>

                                        {{-- Action --}}
                                        <td style="text-align:center">
                                            @if($source == 'calculee')
                                                <span title="Moyenne calculée automatiquement" style="color:var(--k-gray-400);font-size:.8rem;">
                                                    <i class="fas fa-lock"></i>
                                                </span>
                                            @else
                                                <button type="button"
                                                        class="mp-btn-del"
                                                        onclick="supprimerMoyenneManuelle('{{ $matiereId }}')"
                                                        title="Supprimer cette moyenne manuelle">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <div class="mp-empty">
                                                <div class="mp-empty-icon">
                                                    <i class="fas fa-folder-open"></i>
                                                </div>
                                                <h5>Aucune matière trouvée</h5>
                                                <p>Cette classe n'a pas de matières associées ou l'étudiant n'a pas d'évaluations.<br>
                                                   Utilisez le bouton "Ajouter une matière" ci-dessous.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Section ajout matières supplémentaires --}}
                    <div class="mp-add-section">
                        <div class="mp-add-header">
                            <div class="mp-add-title">
                                <i class="fas fa-plus-circle"></i>
                                Matières supplémentaires
                            </div>
                            <button type="button" class="mp-btn-add" onclick="ajouterMatiere()">
                                <i class="fas fa-plus"></i>Ajouter une matière
                            </button>
                        </div>
                        <div id="matieres-supplementaires"></div>
                    </div>

                    {{-- Footer actions --}}
                    <div class="mp-footer">
                        <a href="{{ route('esbtp.resultats.etudiant', $etudiant) }}?classe_id={{ $classe->id }}&periode={{ $periode == 'semestre1' ? '1' : ($periode == 'semestre2' ? '2' : $periode) }}&annee_universitaire_id={{ $anneeUniversitaire->id }}"
                           class="mp-btn-cancel">
                            <i class="fas fa-arrow-left"></i>Annuler
                        </a>
                        <div style="display:flex;gap:10px;flex-wrap:wrap">
                            <button type="submit" class="mp-btn-primary">
                                <i class="fas fa-save"></i>Enregistrer les modifications
                            </button>
                            @php $_mpPdfParams = ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $anneeUniversitaire->id]; @endphp
                            <a href="{{ route('esbtp.bulletins.pdf-params-preview', $_mpPdfParams) }}"
                               class="mp-btn-pdf"
                               style="background:#fff;color:#0453cb;border:1px solid #0453cb;"
                               target="_blank"
                               title="Aperçu du bulletin PDF dans un nouvel onglet">
                                <i class="fas fa-eye"></i>Aperçu
                            </a>
                            <a href="{{ route('esbtp.bulletins.pdf-params', $_mpPdfParams) }}"
                               class="mp-btn-pdf"
                               target="_blank">
                                <i class="fas fa-file-pdf"></i>Générer le bulletin
                            </a>
                        </div>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>
@push('scripts')
<script>
    // ── Tooltips Bootstrap ───────────────────────────────
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipEls = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipEls.map(el => new bootstrap.Tooltip(el));
    });

    // ── Compteur matières dynamiques ─────────────────────
    let matiereCounter = 1000;

    function ajouterMatiere() {
        const container = document.getElementById('matieres-supplementaires');
        const matiereId = 'nouvelle_' + matiereCounter;

        const html = `
            <div class="mp-new-matiere-row" id="matiere-${matiereId}" style="opacity:0;transform:translateY(-8px);transition:all .3s ease">
                <div>
                    <label>Matière</label>
                    <select class="mp-input matiere-select" name="nouvelles_matieres[${matiereId}][matiere_type]" onchange="toggleMatiereInput('${matiereId}')" required style="text-align:left">
                        <option value="">-- Sélectionner --</option>
                        <option value="existante">Matière existante</option>
                        <option value="nouvelle">Créer nouvelle matière</option>
                    </select>
                    <input type="hidden" name="nouvelles_matieres[${matiereId}][id]" value="${matiereId}">
                    <select class="mp-input mt-2 d-none" id="existing-select-${matiereId}" name="nouvelles_matieres[${matiereId}][matiere_existante_id]" style="text-align:left">
                        <option value="">-- Choisir une matière --</option>
                    </select>
                    <input type="text" class="mp-input mt-2 d-none" id="new-input-${matiereId}" name="nouvelles_matieres[${matiereId}][nom_nouvelle]" placeholder="Ex: Mathématiques Avancées">
                </div>
                <div>
                    <label>Moyenne</label>
                    <input type="number" class="mp-input" name="nouvelles_matieres[${matiereId}][moyenne]" min="0" max="20" step="0.01" placeholder="0.00" required>
                </div>
                <div>
                    <label>Coefficient</label>
                    <input type="number" class="mp-input" name="nouvelles_matieres[${matiereId}][coefficient]" min="0" step="0.5" value="1" required>
                </div>
                <div>
                    <label>Appréciation</label>
                    <input type="text" class="mp-input mp-input-appreciation" name="nouvelles_matieres[${matiereId}][appreciation]" placeholder="Optionnel">
                </div>
                <div>
                    <label>&nbsp;</label>
                    <button type="button" class="mp-btn-del" onclick="supprimerMatiere('${matiereId}')">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', html);
        matiereCounter++;

        const el = document.getElementById(`matiere-${matiereId}`);
        requestAnimationFrame(() => {
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        });
    }

    function supprimerMatiere(matiereId) {
        const el = document.getElementById(`matiere-${matiereId}`);
        if (!el) return;
        el.style.opacity = '0';
        el.style.transform = 'translateY(-8px)';
        setTimeout(() => el.remove(), 300);
    }

    function toggleMatiereInput(matiereId) {
        const select   = document.querySelector(`[name="nouvelles_matieres[${matiereId}][matiere_type]"]`);
        const existing = document.getElementById(`existing-select-${matiereId}`);
        const newInput = document.getElementById(`new-input-${matiereId}`);

        existing.classList.add('d-none');
        newInput.classList.add('d-none');

        if (select.value === 'existante') {
            existing.classList.remove('d-none');
            existing.required = true;
            newInput.required = false;
            if (existing.children.length <= 1) chargerMatieresExistantes(matiereId);
        } else if (select.value === 'nouvelle') {
            newInput.classList.remove('d-none');
            newInput.required = true;
            existing.required = false;
        }
    }

    function chargerMatieresExistantes(matiereId) {
        const select = document.getElementById(`existing-select-${matiereId}`);
        select.innerHTML = '<option value="">Chargement...</option>';

        fetch('/api/esbtp/matieres/list')
            .then(r => r.json())
            .then(data => {
                select.innerHTML = '<option value="">-- Choisir une matière --</option>';
                data.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m.id;
                    opt.textContent = m.name + (m.code ? ` (${m.code})` : '');
                    select.appendChild(opt);
                });
            })
            .catch(() => {
                select.innerHTML = '<option value="">Erreur de chargement</option>';
            });
    }

    // ── Supprimer moyenne manuelle ───────────────────────
    function supprimerMoyenneManuelle(matiereId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette moyenne manuelle ?\n\nCette action est irréversible.')) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("esbtp.bulletins.moyennes-delete") }}';
        form.style.display = 'none';

        const fields = {
            '_token':               '{{ csrf_token() }}',
            '_method':              'DELETE',
            'etudiant_id':          '{{ $etudiant->id }}',
            'classe_id':            '{{ $classe->id }}',
            'matiere_id':           matiereId,
            'periode':              '{{ $periode }}',
            'annee_universitaire_id': '{{ $anneeUniversitaire->id }}'
        };

        for (const [k, v] of Object.entries(fields)) {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = k; inp.value = v;
            form.appendChild(inp);
        }

        document.body.appendChild(form);
        form.submit();
    }

    // ── Gestion des coefficients AJAX ───────────────────
    const getCoefficientUrl = '{{ route("esbtp.resultats.get-matiere-coefficient") }}';
    const classeId = '{{ $classe->id }}';

    function checkMatiereCoefficient(matiereId) {
        const coeffInput  = document.querySelector(`[data-matiere-id="${matiereId}"]`);
        const coeffInfoEl = document.getElementById(`coeff-info-${matiereId}`);
        if (!coeffInput || !coeffInfoEl) return;

        coeffInfoEl.style.display = 'block';
        coeffInfoEl.innerHTML = `
            <div style="background:var(--k-primary-muted);border-left:3px solid var(--k-primary);padding:5px 10px;border-radius:4px;font-size:.75rem;color:var(--k-primary)">
                <i class="fas fa-spinner fa-spin me-1"></i>Vérification...
            </div>`;

        fetch(`${getCoefficientUrl}?matiere_id=${matiereId}&classe_id=${classeId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                coeffInfoEl.innerHTML = `
                    <div style="background:#fee2e2;border-left:3px solid #ef4444;padding:5px 10px;border-radius:4px;font-size:.75rem;color:#991b1b">
                        <i class="fas fa-times-circle me-1"></i>${data.message}
                    </div>`;
                return;
            }
            if (data.is_configured) {
                coeffInfoEl.innerHTML = `
                    <div style="background:#d1fae5;border-left:3px solid #10b981;padding:5px 10px;border-radius:4px;font-size:.75rem;color:#065f46">
                        <i class="fas fa-check-circle me-1"></i>Configuré&nbsp;: <strong>${data.coefficient}</strong>
                    </div>`;
                if (!coeffInput.value || parseFloat(coeffInput.value) === 1)
                    coeffInput.value = data.coefficient;
            } else {
                coeffInfoEl.innerHTML = `
                    <div style="background:#fef3c7;border-left:3px solid #f59e0b;padding:5px 10px;border-radius:4px;font-size:.75rem;color:#92400e">
                        <i class="fas fa-exclamation-triangle me-1"></i>Défaut&nbsp;: <strong>${data.coefficient}</strong>
                    </div>`;
            }
        })
        .catch(() => { coeffInfoEl.style.display = 'none'; });
    }

    function syncCoefficient(matiereId) {
        const coeffInput = document.querySelector(`[data-matiere-id="${matiereId}"]`);
        const syncBtn    = document.querySelector(`.sync-coefficient-btn[data-matiere-id="${matiereId}"]`);
        if (!coeffInput || !syncBtn) return;

        syncBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        syncBtn.disabled = true;

        fetch(`${getCoefficientUrl}?matiere_id=${matiereId}&classe_id=${classeId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                coeffInput.value = data.coefficient;
                syncBtn.innerHTML = '<i class="fas fa-check" style="color:var(--k-success)"></i>';
                checkMatiereCoefficient(matiereId);
            } else {
                syncBtn.innerHTML = '<i class="fas fa-exclamation-triangle" style="color:var(--k-warning)"></i>';
                alert('Erreur: ' + data.message);
            }
            setTimeout(() => { syncBtn.innerHTML = '<i class="fas fa-sync-alt"></i>'; syncBtn.disabled = false; }, 1500);
        })
        .catch(() => {
            syncBtn.innerHTML = '<i class="fas fa-times" style="color:var(--k-danger)"></i>';
            setTimeout(() => { syncBtn.innerHTML = '<i class="fas fa-sync-alt"></i>'; syncBtn.disabled = false; }, 1500);
        });
    }

    // ── Init ─────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function() {
        // Vérifier tous les coefficients
        document.querySelectorAll('.coefficient-input').forEach(input => {
            const id = input.getAttribute('data-matiere-id');
            if (id) checkMatiereCoefficient(id);
        });

        // Boutons sync
        document.querySelectorAll('.sync-coefficient-btn').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                syncCoefficient(btn.getAttribute('data-matiere-id'));
            });
        });

        // Rechargement info coeff sur changement
        document.querySelectorAll('.coefficient-input').forEach(input => {
            input.addEventListener('change', () => checkMatiereCoefficient(input.getAttribute('data-matiere-id')));
        });
    });
</script>
@endpush
@endsection
