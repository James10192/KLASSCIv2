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
.kpi-icon {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.6rem;
    opacity: .07;
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
}
.risk-tab {
    padding: .4rem .9rem;
    border-radius: 20px;
    font-size: .78rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    text-decoration: none;
    display: inline-flex; align-items: center; gap: .35rem;
    transition: opacity .15s, transform .1s;
}
.risk-tab:hover { opacity: .85; transform: translateY(-1px); }
.risk-tab.all      { background: var(--rl-dark); color: #fff; }
.risk-tab.critical { background: rgba(30,41,59,.1); color: var(--rl-dark); border: 1px solid rgba(30,41,59,.25); }
.risk-tab.high     { background: rgba(4,83,203,.1); color: var(--rl-primary); border: 1px solid rgba(4,83,203,.25); }
.risk-tab.medium   { background: rgba(94,145,222,.12); color: #2563eb; border: 1px solid rgba(94,145,222,.3); }
.risk-tab.active.critical { background: rgba(30,41,59,.2); }
.risk-tab.active.high     { background: rgba(4,83,203,.18); }
.risk-tab.active.medium   { background: rgba(94,145,222,.22); }
.risk-tab.active.all      { box-shadow: 0 0 0 3px rgba(30,41,59,.25); }

/* ── TABLE ── */
.rel-table-wrap {
    background: var(--rl-white);
    border: 1px solid var(--rl-border);
    border-radius: 12px;
    overflow: hidden;
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

    {{-- ── HERO ── --}}
    <div class="rel-hero">
        <div style="position:relative;z-index:1;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
            <div>
                <h1 class="rel-hero-title">
                    <i class="fas fa-bell-slash" style="margin-right:.5rem;opacity:.8;"></i>
                    Gestion des Relances
                </h1>
                <p class="rel-hero-sub">
                    Étudiants avec soldes impayés &mdash;
                    @if($anneeActive)
                        {{ $anneeActive->annee_debut }}&ndash;{{ $anneeActive->annee_fin }}
                    @else
                        toutes années
                    @endif
                </p>
            </div>
            <div class="rel-hero-actions">
                <a href="{{ route('esbtp.comptabilite.relances.config') }}" class="btn-hero-ghost">
                    <i class="fas fa-cog"></i> Configuration
                </a>
                <a href="{{ route('esbtp.paiements.index') }}" class="btn-hero-ghost">
                    <i class="fas fa-list-alt"></i> Tous les paiements
                </a>
            </div>
        </div>
    </div>

    {{-- ── KPI STRIP ── --}}
    <div class="kpi-strip">
        <div class="kpi-card impaye">
            <div class="kpi-label">Total impayé</div>
            <div class="kpi-value big">{{ number_format($kpis['total_impaye'], 0, ',', ' ') }} <span style="font-size:.65em;font-weight:400;color:var(--rl-muted);">FCFA</span></div>
            <i class="fas fa-exclamation-circle kpi-icon"></i>
        </div>
        <div class="kpi-card critical">
            <div class="kpi-label">Impayés totaux</div>
            <div class="kpi-value">{{ $kpis['count_critical'] }}</div>
            <i class="fas fa-ban kpi-icon"></i>
        </div>
        <div class="kpi-card high">
            <div class="kpi-label">En retard</div>
            <div class="kpi-value">{{ $kpis['count_high'] }}</div>
            <i class="fas fa-clock kpi-icon"></i>
        </div>
        <div class="kpi-card medium">
            <div class="kpi-label">Partiels</div>
            <div class="kpi-value">{{ $kpis['count_medium'] }}</div>
            <i class="fas fa-adjust kpi-icon"></i>
        </div>
        <div class="kpi-card low">
            <div class="kpi-label">À jour</div>
            <div class="kpi-value">{{ $kpis['count_low'] }}</div>
            <i class="fas fa-check-circle kpi-icon"></i>
        </div>
    </div>

    {{-- ── FILTERS ── --}}
    <form method="GET" action="{{ route('esbtp.comptabilite.relances.index') }}">
        <div class="filters-bar">

            {{-- Recherche --}}
            <div class="filter-group search-wrap" style="flex:2;min-width:200px;">
                <label class="filter-label">Recherche</label>
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="filter-control" placeholder="Nom, prénom, matricule…" value="{{ $search }}">
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
                        <option value="{{ $a->id }}" {{ $anneeId == $a->id ? 'selected' : '' }}>{{ $a->annee_debut }}&ndash;{{ $a->annee_fin }}</option>
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

            {{-- Preserve risk filter --}}
            <input type="hidden" name="risk" value="{{ $riskFilter }}">

            <div style="display:flex;gap:.5rem;align-items:flex-end;">
                <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filtrer</button>
                <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="btn-filter-ghost"><i class="fas fa-times"></i> Reset</a>
            </div>
        </div>
    </form>

    {{-- ── RISK TABS ── --}}
    <div class="risk-tabs">
        @php
            $qBase = array_filter(request()->query(), fn($k) => $k !== 'risk' && $k !== 'page', ARRAY_FILTER_USE_KEY);
        @endphp
        <a href="{{ route('esbtp.comptabilite.relances.index', array_merge($qBase, ['risk' => ''])) }}"
           class="risk-tab all {{ !$riskFilter ? 'active' : '' }}">
            <i class="fas fa-list"></i> Tous avec dettes ({{ $kpis['total_etudiants'] }})
        </a>
        <a href="{{ route('esbtp.comptabilite.relances.index', array_merge($qBase, ['risk' => 'critical'])) }}"
           class="risk-tab critical {{ $riskFilter === 'critical' ? 'active' : '' }}">
            <i class="fas fa-ban"></i> Impayés ({{ $kpis['count_critical'] }})
        </a>
        <a href="{{ route('esbtp.comptabilite.relances.index', array_merge($qBase, ['risk' => 'high'])) }}"
           class="risk-tab high {{ $riskFilter === 'high' ? 'active' : '' }}">
            <i class="fas fa-clock"></i> En retard ({{ $kpis['count_high'] }})
        </a>
        <a href="{{ route('esbtp.comptabilite.relances.index', array_merge($qBase, ['risk' => 'medium'])) }}"
           class="risk-tab medium {{ $riskFilter === 'medium' ? 'active' : '' }}">
            <i class="fas fa-adjust"></i> Partiels ({{ $kpis['count_medium'] }})
        </a>
    </div>

    {{-- ── TABLE ── --}}
    <div class="rel-table-wrap">
        @if ($paginated->isEmpty())
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-check-circle"></i></div>
                <h5>Aucun impayé trouvé</h5>
                <p>Tous les étudiants correspondant à vos filtres sont à jour ou aucun résultat pour cette recherche.</p>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="rel-table">
                    <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>Classe</th>
                            <th>Filière</th>
                            <th>Progression</th>
                            <th>Total dû</th>
                            <th>Solde restant</th>
                            <th>Risque</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($paginated as $row)
                            @php
                                $etudiant  = optional($row->inscription->etudiant);
                                $classe    = optional($row->inscription->classe);
                                $filiere   = optional($classe->filiere);
                                $nom       = $etudiant->nom ?? '';
                                $prenoms   = $etudiant->prenoms ?? '';
                                $initiales = strtoupper(mb_substr($nom, 0, 1) . mb_substr($prenoms, 0, 1));
                                $nomComplet = trim("$nom $prenoms") ?: '(sans nom)';

                                $pbClass = match(true) {
                                    $row->pourcentage >= 100 => 'full',
                                    $row->pourcentage >= 50  => 'partial',
                                    $row->pourcentage > 0    => 'low-pay',
                                    default                   => 'none',
                                };
                            @endphp
                            <tr>
                                {{-- Étudiant --}}
                                <td>
                                    <div class="stud-cell">
                                        <div class="stud-avatar">{{ $initiales ?: '?' }}</div>
                                        <div>
                                            <div class="stud-name">{{ $nomComplet }}</div>
                                            <div class="stud-matricule">{{ $etudiant->matricule ?? '—' }}</div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Classe --}}
                                <td>{{ $classe->name ?? '—' }}</td>

                                {{-- Filière --}}
                                <td>{{ $filiere->code ?? $filiere->name ?? '—' }}</td>

                                {{-- Progression --}}
                                <td>
                                    <div class="pbar-wrap">
                                        <div class="pbar-track">
                                            <div class="pbar-fill {{ $pbClass }}"
                                                 style="width:{{ max(4, $row->pourcentage) }}%"></div>
                                        </div>
                                        <span class="pbar-pct">{{ $row->pourcentage }}%</span>
                                    </div>
                                </td>

                                {{-- Total dû --}}
                                <td class="amount-cell">
                                    {{ number_format($row->totalDu, 0, ',', ' ') }}
                                    <span class="amount-unit">FCFA</span>
                                </td>

                                {{-- Solde restant --}}
                                <td class="amount-cell amount-red">
                                    {{ number_format($row->soldeRestant, 0, ',', ' ') }}
                                    <span class="amount-unit">FCFA</span>
                                </td>

                                {{-- Risque --}}
                                <td>
                                    <span class="rbadge {{ $row->risk }}">
                                        @if($row->risk === 'critical')
                                            <i class="fas fa-ban" style="font-size:.65em;"></i>
                                        @elseif($row->risk === 'high')
                                            <i class="fas fa-clock" style="font-size:.65em;"></i>
                                        @elseif($row->risk === 'medium')
                                            <i class="fas fa-adjust" style="font-size:.65em;"></i>
                                        @else
                                            <i class="fas fa-check" style="font-size:.65em;"></i>
                                        @endif
                                        {{ $row->riskLabel }}
                                    </span>
                                </td>

                                {{-- Actions --}}
                                <td>
                                    <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
                                        <a href="{{ route('esbtp.comptabilite.relances.etudiant', $row->inscription->id) }}"
                                           class="act-btn primary" title="Voir fiche relance">
                                            <i class="fas fa-file-invoice-dollar"></i>
                                            <span class="d-none d-xl-inline">Fiche</span>
                                        </a>
                                        <a href="{{ route('esbtp.inscriptions.show', $row->inscription->id) }}"
                                           class="act-btn ghost" title="Voir inscription">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            @if ($paginated->hasPages())
                <div class="rel-pagination">
                    <span class="page-info">
                        {{ $paginated->firstItem() }}–{{ $paginated->lastItem() }}
                        sur {{ $paginated->total() }} résultat(s)
                    </span>
                    <div>
                        {{ $paginated->appends(request()->query())->links() }}
                    </div>
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
