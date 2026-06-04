@extends('layouts.app')

@section('title', 'Bulletins — KLASSCI')

@push('styles')
<style>
:root {
    --bul-primary: #0453cb;
    --bul-primary-d: #033a8e;
    --bul-secondary: #5e91de;
    --bul-accent: #3b7ddb;
    --bul-text: #1e293b;
    --bul-muted: #64748b;
    --bul-surface: #f8fafc;
    --bul-border: #e2e8f0;
    --bul-success: #10b981;
    --bul-warning: #f59e0b;
    --bul-danger: #dc2626;
}

/* ── HERO ───────────────────────────────────────────── */
.bul-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4, 83, 203, .18);
}

.bul-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

.bul-hero-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.bul-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255, 255, 255, .12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, .15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}

.bul-hero h1 {
    font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0;
    letter-spacing: -.01em;
}

.bul-hero-sub {
    color: rgba(255, 255, 255, .72);
    font-size: .88rem;
    margin: .15rem 0 0;
}

.bul-hero-actions {
    display: flex;
    gap: .55rem;
    flex-wrap: wrap;
}

.bul-btn {
    display: inline-flex; align-items: center; gap: .45rem;
    border: 1px solid transparent;
    border-radius: 10px;
    padding: .5rem 1rem;
    font-size: .82rem; font-weight: 600;
    line-height: 1.2;
    cursor: pointer;
    text-decoration: none;
    transition: background .15s, border-color .15s, color .15s, transform .12s;
    white-space: nowrap;
}
.bul-btn:disabled, .bul-btn[aria-disabled="true"] { opacity: .55; cursor: not-allowed; }
.bul-btn--glass {
    background: rgba(255, 255, 255, .15);
    color: #fff;
    border-color: rgba(255, 255, 255, .2);
}
.bul-btn--glass:hover { background: rgba(255, 255, 255, .22); color: #fff; }
.bul-btn--white { background: #fff; color: var(--bul-primary); }
.bul-btn--white:hover { background: #f1f5f9; color: var(--bul-primary-d); }
.bul-btn--primary { background: var(--bul-primary); color: #fff; }
.bul-btn--primary:hover { background: var(--bul-primary-d); color: #fff; }
.bul-btn--ghost {
    background: transparent;
    color: var(--bul-primary);
    border-color: rgba(4, 83, 203, .25);
}
.bul-btn--ghost:hover { background: rgba(4, 83, 203, .06); border-color: var(--bul-primary); }
.bul-btn--danger { background: rgba(220, 38, 38, .08); color: var(--bul-danger); border-color: rgba(220, 38, 38, .25); }
.bul-btn--danger:hover { background: var(--bul-danger); color: #fff; }
.bul-btn--sm { padding: .35rem .65rem; font-size: .75rem; border-radius: 8px; }

/* ── KPIs hero ──────────────────────────────────────── */
.bul-kpis {
    display: flex;
    gap: .75rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}
.bul-kpi {
    flex: 1; min-width: 160px;
    background: rgba(255, 255, 255, .1);
    border: 1px solid rgba(255, 255, 255, .15);
    border-radius: 12px;
    padding: .9rem 1rem;
    display: flex; align-items: center; gap: .85rem;
}
.bul-kpi-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    background: rgba(255, 255, 255, .14);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .9rem;
    flex-shrink: 0;
}
.bul-kpi-body { display: flex; flex-direction: column; gap: .1rem; min-width: 0; }
.bul-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1; }
.bul-kpi-label { font-size: .68rem; color: rgba(255, 255, 255, .7); font-weight: 600; letter-spacing: .3px; text-transform: uppercase; }
.bul-kpi-trail {
    height: 4px; border-radius: 99px;
    background: rgba(255, 255, 255, .15);
    overflow: hidden; margin-top: .35rem;
}
.bul-kpi-trail-fill { height: 100%; background: linear-gradient(90deg, #10b981, #34d399); border-radius: 99px; }

/* ── CARD générique ─────────────────────────────────── */
.bul-card {
    background: #fff;
    border: 1px solid var(--bul-border);
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .04), 0 1px 2px rgba(15, 23, 42, .06);
    padding: 1.25rem 1.5rem;
}
.bul-card + .bul-card { margin-top: 1rem; }

/* ── Filter bar : tous filtres + boutons sur une seule rangée flex ─── */
.bul-filters {
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
    align-items: flex-end;
}
.bul-filter-field {
    display: flex; flex-direction: column; gap: .35rem;
    flex: 1 1 180px;
    min-width: 160px;
}
.bul-filter-field--search { flex: 1.5 1 220px; }
.bul-filter-label {
    font-size: .68rem; font-weight: 700;
    color: var(--bul-muted);
    text-transform: uppercase;
    letter-spacing: .4px;
}
.bul-search {
    display: flex; align-items: center; gap: .55rem;
    background: var(--bul-surface);
    border: 1px solid var(--bul-border);
    border-radius: 10px;
    padding: .45rem .75rem;
    transition: border-color .15s, background .15s;
    height: 40px;
}
.bul-search:focus-within { border-color: var(--bul-primary); background: #fff; }
.bul-search i { color: var(--bul-muted); font-size: .82rem; }
.bul-search input {
    border: none; background: transparent; outline: none;
    font-size: .86rem; color: var(--bul-text); width: 100%;
}
.bul-filters-actions {
    display: flex; gap: .55rem; align-items: center;
    flex-shrink: 0;
    align-self: flex-end;
    padding-bottom: 0;
}
.bul-loader-indicator {
    display: inline-flex; align-items: center; gap: .35rem;
    color: var(--bul-primary);
    font-size: .78rem;
    font-weight: 600;
}
@media (max-width: 768px) {
    .bul-filters { gap: .55rem; }
    .bul-filter-field, .bul-filter-field--search { flex: 1 1 100%; min-width: 100%; }
    .bul-filters-actions { width: 100%; justify-content: flex-end; }
}

/* ── Bulk actions bar ───────────────────────────────── */
.bul-bulkbar {
    display: flex; align-items: center; justify-content: space-between;
    gap: .85rem; flex-wrap: wrap;
    padding: .65rem .85rem;
    margin-bottom: 1rem;
    border-radius: 10px;
    background: linear-gradient(135deg, rgba(4, 83, 203, .08), rgba(59, 125, 219, .10));
    border: 1px solid rgba(4, 83, 203, .20);
}
.bul-bulkbar-info {
    display: flex; align-items: center; gap: .55rem;
    font-size: .85rem; color: var(--bul-text);
}
.bul-bulkbar-info strong { color: var(--bul-primary); font-weight: 700; }
.bul-bulkbar-actions { display: flex; gap: .55rem; flex-wrap: wrap; }

/* ── Legacy banner ──────────────────────────────────── */
.bul-banner {
    display: flex; align-items: center; gap: .75rem;
    padding: .65rem 1rem;
    background: rgba(245, 158, 11, .08);
    border: 1px solid rgba(245, 158, 11, .25);
    border-radius: 10px;
    color: #92400e;
    font-size: .82rem;
    margin-bottom: 1rem;
}
.bul-banner i { color: var(--bul-warning); }
.bul-banner-action { margin-left: auto; }

/* ── Table premium ──────────────────────────────────── */
.bul-table-wrap {
    border: 1px solid var(--bul-border);
    border-radius: 12px;
    background: #fff;
    overflow: hidden;
    transition: opacity .15s;
}
.bul-table-wrap--loading { opacity: .55; pointer-events: none; }
.bul-page {
    display: inline-flex; align-items: center; justify-content: center;
    width: 32px; height: 32px;
    color: var(--bul-primary);
    background: #fff;
    border: 1px solid var(--bul-border);
    border-radius: 8px;
    text-decoration: none;
    font-size: .78rem;
    transition: background .12s, color .12s, border-color .12s;
    cursor: pointer;
}
.bul-page:hover { background: rgba(4, 83, 203, .08); color: var(--bul-primary-d); border-color: rgba(4, 83, 203, .25); }
.bul-page.disabled { color: #cbd5e1; cursor: not-allowed; background: var(--bul-surface); }
.bul-pages li { display: flex; }
.bul-table {
    width: 100%; border-collapse: collapse;
}
.bul-table thead th {
    background: var(--bul-surface);
    color: var(--bul-muted);
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    padding: .75rem 1rem;
    text-align: left;
    border-bottom: 1px solid var(--bul-border);
    white-space: nowrap;
}
.bul-table thead th.center { text-align: center; }
.bul-table tbody td {
    padding: .8rem 1rem;
    font-size: .85rem;
    color: var(--bul-text);
    border-bottom: 1px solid var(--bul-border);
    vertical-align: middle;
}
.bul-table tbody tr:last-child td { border-bottom: none; }
.bul-table tbody tr { transition: background .12s; }
.bul-table tbody tr:hover { background: rgba(4, 83, 203, .03); }
.bul-table tbody td.center { text-align: center; }
.bul-table .checkbox-col { width: 36px; padding: .8rem .65rem .8rem 1rem; }
.bul-table .checkbox-col input[type="checkbox"] { cursor: pointer; }

.bul-etu {
    display: flex; align-items: center; gap: .65rem;
    min-width: 0;
}
.bul-etu-avatar {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--bul-primary), var(--bul-accent));
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .78rem;
    flex-shrink: 0;
}
.bul-etu-meta { display: flex; flex-direction: column; min-width: 0; gap: .1rem; }
.bul-etu-name {
    font-weight: 600; font-size: .88rem;
    color: var(--bul-text);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.bul-etu-matricule { font-size: .7rem; color: var(--bul-muted); font-family: 'Courier New', monospace; }

.bul-periode-badge {
    display: inline-flex; align-items: center;
    padding: .22rem .55rem;
    border-radius: 6px;
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .3px;
}
.bul-periode-badge.s1 { background: rgba(4, 83, 203, .10); color: var(--bul-primary); border: 1px solid rgba(4, 83, 203, .22); }
.bul-periode-badge.s2 { background: rgba(59, 125, 219, .10); color: var(--bul-accent); border: 1px solid rgba(59, 125, 219, .22); }
.bul-periode-badge.annuel { background: rgba(245, 158, 11, .10); color: #b45309; border: 1px solid rgba(245, 158, 11, .25); }

.bul-moy-pill {
    display: inline-flex; align-items: center; gap: .25rem;
    padding: .22rem .55rem;
    border-radius: 6px;
    font-weight: 700; font-size: .78rem;
    font-variant-numeric: tabular-nums;
}
.bul-moy-pill.good { background: rgba(16, 185, 129, .10); color: var(--bul-success); border: 1px solid rgba(16, 185, 129, .22); }
.bul-moy-pill.mid  { background: rgba(245, 158, 11, .10); color: #b45309; border: 1px solid rgba(245, 158, 11, .22); }
.bul-moy-pill.bad  { background: rgba(220, 38, 38, .10); color: var(--bul-danger); border: 1px solid rgba(220, 38, 38, .22); }
.bul-moy-pill.na   { background: rgba(148, 163, 184, .15); color: var(--bul-muted); }

.bul-status-badge {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .22rem .55rem;
    border-radius: 6px;
    font-size: .72rem; font-weight: 700;
}
.bul-status-badge.published { background: rgba(16, 185, 129, .10); color: var(--bul-success); border: 1px solid rgba(16, 185, 129, .22); }
.bul-status-badge.pending { background: rgba(245, 158, 11, .10); color: #b45309; border: 1px solid rgba(245, 158, 11, .22); }

.bul-rang-display {
    display: inline-flex; align-items: baseline; gap: .25rem;
    font-weight: 700; color: var(--bul-text); font-variant-numeric: tabular-nums;
}
.bul-rang-display sup { font-size: .55em; color: var(--bul-muted); font-weight: 500; }
.bul-rang-display .over { font-size: .72rem; color: var(--bul-muted); margin-left: .2rem; font-weight: 500; }

.bul-actions {
    display: inline-flex; align-items: center; gap: .25rem;
}
.bul-action {
    width: 30px; height: 30px;
    display: inline-flex; align-items: center; justify-content: center;
    background: transparent; color: var(--bul-muted);
    border: 1px solid transparent;
    border-radius: 7px;
    cursor: pointer; text-decoration: none;
    font-size: .82rem;
    transition: background .15s, color .15s, border-color .15s;
}
.bul-action:hover { background: rgba(4, 83, 203, .08); color: var(--bul-primary); border-color: rgba(4, 83, 203, .12); }
.bul-action.danger:hover { background: rgba(220, 38, 38, .08); color: var(--bul-danger); border-color: rgba(220, 38, 38, .15); }

/* ── Empty state ────────────────────────────────────── */
.bul-empty {
    display: flex; flex-direction: column; align-items: center;
    padding: 3rem 1.5rem;
    color: var(--bul-muted);
    text-align: center;
}
.bul-empty-icon {
    width: 72px; height: 72px;
    border-radius: 18px;
    background: linear-gradient(135deg, rgba(4, 83, 203, .08), rgba(59, 125, 219, .12));
    color: var(--bul-primary);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.55rem;
    margin-bottom: 1rem;
}
.bul-empty-title { font-size: 1.05rem; color: var(--bul-text); font-weight: 700; margin-bottom: .3rem; }
.bul-empty-msg { font-size: .85rem; max-width: 360px; line-height: 1.5; margin-bottom: 1rem; }

/* ── Pagination ─────────────────────────────────────── */
.bul-pager {
    padding: .85rem 1rem;
    border-top: 1px solid var(--bul-border);
    background: var(--bul-surface);
    display: flex; align-items: center; justify-content: space-between;
    gap: .85rem; flex-wrap: wrap;
}
.bul-pager-info { font-size: .78rem; color: var(--bul-muted); }
.bul-pager-info strong { color: var(--bul-text); font-weight: 700; }
.bul-pager nav { margin: 0; }

@media (max-width: 768px) {
    .bul-hero { padding: 1.5rem 1.25rem 1.25rem; }
    .bul-hero h1 { font-size: 1.2rem; }
    .bul-kpis { gap: .55rem; }
    .bul-kpi { min-width: 140px; padding: .7rem .85rem; }
    .bul-table thead { display: none; }
    .bul-table tbody td { display: block; border-bottom: none; padding: .35rem 1rem; }
    .bul-table tbody tr {
        display: block;
        padding: .65rem 0;
        border-bottom: 1px solid var(--bul-border);
    }
    .bul-actions { justify-content: flex-end; padding-top: .35rem; }
}

/* ── Toasts ─────────────────────────────────────────── */
.bul-toast-stack {
    position: fixed;
    bottom: 1.25rem; right: 1.25rem;
    display: flex; flex-direction: column; gap: .5rem;
    z-index: 99999;
    max-width: 380px;
    pointer-events: none;
}
.bul-toast {
    pointer-events: auto;
    display: flex; align-items: center; gap: .55rem;
    background: #fff;
    border: 1px solid var(--bul-border);
    border-radius: 10px;
    padding: .65rem .85rem;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .12);
    font-size: .85rem;
    color: var(--bul-text);
}
.bul-toast--success { border-left: 4px solid var(--bul-success); }
.bul-toast--success i { color: var(--bul-success); }
.bul-toast--error { border-left: 4px solid var(--bul-danger); }
.bul-toast--error i { color: var(--bul-danger); }
.bul-toast--info { border-left: 4px solid var(--bul-primary); }
.bul-toast--info i { color: var(--bul-primary); }
.bul-toast-close {
    background: transparent; border: none; cursor: pointer;
    color: var(--bul-muted); padding: 0; margin-left: auto;
    font-size: .85rem;
}
.bul-toast-close:hover { color: var(--bul-text); }
</style>
@endpush

@section('content')
<div class="container-fluid" x-data="bulIndex()" x-init="init()">
    {{-- ══ HERO ═══════════════════════════════════════════ --}}
    <div class="bul-hero">
        <div class="bul-hero-top">
            <div class="bul-hero-left">
                <div class="bul-hero-icon"><i class="fas fa-file-alt"></i></div>
                <div>
                    <h1>Bulletins</h1>
                    <p class="bul-hero-sub">Génération, suivi et publication des bulletins de notes</p>
                </div>
            </div>
            <div class="bul-hero-actions">
                @can('bulletins.generate')
                <a href="{{ route('esbtp.bulletins.select') }}" class="bul-btn bul-btn--white">
                    <i class="fas fa-magic-wand-sparkles"></i> Générer
                </a>
                @endcan
                @can('bulletins.configure')
                <a href="{{ route('esbtp.bulletins.configuration') }}"
                   class="bul-btn bul-btn--glass" title="Paramètres bulletin">
                    <i class="fas fa-sliders"></i> Configuration
                </a>
                @endcan
            </div>
        </div>

        <div class="bul-kpis">
            <div class="bul-kpi">
                <div class="bul-kpi-icon"><i class="fas fa-file-lines"></i></div>
                <div class="bul-kpi-body">
                    <div class="bul-kpi-value">{{ number_format($stats['total']) }}</div>
                    <div class="bul-kpi-label">Bulletins {{ $annee_id ? 'année active' : 'tous' }}</div>
                </div>
            </div>
            <div class="bul-kpi">
                <div class="bul-kpi-icon"><i class="fas fa-circle-check"></i></div>
                <div class="bul-kpi-body" style="flex:1;">
                    <div style="display:flex; align-items:baseline; gap:.4rem;">
                        <span class="bul-kpi-value">{{ $stats['published'] }}</span>
                        <span style="font-size:.7rem; color:rgba(255,255,255,.6); font-weight:600;">/ {{ $stats['total'] }}</span>
                    </div>
                    <div class="bul-kpi-label">Publiés ({{ $stats['publish_pct'] }}%)</div>
                    <div class="bul-kpi-trail"><div class="bul-kpi-trail-fill" style="width:{{ $stats['publish_pct'] }}%;"></div></div>
                </div>
            </div>
            <div class="bul-kpi">
                <div class="bul-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
                <div class="bul-kpi-body">
                    <div class="bul-kpi-value">{{ $stats['pending'] }}</div>
                    <div class="bul-kpi-label">En attente</div>
                </div>
            </div>
            <div class="bul-kpi">
                <div class="bul-kpi-icon"><i class="fas fa-user-graduate"></i></div>
                <div class="bul-kpi-body">
                    <div class="bul-kpi-value">{{ $stats['covered'] }}</div>
                    <div class="bul-kpi-label">Étudiants couverts</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bandeau session messages --}}
    @if(session('success'))
        <div class="bul-banner" style="background:rgba(16,185,129,.08); border-color:rgba(16,185,129,.25); color:#065f46;">
            <i class="fas fa-circle-check" style="color:var(--bul-success);"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="bul-banner" style="background:rgba(220,38,38,.08); border-color:rgba(220,38,38,.25); color:#7f1d1d;">
            <i class="fas fa-circle-exclamation" style="color:var(--bul-danger);"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Bandeau bulletins legacy annuel --}}
    @if($stats['legacy_annuel'] > 0)
        <div class="bul-banner">
            <i class="fas fa-triangle-exclamation"></i>
            <span>
                <strong>{{ $stats['legacy_annuel'] }} bulletin{{ $stats['legacy_annuel'] > 1 ? 's' : '' }} legacy "Annuel"</strong>
                en base. Le bulletin annuel n'est pas une période standard — la moyenne annuelle est intégrée dans le bulletin S2.
            </span>
            <a href="{{ route('esbtp.bulletins.index', array_merge(request()->query(), ['periode_id' => 'annuel'])) }}"
               class="bul-btn bul-btn--sm bul-btn--ghost bul-banner-action">
                Voir ces bulletins
            </a>
        </div>
    @endif

    {{-- ══ FILTRES ════════════════════════════════════════ --}}
    <div class="bul-card">
        <form id="bul-filter-form" action="{{ route('esbtp.bulletins.index') }}" method="GET"
              class="bul-filters" @submit.prevent="fetchPage(1)">
            <div class="bul-filter-field">
                <label class="bul-filter-label">Année universitaire</label>
                <x-au-select
                    name="annee_universitaire_id"
                    :value="$annee_id"
                    placeholder="Toutes les années"
                    icon="fa-calendar"
                    :options="$anneesUniversitaires->mapWithKeys(fn($a) => [$a->id => $a->name])->toArray()" />
            </div>

            <div class="bul-filter-field">
                <label class="bul-filter-label">Classe</label>
                <x-au-select
                    name="classe_id"
                    :value="$classe_id"
                    placeholder="Toutes les classes"
                    icon="fa-school"
                    :searchable="$classes->count() > 8"
                    :options="$classes->mapWithKeys(fn($c) => [$c->id => $c->name])->toArray()" />
            </div>

            <div class="bul-filter-field">
                <label class="bul-filter-label">Période</label>
                <x-au-select
                    name="periode_id"
                    :value="$periode_id"
                    placeholder="Toutes les périodes"
                    icon="fa-layer-group"
                    :options="$periodes->mapWithKeys(fn($p) => [$p->id => $p->nom])->toArray()" />
            </div>

            <div class="bul-filter-field">
                <label class="bul-filter-label">Statut</label>
                <x-au-select
                    name="published"
                    :value="$published"
                    placeholder="Tous"
                    icon="fa-circle-check"
                    :options="['1' => 'Publiés', '0' => 'En attente']" />
            </div>

            <div class="bul-filter-field bul-filter-field--search">
                <label class="bul-filter-label">Recherche étudiant</label>
                <div class="bul-search">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Nom, prénom, matricule…"
                           @input.debounce.400ms="fetchPage(1)">
                </div>
            </div>

            <div class="bul-filters-actions">
                <span class="bul-loader-indicator" x-show="loading" x-cloak>
                    <i class="fas fa-spinner fa-spin"></i> Chargement…
                </span>
                <button type="button" class="bul-btn bul-btn--ghost" @click="resetFilters()">
                    <i class="fas fa-rotate-left"></i> Réinitialiser
                </button>
                <button type="submit" class="bul-btn bul-btn--primary" :disabled="loading">
                    <i class="fas fa-filter"></i> Filtrer
                </button>
            </div>
        </form>
    </div>

    {{-- ══ BULK ACTIONS BAR ══════════════════════════════ --}}
    <div class="bul-bulkbar" x-cloak x-show="selected.length > 0">
        <div class="bul-bulkbar-info">
            <i class="fas fa-square-check" style="color:var(--bul-primary);"></i>
            <span><strong x-text="selected.length"></strong> bulletin(s) sélectionné(s)</span>
            <button type="button" class="bul-btn bul-btn--sm bul-btn--ghost" @click="clearSelection()">
                <i class="fas fa-xmark"></i> Désélectionner
            </button>
        </div>
        <div class="bul-bulkbar-actions">
            @can('bulletins.publish.bulk')
            <button type="button" class="bul-btn bul-btn--sm bul-btn--primary"
                    :disabled="busy" @click="bulkPublish()">
                <span x-show="!busy"><i class="fas fa-paper-plane"></i> Publier</span>
                <span x-show="busy" x-cloak><i class="fas fa-spinner fa-spin"></i> ...</span>
            </button>
            @endcan
            @can('bulletins.regenerate.bulk')
            <button type="button" class="bul-btn bul-btn--sm bul-btn--ghost"
                    :disabled="busy" @click="bulkRegenerate()" title="Recalculer les moyennes à partir des données live">
                <i class="fas fa-arrows-rotate"></i> Régénérer
            </button>
            @endcan
            @can('bulletins.delete')
            <button type="button" class="bul-btn bul-btn--sm bul-btn--danger"
                    :disabled="busy" @click="bulkDelete()">
                <i class="fas fa-trash"></i> Supprimer
            </button>
            @endcan
        </div>
    </div>

    {{-- ══ TABLE (partial AJAX) ═══════════════════════════ --}}
    <div class="bul-table-wrap" id="bul-table-wrap" :class="loading ? 'bul-table-wrap--loading' : ''">
        @include('esbtp.bulletins.partials._table', compact('bulletins', 'classe_id', 'periode_id', 'published', 'search'))
    </div>

    {{-- Toast container --}}
    <div class="bul-toast-stack" aria-live="polite">
        <template x-for="t in toasts" :key="t.id">
            <div class="bul-toast" :class="'bul-toast--' + t.type" x-transition.opacity>
                <i :class="t.type === 'success' ? 'fas fa-circle-check' : (t.type === 'error' ? 'fas fa-circle-exclamation' : 'fas fa-circle-info')"></i>
                <span x-text="t.message"></span>
                <button class="bul-toast-close" @click="removeToast(t.id)"><i class="fas fa-xmark"></i></button>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
function bulIndex() {
    return {
        selected: [],
        busy: false,
        loading: false,
        toasts: [],
        toastSeq: 0,
        currentPage: {{ $bulletins->currentPage() }},
        allIds: @json($bulletins->pluck('id')->all()),
        baseUrl: @json(route('esbtp.bulletins.index')),

        init() {
            // AJAX no-reload : intercepter les changes de select premium
            const form = document.getElementById('bul-filter-form');
            if (form) {
                form.querySelectorAll('select[name]').forEach(sel => {
                    sel.addEventListener('change', () => { this.fetchPage(1); });
                });
            }
            // Click sur les liens de pagination dans le partial AJAX
            document.addEventListener('click', (ev) => {
                const a = ev.target.closest('a.bul-page[data-page]');
                if (a && document.getElementById('bul-table-wrap')?.contains(a)) {
                    ev.preventDefault();
                    this.fetchPage(parseInt(a.dataset.page, 10));
                }
            });
            // Bouton retour browser : recharger via fetch
            window.addEventListener('popstate', () => { this.fetchPage(this.currentPage, false); });
            window.addEventListener('toast', (ev) => this.pushToast(ev.detail));
        },

        async fetchPage(page = 1, push = true) {
            this.loading = true;
            this.selected = [];
            try {
                const form = document.getElementById('bul-filter-form');
                const params = new URLSearchParams(new FormData(form));
                params.set('page', page);
                const url = this.baseUrl + '?' + params.toString();
                const res = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });
                if (!res.ok) throw new Error(`Erreur HTTP ${res.status}`);
                const data = await res.json();
                const wrap = document.getElementById('bul-table-wrap');
                if (wrap) wrap.innerHTML = data.html;
                this.allIds = data.ids || [];
                this.currentPage = page;
                this.updateKpis(data.stats);
                if (push) {
                    const cleanUrl = this.baseUrl + '?' + params.toString();
                    window.history.pushState({}, '', cleanUrl);
                }
            } catch (err) {
                this.pushToast({ type: 'error', message: err.message || 'Erreur de chargement.' });
            } finally {
                this.loading = false;
            }
        },

        updateKpis(stats) {
            if (!stats) return;
            const values = document.querySelectorAll('.bul-kpi-value');
            if (values[0]) values[0].textContent = new Intl.NumberFormat('fr-FR').format(stats.total);
            if (values[1]) values[1].textContent = stats.published;
            if (values[2]) values[2].textContent = stats.pending;
            if (values[3]) values[3].textContent = stats.covered;
            const fill = document.querySelector('.bul-kpi-trail-fill');
            if (fill) fill.style.width = (stats.publish_pct || 0) + '%';
            const pctLabel = document.querySelectorAll('.bul-kpi-label')[1];
            if (pctLabel) pctLabel.textContent = `Publiés (${stats.publish_pct || 0}%)`;
        },

        resetFilters() {
            const form = document.getElementById('bul-filter-form');
            if (!form) return;
            form.querySelectorAll('select[name]').forEach(sel => { sel.value = ''; sel.dispatchEvent(new Event('change', { bubbles: true })); });
            const search = form.querySelector('input[name="search"]');
            if (search) search.value = '';
            this.fetchPage(1);
        },

        pushToast(detail) {
            const id = ++this.toastSeq;
            this.toasts.push({ id, type: detail.type || 'info', message: detail.message || '' });
            setTimeout(() => this.removeToast(id), 4500);
        },
        removeToast(id) {
            const idx = this.toasts.findIndex(t => t.id === id);
            if (idx !== -1) this.toasts.splice(idx, 1);
        },

        toggle(id) {
            const idx = this.selected.indexOf(id);
            if (idx === -1) this.selected.push(id);
            else this.selected.splice(idx, 1);
        },
        toggleAll(ev) {
            this.selected = ev.target.checked ? [...this.allIds] : [];
        },
        allSelected() {
            return this.allIds.length > 0 && this.selected.length === this.allIds.length;
        },
        clearSelection() { this.selected = []; },

        async bulkPublish() {
            if (!confirm(`Publier ${this.selected.length} bulletin(s) ?`)) return;
            await this.callBulk('{{ route('esbtp.bulletins.bulk-publish') }}', 'PATCH');
        },
        async bulkRegenerate() {
            if (!confirm(`Régénérer ${this.selected.length} bulletin(s) ? Les moyennes seront recalculées.`)) return;
            await this.callBulk('{{ route('esbtp.bulletins.bulk-regenerate') }}', 'POST');
        },
        async bulkDelete() {
            if (!confirm(`Supprimer définitivement ${this.selected.length} bulletin(s) ?`)) return;
            await this.callBulk('{{ route('esbtp.bulletins.bulk-delete') }}', 'DELETE');
        },
        confirmDelete(id) {
            if (!confirm('Supprimer ce bulletin ?')) return;
            this.selected = [id];
            this.bulkDelete();
        },

        async callBulk(url, method) {
            this.busy = true;
            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ ids: this.selected }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || `Erreur HTTP ${res.status}`);
                this.pushToast({ type: 'success', message: data.message || 'Action effectuée.' });
                // Refresh table sans reload page
                await this.fetchPage(this.currentPage, false);
            } catch (err) {
                this.pushToast({ type: 'error', message: err.message || 'Erreur inattendue.' });
            } finally {
                this.busy = false;
            }
        },
    };
}
</script>
@endpush
@endsection
