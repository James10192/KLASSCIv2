@extends('layouts.app')

@section('title', 'Suivi des Paiements - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="{{ asset('css/cursor-fix.css') }}">
<style>
    /* ═══════════════════════════════════════════════
       Namespace pi-* (paiements.index premium)
       ═══════════════════════════════════════════════ */
    .pi-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 1.75rem 2rem 1.5rem;
        color: #fff;
        margin-bottom: 1.25rem;
        position: relative;
        /* overflow visible : dropdowns (Exporter) ne sont pas clipes par le hero */
    }
    .pi-hero-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .pi-hero-left {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        flex: 1;
        min-width: 0;
    }
    .pi-hero-icon {
        width: 52px; height: 52px;
        border-radius: 14px;
        background: rgba(255,255,255,.12);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; flex-shrink: 0; color: #fff;
    }
    .pi-hero h1 {
        font-size: 1.45rem;
        font-weight: 700;
        color: #fff;
        margin: 0 0 .2rem;
        letter-spacing: -.01em;
    }
    .pi-hero p {
        color: rgba(255,255,255,.72);
        font-size: .88rem;
        margin: 0 0 .55rem;
    }
    .pi-hero-chips {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
    }
    .pi-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .25rem .65rem;
        background: rgba(255,255,255,.14);
        border: 1px solid rgba(255,255,255,.2);
        border-radius: 99px;
        font-size: .74rem;
        font-weight: 600;
        color: rgba(255,255,255,.94);
    }
    .pi-chip i { font-size: .7rem; }
    .pi-chip .pi-chip-btn {
        background: none;
        border: none;
        color: inherit;
        padding: 0 0 0 .25rem;
        margin-left: .1rem;
        cursor: pointer;
        opacity: .75;
        transition: opacity .2s;
        font-size: .72rem;
    }
    .pi-chip .pi-chip-btn:hover { opacity: 1; }

    .pi-hero-actions {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
        align-items: center;
    }
    .pi-btn {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .5rem 1rem;
        font-size: .82rem;
        font-weight: 600;
        border-radius: 10px;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all .2s ease;
        text-decoration: none;
        white-space: nowrap;
        font-family: inherit;
    }
    .pi-btn--glass {
        background: rgba(255,255,255,.15);
        color: #fff;
        border-color: rgba(255,255,255,.2);
    }
    .pi-btn--glass:hover {
        background: rgba(255,255,255,.22);
        color: #fff;
    }
    .pi-btn--white {
        background: #fff;
        color: #0453cb;
        border-color: transparent;
    }
    .pi-btn--white:hover {
        background: #f8fafc;
        color: #0453cb;
        transform: translateY(-1px);
    }
    .pi-btn i { font-size: .78rem; }
    .pi-hero-actions .dropdown { position: relative; }
    .pi-hero-actions .dropdown-menu {
        font-size: .85rem;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(15,23,42,.2);
        border: 1px solid #e2e8f0;
        padding: .35rem;
        margin-top: .35rem;
        z-index: 1055;
    }
    .pi-hero-actions .dropdown-menu .dropdown-item {
        border-radius: 6px;
        padding: .45rem .65rem;
        font-size: .85rem;
    }

    /* ═══════ Hero KPIs (row 2 — glass cards semi-transparentes) ═══════ */
    .pi-hero-kpis {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: .75rem;
        margin-top: 1.5rem;
    }
    .pi-hero-kpi {
        display: block;
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.18);
        border-radius: 12px;
        padding: .9rem 1rem;
        color: #fff;
        text-decoration: none;
        transition: background .2s, border-color .2s, transform .15s;
    }
    .pi-hero-kpi:hover {
        background: rgba(255,255,255,.16);
        border-color: rgba(255,255,255,.3);
        color: #fff;
        transform: translateY(-2px);
        text-decoration: none;
    }
    .pi-hero-kpi.is-active {
        background: rgba(255,255,255,.2);
        border-color: rgba(255,255,255,.45);
        box-shadow: 0 4px 14px rgba(0,0,0,.15);
    }
    .pi-hero-kpi-head {
        display: flex;
        align-items: center;
        gap: .35rem;
        margin-bottom: .35rem;
        color: rgba(255,255,255,.8);
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .pi-hero-kpi-head i { font-size: .72rem; }
    .pi-hero-kpi-label { flex: 1; }
    .pi-hero-kpi-value {
        font-size: 1.35rem;
        font-weight: 700;
        color: #fff;
        letter-spacing: -.01em;
        line-height: 1.1;
    }
    .pi-hero-kpi-unit {
        font-size: .66rem;
        font-weight: 600;
        color: rgba(255,255,255,.65);
        margin-left: .35rem;
        letter-spacing: .05em;
    }
    .pi-hero-kpi-meta {
        margin-top: .15rem;
        font-size: .72rem;
        color: rgba(255,255,255,.65);
        font-weight: 500;
    }

    /* ═══════ Alert session ═══════ */
    .pi-alert--success {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
        border-radius: 12px;
        padding: .75rem 1rem;
        display: flex;
        align-items: center;
        gap: .6rem;
        margin-bottom: 1rem;
        font-size: .88rem;
        font-weight: 500;
    }
    .pi-alert--success > i { color: #10b981; font-size: 1rem; }
    .pi-alert--success .btn-close { margin-left: auto; opacity: .6; }

    /* ═══════ Filter bar ═══════ */
    .pi-filters {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 1rem 1.25rem 1.1rem;
        margin-bottom: 1rem;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
    }
    .pi-filters-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: .85rem;
        flex-wrap: wrap;
    }
    .pi-filters-title {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        color: #0f172a;
        font-weight: 700;
        font-size: .88rem;
    }
    .pi-filters-title i {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .72rem;
    }
    .pi-filters-active {
        display: none;
        align-items: center;
        gap: .5rem;
    }
    .pi-filters-active.is-visible { display: inline-flex; }
    .pi-filter-badge {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .25rem .65rem;
        background: rgba(4,83,203,.08);
        color: #0453cb;
        border: 1px solid rgba(4,83,203,.18);
        border-radius: 99px;
        font-size: .72rem;
        font-weight: 600;
    }
    .pi-filter-clear {
        background: none;
        border: none;
        color: #94a3b8;
        font-size: .76rem;
        font-weight: 600;
        cursor: pointer;
        padding: .25rem .55rem;
        border-radius: 6px;
        transition: all .2s;
    }
    .pi-filter-clear:hover {
        background: #fee2e2;
        color: #dc2626;
    }
    .pi-filters-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr auto;
        gap: .75rem;
        align-items: end;
    }
    .pi-field label {
        display: block;
        font-size: .72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        margin-bottom: .3rem;
    }
    .pi-field .form-control,
    .pi-field .form-select {
        font-size: .88rem;
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        padding: .5rem .75rem;
        background-color: #fff;
        transition: border-color .2s, box-shadow .2s;
    }
    .pi-field .form-control:focus,
    .pi-field .form-select:focus {
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4,83,203,.08);
    }
    .pi-filter-submit {
        padding: .55rem 1rem;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all .2s;
        font-size: .85rem;
        white-space: nowrap;
        height: 41px;
    }
    .pi-filter-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(4,83,203,.25);
    }
    .pi-filter-submit i { margin-right: .3rem; }

    /* ═══════ Bulk subheader sticky ═══════ */
    .pi-bulk-bar {
        position: sticky;
        top: 70px;
        z-index: 1020;
        background: linear-gradient(135deg, #0453cb 0%, #3b7ddb 100%);
        color: #fff;
        border-radius: 12px;
        padding-left: 1.25rem;
        padding-right: 1.25rem;
        padding-top: 0;
        padding-bottom: 0;
        margin-bottom: 0;
        overflow: hidden;
        max-height: 0;
        opacity: 0;
        transform: translateY(-8px);
        pointer-events: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        transition: max-height .25s ease, opacity .2s ease, transform .2s ease, margin .25s ease, padding .25s ease, box-shadow .2s ease;
    }
    .pi-bulk-bar.is-visible {
        max-height: 220px;
        margin-bottom: 1rem;
        padding-top: .9rem;
        padding-bottom: .9rem;
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
        box-shadow: 0 8px 24px rgba(4,83,203,.25);
    }
    .pi-bulk-info {
        display: inline-flex;
        align-items: center;
        gap: .55rem;
        font-size: .9rem;
        font-weight: 500;
    }
    .pi-bulk-info i { font-size: 1.05rem; }
    .pi-bulk-info strong { font-size: 1.1rem; font-weight: 700; }
    .pi-bulk-actions {
        display: inline-flex;
        gap: .45rem;
        flex-wrap: wrap;
    }
    .pi-bulk-actions .pi-btn { font-size: .82rem; padding: .45rem .9rem; }
    .pi-btn--outline-white {
        background: transparent;
        color: #fff;
        border-color: rgba(255,255,255,.4);
    }
    .pi-btn--outline-white:hover {
        background: rgba(255,255,255,.12);
        color: #fff;
        border-color: rgba(255,255,255,.6);
    }
    .pi-btn--ghost-white {
        background: transparent;
        color: rgba(255,255,255,.82);
        border-color: transparent;
    }
    .pi-btn--ghost-white:hover {
        background: rgba(255,255,255,.1);
        color: #fff;
    }

    /* ═══════ Filter breadcrumb chip (hero) ═══════ */
    .pi-chip--filter {
        background: rgba(251,191,36,.2);
        border-color: rgba(251,191,36,.5);
        color: #fef3c7;
    }
    .pi-chip--filter .pi-chip-btn { color: #fef3c7; opacity: .85; }

    /* ═══════ KPI drill-down indicators (metrics partial) ═══════ */
    .pi-kpi-link {
        display: block;
        text-decoration: none;
        color: inherit;
        cursor: pointer;
        transition: transform .15s ease, box-shadow .2s ease;
    }
    .pi-kpi-link:hover {
        color: inherit;
        text-decoration: none;
        transform: translateY(-2px);
    }
    .pi-kpi-link:hover .kpi-card {
        box-shadow: 0 8px 20px rgba(4,83,203,.1);
        border-color: #cbd5e1;
    }
    .pi-kpi-link.is-active .kpi-card {
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4,83,203,.1);
    }

    /* ═══════ Toast ═══════ */
    .pi-toast-container {
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 1080;
        display: flex;
        flex-direction: column;
        gap: .5rem;
        max-width: 360px;
        pointer-events: none;
    }
    .pi-toast {
        background: #fff;
        border-radius: 12px;
        padding: .85rem 1rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.15);
        display: flex;
        align-items: flex-start;
        gap: .6rem;
        border-left: 4px solid #10b981;
        pointer-events: auto;
        animation: pi-toast-in .25s ease forwards;
        font-size: .86rem;
    }
    .pi-toast.is-leaving { animation: pi-toast-out .2s ease forwards; }
    .pi-toast--error { border-left-color: #dc2626; }
    .pi-toast--warning { border-left-color: #f59e0b; }
    .pi-toast--info { border-left-color: #0453cb; }
    .pi-toast-icon {
        font-size: 1rem;
        color: #10b981;
        flex-shrink: 0;
        margin-top: .15rem;
    }
    .pi-toast--error .pi-toast-icon { color: #dc2626; }
    .pi-toast--warning .pi-toast-icon { color: #f59e0b; }
    .pi-toast--info .pi-toast-icon { color: #0453cb; }
    .pi-toast-body { flex: 1; color: #1e293b; line-height: 1.4; }
    .pi-toast-title { font-weight: 700; color: #0f172a; margin-bottom: .1rem; font-size: .88rem; }
    .pi-toast-close {
        background: none;
        border: none;
        color: #94a3b8;
        font-size: 1.1rem;
        cursor: pointer;
        padding: 0 .15rem;
        line-height: 1;
        margin-left: .15rem;
        transition: color .2s;
    }
    .pi-toast-close:hover { color: #475569; }

    @keyframes pi-toast-in {
        from { opacity: 0; transform: translateX(20px); }
        to { opacity: 1; transform: translateX(0); }
    }
    @keyframes pi-toast-out {
        to { opacity: 0; transform: translateX(20px); }
    }

    /* ═══════ Responsive ═══════ */
    @media (max-width: 992px) {
        .pi-hero-top { flex-direction: column; align-items: stretch; }
        .pi-hero-actions { justify-content: flex-start; }
        .pi-hero-kpis { grid-template-columns: repeat(2, 1fr); }
        .pi-filters-row { grid-template-columns: 1fr 1fr; }
        .pi-filters-row .pi-field:first-child { grid-column: span 2; }
        .pi-filter-submit { grid-column: span 2; }
        .pi-bulk-bar { top: 60px; }
        .pi-bulk-actions { width: 100%; }
    }
    @media (max-width: 576px) {
        .pi-hero { padding: 1.5rem 1.25rem 1.25rem; border-radius: 14px; }
        .pi-hero h1 { font-size: 1.2rem; }
        .pi-hero p { font-size: .82rem; }
        .pi-hero-kpis { grid-template-columns: 1fr; gap: .5rem; }
        .pi-hero-kpi-value { font-size: 1.15rem; }
        .pi-filters-row { grid-template-columns: 1fr; }
        .pi-filters-row .pi-field:first-child,
        .pi-filters-row .pi-filter-submit { grid-column: span 1; }
    }

    /* Fix cursor pour les éléments du modal de rejet */
    .modal-body textarea,
    .modal-body input[type="text"],
    .modal-body input[type="email"],
    .modal-body input[type="number"] {
        cursor: text !important;
    }

    .modal-body input[type="checkbox"],
    .modal-body input[type="radio"] {
        cursor: pointer !important;
    }

    .modal-body label {
        cursor: default !important;
    }

    .modal-body .form-check-label {
        cursor: pointer !important;
    }
</style>
@endsection

@section('content')
@php
    $anneeNom = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->value('name') ?? (date('Y').'-'.(date('Y')+1));
    $activeFilters = collect(['search', 'status', 'date_debut', 'date_fin'])
        ->filter(fn($k) => filled(request($k)))
        ->count();
@endphp
<div class="dashboard-acasi">
    <div class="main-content">
        {{-- Hero premium pi-* --}}
        <div class="pi-hero">
            <div class="pi-hero-top">
                <div class="pi-hero-left">
                    <span class="pi-hero-icon"><i class="fas fa-wallet"></i></span>
                    <div>
                        <h1>Suivi des Paiements</h1>
                        <p>Monitoring des paiements étudiants et relances automatiques</p>
                        <div class="pi-hero-chips">
                            <span class="pi-chip">
                                <i class="fas fa-calendar"></i>
                                {{ $anneeNom }}
                                <button type="button" class="pi-chip-btn" onclick="showYearChangeInfo()" title="Comment changer d'année ?" aria-label="Information changement d'année">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                            </span>
                            @if(request('status'))
                                @php
                                    $statusLabels = ['en_attente' => 'En attente', 'validé' => 'Validés', 'rejeté' => 'Rejetés'];
                                    $statusLabel = $statusLabels[request('status')] ?? request('status');
                                    $removeStatusUrl = route('esbtp.paiements.index') . '?' . http_build_query(request()->except('status'));
                                @endphp
                                <span class="pi-chip pi-chip--filter" role="status">
                                    <i class="fas fa-filter"></i>
                                    Statut : {{ $statusLabel }}
                                    <a href="{{ $removeStatusUrl }}" class="pi-chip-btn" title="Retirer ce filtre" aria-label="Retirer le filtre statut">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="pi-hero-actions">
                    <button type="button" class="pi-btn pi-btn--glass" id="paiements-refresh-btn" title="Rafraîchir les données">
                        <i class="fas fa-sync-alt"></i>
                        <span>Rafraîchir</span>
                    </button>
                    <div class="dropdown">
                        <button type="button" class="pi-btn pi-btn--glass dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-download"></i>
                            <span>Exporter</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="#" onclick="exportPaiements('excel'); return false;">
                                    <i class="fas fa-file-excel text-success me-2"></i>Excel (.xlsx)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="exportPaiements('csv'); return false;">
                                    <i class="fas fa-file-csv text-info me-2"></i>CSV
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="exportPaiements('pdf'); return false;">
                                    <i class="fas fa-file-pdf text-danger me-2"></i>PDF
                                </a>
                            </li>
                        </ul>
                    </div>
                    <a href="{{ route('esbtp.paiements.suivi-categories') }}" class="pi-btn pi-btn--glass">
                        <i class="fas fa-chart-bar"></i>
                        <span>Suivi par Catégorie</span>
                    </a>
                    @can('paiements.create')
                    <a href="{{ route('esbtp.paiements.create') }}" class="pi-btn pi-btn--white">
                        <i class="fas fa-plus"></i>
                        <span>Nouveau paiement</span>
                    </a>
                    @endcan
                </div>
            </div>

            {{-- Row 2 : KPIs glass — refresh AJAX via #paiements-metrics-kpis --}}
            <div id="paiements-metrics-kpis">
                @include('esbtp.paiements.partials.metrics-kpis', ['stats' => $stats])
            </div>
        </div>

        @if(session('success'))
            <div class="pi-alert--success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        @endif

        {{-- Alerte note (statique, pas AJAX) --}}
        <div class="alert alert-info alert-sm mb-lg">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Note :</strong> Ces chiffres représentent les paiements <u>enregistrés dans le système</u> selon leur statut de validation.
            Pour voir les montants attendus vs payés par catégorie de frais, consultez le
            <a href="{{ route('esbtp.paiements.suivi-categories') }}" class="alert-link">Suivi par Catégorie</a>.
        </div>

        {{-- Répartition par type (partial AJAX-refreshable via #paiements-metrics-details) --}}
        <div id="paiements-metrics-details">
            @include('esbtp.paiements.partials.metrics-details', ['stats' => $stats])
        </div>

        {{-- Filter bar groupée --}}
        <div class="pi-filters">
            <div class="pi-filters-head">
                <div class="pi-filters-title">
                    <i class="fas fa-filter"></i>
                    <span>Filtres</span>
                </div>
                <div class="pi-filters-active {{ $activeFilters > 0 ? 'is-visible' : '' }}" id="pi-filters-active">
                    <span class="pi-filter-badge">
                        <i class="fas fa-check-circle"></i>
                        <span id="pi-filters-count">{{ $activeFilters }}</span> filtre{{ $activeFilters > 1 ? 's' : '' }} actif{{ $activeFilters > 1 ? 's' : '' }}
                    </span>
                    <button type="button" class="pi-filter-clear" onclick="clearAllFilters()">
                        <i class="fas fa-times me-1"></i>Effacer tout
                    </button>
                </div>
            </div>
            <form action="{{ route('esbtp.paiements.index') }}" method="GET" id="paiements-filter-form">
                <div class="pi-filters-row">
                    <div class="pi-field">
                        <label for="search">Recherche</label>
                        <input type="text" name="search" id="search" class="form-control"
                               placeholder="Matricule, nom, n° reçu..." value="{{ request('search') }}">
                    </div>
                    <div class="pi-field">
                        <label for="status">Statut</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Tous</option>
                            <option value="en_attente" {{ request('status') == 'en_attente' ? 'selected' : '' }}>En attente</option>
                            <option value="validé" {{ request('status') == 'validé' ? 'selected' : '' }}>Validé</option>
                            <option value="rejeté" {{ request('status') == 'rejeté' ? 'selected' : '' }}>Rejeté</option>
                        </select>
                    </div>
                    <div class="pi-field">
                        <label for="date_debut">Date début</label>
                        <input type="date" name="date_debut" id="date_debut" class="form-control" value="{{ request('date_debut') }}">
                    </div>
                    <div class="pi-field">
                        <label for="date_fin">Date fin</label>
                        <input type="date" name="date_fin" id="date_fin" class="form-control" value="{{ request('date_fin') }}">
                    </div>
                    <button type="submit" class="pi-filter-submit">
                        <i class="fas fa-search"></i>Rechercher
                    </button>
                </div>
            </form>
        </div>

        {{-- Sticky bulk subheader (remplace fixed-bottom bar) --}}
        @can('paiements.validate')
        <div id="bulk-actions-bar" class="pi-bulk-bar" role="toolbar" aria-label="Actions groupées sur paiements sélectionnés">
            <div class="pi-bulk-info">
                <i class="fas fa-check-circle"></i>
                <strong id="selected-count">0</strong>
                <span>paiement(s) sélectionné(s)</span>
            </div>
            <div class="pi-bulk-actions">
                <button type="button" class="pi-btn pi-btn--white" onclick="bulkValider()">
                    <i class="fas fa-check-double"></i>
                    <span>Valider</span>
                </button>
                <button type="button" class="pi-btn pi-btn--outline-white" onclick="openBulkRejetModal()">
                    <i class="fas fa-times"></i>
                    <span>Rejeter</span>
                </button>
                <button type="button" class="pi-btn pi-btn--ghost-white" onclick="clearSelection()">
                    <i class="fas fa-times-circle"></i>
                    <span>Annuler</span>
                </button>
            </div>
        </div>
        @endcan

        {{-- Tableau des Paiements --}}
        <div id="paiements-table-container"
             data-refresh-url="{{ route('esbtp.paiements.refresh') }}"
             data-last-updated="{{ optional($lastUpdatedAt)->toIso8601String() }}">
            @include('esbtp.paiements.partials.table', ['paiements' => $paiements])
        </div>
    </div>
</div>

{{-- Toast container (feedback post-action) --}}
<div id="pi-toast-container" class="pi-toast-container" aria-live="polite" aria-atomic="true"></div>
@endsection

@push('scripts')
<script src="{{ asset('js/inscriptions/common.js') }}"></script>
<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 14px;
}

.table th {
    font-weight: 600;
    color: var(--text-primary);
    border-bottom: 2px solid #e5e7eb;
}

.table td {
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Styles pour les dropdowns PDF compacts */
.pdf-dropdown .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    min-width: auto;
}

.pdf-dropdown .dropdown-menu {
    min-width: 140px;
    font-size: 0.875rem;
}

.pdf-dropdown .dropdown-item {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.pdf-dropdown .dropdown-item i {
    width: 14px;
    text-align: center;
}

tr[data-paiement-id] {
    position: relative;
    overflow: hidden;
}

tr[data-paiement-id].is-loading {
    opacity: 0.85;
}

.paiement-actions-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.paiement-actions-buttons {
    display: inline-flex;
}

.paiement-actions-spinner {
    display: none;
    min-width: 32px;
}

.paiement-actions-wrapper.is-loading .paiement-actions-buttons {
    display: none !important;
}

.paiement-actions-wrapper.is-loading .paiement-actions-spinner {
    display: flex !important;
    align-items: center;
    justify-content: center;
}

.paiement-row-highlight {
    position: absolute;
    top: 0;
    left: -80%;
    width: 160%;
    height: 100%;
    opacity: 0;
    pointer-events: none;
    transform: translateX(-65%) skewX(-12deg);
    background: linear-gradient(90deg, rgba(40, 167, 69, 0) 0%, rgba(40, 167, 69, 0.75) 50%, rgba(40, 167, 69, 0) 100%);
    transition: opacity 0.2s ease;
    z-index: 5;
}

.paiement-row-highlight.reject {
    background: linear-gradient(90deg, rgba(220, 53, 69, 0) 0%, rgba(220, 53, 69, 0.75) 50%, rgba(220, 53, 69, 0) 100%);
}

.paiement-row-highlight.animate {
    animation: paiement-row-highlight-move 3.2s ease-out forwards;
}

.paiement-row-flash {
    animation: paiement-row-flash 0.8s ease-in-out;
}

@keyframes paiement-row-highlight-move {
    0% {
        opacity: 0;
        transform: translateX(-65%) skewX(-12deg);
    }
    18% {
        opacity: 0.92;
    }
    55% {
        opacity: 0.72;
    }
    100% {
        opacity: 0;
        transform: translateX(115%) skewX(-12deg);
    }
}

@keyframes paiement-row-flash {
    0% {
        background-color: transparent;
    }
    25% {
        background-color: rgba(40, 167, 69, 0.12);
    }
    100% {
        background-color: transparent;
    }
}

.paiement-row-flash.reject {
    animation-name: paiement-row-flash-reject;
}

@keyframes paiement-row-flash-reject {
    0% {
        background-color: transparent;
    }
    25% {
        background-color: rgba(220, 53, 69, 0.12);
    }
    100% {
        background-color: transparent;
    }
}

@keyframes slideUp {
    from {
        bottom: -100px;
        opacity: 0;
    }
    to {
        bottom: 20px;
        opacity: 1;
    }
}

</style>

<script>
function showYearChangeInfo() {
    $('#yearChangeModal').modal('show');
}

(function () {
    const pollingInterval = 30000; // 30 secondes
    let pollingTimer = null;
    let lastUpdatedAt = null;
    let currentPaiementsCount = 0; // Nombre actuel de paiements
    let currentUrl = window.location.href;

    /**
     * Met à jour l'affichage de la barre d'actions groupées (sticky subheader)
     * et le compteur de paiements sélectionnés
     */
    window.updateBulkActionsBar = function() {
        const checkedCount = $('.paiement-checkbox:checked').length;
        const $bulkActionsBar = $('#bulk-actions-bar');
        const $selectedCountSpan = $('#selected-count');

        debugLog('📊 updateBulkActionsBar appelée, checkboxes cochées:', checkedCount);

        if (checkedCount > 0) {
            $bulkActionsBar.addClass('is-visible');
            $selectedCountSpan.text(checkedCount);
        } else {
            $bulkActionsBar.removeClass('is-visible');
        }

        // Mettre à jour l'état de la checkbox "Tout sélectionner"
        const totalCheckboxes = $('.paiement-checkbox').length;
        $('#select-all').prop('checked', checkedCount === totalCheckboxes && totalCheckboxes > 0);
    };

    /**
     * Initialiser les écouteurs d'événements pour les checkboxes
     */
    function initCheckboxListeners() {
        debugLog('🔧 Initialisation des listeners de checkboxes...');

        // Checkbox "Tout sélectionner"
        $(document).off('change', '#select-all').on('change', '#select-all', function() {
            const isChecked = $(this).prop('checked');
            debugLog('☑️ Select-all changé:', isChecked);
            $('.paiement-checkbox').prop('checked', isChecked);
            updateBulkActionsBar();
        });

        // Checkboxes individuelles
        $(document).off('change', '.paiement-checkbox').on('change', '.paiement-checkbox', function() {
            debugLog('☑️ Checkbox individuelle changée');
            updateBulkActionsBar();
        });

        debugLog('✅ Listeners de checkboxes initialisés');
    }

    /**
     * Affiche ou masque le spinner d'action sur une ligne donnée
     * @param {string|number} paiementId
     * @param {boolean} isLoading
     */
    function setPaiementRowLoadingState(paiementId, isLoading) {
        const row = document.querySelector(`tr[data-paiement-id="${paiementId}"]`);
        if (!row) {
            return;
        }

        const actionsWrapper = row.querySelector('.paiement-actions-wrapper');

        if (actionsWrapper) {
            actionsWrapper.classList.toggle('is-loading', Boolean(isLoading));
        }

        row.classList.toggle('is-loading', Boolean(isLoading));
    }

    const PAIEMENT_HIGHLIGHT_DURATION = 3200;
    const PAIEMENT_STATUS_PASS_RATIO = 0.8;

    /**
     * Déclenche l'animation de lumière sur une ligne fraîchement mise à jour
     * @param {HTMLTableRowElement} row
     * @param {'validate' | 'reject'} actionType
     * @param {Object} [options]
     */
    function triggerPaiementRowHighlight(row, actionType, options = {}) {
        if (!row) {
            return;
        }

        const { onStatusPassed } = options;

        row.classList.remove('paiement-row-flash', 'reject');
        void row.offsetWidth; // force reflow pour relancer l'animation

        const highlight = document.createElement('div');
        highlight.className = 'paiement-row-highlight';
        if (actionType === 'reject') {
            highlight.classList.add('reject');
        }

        row.appendChild(highlight);

        requestAnimationFrame(() => {
            highlight.classList.add('animate');
        });

        if (typeof onStatusPassed === 'function') {
            setTimeout(() => {
                onStatusPassed(highlight);
            }, PAIEMENT_HIGHLIGHT_DURATION * PAIEMENT_STATUS_PASS_RATIO);
        }

        const cleanup = () => {
            highlight.removeEventListener('animationend', cleanup);
            highlight.remove();
        };

        highlight.addEventListener('animationend', cleanup);

        row.classList.add('paiement-row-flash');
        if (actionType === 'reject') {
            row.classList.add('reject');
        }

        setTimeout(() => {
            row.classList.remove('paiement-row-flash', 'reject');
        }, 1200);
    }

    // Rendre accessible depuis les fonctions globales
    window.setPaiementRowLoadingState = setPaiementRowLoadingState;

    /**
     * Affiche un toast de feedback (success / error / warning / info).
     * Auto-dismiss après 5s, dismissable manuellement.
     */
    window.showToast = window.showToast || function(message, type = 'success', title = null) {
        const container = document.getElementById('pi-toast-container');
        if (!container) {
            return;
        }

        const ICONS = { success: 'check-circle', error: 'times-circle', warning: 'exclamation-triangle', info: 'info-circle' };
        const TITLES = { success: 'Succès', error: 'Erreur', warning: 'Attention', info: 'Information' };

        const toast = document.createElement('div');
        toast.className = 'pi-toast pi-toast--' + type;
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

        const icon = document.createElement('i');
        icon.className = 'fas fa-' + (ICONS[type] || 'info-circle') + ' pi-toast-icon';

        const body = document.createElement('div');
        body.className = 'pi-toast-body';
        const titleEl = document.createElement('div');
        titleEl.className = 'pi-toast-title';
        titleEl.textContent = title || TITLES[type] || '';
        const msgEl = document.createElement('div');
        msgEl.textContent = message;
        body.appendChild(titleEl);
        body.appendChild(msgEl);

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'pi-toast-close';
        closeBtn.setAttribute('aria-label', 'Fermer');
        closeBtn.innerHTML = '&times;';

        toast.appendChild(icon);
        toast.appendChild(body);
        toast.appendChild(closeBtn);
        container.appendChild(toast);

        let dismissTimer = null;
        const removeToast = () => {
            if (dismissTimer) clearTimeout(dismissTimer);
            toast.classList.add('is-leaving');
            setTimeout(() => toast.remove(), 220);
        };
        closeBtn.addEventListener('click', removeToast);
        dismissTimer = setTimeout(removeToast, 5000);
    };

    /**
     * Fetch les données depuis le serveur et met à jour le DOM
     * @param {boolean} showLog - Afficher les logs dans la console
     * @param {boolean} showOverlay - Afficher l'overlay de chargement (true pour refresh manuel, false pour polling)
     */
    function fetchPaiementsData(showLog = true, showOverlay = true) {
        const spinner = document.getElementById('paiements-refresh-spinner');
        const btn = document.getElementById('paiements-refresh-btn');
        const tableContainer = document.getElementById('paiements-table-container');

        // Afficher le spinner du bouton
        if (spinner && btn) {
            btn.style.display = 'none';
            spinner.classList.remove('d-none');
        }

        // 🎨 Ajouter overlay de chargement UNIQUEMENT pour le refresh manuel (pas pour le polling automatique)
        if (showOverlay && tableContainer) {
            tableContainer.style.position = 'relative';

            const loadingOverlay = document.createElement('div');
            loadingOverlay.id = 'table-loading-overlay';
            loadingOverlay.style.position = 'absolute';
            loadingOverlay.style.top = '0';
            loadingOverlay.style.left = '0';
            loadingOverlay.style.width = '100%';
            loadingOverlay.style.height = '100%';
            loadingOverlay.style.backgroundColor = 'rgba(255, 255, 255, 0.7)';
            loadingOverlay.style.display = 'flex';
            loadingOverlay.style.alignItems = 'center';
            loadingOverlay.style.justifyContent = 'center';
            loadingOverlay.style.zIndex = '10';
            loadingOverlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div>';

            tableContainer.appendChild(loadingOverlay);
        }

        const params = new URLSearchParams(window.location.search);
        const refreshUrl = '{{ route('esbtp.paiements.refresh') }}?' + params.toString();

        fetch(refreshUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Verifier si on a recu les donnees attendues (nouvelle signature : metrics_kpis + metrics_details)
            if (data.table && (data.metrics_kpis || data.metrics_details)) {
                if (data.metrics_kpis) {
                    const kpisEl = document.getElementById('paiements-metrics-kpis');
                    if (kpisEl) kpisEl.innerHTML = data.metrics_kpis;
                }
                if (data.metrics_details) {
                    const detailsEl = document.getElementById('paiements-metrics-details');
                    if (detailsEl) detailsEl.innerHTML = data.metrics_details;
                }

                // Mettre à jour le tableau
                if (data.table) {
                    document.getElementById('paiements-table-container').innerHTML = data.table;
                    // Réinitialiser les listeners
                    initCheckboxListeners();

                    // Mettre à jour le nombre total de paiements disponibles
                    if (data.summary && typeof data.summary.total === 'number') {
                        currentPaiementsCount = data.summary.total;
                    } else {
                        currentPaiementsCount = document.querySelectorAll('tr[data-paiement-id]').length;
                    }
                    debugLog('📊 Nombre de paiements affichés:', currentPaiementsCount);
                }

                // Mettre à jour l'URL sans recharger la page
                if (data.url) {
                    history.pushState({}, '', data.url);
                    currentUrl = data.url;
                }

                // Mettre à jour le timestamp
                if (data.last_updated_at) {
                    lastUpdatedAt = data.last_updated_at;
                }

                if (showLog) {
                    debugLog('✅ Données rafraîchies avec succès');
                }
            } else {
                debugError('❌ Réponse invalide du serveur:', data);
            }
        })
        .catch(error => {
            debugError('❌ Erreur lors du refresh:', error);
        })
        .finally(() => {
            // Masquer le spinner du bouton
            if (spinner && btn) {
                spinner.classList.add('d-none');
                btn.style.display = 'flex';
            }

            // 🎨 Retirer l'overlay de chargement
            const overlay = document.getElementById('table-loading-overlay');
            if (overlay) {
                overlay.remove();
            }

            if (tableContainer) {
                tableContainer.style.position = '';
            }
        });
    }

    /**
     * Vérifie s'il y a des changements sans charger toutes les données
     */
    function checkForUpdates() {
        const params = new URLSearchParams(window.location.search);
        const checkUrl = '{{ route('esbtp.paiements.check-updates') }}?' + params.toString();

        fetch(checkUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Comparer avec l'état actuel
            const hasChanges = (
                data.count !== currentPaiementsCount ||
                data.last_updated_at !== lastUpdatedAt
            );

            if (hasChanges) {
                debugLog('🆕 Changements détectés! Count:', currentPaiementsCount, '→', data.count, '| Last update:', lastUpdatedAt, '→', data.last_updated_at);

                // Mettre à jour les valeurs courantes
                currentPaiementsCount = data.count;
                lastUpdatedAt = data.last_updated_at;

                // Rafraîchir les données SANS overlay (polling automatique non-intrusif)
                fetchPaiementsData(false, false);
            } else {
                debugLog('✓ Pas de changements (count:', data.count, ')');
            }
        })
        .catch(error => {
            debugError('❌ Erreur lors de la vérification des mises à jour:', error);
        });
    }

    /**
     * Démarre le polling automatique intelligent
     */
    function startPolling() {
        if (pollingTimer) {
            clearInterval(pollingTimer);
        }

        pollingTimer = setInterval(() => {
            debugLog('🔄 Vérification des changements...');
            checkForUpdates();
        }, pollingInterval);

        debugLog(`✅ Polling intelligent démarré (intervalle: ${pollingInterval}ms)`);
    }

    /**
     * Arrête le polling automatique
     */
    function stopPolling() {
        if (pollingTimer) {
            clearInterval(pollingTimer);
            pollingTimer = null;
            debugLog('⏸️ Polling arrêté');
        }
    }

    /**
     * Intercepte la soumission du formulaire de filtres
     * pour faire une requête AJAX au lieu de recharger la page
     */
    $('#paiements-filter-form').off('submit').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const params = new URLSearchParams(formData);

        // Construire l'URL avec les paramètres de filtres
        const newUrl = '{{ route('esbtp.paiements.index') }}?' + params.toString();

        // Mettre à jour l'URL dans le navigateur
        history.pushState({}, '', newUrl);
        currentUrl = newUrl;

        // Fetch les données
        fetchPaiementsData();
    });

    /**
     * Intercepte les clics sur les liens de pagination
     * pour faire une requête AJAX au lieu de recharger la page
     */
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();

        const url = $(this).attr('href');

        if (!url || url === '#') {
            return;
        }

        // Mettre à jour l'URL dans le navigateur
        history.pushState({}, '', url);
        currentUrl = url;

        // Fetch les données
        fetchPaiementsData();
    });

    /**
     * Gestion du bouton "Rafraîchir" manuel
     */
    $('#paiements-refresh-btn').off('click').on('click', function() {
        debugLog('🔄 Refresh manuel déclenché');
        fetchPaiementsData();
    });

    /**
     * Gestion du bouton retour/avancer du navigateur
     */
    window.addEventListener('popstate', function() {
        debugLog('⬅️ Navigation navigateur détectée');
        fetchPaiementsData();
    });

    /**
     * Rafraîchit une ligne spécifique de paiement après validation/rejet
     * avec animation de lumière verte/rouge qui parcourt la ligne
     */
    window.refreshPaiementLigne = function(paiementId, actionType = 'validate') {
        debugLog('🔄 Refresh ligne paiement:', paiementId, 'action:', actionType);

        const refreshUrl = `/esbtp/paiements/${paiementId}/refresh-ligne`;
        const existingRow = document.querySelector(`tr[data-paiement-id="${paiementId}"]`);
        const wasChecked = existingRow
            ? Boolean(existingRow.querySelector('.paiement-checkbox')?.checked)
            : false;

        setPaiementRowLoadingState(paiementId, true);

        fetch(refreshUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success || !data.html) {
                throw new Error(data.message || 'Réponse serveur invalide');
            }

            const template = document.createElement('template');
            template.innerHTML = data.html.trim();

            let rowFragment = template.content.querySelector(`tr[data-paiement-id="${paiementId}"]`);
            if (!rowFragment) {
                rowFragment = template.content.querySelector('tr[data-paiement-id]');
            }

            if (!rowFragment) {
                debugError('❌ Contenu HTML reçu:', data.html);
                throw new Error('HTML retourné sans ligne de paiement valide');
            }

            const newRow = rowFragment.cloneNode(true);
            const clonedCells = Array.from(newRow.children).map(cell => cell.cloneNode(true));

            const modalFragment = template.content.querySelector(`#rejetModal${paiementId}`);
            if (modalFragment) {
                const newModal = modalFragment.cloneNode(true);
                const existingModal = document.getElementById(`rejetModal${paiementId}`);
                if (existingModal) {
                    existingModal.replaceWith(newModal);
                } else {
                    document.body.appendChild(newModal);
                }
            } else {
                const existingModal = document.getElementById(`rejetModal${paiementId}`);
                if (existingModal) {
                    existingModal.remove();
                }
            }

            const tbody = document.querySelector('#paiements-table-container tbody');

            if (!existingRow || !existingRow.parentNode) {
                if (tbody) {
                    tbody.appendChild(newRow);
                }

                if (wasChecked) {
                    const appendedCheckbox = newRow.querySelector('.paiement-checkbox');
                    if (appendedCheckbox) {
                        appendedCheckbox.checked = true;
                    }
                }

                updateBulkActionsBar();
                setPaiementRowLoadingState(paiementId, false);
                triggerPaiementRowHighlight(newRow, actionType);

                debugLog('🎉 Ligne rafraîchie avec succès (nouvelle ligne ajoutée):', paiementId);
                return;
            }

            let contentUpdated = false;

            const applyUpdatedContent = (highlightEl = null) => {
                if (contentUpdated) {
                    return;
                }
                contentUpdated = true;

                const highlightNode = highlightEl && highlightEl instanceof Node ? highlightEl : existingRow.querySelector('.paiement-row-highlight');
                const existingCells = Array.from(existingRow.children).filter(child => child !== highlightNode);

                existingCells.forEach((cell, index) => {
                    const replacement = clonedCells[index];
                    if (replacement) {
                        cell.replaceWith(replacement);
                    } else {
                        cell.remove();
                    }
                });

                const extraCells = clonedCells.slice(existingCells.length);
                if (extraCells.length > 0) {
                    const fragment = document.createDocumentFragment();
                    extraCells.forEach(node => fragment.appendChild(node));

                    if (highlightNode && highlightNode.parentNode) {
                        highlightNode.parentNode.insertBefore(fragment, highlightNode);
                    } else {
                        existingRow.appendChild(fragment);
                    }
                }

                if (highlightNode && highlightNode.parentNode !== existingRow) {
                    existingRow.appendChild(highlightNode);
                }

                if (wasChecked) {
                    const restoredCheckbox = existingRow.querySelector('.paiement-checkbox');
                    if (restoredCheckbox) {
                        restoredCheckbox.checked = true;
                    }
                }

                updateBulkActionsBar();
                setPaiementRowLoadingState(paiementId, false);

                existingRow.classList.add('paiement-row-flash');
                if (actionType === 'reject') {
                    existingRow.classList.add('reject');
                }
                setTimeout(() => {
                    existingRow.classList.remove('paiement-row-flash', 'reject');
                }, 1200);
            };

            triggerPaiementRowHighlight(existingRow, actionType, {
                onStatusPassed: (highlightEl) => {
                    applyUpdatedContent(highlightEl);
                }
            });

            // Fallback au cas où l'animation ne se lancerait pas
            setTimeout(() => {
                if (!contentUpdated) {
                    applyUpdatedContent();
                }
            }, PAIEMENT_HIGHLIGHT_DURATION + 100);

            debugLog('🎉 Ligne rafraîchie avec succès (mise à jour différée):', paiementId);
        })
        .catch(error => {
            debugError('❌ Erreur refresh ligne:', error);
            setPaiementRowLoadingState(paiementId, false);
            window.showToast('Erreur lors de la mise à jour : ' + error.message, 'error');
        });
    };

    /**
     * Fonction pour exporter les paiements avec les filtres actifs
     * @param {string} format - Format d'export: 'excel', 'csv', ou 'pdf'
     */
    window.exportPaiements = function(format) {
        debugLog('📤 Export paiements format:', format);

        // Récupérer les paramètres de filtres actuels
        const params = new URLSearchParams(window.location.search);

        // Construire l'URL d'export avec les filtres
        let exportUrl = '';
        switch(format) {
            case 'excel':
                exportUrl = '{{ route('esbtp.paiements.export.excel') }}';
                break;
            case 'csv':
                exportUrl = '{{ route('esbtp.paiements.export.csv') }}';
                break;
            case 'pdf':
                exportUrl = '{{ route('esbtp.paiements.export.pdf') }}';
                break;
            default:
                debugError('❌ Format d\'export inconnu:', format);
                return;
        }

        // Ajouter les paramètres de filtres
        if (params.toString()) {
            exportUrl += '?' + params.toString();
        }

        debugLog('🔗 URL d\'export:', exportUrl);

        // Rediriger vers l'URL d'export (le téléchargement démarrera automatiquement)
        window.location.href = exportUrl;
    };

    /**
     * Initialisation au chargement de la page
     */
    $(document).ready(function() {
        debugLog('✅ Scripts paiements initialisés');

        // Initialiser les listeners de checkboxes
        initCheckboxListeners();

        // Démarrer le polling automatique
        startPolling();

        // Vérifier combien de checkboxes existent au chargement
        debugLog('🔍 Vérification checkboxes au chargement:');
        debugLog('   - Total checkboxes paiement:', $('.paiement-checkbox').length);
        debugLog('   - Select-all existe:', $('#select-all').length > 0);
        debugLog('   - Bulk actions bar existe:', $('#bulk-actions-bar').length > 0);

        // Initialiser l'état de la barre d'actions groupées
        updateBulkActionsBar();

        // Auto-submit quand on change un select ou une date
        $('#status, #date_debut, #date_fin').off('change').on('change', function() {
            debugLog('📝 Changement détecté, soumission automatique du formulaire');
            $('#paiements-filter-form').submit();
        });

        /**
         * Intercepter les clics sur les boutons de validation de paiement
         * Utilisation de addEventListener avec capture phase pour intercepter AVANT les autres handlers
         */
        debugLog('🎯 Installation du handler de validation avec capture phase...');

        document.addEventListener('click', async function(e) {
            // Vérifier si le clic est sur un bouton de validation ou un de ses enfants
            let btn = e.target.closest('.valider-paiement-btn');

            // Fallback: vérifier aussi btn-outline-success (au cas où la classe serait différente)
            if (!btn) {
                btn = e.target.closest('.btn-outline-success[data-paiement-id]');
            }

            if (btn) {
                debugLog('🔘 Clic détecté sur bouton valider (CAPTURE PHASE)');
                debugLog('🎯 Bouton trouvé:', btn);

                // STOP IMMÉDIATEMENT tout avant même de vérifier quoi que ce soit
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                const paiementId = btn.getAttribute('data-paiement-id');
                debugLog('📋 Paiement ID:', paiementId);

                if (!paiementId) {
                    debugError('❌ Pas de paiement ID trouvé sur le bouton');
                    return false;
                }

                const confirmed = typeof window.iiConfirm === 'function'
                    ? await window.iiConfirm({
                        title: 'Valider le paiement',
                        message: 'Valider ce paiement ? Le montant sera comptabilisé immédiatement.',
                        confirmLabel: 'Valider',
                        cancelLabel: 'Annuler',
                    })
                    : window.confirm('Valider ce paiement ?');

                if (!confirmed) {
                    debugLog('⏸️ Validation annulée par l\'utilisateur');
                    return false;
                }

                debugLog('🔄 Lancement validation AJAX pour paiement:', paiementId);

                // Récupérer l'URL depuis l'attribut data
                const actionUrl = btn.getAttribute('data-action-url');
                debugLog('🌐 URL d\'action:', actionUrl);

                if (!actionUrl) {
                    debugError('❌ Pas d\'URL d\'action trouvée sur le bouton');
                    return false;
                }

                setPaiementRowLoadingState(paiementId, true);

                fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => {
                    debugLog('📡 Réponse serveur reçue:', response.status);
                    return response.json();
                })
                .then(data => {
                    debugLog('📦 Données JSON:', data);
                    if (data.success) {
                        debugLog('✅ Paiement validé, lancement refresh ligne');
                        // Rafraîchir la ligne avec animation verte
                        window.refreshPaiementLigne(paiementId, 'validate');
                    } else {
                        setPaiementRowLoadingState(paiementId, false);
                        window.showToast(data.message || 'Erreur inconnue lors de la validation.', 'error');
                    }
                })
                .catch(error => {
                    debugError('❌ Erreur validation:', error);
                    setPaiementRowLoadingState(paiementId, false);
                    window.showToast('Erreur lors de la validation : ' + error.message, 'error');
                });

                return false;
            }
        }, true); // true = capture phase (s'exécute AVANT le bubbling)

        debugLog('✅ Handler de validation installé avec capture phase');
    });
})();
</script>

<!-- Fonctions globales pour actions groupées (hors IIFE) -->
<script>
// Note: updateBulkActionsBar() est déjà définie dans le IIFE principal ci-dessus
// Pas besoin de la redéfinir ici

function toggleRowsLoadingState(ids, isLoading) {
    if (!Array.isArray(ids) || typeof window.setPaiementRowLoadingState !== 'function') {
        return;
    }
    ids.forEach(id => window.setPaiementRowLoadingState(id, isLoading));
}

function clearAllFilters() {
    const $form = $('#paiements-filter-form');
    $form.find('input[type="text"], input[type="date"]').val('');
    $form.find('select').val('');
    $form.submit();
}

async function bulkValider() {
    const selectedIds = getSelectedPaiementIds();

    if (selectedIds.length === 0) {
        window.showToast('Veuillez sélectionner au moins un paiement.', 'warning');
        return;
    }

    const confirmed = typeof window.iiConfirm === 'function'
        ? await window.iiConfirm({
            title: 'Valider la sélection',
            message: `Valider ${selectedIds.length} paiement${selectedIds.length > 1 ? 's' : ''} ? Les montants seront comptabilisés immédiatement.`,
            confirmLabel: `Valider (${selectedIds.length})`,
            cancelLabel: 'Annuler',
        })
        : window.confirm(`Êtes-vous sûr de vouloir valider ${selectedIds.length} paiement(s) ?`);

    if (!confirmed) {
        return;
    }

    debugLog('🔄 Validation en masse de', selectedIds.length, 'paiements:', selectedIds);

    toggleRowsLoadingState(selectedIds, true);

    // Requête AJAX au lieu de form submit
    fetch('{{ route('esbtp.paiements.bulk-valider') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            paiements: selectedIds
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        debugLog('✅ Réponse bulk validation:', data);

        if (data.success) {
            // Rafraîchir chaque ligne validée avec animation
            selectedIds.forEach((id, index) => {
                setTimeout(() => {
                    if (typeof window.refreshPaiementLigne === 'function') {
                        window.refreshPaiementLigne(id, 'validate');
                    }
                }, index * 100); // Décalage de 100ms entre chaque ligne
            });

            window.showToast(data.message || 'Paiements validés avec succès.', 'success');
            clearSelection();
        } else {
            toggleRowsLoadingState(selectedIds, false);
            window.showToast(data.message || 'Erreur lors de la validation.', 'error');
        }
    })
    .catch(error => {
        debugError('❌ Erreur bulk validation:', error);
        toggleRowsLoadingState(selectedIds, false);
        window.showToast('Erreur lors de la validation. Veuillez réessayer.', 'error');
    });
}

function openBulkRejetModal() {
    const selectedIds = getSelectedPaiementIds();

    if (selectedIds.length === 0) {
        window.showToast('Veuillez sélectionner au moins un paiement.', 'warning');
        return;
    }

    $('#bulk-rejet-count').text(selectedIds.length);

    const container = $('#bulk-selected-paiements');
    container.empty();

    selectedIds.forEach(function(id) {
        container.append($('<input>', {
            type: 'hidden',
            name: 'paiements[]',
            value: id
        }));
    });

    $('#bulk_motif_rejet').val('');
    $('#bulk_confirmer_rejet').prop('checked', false);

    // Bootstrap 5 modal
    const modal = new bootstrap.Modal(document.getElementById('bulkRejetModal'));
    modal.show();
}

function clearSelection() {
    $('.paiement-checkbox').prop('checked', false);
    $('#select-all').prop('checked', false);
    updateBulkActionsBar();
}

function getSelectedPaiementIds() {
    const ids = [];
    $('.paiement-checkbox:checked').each(function() {
        ids.push($(this).val());
    });
    return ids;
}

// Intercepter la soumission du formulaire de rejet en masse
$(document).ready(function() {
    $('#bulk-rejet-form').off('submit').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const motifRejet = $('#bulk_motif_rejet').val();
        const selectedIds = getSelectedPaiementIds();

        if (!motifRejet.trim()) {
            window.showToast('Veuillez saisir un motif de rejet.', 'warning');
            return;
        }

        if (!$('#bulk_confirmer_rejet').is(':checked')) {
            window.showToast('Veuillez cocher la case de confirmation.', 'warning');
            return;
        }

        debugLog('🔄 Rejet en masse de', selectedIds.length, 'paiements:', selectedIds);

        if (selectedIds.length === 0) {
            window.showToast('Veuillez sélectionner au moins un paiement.', 'warning');
            return;
        }

        toggleRowsLoadingState(selectedIds, true);

        // Requête AJAX
        fetch('{{ route('esbtp.paiements.bulk-rejeter') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                paiements: selectedIds,
                motif_rejet: motifRejet
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            debugLog('✅ Réponse bulk rejet:', data);

            if (data.success) {
                // Fermer le modal
                bootstrap.Modal.getInstance(document.getElementById('bulkRejetModal')).hide();

                // Rafraîchir chaque ligne rejetée avec animation
                selectedIds.forEach((id, index) => {
                    setTimeout(() => {
                        if (typeof window.refreshPaiementLigne === 'function') {
                            window.refreshPaiementLigne(id, 'reject');
                        }
                    }, index * 100); // Décalage de 100ms
                });

                window.showToast(data.message || 'Paiements rejetés avec succès.', 'success');
                clearSelection();
            } else {
                toggleRowsLoadingState(selectedIds, false);
                window.showToast(data.message || 'Erreur lors du rejet.', 'error');
            }
        })
        .catch(error => {
            debugError('❌ Erreur bulk rejet:', error);
            toggleRowsLoadingState(selectedIds, false);
            window.showToast('Erreur lors du rejet. Veuillez réessayer.', 'error');
        });
    });

    // Intercepter le clic sur le bouton de rejet individuel
    $(document).on('click', '.rejeter-paiement-submit-btn', function(e) {
        e.preventDefault();
        debugLog('🛑 Clic sur bouton rejeter intercepté');

        const button = $(this);
        const paiementId = button.data('paiement-id');
        const modal = button.closest('.modal');
        const form = modal.find('form');
        const actionUrl = form.attr('action');

        // Utiliser les IDs spécifiques avec le paiementId
        const motifRejetTextarea = modal.find('#motif_rejet' + paiementId);
        const confirmerRejetCheckbox = modal.find('#confirmer_rejet' + paiementId);

        const motifRejet = motifRejetTextarea.val();
        const confirmerRejet = confirmerRejetCheckbox.is(':checked');

        debugLog('📝 Données formulaire:', {
            paiementId: paiementId,
            actionUrl: actionUrl,
            motifRejet: motifRejet,
            motifRejetLength: motifRejet ? motifRejet.length : 0,
            confirmerRejet: confirmerRejet,
            textareaFound: motifRejetTextarea.length > 0,
            checkboxFound: confirmerRejetCheckbox.length > 0
        });

        if (!motifRejet || !motifRejet.trim()) {
            window.showToast('Veuillez saisir un motif de rejet.', 'warning');
            return;
        }

        if (!confirmerRejet) {
            window.showToast('Veuillez cocher la case de confirmation.', 'warning');
            return;
        }

        debugLog('🔄 Rejet individuel paiement ID:', paiementId);

        if (typeof window.setPaiementRowLoadingState === 'function') {
            window.setPaiementRowLoadingState(paiementId, true);
        }
        button.prop('disabled', true);

        // Construire FormData avec CSRF token
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('motif_rejet', motifRejet);

        // Requête AJAX
        fetch(actionUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            debugLog('✅ Réponse rejet individuel:', data);

            if (data.success) {
                // Fermer le modal
                const modalElement = modal.get(0);
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) {
                    modalInstance.hide();
                }

                // Rafraîchir la ligne du paiement
                if (typeof window.refreshPaiementLigne === 'function') {
                    window.refreshPaiementLigne(paiementId, 'reject');
                }

                window.showToast(data.message || 'Paiement rejeté avec succès.', 'success');
            } else {
                if (typeof window.setPaiementRowLoadingState === 'function') {
                    window.setPaiementRowLoadingState(paiementId, false);
                }
                button.prop('disabled', false);
                window.showToast(data.message || 'Erreur lors du rejet.', 'error');
            }
        })
        .catch(error => {
            debugError('❌ Erreur rejet individuel:', error);
            if (typeof window.setPaiementRowLoadingState === 'function') {
                window.setPaiementRowLoadingState(paiementId, false);
            }
            button.prop('disabled', false);
            window.showToast('Erreur lors du rejet. Veuillez réessayer.', 'error');
        });
    });
});
</script>

@endpush

<!-- Modal pour les instructions de changement d'année -->
<div class="modal fade" id="yearChangeModal" tabindex="-1" role="dialog" aria-labelledby="yearChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="yearChangeModalLabel">Comment changer l'année académique ?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; font-weight: bold; color: #999; cursor: pointer;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Pour consulter les données d'une autre année :</strong></p>
                <ol style="padding-left: 20px; line-height: 1.6; margin: 15px 0;">
                    <li><strong>Aller dans</strong> : Menu → Années Universitaires</li>
                    <li><strong>Trouver l'année souhaitée</strong> (ex: 2023-2024)</li>
                    <li><strong>Cliquer sur "Activer"</strong> pour la définir comme année courante</li>
                    <li><strong>Revenir ici</strong> : Les paiements affichés se mettront à jour automatiquement</li>
                </ol>
                <hr style="margin: 15px 0;">
                <p style="color: #6b7280; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Note :</strong> Seule une année peut être "courante" à la fois. 
                    Changer l'année courante affecte l'affichage des paiements dans toute l'application.
                </p>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong>Exemple :</strong><br>
                    • Année courante = 2024-2025 → Voir les paiements de 2024-2025<br>
                    • Année courante = 2023-2024 → Voir les paiements de 2023-2024
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="$('#yearChangeModal').modal('hide');">Fermer</button>
                <a href="{{ route('esbtp.annees-universitaires.index') }}" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt"></i> Aller aux Années
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal de rejet groupé -->
@can('paiements.validate')
<div class="modal fade" id="bulkRejetModal" tabindex="-1" aria-labelledby="bulkRejetModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="bulk-rejet-form" method="POST" action="{{ route('esbtp.paiements.bulk-rejeter') }}">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="bulkRejetModalLabel">
                        <i class="fas fa-times-circle me-2"></i>
                        Rejeter les paiements sélectionnés
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Vous êtes sur le point de rejeter <strong><span id="bulk-rejet-count">0</span> paiement(s)</strong>.
                    </div>

                    <div class="mb-3">
                        <label for="bulk_motif_rejet" class="form-label">Motif du rejet <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="bulk_motif_rejet" name="motif_rejet" rows="4"
                                  placeholder="Expliquez pourquoi ces paiements sont rejetés..." required></textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="bulk_confirmer_rejet" required>
                        <label class="form-check-label" for="bulk_confirmer_rejet">
                            Je confirme le rejet de ces paiements
                        </label>
                    </div>

                    <div id="bulk-selected-paiements"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times-circle me-1"></i>Rejeter les paiements
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
