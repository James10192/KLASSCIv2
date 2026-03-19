@extends('layouts.app')

@section('title', 'Bulletins LMD — KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ══════════════════════════════════════════════
       LMD Bulletins Index — Premium Redesign
       Prefix: lb- (lmd-bulletin)
       ══════════════════════════════════════════════ */

    .lb-page { max-width: 1440px; margin: 0 auto; padding: 0 1rem 2rem; }

    /* ── Hero ── */
    .lb-hero {
        position: relative;
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        color: #fff;
        margin-bottom: 1.5rem;
        overflow: hidden;
        animation: lb-fadeDown .5s ease-out;
    }
    .lb-hero::before {
        content: '';
        position: absolute;
        top: -60%;
        right: -10%;
        width: 420px;
        height: 420px;
        background: radial-gradient(circle, rgba(255,255,255,.07) 0%, transparent 70%);
        pointer-events: none;
    }
    .lb-hero::after {
        content: '';
        position: absolute;
        bottom: -40%;
        left: 5%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,.04) 0%, transparent 70%);
        pointer-events: none;
    }

    .lb-hero-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        position: relative;
        z-index: 1;
    }
    .lb-hero-left { display: flex; align-items: center; gap: 1rem; }
    .lb-hero-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: rgba(255,255,255,.12);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        border: 1px solid rgba(255,255,255,.15);
        flex-shrink: 0;
    }
    .lb-hero-info h1 {
        font-size: 1.45rem;
        font-weight: 700;
        margin: 0 0 .2rem;
        color: #fff;
        letter-spacing: -.02em;
    }
    .lb-hero-info p {
        margin: 0;
        opacity: .8;
        font-size: .88rem;
    }
    .lb-hero-actions {
        display: flex;
        gap: .5rem;
        position: relative;
        z-index: 1;
    }
    .lb-hero-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .55rem 1.1rem;
        border-radius: 10px;
        font-size: .84rem;
        font-weight: 600;
        border: 1.5px solid rgba(255,255,255,.3);
        color: #fff;
        background: rgba(255,255,255,.08);
        text-decoration: none;
        transition: all .2s;
        backdrop-filter: blur(4px);
    }
    .lb-hero-btn:hover { background: rgba(255,255,255,.18); color: #fff; text-decoration: none; }
    .lb-hero-btn--solid {
        background: #fff;
        color: #0453cb;
        border-color: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,.12);
    }
    .lb-hero-btn--solid:hover { background: #edf2fc; color: #0453cb; }

    /* KPIs inside hero */
    .lb-hero-kpis {
        display: flex;
        gap: .75rem;
        margin-top: 1.5rem;
        position: relative;
        z-index: 1;
        flex-wrap: wrap;
    }
    .lb-kpi {
        flex: 1;
        min-width: 150px;
        background: rgba(255,255,255,.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,.15);
        border-radius: 12px;
        padding: .9rem 1rem;
        display: flex;
        align-items: center;
        gap: .75rem;
        transition: background .2s;
    }
    .lb-kpi:hover { background: rgba(255,255,255,.15); }
    .lb-kpi-icon {
        width: 38px;
        height: 38px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .95rem;
        flex-shrink: 0;
    }
    .lb-kpi--total .lb-kpi-icon    { background: rgba(255,255,255,.18); color: #fff; }
    .lb-kpi--published .lb-kpi-icon { background: rgba(16,185,129,.25); color: #6ee7b7; }
    .lb-kpi--draft .lb-kpi-icon     { background: rgba(251,191,36,.2); color: #fcd34d; }
    .lb-kpi--avg .lb-kpi-icon       { background: rgba(129,140,248,.25); color: #a5b4fc; }
    .lb-kpi-value {
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1;
        color: #fff;
    }
    .lb-kpi-label {
        font-size: .75rem;
        color: rgba(255,255,255,.65);
        margin-top: .15rem;
    }

    /* ── Filter bar ── */
    .lb-filters {
        background: #fff;
        border-radius: 14px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
        border: 1px solid #e8ecf1;
        display: flex;
        align-items: flex-end;
        gap: 1rem;
        flex-wrap: wrap;
        animation: lb-fadeUp .45s ease-out .1s both;
    }
    .lb-filter-group {
        display: flex;
        flex-direction: column;
        gap: .3rem;
        flex: 1;
        min-width: 145px;
    }
    .lb-filter-label {
        font-size: .72rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: .06em;
    }
    .lb-filter-select,
    .lb-filter-input {
        padding: .5rem .75rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 9px;
        font-size: .86rem;
        color: #1e293b;
        background: #f8fafc;
        transition: all .2s;
        width: 100%;
    }
    .lb-filter-select:focus,
    .lb-filter-input:focus {
        outline: none;
        border-color: #0453cb;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(4,83,203,.08);
    }
    .lb-filter-actions {
        display: flex;
        gap: .4rem;
        flex-shrink: 0;
    }
    .lb-filter-btn {
        padding: .5rem .9rem;
        border-radius: 9px;
        font-size: .84rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all .2s;
        display: inline-flex;
        align-items: center;
        gap: .3rem;
    }
    .lb-filter-btn--primary {
        background: #0453cb;
        color: #fff;
    }
    .lb-filter-btn--primary:hover { background: #0340a0; }
    .lb-filter-btn--reset {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }
    .lb-filter-btn--reset:hover { background: #e2e8f0; }

    /* ── Table card ── */
    .lb-table-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8ecf1;
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
        overflow: hidden;
        animation: lb-fadeUp .45s ease-out .2s both;
    }
    .lb-table-header {
        padding: 1.15rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .5rem;
    }
    .lb-table-title {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .lb-table-title i { color: #0453cb; font-size: .9rem; }
    .lb-table-count {
        font-size: .8rem;
        color: #94a3b8;
        font-weight: 500;
    }
    .lb-table-wrapper { overflow-x: auto; }

    .lb-table {
        width: 100%;
        border-collapse: collapse;
    }
    .lb-table thead th {
        padding: .75rem 1rem;
        font-size: .72rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: .06em;
        background: #fafbfc;
        border-bottom: 1px solid #f1f5f9;
        white-space: nowrap;
    }
    .lb-table tbody tr {
        transition: background .15s;
        border-bottom: 1px solid #f8fafc;
    }
    .lb-table tbody tr:hover { background: #f8fbff; }
    .lb-table tbody tr:last-child { border-bottom: none; }
    .lb-table tbody td {
        padding: .8rem 1rem;
        font-size: .87rem;
        color: #475569;
        vertical-align: middle;
    }

    /* Cell styles */
    .lb-matricule {
        font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace;
        font-size: .8rem;
        color: #64748b;
        letter-spacing: .02em;
    }
    .lb-student-name {
        font-weight: 600;
        color: #1e293b;
    }
    .lb-student-name small {
        display: block;
        font-weight: 400;
        font-size: .78rem;
        color: #94a3b8;
        margin-top: .1rem;
    }
    .lb-semestre-tag {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: .2rem .6rem;
        border-radius: 6px;
        font-size: .78rem;
        font-weight: 700;
        background: #eef2ff;
        color: #4f46e5;
        min-width: 36px;
    }

    /* Moyenne cell */
    .lb-moyenne {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .15rem;
    }
    .lb-moyenne-value {
        font-size: 1rem;
        font-weight: 800;
        line-height: 1;
        letter-spacing: -.02em;
    }
    .lb-moyenne-mention {
        font-size: .65rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .04em;
        opacity: .75;
    }
    .lb-moy--excellent { color: #059669; }
    .lb-moy--good      { color: #0453cb; }
    .lb-moy--fail       { color: #dc2626; }

    /* Credits cell — mini progress bar */
    .lb-credits {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .3rem;
        min-width: 80px;
    }
    .lb-credits-text {
        font-size: .85rem;
        font-weight: 700;
        color: #1e293b;
    }
    .lb-credits-text span { font-weight: 400; color: #94a3b8; }
    .lb-credits-bar {
        width: 64px;
        height: 4px;
        border-radius: 2px;
        background: #f1f5f9;
        overflow: hidden;
    }
    .lb-credits-fill {
        height: 100%;
        border-radius: 2px;
        transition: width .4s ease;
    }
    .lb-credits-fill--full { background: #10b981; }
    .lb-credits-fill--mid  { background: #0453cb; }
    .lb-credits-fill--low  { background: #dc2626; }

    /* Rang cell */
    .lb-rang {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .05rem;
    }
    .lb-rang-value {
        font-size: .95rem;
        font-weight: 700;
        color: #1e293b;
    }
    .lb-rang-total {
        font-size: .68rem;
        color: #94a3b8;
    }

    /* Status badges */
    .lb-badge {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .25rem .65rem;
        border-radius: 20px;
        font-size: .74rem;
        font-weight: 600;
        letter-spacing: .01em;
    }
    .lb-badge-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .lb-badge--published {
        background: #ecfdf5;
        color: #059669;
    }
    .lb-badge--published .lb-badge-dot { background: #10b981; }
    .lb-badge--draft {
        background: #fef9ee;
        color: #b45309;
    }
    .lb-badge--draft .lb-badge-dot { background: #f59e0b; }

    /* Action buttons */
    .lb-actions { display: flex; gap: .3rem; justify-content: center; }
    .lb-act {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid #e8ecf1;
        background: #fff;
        color: #64748b;
        font-size: .8rem;
        cursor: pointer;
        transition: all .2s;
        text-decoration: none;
    }
    .lb-act:hover { color: #fff; text-decoration: none; }
    .lb-act--view:hover    { background: #0453cb; border-color: #0453cb; color: #fff; }
    .lb-act--pdf:hover     { background: #059669; border-color: #059669; color: #fff; }
    .lb-act--publish:hover { background: #d97706; border-color: #d97706; color: #fff; }
    .lb-act--delete:hover  { background: #dc2626; border-color: #dc2626; color: #fff; }

    /* Empty state */
    .lb-empty {
        padding: 4rem 2rem;
        text-align: center;
    }
    .lb-empty-icon {
        width: 72px;
        height: 72px;
        border-radius: 18px;
        background: #f1f5f9;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
    }
    .lb-empty-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: .4rem;
    }
    .lb-empty-text {
        font-size: .88rem;
        color: #94a3b8;
        margin-bottom: 1.25rem;
    }
    .lb-empty-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .6rem 1.2rem;
        background: #0453cb;
        color: #fff;
        border-radius: 10px;
        font-size: .85rem;
        font-weight: 600;
        text-decoration: none;
        transition: background .2s;
    }
    .lb-empty-btn:hover { background: #0340a0; color: #fff; text-decoration: none; }

    /* Pagination */
    .lb-pagination {
        padding: 1rem 1.5rem;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: center;
    }
    .lb-pagination .pagination { margin: 0; }

    /* ── Animations ── */
    @keyframes lb-fadeDown {
        from { opacity: 0; transform: translateY(-12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes lb-fadeUp {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Responsive ── */
    @media (max-width: 768px) {
        .lb-hero { padding: 1.5rem; border-radius: 14px; }
        .lb-hero-top { flex-direction: column; }
        .lb-hero-kpis { flex-direction: column; }
        .lb-filters { flex-direction: column; align-items: stretch; }
        .lb-filter-group { min-width: 100%; }
        .lb-table thead th,
        .lb-table tbody td { padding: .6rem .7rem; }
    }
</style>
@endpush

@section('content')
<div class="lb-page">
    <div class="main-content">

        {{-- ══ Hero ══ --}}
        @php
            $totalBulletins = $bulletins->total();
            $publies = $bulletins->getCollection()->where('is_published', true)->count();
            $nonPublies = $totalBulletins - $publies;
            $avgMoyenne = $bulletins->getCollection()->avg('moyenne_generale');
        @endphp

        <div class="lb-hero">
            <div class="lb-hero-top">
                <div class="lb-hero-left">
                    <div class="lb-hero-icon"><i class="fas fa-graduation-cap"></i></div>
                    <div class="lb-hero-info">
                        <h1>Bulletins LMD</h1>
                        <p>Gestion des bulletins semestriels — Système LMD</p>
                    </div>
                </div>
                <div class="lb-hero-actions">
                    <a href="{{ route('esbtp.lmd.resultats.index') }}" class="lb-hero-btn">
                        <i class="fas fa-chart-bar"></i>Résultats
                    </a>
                    <a href="{{ route('esbtp.lmd.bulletins.select') }}" class="lb-hero-btn--solid lb-hero-btn">
                        <i class="fas fa-plus"></i>Générer
                    </a>
                </div>
            </div>

            {{-- KPIs inside hero --}}
            <div class="lb-hero-kpis">
                <div class="lb-kpi lb-kpi--total">
                    <div class="lb-kpi-icon"><i class="fas fa-file-alt"></i></div>
                    <div>
                        <div class="lb-kpi-value">{{ $totalBulletins }}</div>
                        <div class="lb-kpi-label">Total bulletins</div>
                    </div>
                </div>
                <div class="lb-kpi lb-kpi--published">
                    <div class="lb-kpi-icon"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <div class="lb-kpi-value">{{ $publies }}</div>
                        <div class="lb-kpi-label">Publiés</div>
                    </div>
                </div>
                <div class="lb-kpi lb-kpi--draft">
                    <div class="lb-kpi-icon"><i class="fas fa-clock"></i></div>
                    <div>
                        <div class="lb-kpi-value">{{ $nonPublies }}</div>
                        <div class="lb-kpi-label">Non publiés</div>
                    </div>
                </div>
                <div class="lb-kpi lb-kpi--avg">
                    <div class="lb-kpi-icon"><i class="fas fa-calculator"></i></div>
                    <div>
                        <div class="lb-kpi-value">{{ $avgMoyenne ? number_format($avgMoyenne, 2) : '—' }}</div>
                        <div class="lb-kpi-label">Moyenne gén.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Flash messages --}}
        @foreach(['success' => 'check-circle', 'error' => 'exclamation-circle'] as $type => $icon)
            @if(session($type))
                <div class="alert alert-{{ $type === 'error' ? 'danger' : $type }} alert-dismissible fade show" role="alert" style="border-radius:10px;">
                    <i class="fas fa-{{ $icon }} me-2"></i>{{ session($type) }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
        @endforeach

        {{-- ══ Filters ══ --}}
        <form method="GET" action="{{ route('esbtp.lmd.bulletins.index') }}" id="lb-filter-form">
            <div class="lb-filters">
                <div class="lb-filter-group">
                    <label class="lb-filter-label">Classe</label>
                    <select class="lb-filter-select" name="classe_id" onchange="document.getElementById('lb-filter-form').submit()">
                        <option value="">Toutes les classes</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ request('classe_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="lb-filter-group">
                    <label class="lb-filter-label">Année</label>
                    <select class="lb-filter-select" name="annee_universitaire_id" onchange="document.getElementById('lb-filter-form').submit()">
                        <option value="">Toutes</option>
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id }}" {{ request('annee_universitaire_id') == $annee->id ? 'selected' : '' }}>
                                {{ $annee->name ?? $annee->libelle ?? $annee->id }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="lb-filter-group" style="max-width:130px;">
                    <label class="lb-filter-label">Semestre</label>
                    <select class="lb-filter-select" name="semestre" onchange="document.getElementById('lb-filter-form').submit()">
                        <option value="">Tous</option>
                        @for($s = 1; $s <= 10; $s++)
                            <option value="{{ $s }}" {{ request('semestre') == $s ? 'selected' : '' }}>S{{ $s }}</option>
                        @endfor
                    </select>
                </div>
                <div class="lb-filter-group">
                    <label class="lb-filter-label">Recherche</label>
                    <input type="text" class="lb-filter-input" name="search" value="{{ request('search') }}" placeholder="Matricule, nom...">
                </div>
                <div class="lb-filter-actions">
                    <button type="submit" class="lb-filter-btn lb-filter-btn--primary">
                        <i class="fas fa-search"></i>
                    </button>
                    @if(request()->hasAny(['classe_id', 'annee_universitaire_id', 'semestre', 'search']))
                        <a href="{{ route('esbtp.lmd.bulletins.index') }}" class="lb-filter-btn lb-filter-btn--reset">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>

        {{-- ══ Table ══ --}}
        <div class="lb-table-card">
            <div class="lb-table-header">
                <div class="lb-table-title">
                    <i class="fas fa-list-ul"></i>
                    Liste des bulletins
                </div>
                <div class="lb-table-count">
                    {{ $bulletins->total() }} bulletin{{ $bulletins->total() > 1 ? 's' : '' }}
                </div>
            </div>
            <div class="lb-table-wrapper">
                <table class="lb-table">
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Étudiant</th>
                            <th>Classe</th>
                            <th style="text-align:center;">Sem.</th>
                            <th style="text-align:center;">Moyenne</th>
                            <th style="text-align:center;">Crédits</th>
                            <th style="text-align:center;">Rang</th>
                            <th style="text-align:center;">Statut</th>
                            <th style="text-align:center; width:130px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bulletins as $b)
                            @php
                                $moy = $b->moyenne_generale ?? 0;
                                $moyClass = $moy >= 14 ? 'lb-moy--excellent' : ($moy >= 10 ? 'lb-moy--good' : 'lb-moy--fail');
                                $mention = $b->mention_generale;
                                $credCap = $b->credits_capitalises ?? 0;
                                $credTot = $b->credits_totaux ?? 0;
                                $credPct = $credTot > 0 ? round(($credCap / $credTot) * 100) : 0;
                                $credFillClass = $credPct >= 100 ? 'lb-credits-fill--full' : ($credPct >= 50 ? 'lb-credits-fill--mid' : 'lb-credits-fill--low');
                            @endphp
                            <tr>
                                <td>
                                    <span class="lb-matricule">{{ $b->etudiant->matricule ?? '—' }}</span>
                                </td>
                                <td>
                                    <div class="lb-student-name">
                                        {{ $b->etudiant->nom ?? '' }} {{ $b->etudiant->prenoms ?? $b->etudiant->prenom ?? '' }}
                                    </div>
                                </td>
                                <td style="color:#64748b;">{{ $b->classe->name ?? '—' }}</td>
                                <td style="text-align:center;">
                                    <span class="lb-semestre-tag">S{{ $b->semestre }}</span>
                                </td>
                                <td style="text-align:center;">
                                    <div class="lb-moyenne">
                                        <span class="lb-moyenne-value {{ $moyClass }}">{{ number_format($moy, 2) }}</span>
                                        @if($mention)
                                            <span class="lb-moyenne-mention {{ $moyClass }}">{{ $mention }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td style="text-align:center;">
                                    <div class="lb-credits">
                                        <div class="lb-credits-text">{{ $credCap }} <span>/ {{ $credTot }}</span></div>
                                        <div class="lb-credits-bar">
                                            <div class="lb-credits-fill {{ $credFillClass }}" style="width:{{ $credPct }}%;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align:center;">
                                    <div class="lb-rang">
                                        <span class="lb-rang-value">{{ $b->rang ?? '—' }}</span>
                                        @if($b->effectif)
                                            <span class="lb-rang-total">/ {{ $b->effectif }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td style="text-align:center;">
                                    @if($b->is_published)
                                        <span class="lb-badge lb-badge--published">
                                            <span class="lb-badge-dot"></span>Publié
                                        </span>
                                    @else
                                        <span class="lb-badge lb-badge--draft">
                                            <span class="lb-badge-dot"></span>Brouillon
                                        </span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    <div class="lb-actions">
                                        <a href="{{ route('esbtp.lmd.bulletins.show', $b) }}" class="lb-act lb-act--view" title="Aperçu">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('esbtp.lmd.bulletins.pdf', $b) }}" class="lb-act lb-act--pdf" title="Télécharger PDF">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <form method="POST" action="{{ route('esbtp.lmd.bulletins.toggle-publication', $b) }}" style="display:inline;">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="lb-act lb-act--publish" title="{{ $b->is_published ? 'Dépublier' : 'Publier' }}">
                                                <i class="fas fa-{{ $b->is_published ? 'eye-slash' : 'check' }}"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('esbtp.lmd.bulletins.destroy', $b) }}" style="display:inline;" onsubmit="return confirm('Supprimer ce bulletin et ses résultats ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="lb-act lb-act--delete" title="Supprimer">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="lb-empty">
                                        <div class="lb-empty-icon"><i class="fas fa-graduation-cap"></i></div>
                                        <div class="lb-empty-title">Aucun bulletin trouvé</div>
                                        <div class="lb-empty-text">Générez des bulletins LMD pour les afficher ici.</div>
                                        <a href="{{ route('esbtp.lmd.bulletins.select') }}" class="lb-empty-btn">
                                            <i class="fas fa-plus"></i>Générer des bulletins
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($bulletins->hasPages())
                <div class="lb-pagination">
                    {{ $bulletins->appends(request()->query())->links() }}
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
