@extends('layouts.app')

@section('title', 'Relances Paiements')

@push('styles')
<style>
/* ───────────────────────────────────────────────
   RELANCES INDEX — KLASSCI Blue Intelligence
   Palette: #0453cb (primary) · #5e91de (secondary)
            #1e293b (dark) · #10b981 (success)
            #64748b (muted) · #f1f5f9 (surface)
──────────────────────────────────────────────── */
:root {
    --rl-primary:    #0453cb;
    --rl-secondary:  #5e91de;
    --rl-dark:       #1e293b;
    --rl-success:    #10b981;
    --rl-muted:      #64748b;
    --rl-surface:    #f1f5f9;
    --rl-white:      #ffffff;
    --rl-border:     #e2e8f0;
}

/* ── HERO HEADER ── */
.rel-hero {
    background: linear-gradient(135deg, #0c1a3a 0%, #0453cb 60%, #1a4fa8 100%);
    position: relative;
    overflow: hidden;
    padding: 2rem 2rem 1.5rem;
    border-radius: 16px;
    margin-bottom: 1.5rem;
}
.rel-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 60% 80% at 85% 50%, rgba(94,145,222,.18) 0%, transparent 70%),
        url("data:image/svg+xml,%3Csvg width='60' height='60' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='30' cy='30' r='1' fill='rgba(255,255,255,.04)'/%3E%3C/svg%3E");
    pointer-events: none;
}
.rel-hero-title {
    font-size: 1.6rem;
    font-weight: 700;
    color: #fff;
    margin: 0;
    letter-spacing: -.3px;
}
.rel-hero-sub {
    color: rgba(255,255,255,.65);
    font-size: .85rem;
    margin: .25rem 0 0;
}
.rel-hero-actions { display: flex; gap: .75rem; align-items: center; flex-wrap: wrap; }
.btn-hero-ghost {
    background: rgba(255,255,255,.12);
    color: #fff;
    border: 1px solid rgba(255,255,255,.25);
    padding: .55rem 1.2rem;
    border-radius: 8px;
    font-weight: 500;
    font-size: .85rem;
    text-decoration: none;
    transition: background .15s;
    display: inline-flex; align-items: center; gap: .4rem;
}
.btn-hero-ghost:hover { background: rgba(255,255,255,.2); color: #fff; }

/* ── KPI STRIP ── */
.kpi-strip {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.kpi-card {
    background: var(--rl-white);
    border: 1px solid var(--rl-border);
    border-radius: 12px;
    padding: 1.1rem 1.25rem;
    position: relative;
    overflow: hidden;
    transition: box-shadow .2s;
    cursor: pointer;
}
.kpi-card:hover { box-shadow: 0 4px 20px rgba(4,83,203,.1); }
.kpi-card::after {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 4px;
    border-radius: 12px 0 0 12px;
}
.kpi-card.impaye::after   { background: var(--rl-primary); }
.kpi-card.pending::after  { background: #f59e0b; }
.kpi-card.critical::after { background: var(--rl-dark); }
.kpi-card.high::after     { background: var(--rl-primary); }
.kpi-card.medium::after   { background: var(--rl-secondary); }
.kpi-card.low::after      { background: var(--rl-success); }
.kpi-label {
    font-size: .72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--rl-muted);
    margin-bottom: .3rem;
}
.kpi-value {
    font-size: 1.45rem;
    font-weight: 700;
    color: var(--rl-dark);
    line-height: 1;
}
.kpi-value.big { font-size: 1.05rem; }
.kpi-sub {
    font-size: .7rem;
    color: var(--rl-muted);
    margin-top: .35rem;
    display: flex;
    align-items: center;
    gap: .3rem;
    line-height: 1.3;
}
.kpi-sub.pending-hint { color: #b45309; }
.kpi-sub.info-hint    { color: #64748b; }
.kpi-icon {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.6rem;
    opacity: .07;
}

/* ── Pending badge in table ── */
.pending-badge {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    background: rgba(245,158,11,.1);
    border: 1px solid rgba(245,158,11,.3);
    color: #b45309;
    border-radius: 6px;
    padding: .18rem .5rem;
    font-size: .68rem;
    font-weight: 600;
    white-space: nowrap;
    margin-top: .2rem;
}

/* ── FILTERS BAR ── */
.filters-bar {
    background: var(--rl-white);
    border: 1px solid var(--rl-border);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.25rem;
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    align-items: flex-end;
}
.filter-group { display: flex; flex-direction: column; gap: .3rem; min-width: 140px; flex: 1; }
.filter-label { font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--rl-muted); }
.filter-control {
    border: 1px solid var(--rl-border);
    border-radius: 8px;
    padding: .45rem .75rem;
    font-size: .85rem;
    color: var(--rl-dark);
    background: var(--rl-surface);
    transition: border-color .15s;
    height: 38px;
}
.filter-control:focus { outline: none; border-color: var(--rl-primary); background: #fff; }
.search-wrap { position: relative; }
.search-wrap .search-icon { position: absolute; left: .75rem; top: 50%; transform: translateY(-50%); color: var(--rl-muted); font-size: .85rem; pointer-events: none; }
.search-wrap .filter-control { padding-left: 2.2rem; width: 100%; }
.btn-filter {
    background: var(--rl-primary);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: .45rem 1rem;
    font-size: .85rem;
    font-weight: 600;
    cursor: pointer;
    height: 38px;
    display: inline-flex; align-items: center; gap: .4rem;
    transition: opacity .15s;
    text-decoration: none;
}
.btn-filter:hover { opacity: .88; color: #fff; }
.btn-filter-ghost {
    background: transparent;
    color: var(--rl-muted);
    border: 1px solid var(--rl-border);
    border-radius: 8px;
    padding: .45rem .9rem;
    font-size: .8rem;
    cursor: pointer;
    height: 38px;
    display: inline-flex; align-items: center; gap: .35rem;
    text-decoration: none;
    transition: border-color .15s, color .15s;
}
.btn-filter-ghost:hover { border-color: var(--rl-primary); color: var(--rl-primary); }

/* ── RISK TABS ── */
.risk-tabs {
    display: flex;
    gap: .5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    align-items: center;
}
.risk-tab {
    padding: .4rem .9rem;
    border-radius: 20px;
    font-size: .78rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    background: transparent;
    text-decoration: none;
    display: inline-flex; align-items: center; gap: .35rem;
    transition: opacity .15s, transform .1s, box-shadow .15s;
    position: relative;
}
.risk-tab:hover { transform: translateY(-1px); }

/* Inactive state — subtle outline */
.risk-tab.all      { color: var(--rl-dark);      border: 1.5px solid rgba(30,41,59,.25);    }
.risk-tab.critical { color: var(--rl-dark);       border: 1.5px solid rgba(30,41,59,.2);     }
.risk-tab.high     { color: var(--rl-primary);    border: 1.5px solid rgba(4,83,203,.25);    }
.risk-tab.medium   { color: #2563eb;              border: 1.5px solid rgba(94,145,222,.3);   }

/* Active state — filled */
.risk-tab.all.active      { background: var(--rl-dark);      color: #fff; border-color: var(--rl-dark);      box-shadow: 0 2px 8px rgba(30,41,59,.3); }
.risk-tab.critical.active { background: var(--rl-dark);      color: #fff; border-color: var(--rl-dark);      box-shadow: 0 2px 8px rgba(30,41,59,.3); }
.risk-tab.high.active     { background: var(--rl-primary);   color: #fff; border-color: var(--rl-primary);   box-shadow: 0 2px 8px rgba(4,83,203,.3); }
.risk-tab.medium.active   { background: var(--rl-secondary); color: #fff; border-color: var(--rl-secondary); box-shadow: 0 2px 8px rgba(94,145,222,.3); }

/* ── TABLE ── */
.rel-table-wrap {
    background: var(--rl-white);
    border: 1px solid var(--rl-border);
    border-radius: 12px;
    overflow: hidden;
    position: relative;
    min-height: 120px;
}
.rel-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .85rem;
}
.rel-table thead {
    background: var(--rl-surface);
    border-bottom: 1px solid var(--rl-border);
}
.rel-table th {
    padding: .75rem 1rem;
    text-align: left;
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--rl-muted);
    white-space: nowrap;
}
.rel-table td {
    padding: .85rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.rel-table tr:last-child td { border-bottom: none; }
.rel-table tbody tr:hover td { background: #f8fafc; }

/* Student cell */
.stud-cell { display: flex; align-items: center; gap: .75rem; }
.stud-avatar {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--rl-primary), var(--rl-secondary));
    display: flex; align-items: center; justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: .8rem;
    flex-shrink: 0;
}
.stud-name { font-weight: 600; color: var(--rl-dark); font-size: .88rem; line-height: 1.2; }
.stud-matricule { font-size: .72rem; color: var(--rl-muted); }

/* Risk badge */
.rbadge {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .25rem .65rem;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 700;
    white-space: nowrap;
}
.rbadge.critical { background: rgba(30,41,59,.1); color: var(--rl-dark); border: 1px solid rgba(30,41,59,.2); }
.rbadge.high     { background: rgba(4,83,203,.1);  color: var(--rl-primary); border: 1px solid rgba(4,83,203,.2); }
.rbadge.medium   { background: rgba(94,145,222,.12); color: #2563eb; border: 1px solid rgba(94,145,222,.25); }
.rbadge.low      { background: rgba(16,185,129,.1); color: #059669; border: 1px solid rgba(16,185,129,.2); }

/* Progress bar */
.pbar-wrap { display: flex; align-items: center; gap: .6rem; min-width: 110px; }
.pbar-track {
    flex: 1;
    height: 5px;
    background: #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
}
.pbar-fill { height: 100%; border-radius: 10px; transition: width .4s ease; }
.pbar-fill.full    { background: var(--rl-success); }
.pbar-fill.partial { background: var(--rl-secondary); }
.pbar-fill.low-pay { background: var(--rl-primary); }
.pbar-fill.none    { background: var(--rl-dark); width: 4px !important; }
.pbar-pct { font-size: .72rem; font-weight: 700; color: var(--rl-muted); white-space: nowrap; min-width: 28px; }

/* Amount cells */
.amount-cell { font-weight: 600; white-space: nowrap; }
.amount-unit { font-size: .7em; opacity: .5; }
.amount-red  { color: var(--rl-primary); }

/* Action buttons */
.act-btn {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .35rem .75rem;
    border-radius: 7px;
    font-size: .75rem;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: opacity .15s, transform .1s;
    white-space: nowrap;
}
.act-btn:hover { opacity: .85; transform: translateY(-1px); }
.act-btn.primary { background: var(--rl-primary); color: #fff; }
.act-btn.ghost   { background: transparent; border: 1px solid var(--rl-border); color: var(--rl-muted); }
.act-btn.ghost:hover { border-color: var(--rl-primary); color: var(--rl-primary); }

/* ── EMPTY STATE ── */
.empty-state { text-align: center; padding: 4rem 2rem; color: var(--rl-muted); }
.empty-state .empty-icon { font-size: 3rem; margin-bottom: 1rem; opacity: .25; color: var(--rl-success); }
.empty-state h5 { font-weight: 600; color: var(--rl-dark); margin-bottom: .5rem; }
.empty-state p { font-size: .85rem; }

/* ── PAGINATION ── */
.rel-pagination {
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--rl-border);
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: .75rem;
}
.rel-pagination .page-info { font-size: .8rem; color: var(--rl-muted); }

/* ── LOADING OVERLAY ── */
.rel-loading {
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,.75);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    border-radius: 12px;
    backdrop-filter: blur(2px);
    opacity: 0;
    pointer-events: none;
    transition: opacity .2s;
}
.rel-loading.visible { opacity: 1; pointer-events: all; }
.rel-spinner {
    width: 28px; height: 28px;
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
    .kpi-strip { grid-template-columns: repeat(2, 1fr); }
    .rel-table th:nth-child(5),
    .rel-table td:nth-child(5) { display: none; }
    .rel-hero-title { font-size: 1.25rem; }
}
@media (max-width: 576px) {
    .filters-bar { flex-direction: column; }
    .filter-group { min-width: 100%; }
    .kpi-strip { grid-template-columns: repeat(2, 1fr); }
}
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
<div class="main-content">

    {{-- ── HERO ── --}}
    <div class="rel-hero">
        <div style="position:relative;z-index:1;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
            <div>
                <h1 class="rel-hero-title">
                    <i class="fas fa-bell-slash" style="margin-right:.5rem;opacity:.8;"></i>
                    Gestion des Relances
                    @if($anneeActive)
                        <span style="display:inline-flex;align-items:center;gap:.3rem;background:rgba(16,185,129,.18);color:#10b981;border:1px solid rgba(16,185,129,.35);border-radius:20px;padding:.15rem .65rem;font-size:.6rem;font-weight:600;letter-spacing:.04em;vertical-align:middle;margin-left:.5rem;">
                            <i class="fas fa-circle" style="font-size:.4rem;"></i>
                            {{ $anneeActive->name }}
                        </span>
                    @endif
                </h1>
                <p class="rel-hero-sub">
                    Étudiants avec soldes impayés &mdash; toutes années filtrables
                </p>
            </div>
            <div class="rel-hero-actions">
                @can('comptabilite.reports.export')
                <a href="{{ route('esbtp.comptabilite.relances.export-excel', request()->query()) }}" class="btn-hero-ghost">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
                <a href="{{ route('esbtp.comptabilite.relances.export-pdf', request()->query()) }}" class="btn-hero-ghost">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
                @endcan
                <a href="{{ route('esbtp.comptabilite.relances.config') }}" class="btn-hero-ghost">
                    <i class="fas fa-cog"></i> Configuration
                </a>
                <a href="{{ route('esbtp.paiements.index') }}" class="btn-hero-ghost">
                    <i class="fas fa-list-alt"></i> Tous les paiements
                </a>
            </div>
        </div>
    </div>

    {{-- ── BANNER CONFIG MANQUANTE ── --}}
    @if($configManquante)
    <div style="background:linear-gradient(90deg,#fff8e1 0%,#fffde7 100%);border:1.5px solid #f59e0b;border-radius:12px;padding:.9rem 1.2rem;margin-bottom:1rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:.6rem;color:#92400e;">
            <i class="fas fa-exclamation-triangle" style="font-size:1.1rem;"></i>
            <span style="font-weight:600;font-size:.9rem;">Les délais de relance ne sont pas configurés — les niveaux de risque ne peuvent pas être calculés.</span>
        </div>
        <a href="{{ route('esbtp.comptabilite.relances.config') }}" style="background:#0453cb;color:#fff;padding:.45rem 1rem;border-radius:8px;font-size:.82rem;font-weight:600;white-space:nowrap;text-decoration:none;">
            <i class="fas fa-cog me-1"></i> Configurer maintenant
        </a>
    </div>
    @endif

    {{-- ── KPI STRIP ── --}}
    <div class="kpi-strip">

        {{-- Total impayé (validé uniquement) --}}
        <div class="kpi-card impaye">
            <div class="kpi-label">Total impayé</div>
            <div class="kpi-value big">
                <span id="kpi-total-impaye">{{ number_format($kpis['total_impaye'], 0, ',', ' ') }}</span>
                <span style="font-size:.65em;font-weight:400;color:var(--rl-muted);">FCFA</span>
            </div>
            @if(($kpis['total_en_attente'] ?? 0) > 0)
            <div class="kpi-sub info-hint">
                <i class="fas fa-info-circle" style="font-size:.7em;"></i>
                Paiements validés uniquement
            </div>
            @else
            <div class="kpi-sub info-hint">
                <i class="fas fa-shield-check" style="font-size:.7em;"></i>
                Soldes confirmés
            </div>
            @endif
            <i class="fas fa-exclamation-circle kpi-icon"></i>
        </div>

        {{-- Paiements en attente de validation --}}
        <div class="kpi-card pending" title="Paiements enregistrés mais non encore validés par le secrétariat">
            <div class="kpi-label">En attente de validation</div>
            <div class="kpi-value big">
                <span id="kpi-total-en-attente">{{ number_format($kpis['total_en_attente'] ?? 0, 0, ',', ' ') }}</span>
                <span style="font-size:.65em;font-weight:400;color:var(--rl-muted);">FCFA</span>
            </div>
            <div class="kpi-sub pending-hint">
                <i class="fas fa-clock" style="font-size:.7em;"></i>
                Non déduits du solde
            </div>
            <i class="fas fa-clock kpi-icon" style="color:#f59e0b;"></i>
        </div>

        <div class="kpi-card critical" data-risk="critical" title="Cliquer pour filtrer">
            <div class="kpi-label">Impayés (0% réglé)</div>
            <div class="kpi-value" id="kpi-count-critical">{{ $kpis['count_critical'] }}</div>
            <i class="fas fa-ban kpi-icon"></i>
        </div>
        <div class="kpi-card high" data-risk="high" title="Cliquer pour filtrer">
            <div class="kpi-label">En cours (partiel)</div>
            <div class="kpi-value" id="kpi-count-high">{{ $kpis['count_high'] }}</div>
            <i class="fas fa-hourglass-half kpi-icon"></i>
        </div>
        <div class="kpi-card medium" data-risk="medium" title="Cliquer pour filtrer">
            <div class="kpi-label">Presque soldés (≥ 75%)</div>
            <div class="kpi-value" id="kpi-count-medium">{{ $kpis['count_medium'] }}</div>
            <i class="fas fa-tasks kpi-icon"></i>
        </div>
        <div class="kpi-card low">
            <div class="kpi-label">À jour</div>
            <div class="kpi-value" id="kpi-count-low">{{ $kpis['count_low'] }}</div>
            <i class="fas fa-check-circle kpi-icon"></i>
        </div>
    </div>

    {{-- ── FILTERS ── --}}
    <form id="relances-filters-form" method="GET" action="{{ route('esbtp.comptabilite.relances.index') }}">
        <div class="filters-bar">

            {{-- Recherche --}}
            <div class="filter-group" style="flex:2;min-width:200px;">
                <label class="filter-label">Recherche</label>
                <div class="search-wrap">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" id="filter-search" class="filter-control" placeholder="Nom, prénom, matricule…" value="{{ $search }}">
                </div>
            </div>

            {{-- Filière --}}
            <div class="filter-group">
                <label class="filter-label">Filière</label>
                <select name="filiere_id" class="filter-control">
                    <option value="">Toutes</option>
                    @foreach ($filieres as $f)
                        <option value="{{ $f->id }}" {{ $filiereId == $f->id ? 'selected' : '' }}>{{ $f->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Classe --}}
            <div class="filter-group">
                <label class="filter-label">Classe</label>
                <select name="classe_id" class="filter-control">
                    <option value="">Toutes</option>
                    @foreach ($classes as $c)
                        <option value="{{ $c->id }}" {{ $classeId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Année --}}
            <div class="filter-group">
                <label class="filter-label">Année</label>
                <select name="annee_id" class="filter-control">
                    @foreach ($annees as $a)
                        <option value="{{ $a->id }}" {{ $anneeId == $a->id ? 'selected' : '' }}>{{ $a->name }}@if($a->is_current) (en cours)@endif</option>
                    @endforeach
                </select>
            </div>

            {{-- Par page --}}
            <div class="filter-group" style="min-width:90px;max-width:110px;">
                <label class="filter-label">Par page</label>
                <select name="per_page" class="filter-control">
                    @foreach ([10, 25, 50, 100] as $pp)
                        <option value="{{ $pp }}" {{ $perPage == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Champ caché pour le filtre de risque actif --}}
            <input type="hidden" name="risk" id="risk-hidden" value="{{ $riskFilter }}">

            <div style="display:flex;gap:.5rem;align-items:flex-end;">
                <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filtrer</button>
                <button type="button" id="btn-reset" class="btn-filter-ghost"><i class="fas fa-times"></i> Reset</button>
            </div>
        </div>
    </form>

    {{-- ── RISK TABS ── --}}
    <div class="risk-tabs" id="risk-tabs">
        <button type="button" class="risk-tab all {{ !$riskFilter ? 'active' : '' }}"
                data-risk="">
            <i class="fas fa-list"></i>
            Tous avec dettes <span class="tab-count">(<span id="tab-count-all">{{ $kpis['total_etudiants'] }}</span>)</span>
        </button>
        <button type="button" class="risk-tab critical {{ $riskFilter === 'critical' ? 'active' : '' }}"
                data-risk="critical"
                title="Aucun paiement effectué — 0% du total réglé">
            <i class="fas fa-ban"></i>
            Impayés <span class="tab-count">(<span id="tab-count-critical">{{ $kpis['count_critical'] }}</span>)</span>
        </button>
        <button type="button" class="risk-tab high {{ $riskFilter === 'high' ? 'active' : '' }}"
                data-risk="high"
                title="Paiement commencé mais solde important encore dû (> 25% restant)">
            <i class="fas fa-hourglass-half"></i>
            En cours <span class="tab-count">(<span id="tab-count-high">{{ $kpis['count_high'] }}</span>)</span>
        </button>
        <button type="button" class="risk-tab medium {{ $riskFilter === 'medium' ? 'active' : '' }}"
                data-risk="medium"
                title="Au moins 75% réglé — faible solde restant (≤ 25% du total)">
            <i class="fas fa-tasks"></i>
            Presque soldés <span class="tab-count">(<span id="tab-count-medium">{{ $kpis['count_medium'] }}</span>)</span>
        </button>
    </div>

    {{-- ── TABLE WRAP (cible AJAX) ── --}}
    <div class="rel-table-wrap" id="relances-table-wrap">

        {{-- Spinner overlay --}}
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

    /* ── helpers ── */
    function showLoader()  { loading.classList.add('visible'); }
    function hideLoader()  { loading.classList.remove('visible'); }

    function setActiveTab(risk) {
        tabs.forEach(btn => btn.classList.remove('active'));
        tabs.forEach(btn => {
            if (btn.dataset.risk === risk) btn.classList.add('active');
        });
    }

    /**
     * Fetch table HTML via AJAX and inject into wrapper (below the spinner).
     * Also updates browser URL so refresh / back button work correctly.
     */
    function fetchTable(params) {
        showLoader();

        const url = new URL('{{ route('esbtp.comptabilite.relances.index') }}', window.location.origin);
        Object.entries(params).forEach(([k, v]) => {
            if (v !== null && v !== undefined && v !== '') {
                url.searchParams.set(k, v);
            }
        });

        // Update browser URL (no reload)
        window.history.pushState({}, '', url.toString());

        fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            // Replace table content (keep spinner in place)
            const tmp = document.createElement('div');
            tmp.innerHTML = data.table;

            // Remove old table content (everything except the spinner)
            Array.from(tableWrap.children).forEach(child => {
                if (!child.classList.contains('rel-loading')) child.remove();
            });

            // Append new content
            Array.from(tmp.childNodes).forEach(node => tableWrap.appendChild(node));

            // Update KPI cards
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

            // Wire pagination links to AJAX too
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

    /* ── Tab click → AJAX ── */
    tabs.forEach(btn => {
        btn.addEventListener('click', function () {
            const risk = this.dataset.risk;
            riskHidden.value = risk;
            setActiveTab(risk);
            const params = collectFormParams();
            params.page = 1; // reset to first page on tab change
            fetchTable(params);
        });
    });

    /* ── KPI card click → same as tab click ── */
    document.querySelectorAll('.kpi-card[data-risk]').forEach(card => {
        card.addEventListener('click', function () {
            const risk = this.dataset.risk;
            riskHidden.value = risk;
            setActiveTab(risk);
            const params = collectFormParams();
            params.page = 1;
            fetchTable(params);
        });
    });

    /* ── Form submit → AJAX ── */
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const params = collectFormParams();
        params.page = 1;
        fetchTable(params);
    });

    /* ── Reset button ── */
    btnReset.addEventListener('click', function () {
        form.reset();
        riskHidden.value = '';
        setActiveTab('');
        fetchTable({ page: 1 });
    });

    /* ── Per-page select → auto submit ── */
    form.querySelector('select[name="per_page"]').addEventListener('change', function () {
        const params = collectFormParams();
        params.page = 1;
        fetchTable(params);
    });

    /* ── Wire pagination links ── */
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

    // Wire initial pagination links
    wirePaginationLinks();

})();
</script>
@endpush
