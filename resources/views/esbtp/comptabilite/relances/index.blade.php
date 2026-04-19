@extends('layouts.app')

@section('title', 'Relances Paiements')

@push('styles')
<style>
/* ───────────────────────────────────────────────
   RELANCES INDEX — KLASSCI Premium Design v2
   Namespace: rl-*
   Palette: #0453cb → #5e91de (monochrome bleu)
──────────────────────────────────────────────── */
:root {
    --rl-primary:    #0453cb;
    --rl-primary-d:  #033a8e;
    --rl-secondary:  #5e91de;
    --rl-accent:     #3b7ddb;
    --rl-dark:       #0f172a;
    --rl-text:       #1e293b;
    --rl-muted:      #64748b;
    --rl-success:    #10b981;
    --rl-surface:    #f8fafc;
    --rl-white:      #ffffff;
    --rl-border:     #e2e8f0;
    --rl-shadow-sm:  0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    --rl-shadow-md:  0 4px 16px rgba(4,83,203,.06), 0 1px 3px rgba(15,23,42,.04);
    --rl-shadow-lg:  0 8px 30px rgba(4,83,203,.08), 0 2px 8px rgba(15,23,42,.04);
    --rl-shadow-xl:  0 12px 40px rgba(4,83,203,.12), 0 4px 12px rgba(15,23,42,.06);
    --rl-radius:     14px;
    --rl-radius-sm:  10px;
}

/* ── HERO ── */
.rl-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    position: relative;
    padding: 1.75rem 2rem 1.5rem;
    border-radius: 18px;
    margin-bottom: 1.25rem;
    color: #fff;
    /* overflow visible pour ne pas cliper les dropdowns */
}
.rl-hero-inner {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}
.rl-hero-left {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    flex: 1;
    min-width: 0;
}
.rl-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.rl-hero-title {
    font-size: 1.55rem;
    font-weight: 700;
    color: #fff;
    margin: 0;
    letter-spacing: -.4px;
    line-height: 1.2;
}
.rl-hero-title .rl-year-badge {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    background: rgba(255,255,255,.14);
    color: rgba(255,255,255,.94);
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 99px;
    padding: .2rem .65rem;
    font-size: .7rem;
    font-weight: 600;
    letter-spacing: .04em;
    vertical-align: middle;
    margin-left: .5rem;
}
.rl-hero-chips {
    display: flex; flex-wrap: wrap; gap: .4rem;
    margin-top: .55rem;
}

/* Row 2 : Hero KPIs glass */
.rl-hero-kpis {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: .6rem;
    margin-top: 1.5rem;
}
.rl-hero-kpi {
    display: block;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 12px;
    padding: .75rem .85rem;
    color: #fff;
    text-decoration: none;
    transition: background .2s, border-color .2s, transform .15s;
    cursor: default;
}
.rl-hero-kpi[data-risk] { cursor: pointer; }
.rl-hero-kpi[data-risk]:hover,
.rl-hero-kpi.is-clickable:hover {
    background: rgba(255,255,255,.16);
    border-color: rgba(255,255,255,.3);
    transform: translateY(-2px);
    text-decoration: none;
    color: #fff;
}
.rl-hero-kpi.is-active {
    background: rgba(255,255,255,.2);
    border-color: rgba(255,255,255,.45);
    box-shadow: 0 4px 14px rgba(0,0,0,.15);
}
.rl-hero-kpi-head {
    display: flex;
    align-items: center;
    gap: .35rem;
    margin-bottom: .3rem;
    color: rgba(255,255,255,.8);
    font-size: .65rem;
    font-weight: 600;
    letter-spacing: .04em;
    text-transform: uppercase;
}
.rl-hero-kpi-head i { font-size: .7rem; }
.rl-hero-kpi-label { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rl-hero-kpi-value {
    font-size: 1.2rem;
    font-weight: 700;
    color: #fff;
    line-height: 1.1;
    letter-spacing: -.01em;
}
.rl-hero-kpi-value.is-amount {
    font-size: 1rem;
}
.rl-hero-kpi-unit {
    font-size: .58rem;
    font-weight: 600;
    color: rgba(255,255,255,.6);
    margin-left: .2rem;
    letter-spacing: .05em;
}
.rl-hero-kpi-hint {
    margin-top: .15rem;
    font-size: .62rem;
    color: rgba(255,255,255,.6);
    font-weight: 500;
    line-height: 1.25;
}
@media (max-width: 1280px) {
    .rl-hero-kpis { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
    .rl-hero-kpis { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 480px) {
    .rl-hero-kpis { grid-template-columns: 1fr; }
}
.rl-hero-sub {
    color: rgba(255,255,255,.5);
    font-size: .82rem;
    margin: .35rem 0 0;
    font-weight: 400;
}
.rl-hero-actions {
    display: flex;
    gap: .6rem;
    align-items: center;
    flex-wrap: wrap;
}
.rl-btn-ghost {
    background: rgba(255,255,255,.07);
    color: rgba(255,255,255,.85);
    border: 1px solid rgba(255,255,255,.15);
    padding: .5rem 1.1rem;
    border-radius: 9px;
    font-weight: 500;
    font-size: .8rem;
    text-decoration: none;
    transition: all .2s ease;
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    backdrop-filter: blur(4px);
}
.rl-btn-ghost:hover {
    background: rgba(255,255,255,.14);
    color: #fff;
    border-color: rgba(255,255,255,.28);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,.15);
}

/* ── CONFIG BANNER ── */
.rl-config-banner {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border: 1.5px solid #fbbf24;
    border-radius: var(--rl-radius);
    padding: .85rem 1.25rem;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    box-shadow: 0 2px 8px rgba(251,191,36,.1);
}
.rl-config-banner-text {
    display: flex;
    align-items: center;
    gap: .6rem;
    color: #92400e;
    font-weight: 600;
    font-size: .85rem;
}
.rl-config-banner-btn {
    background: var(--rl-primary);
    color: #fff;
    padding: .45rem 1rem;
    border-radius: 8px;
    font-size: .8rem;
    font-weight: 600;
    white-space: nowrap;
    text-decoration: none;
    transition: all .15s;
}
.rl-config-banner-btn:hover {
    background: var(--rl-primary-d);
    color: #fff;
    transform: translateY(-1px);
}

/* ── FILTERS BAR ── */
.rl-filters {
    background: var(--rl-white);
    border: 1px solid var(--rl-border);
    border-radius: var(--rl-radius);
    padding: 1.1rem 1.25rem;
    margin-bottom: 1.25rem;
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    align-items: flex-end;
    box-shadow: var(--rl-shadow-sm);
}
.rl-filter-group {
    display: flex;
    flex-direction: column;
    gap: .3rem;
    min-width: 140px;
    flex: 1;
}
.rl-filter-label {
    font-size: .68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--rl-muted);
}
.rl-filter-input {
    border: 1.5px solid var(--rl-border);
    border-radius: 9px;
    padding: .5rem .75rem;
    font-size: .82rem;
    color: var(--rl-text);
    background: var(--rl-surface);
    transition: all .2s ease;
    height: 38px;
    outline: none;
}
.rl-filter-input:focus {
    border-color: var(--rl-primary);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(4,83,203,.08);
}
.rl-search-wrap { position: relative; }
.rl-search-wrap .rl-search-icon {
    position: absolute;
    left: .75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--rl-muted);
    font-size: .82rem;
    pointer-events: none;
}
.rl-search-wrap .rl-filter-input { padding-left: 2.2rem; width: 100%; }
.rl-btn-filter {
    background: var(--rl-primary);
    color: #fff;
    border: none;
    border-radius: 9px;
    padding: .5rem 1.1rem;
    font-size: .82rem;
    font-weight: 600;
    cursor: pointer;
    height: 38px;
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    transition: all .2s;
    box-shadow: 0 2px 6px rgba(4,83,203,.2);
}
.rl-btn-filter:hover {
    background: var(--rl-primary-d);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(4,83,203,.25);
}
.rl-btn-reset {
    background: transparent;
    color: var(--rl-muted);
    border: 1.5px solid var(--rl-border);
    border-radius: 9px;
    padding: .5rem .9rem;
    font-size: .78rem;
    cursor: pointer;
    height: 38px;
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    text-decoration: none;
    transition: all .2s;
}
.rl-btn-reset:hover {
    border-color: var(--rl-primary);
    color: var(--rl-primary);
    background: rgba(4,83,203,.03);
}

/* ── RISK TABS ── */
.rl-tabs {
    display: flex;
    gap: .5rem;
    margin-bottom: 1.15rem;
    flex-wrap: wrap;
    align-items: center;
    padding: .25rem;
    background: rgba(241,245,249,.5);
    border-radius: 24px;
    width: fit-content;
}
.risk-tab {
    padding: .45rem 1rem;
    border-radius: 20px;
    font-size: .76rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    background: transparent;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    transition: all .2s ease;
    position: relative;
    color: var(--rl-muted);
}
.risk-tab:hover {
    background: rgba(4,83,203,.05);
    color: var(--rl-text);
}
.risk-tab .rl-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    flex-shrink: 0;
}
.risk-tab.all .rl-dot      { background: var(--rl-text); }
.risk-tab.critical .rl-dot { background: var(--rl-dark); }
.risk-tab.high .rl-dot     { background: var(--rl-primary); }
.risk-tab.medium .rl-dot   { background: var(--rl-secondary); }

/* Active states */
.risk-tab.all.active {
    background: var(--rl-dark);
    color: #fff;
    box-shadow: 0 2px 8px rgba(15,23,42,.2);
}
.risk-tab.all.active .rl-dot { background: #fff; }
.risk-tab.critical.active {
    background: var(--rl-dark);
    color: #fff;
    box-shadow: 0 2px 8px rgba(15,23,42,.2);
}
.risk-tab.critical.active .rl-dot { background: #fff; }
.risk-tab.high.active {
    background: var(--rl-primary);
    color: #fff;
    box-shadow: 0 2px 8px rgba(4,83,203,.25);
}
.risk-tab.high.active .rl-dot { background: #fff; }
.risk-tab.medium.active {
    background: var(--rl-secondary);
    color: #fff;
    box-shadow: 0 2px 8px rgba(94,145,222,.25);
}
.risk-tab.medium.active .rl-dot { background: #fff; }

.risk-tab .tab-count {
    font-weight: 500;
    opacity: .7;
    font-size: .72rem;
}

/* ── TABLE ── */
.rel-table-wrap {
    background: var(--rl-white);
    border: 1px solid var(--rl-border);
    border-radius: var(--rl-radius);
    overflow: hidden;
    position: relative;
    min-height: 120px;
    box-shadow: var(--rl-shadow-md);
}
.rel-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .83rem;
}
.rel-table thead {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 2px solid var(--rl-border);
}
.rel-table th {
    padding: .8rem 1rem;
    text-align: left;
    font-size: .67rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--rl-muted);
    white-space: nowrap;
}
.rel-table td {
    padding: .9rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    color: var(--rl-text);
}
.rel-table tr:last-child td { border-bottom: none; }
.rel-table tbody tr {
    transition: all .15s ease;
}
.rel-table tbody tr:hover td {
    background: rgba(4,83,203,.015);
}

/* Student cell */
.stud-cell { display: flex; align-items: center; gap: .75rem; }
.stud-avatar {
    width: 38px; height: 38px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--rl-primary), var(--rl-secondary));
    display: flex; align-items: center; justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: .78rem;
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(4,83,203,.2);
    letter-spacing: .5px;
}
.stud-name {
    font-weight: 600;
    color: var(--rl-text);
    font-size: .85rem;
    line-height: 1.2;
}
.stud-matricule {
    font-size: .7rem;
    color: var(--rl-muted);
    margin-top: 1px;
    font-weight: 500;
    letter-spacing: .02em;
}

/* Risk badge */
.rbadge {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .3rem .7rem;
    border-radius: 8px;
    font-size: .7rem;
    font-weight: 600;
    white-space: nowrap;
    letter-spacing: .01em;
}
.rbadge.critical { background: rgba(15,23,42,.06); color: var(--rl-dark); }
.rbadge.high     { background: rgba(4,83,203,.07); color: var(--rl-primary); }
.rbadge.medium   { background: rgba(94,145,222,.08); color: #2563eb; }
.rbadge.low      { background: rgba(16,185,129,.07); color: #059669; }

/* Progress bar */
.pbar-wrap { display: flex; align-items: center; gap: .6rem; min-width: 120px; }
.pbar-track {
    flex: 1;
    height: 6px;
    background: #e9eef5;
    border-radius: 10px;
    overflow: hidden;
}
.pbar-fill {
    height: 100%;
    border-radius: 10px;
    transition: width .5s cubic-bezier(.4,0,.2,1);
}
.pbar-fill.full    { background: linear-gradient(90deg, var(--rl-success), #34d399); }
.pbar-fill.partial { background: linear-gradient(90deg, var(--rl-primary), var(--rl-secondary)); }
.pbar-fill.low-pay { background: linear-gradient(90deg, var(--rl-primary-d), var(--rl-primary)); }
.pbar-fill.none    { background: var(--rl-dark); width: 4px !important; }
.pbar-pct {
    font-size: .72rem;
    font-weight: 700;
    color: var(--rl-muted);
    white-space: nowrap;
    min-width: 30px;
    text-align: right;
}

/* Amount cells */
.amount-cell { font-weight: 600; white-space: nowrap; font-variant-numeric: tabular-nums; }
.amount-unit { font-size: .65em; font-weight: 400; opacity: .45; margin-left: .15em; }
.amount-red  { color: var(--rl-primary); }

/* Pending badge */
.pending-badge {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    background: rgba(245,158,11,.06);
    border: 1px solid rgba(245,158,11,.2);
    color: #b45309;
    border-radius: 6px;
    padding: .18rem .5rem;
    font-size: .65rem;
    font-weight: 600;
    white-space: nowrap;
    margin-top: .25rem;
}

/* Action buttons */
.act-btn {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .4rem .8rem;
    border-radius: 8px;
    font-size: .73rem;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all .2s ease;
    white-space: nowrap;
}
.act-btn:hover { transform: translateY(-1px); }
.act-btn.primary {
    background: var(--rl-primary);
    color: #fff;
    box-shadow: 0 2px 6px rgba(4,83,203,.18);
}
.act-btn.primary:hover {
    background: var(--rl-primary-d);
    box-shadow: 0 4px 12px rgba(4,83,203,.25);
    color: #fff;
}
.act-btn.ghost {
    background: transparent;
    border: 1.5px solid var(--rl-border);
    color: var(--rl-muted);
}
.act-btn.ghost:hover {
    border-color: var(--rl-primary);
    color: var(--rl-primary);
    background: rgba(4,83,203,.03);
}

/* ── EMPTY STATE ── */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--rl-muted);
}
.empty-state .empty-icon {
    width: 60px; height: 60px;
    border-radius: 16px;
    background: rgba(16,185,129,.06);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.25rem;
    font-size: 1.5rem;
    color: var(--rl-success);
}
.empty-state h5 { font-weight: 600; color: var(--rl-text); margin-bottom: .4rem; }
.empty-state p { font-size: .83rem; max-width: 320px; margin: 0 auto; line-height: 1.5; }

/* ── PAGINATION ── */
.rel-pagination {
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--rl-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: .75rem;
}
.rel-pagination .page-info {
    font-size: .78rem;
    color: var(--rl-muted);
    font-weight: 500;
}

/* ── LOADING OVERLAY ── */
.rel-loading {
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    border-radius: var(--rl-radius);
    backdrop-filter: blur(3px);
    opacity: 0;
    pointer-events: none;
    transition: opacity .2s;
}
.rel-loading.visible { opacity: 1; pointer-events: all; }
.rel-spinner {
    width: 30px; height: 30px;
    border: 3px solid var(--rl-border);
    border-top-color: var(--rl-primary);
    border-radius: 50%;
    animation: rl-spin .6s linear infinite;
}
@keyframes rl-spin { to { transform: rotate(360deg); } }

/* ── RESPONSIVE ── */
@media (max-width: 992px) {
    .rel-table th:nth-child(3),
    .rel-table td:nth-child(3) { display: none; }
}
@media (max-width: 768px) {
    .rel-table th:nth-child(5),
    .rel-table td:nth-child(5) { display: none; }
    .rl-hero { padding: 1.5rem; border-radius: 14px; }
    .rl-hero-title { font-size: 1.2rem; }
    .rl-tabs { width: 100%; }
}
@media (max-width: 576px) {
    .rl-filters { flex-direction: column; }
    .rl-filter-group { min-width: 100%; }
    .rl-hero-actions { width: 100%; }
    .rl-btn-ghost { flex: 1; justify-content: center; font-size: .75rem; }
}
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
<div class="main-content">

    {{-- ── HERO (pattern planning-header : row 1 titre+actions / row 2 KPIs glass) ── --}}
    <div class="rl-hero">
        <div class="rl-hero-inner">
            <div class="rl-hero-left">
                <span class="rl-hero-icon"><i class="fas fa-bell"></i></span>
                <div>
                    <h1 class="rl-hero-title">
                        Gestion des Relances
                        @if($anneeActive)
                            <span class="rl-year-badge">
                                <i class="fas fa-circle" style="font-size:.35rem;"></i>
                                {{ $anneeActive->name }}
                            </span>
                        @endif
                    </h1>
                    <p class="rl-hero-sub">Suivi des soldes impayés et relances de paiement</p>
                </div>
            </div>
            <div class="rl-hero-actions">
                @can('comptabilite.reports.export')
                <a href="{{ route('esbtp.comptabilite.relances.export-excel', request()->query()) }}" class="rl-btn-ghost">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
                <a href="{{ route('esbtp.comptabilite.relances.export-pdf', request()->query()) }}" class="rl-btn-ghost">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
                @endcan
                <a href="{{ route('esbtp.comptabilite.relances.config') }}" class="rl-btn-ghost">
                    <i class="fas fa-cog"></i> Configuration
                </a>
                <a href="{{ route('esbtp.paiements.index') }}" class="rl-btn-ghost">
                    <i class="fas fa-list-alt"></i> Paiements
                </a>
            </div>
        </div>

        {{-- Row 2 : KPIs glass (IDs preserves pour compat JS) --}}
        <div class="rl-hero-kpis">
            <div class="rl-hero-kpi" title="Total impaye (paiements valides uniquement)">
                <div class="rl-hero-kpi-head">
                    <i class="fas fa-coins"></i>
                    <span class="rl-hero-kpi-label">Total impayé</span>
                </div>
                <div class="rl-hero-kpi-value is-amount">
                    <span id="kpi-total-impaye">{{ number_format($kpis['total_impaye'], 0, ',', ' ') }}</span>
                    <span class="rl-hero-kpi-unit">FCFA</span>
                </div>
                <div class="rl-hero-kpi-hint">Paiements validés uniquement</div>
            </div>

            <div class="rl-hero-kpi" title="Paiements enregistrés mais non encore validés">
                <div class="rl-hero-kpi-head">
                    <i class="fas fa-clock"></i>
                    <span class="rl-hero-kpi-label">En attente validation</span>
                </div>
                <div class="rl-hero-kpi-value is-amount">
                    <span id="kpi-total-en-attente">{{ number_format($kpis['total_en_attente'] ?? 0, 0, ',', ' ') }}</span>
                    <span class="rl-hero-kpi-unit">FCFA</span>
                </div>
                <div class="rl-hero-kpi-hint">Non déduits du solde</div>
            </div>

            <div class="rl-hero-kpi" data-risk="critical" title="Cliquer pour filtrer">
                <div class="rl-hero-kpi-head">
                    <i class="fas fa-ban"></i>
                    <span class="rl-hero-kpi-label">Impayés (0% réglé)</span>
                </div>
                <div class="rl-hero-kpi-value" id="kpi-count-critical">{{ $kpis['count_critical'] }}</div>
            </div>

            <div class="rl-hero-kpi" data-risk="high" title="Cliquer pour filtrer">
                <div class="rl-hero-kpi-head">
                    <i class="fas fa-hourglass-half"></i>
                    <span class="rl-hero-kpi-label">En cours (partiel)</span>
                </div>
                <div class="rl-hero-kpi-value" id="kpi-count-high">{{ $kpis['count_high'] }}</div>
            </div>

            <div class="rl-hero-kpi" data-risk="medium" title="Cliquer pour filtrer">
                <div class="rl-hero-kpi-head">
                    <i class="fas fa-tasks"></i>
                    <span class="rl-hero-kpi-label">Presque soldés</span>
                </div>
                <div class="rl-hero-kpi-value" id="kpi-count-medium">{{ $kpis['count_medium'] }}</div>
            </div>

            <div class="rl-hero-kpi">
                <div class="rl-hero-kpi-head">
                    <i class="fas fa-check-circle"></i>
                    <span class="rl-hero-kpi-label">À jour</span>
                </div>
                <div class="rl-hero-kpi-value" id="kpi-count-low">{{ $kpis['count_low'] }}</div>
            </div>
        </div>
    </div>

    {{-- ── BANNER CONFIG MANQUANTE ── --}}
    @if($configManquante)
    <div class="rl-config-banner">
        <div class="rl-config-banner-text">
            <i class="fas fa-exclamation-triangle" style="font-size:1rem;"></i>
            Les délais de relance ne sont pas configurés.
        </div>
        <a href="{{ route('esbtp.comptabilite.relances.config') }}" class="rl-config-banner-btn">
            <i class="fas fa-cog me-1"></i> Configurer
        </a>
    </div>
    @endif

    {{-- ── FILTERS ── --}}
    <form id="relances-filters-form" method="GET" action="{{ route('esbtp.comptabilite.relances.index') }}">
        <div class="rl-filters">

            <div class="rl-filter-group" style="flex:2;min-width:200px;">
                <label class="rl-filter-label">Recherche</label>
                <div class="rl-search-wrap">
                    <i class="fas fa-search rl-search-icon"></i>
                    <input type="text" name="search" id="filter-search" class="rl-filter-input" placeholder="Nom, prénom, matricule…" value="{{ $search }}">
                </div>
            </div>

            <div class="rl-filter-group">
                <label class="rl-filter-label">Filière</label>
                <select name="filiere_id" class="rl-filter-input">
                    <option value="">Toutes</option>
                    @foreach ($filieres as $f)
                        <option value="{{ $f->id }}" {{ $filiereId == $f->id ? 'selected' : '' }}>{{ $f->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="rl-filter-group">
                <label class="rl-filter-label">Classe</label>
                <select name="classe_id" class="rl-filter-input">
                    <option value="">Toutes</option>
                    @foreach ($classes as $c)
                        <option value="{{ $c->id }}" {{ $classeId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="rl-filter-group">
                <label class="rl-filter-label">Année</label>
                <select name="annee_id" class="rl-filter-input">
                    @foreach ($annees as $a)
                        <option value="{{ $a->id }}" {{ $anneeId == $a->id ? 'selected' : '' }}>{{ $a->name }}@if($a->is_current) (en cours)@endif</option>
                    @endforeach
                </select>
            </div>

            <div class="rl-filter-group" style="min-width:90px;max-width:110px;">
                <label class="rl-filter-label">Par page</label>
                <select name="per_page" class="rl-filter-input">
                    @foreach ([10, 25, 50, 100] as $pp)
                        <option value="{{ $pp }}" {{ $perPage == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                    @endforeach
                </select>
            </div>

            <input type="hidden" name="risk" id="risk-hidden" value="{{ $riskFilter }}">

            <div style="display:flex;gap:.5rem;align-items:flex-end;">
                <button type="submit" class="rl-btn-filter"><i class="fas fa-filter"></i> Filtrer</button>
                <button type="button" id="btn-reset" class="rl-btn-reset"><i class="fas fa-times"></i> Reset</button>
            </div>
        </div>
    </form>

    {{-- ── RISK TABS ── --}}
    <div class="rl-tabs" id="risk-tabs">
        <button type="button" class="risk-tab all {{ !$riskFilter ? 'active' : '' }}" data-risk="">
            <span class="rl-dot"></span>
            Tous <span class="tab-count">(<span id="tab-count-all">{{ $kpis['total_etudiants'] }}</span>)</span>
        </button>
        <button type="button" class="risk-tab critical {{ $riskFilter === 'critical' ? 'active' : '' }}" data-risk="critical"
                title="Aucun paiement effectué">
            <span class="rl-dot"></span>
            Impayés <span class="tab-count">(<span id="tab-count-critical">{{ $kpis['count_critical'] }}</span>)</span>
        </button>
        <button type="button" class="risk-tab high {{ $riskFilter === 'high' ? 'active' : '' }}" data-risk="high"
                title="Paiement partiel (> 25% restant)">
            <span class="rl-dot"></span>
            En cours <span class="tab-count">(<span id="tab-count-high">{{ $kpis['count_high'] }}</span>)</span>
        </button>
        <button type="button" class="risk-tab medium {{ $riskFilter === 'medium' ? 'active' : '' }}" data-risk="medium"
                title="Au moins 75% réglé">
            <span class="rl-dot"></span>
            Presque soldés <span class="tab-count">(<span id="tab-count-medium">{{ $kpis['count_medium'] }}</span>)</span>
        </button>
    </div>

    {{-- ── TABLE WRAP (cible AJAX) ── --}}
    <div class="rel-table-wrap" id="relances-table-wrap">
        <div class="rel-loading" id="relances-loading">
            <div class="rel-spinner"></div>
        </div>
        @include('esbtp.comptabilite.relances._table')
    </div>

</div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const tableWrap  = document.getElementById('relances-table-wrap');
    const loading    = document.getElementById('relances-loading');
    const riskHidden = document.getElementById('risk-hidden');
    const form       = document.getElementById('relances-filters-form');
    const tabs       = document.querySelectorAll('#risk-tabs .risk-tab');
    const btnReset   = document.getElementById('btn-reset');

    function showLoader()  { loading.classList.add('visible'); }
    function hideLoader()  { loading.classList.remove('visible'); }

    function setActiveTab(risk) {
        tabs.forEach(btn => btn.classList.remove('active'));
        tabs.forEach(btn => {
            if (btn.dataset.risk === risk) btn.classList.add('active');
        });
    }

    function fetchTable(params) {
        showLoader();

        const url = new URL('{{ route('esbtp.comptabilite.relances.index') }}', window.location.origin);
        Object.entries(params).forEach(([k, v]) => {
            if (v !== null && v !== undefined && v !== '') {
                url.searchParams.set(k, v);
            }
        });

        window.history.pushState({}, '', url.toString());

        fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            const tmp = document.createElement('div');
            tmp.innerHTML = data.table;

            Array.from(tableWrap.children).forEach(child => {
                if (!child.classList.contains('rel-loading')) child.remove();
            });

            Array.from(tmp.childNodes).forEach(node => tableWrap.appendChild(node));

            const kpis = data.kpis;
            if (kpis) {
                const fmt = v => new Intl.NumberFormat('fr-FR').format(v);
                const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
                set('kpi-total-impaye',     fmt(kpis.total_impaye));
                set('kpi-total-en-attente', fmt(kpis.total_en_attente || 0));
                set('kpi-count-critical',   kpis.count_critical);
                set('kpi-count-high',       kpis.count_high);
                set('kpi-count-medium',     kpis.count_medium);
                set('kpi-count-low',        kpis.count_low);
                set('tab-count-all',        kpis.total_etudiants);
                set('tab-count-critical',   kpis.count_critical);
                set('tab-count-high',       kpis.count_high);
                set('tab-count-medium',     kpis.count_medium);
            }

            wirePaginationLinks();
            hideLoader();
        })
        .catch(() => hideLoader());
    }

    function collectFormParams() {
        const data = new FormData(form);
        const params = {};
        for (const [k, v] of data.entries()) params[k] = v;
        params.risk = riskHidden.value;
        return params;
    }

    /* Tab click */
    tabs.forEach(btn => {
        btn.addEventListener('click', function () {
            const risk = this.dataset.risk;
            riskHidden.value = risk;
            setActiveTab(risk);
            const params = collectFormParams();
            params.page = 1;
            fetchTable(params);
        });
    });

    /* KPI card click */
    document.querySelectorAll('.rl-hero-kpi[data-risk]').forEach(card => {
        card.addEventListener('click', function () {
            const risk = this.dataset.risk;
            riskHidden.value = risk;
            setActiveTab(risk);
            const params = collectFormParams();
            params.page = 1;
            fetchTable(params);
        });
    });

    /* Form submit */
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const params = collectFormParams();
        params.page = 1;
        fetchTable(params);
    });

    /* Reset */
    btnReset.addEventListener('click', function () {
        form.reset();
        riskHidden.value = '';
        setActiveTab('');
        fetchTable({ page: 1 });
    });

    /* Per-page auto submit */
    form.querySelector('select[name="per_page"]').addEventListener('change', function () {
        const params = collectFormParams();
        params.page = 1;
        fetchTable(params);
    });

    /* Pagination links */
    function wirePaginationLinks() {
        tableWrap.querySelectorAll('.pagination a[href]').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const href = new URL(this.href);
                const page = href.searchParams.get('page') || 1;
                const params = collectFormParams();
                params.page = page;
                fetchTable(params);
            });
        });
    }

    wirePaginationLinks();
})();
</script>
@endpush
