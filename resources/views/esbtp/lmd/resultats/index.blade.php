@extends('layouts.app')

@section('title', 'Résultats LMD — KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ══════════════════════════════════════════════
       LMD Résultats Index — Premium Redesign
       Prefix: lr- (lmd-resultats)
       ══════════════════════════════════════════════ */

    .lr-page { max-width: 1440px; margin: 0 auto; padding: 0 1rem 2rem; }

    /* ── Hero ── */
    .lr-hero {
        position: relative;
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        color: #fff;
        margin-bottom: 1.5rem;
        overflow: hidden;
        animation: lr-fadeDown .5s ease-out;
    }
    .lr-hero::before {
        content: '';
        position: absolute;
        top: -60%;
        right: -10%;
        width: 420px;
        height: 420px;
        background: radial-gradient(circle, rgba(255,255,255,.07) 0%, transparent 70%);
        pointer-events: none;
    }
    .lr-hero::after {
        content: '';
        position: absolute;
        bottom: -40%;
        left: 5%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,.04) 0%, transparent 70%);
        pointer-events: none;
    }

    .lr-hero-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        position: relative;
        z-index: 1;
    }
    .lr-hero-left { display: flex; align-items: center; gap: 1rem; }
    .lr-hero-icon {
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
    .lr-hero-info h1 {
        font-size: 1.45rem;
        font-weight: 700;
        margin: 0 0 .2rem;
        color: #fff;
        letter-spacing: -.02em;
    }
    .lr-hero-info p {
        margin: 0;
        opacity: .8;
        font-size: .88rem;
    }
    .lr-hero-actions {
        display: flex;
        gap: .5rem;
        position: relative;
        z-index: 1;
    }
    .lr-hero-btn {
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
    .lr-hero-btn:hover { background: rgba(255,255,255,.18); color: #fff; text-decoration: none; }
    .lr-hero-btn--solid {
        background: #fff;
        color: #0453cb;
        border-color: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,.12);
    }
    .lr-hero-btn--solid:hover { background: #edf2fc; color: #0453cb; }

    /* KPIs inside hero */
    .lr-hero-kpis {
        display: flex;
        gap: .75rem;
        margin-top: 1.5rem;
        position: relative;
        z-index: 1;
        flex-wrap: wrap;
    }
    .lr-kpi {
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
    .lr-kpi:hover { background: rgba(255,255,255,.15); }
    .lr-kpi-icon {
        width: 38px;
        height: 38px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .95rem;
        flex-shrink: 0;
    }
    .lr-kpi--classes .lr-kpi-icon   { background: rgba(255,255,255,.18); color: #fff; }
    .lr-kpi--bulletins .lr-kpi-icon { background: rgba(129,140,248,.25); color: #a5b4fc; }
    .lr-kpi--taux .lr-kpi-icon      { background: rgba(16,185,129,.25); color: #6ee7b7; }
    .lr-kpi--etudiants .lr-kpi-icon { background: rgba(251,191,36,.2); color: #fcd34d; }
    .lr-kpi-value {
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1;
        color: #fff;
    }
    .lr-kpi-label {
        font-size: .75rem;
        color: rgba(255,255,255,.65);
        margin-top: .15rem;
    }

    /* ── Filter bar ── */
    .lr-filters {
        background: #fff;
        border-radius: 14px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
        border: 1px solid #e8ecf1;
        display: flex;
        align-items: flex-end;
        gap: 1rem;
        flex-wrap: wrap;
        animation: lr-fadeUp .45s ease-out .1s both;
    }
    .lr-filter-group {
        display: flex;
        flex-direction: column;
        gap: .3rem;
        flex: 1;
        min-width: 200px;
        max-width: 320px;
    }
    .lr-filter-label {
        font-size: .72rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: .06em;
    }
    .lr-filter-select {
        padding: .5rem .75rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 9px;
        font-size: .86rem;
        color: #1e293b;
        background: #f8fafc;
        transition: all .2s;
        width: 100%;
    }
    .lr-filter-select:focus {
        outline: none;
        border-color: #0453cb;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(4,83,203,.08);
    }
    .lr-filter-info {
        font-size: .78rem;
        color: #94a3b8;
        display: flex;
        align-items: center;
        gap: .3rem;
        margin-left: auto;
        padding-bottom: .3rem;
    }

    /* ── Section header ── */
    .lr-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
        animation: lr-fadeUp .45s ease-out .15s both;
    }
    .lr-section-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .lr-section-title i { color: #0453cb; font-size: .9rem; }
    .lr-section-count {
        font-size: .8rem;
        color: #94a3b8;
        font-weight: 500;
    }

    /* ── Cards grid ── */
    .lr-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
        gap: 1rem;
        animation: lr-fadeUp .45s ease-out .2s both;
    }
    .lr-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8ecf1;
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
        overflow: hidden;
        transition: transform .25s ease, box-shadow .25s ease;
        display: flex;
        flex-direction: column;
    }
    .lr-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 28px rgba(4,83,203,.1);
    }

    /* Card header */
    .lr-card-head {
        padding: 1.15rem 1.25rem .85rem;
        display: flex;
        align-items: flex-start;
        gap: .85rem;
    }
    .lr-card-icon {
        width: 42px;
        height: 42px;
        border-radius: 11px;
        background: linear-gradient(135deg, #0453cb 0%, #3b7ddb 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .lr-card-title {
        font-size: 1.02rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 .2rem;
        line-height: 1.3;
    }
    .lr-card-sub {
        font-size: .8rem;
        color: #94a3b8;
        line-height: 1.3;
    }

    /* Card metrics */
    .lr-card-metrics {
        padding: .75rem 1.25rem 1rem;
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: .5rem;
        flex: 1;
    }
    .lr-metric {
        display: flex;
        flex-direction: column;
        gap: .15rem;
    }
    .lr-metric-label {
        font-size: .68rem;
        font-weight: 600;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .lr-metric-value {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1;
    }

    /* Validation rate — inline bar */
    .lr-card-bar-row {
        padding: 0 1.25rem .85rem;
    }
    .lr-bar-label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: .35rem;
    }
    .lr-bar-label-text {
        font-size: .7rem;
        font-weight: 600;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .lr-bar-label-value {
        font-size: .78rem;
        font-weight: 700;
    }
    .lr-bar-label-value--green  { color: #059669; }
    .lr-bar-label-value--orange { color: #d97706; }
    .lr-bar-label-value--red    { color: #dc2626; }
    .lr-bar-track {
        width: 100%;
        height: 6px;
        border-radius: 3px;
        background: #f1f5f9;
        overflow: hidden;
    }
    .lr-bar-fill {
        height: 100%;
        border-radius: 3px;
        transition: width .6s ease;
    }
    .lr-bar-fill--green  { background: linear-gradient(90deg, #10b981, #34d399); }
    .lr-bar-fill--orange { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
    .lr-bar-fill--red    { background: linear-gradient(90deg, #ef4444, #f87171); }

    /* Card footer */
    .lr-card-foot {
        padding: .75rem 1.25rem 1.1rem;
        border-top: 1px solid #f1f5f9;
        display: flex;
        gap: .5rem;
        justify-content: flex-end;
    }
    .lr-card-btn {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .45rem 1rem;
        border-radius: 9px;
        font-size: .82rem;
        font-weight: 600;
        text-decoration: none;
        transition: all .2s;
        border: none;
        cursor: pointer;
    }
    .lr-card-btn--primary {
        background: #0453cb;
        color: #fff;
    }
    .lr-card-btn--primary:hover { background: #0340a0; color: #fff; text-decoration: none; }
    .lr-card-btn--outline {
        background: #fff;
        color: #0453cb;
        border: 1.5px solid #e2e8f0;
    }
    .lr-card-btn--outline:hover { border-color: #0453cb; background: #f0f5ff; color: #0453cb; text-decoration: none; }

    /* ── Empty state ── */
    .lr-empty-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8ecf1;
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
        animation: lr-fadeUp .45s ease-out .2s both;
    }
    .lr-empty {
        text-align: center;
        padding: 4rem 2rem;
    }
    .lr-empty-icon {
        width: 76px;
        height: 76px;
        border-radius: 20px;
        background: #f1f5f9;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: #cbd5e1;
        margin-bottom: 1.15rem;
    }
    .lr-empty-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: .4rem;
    }
    .lr-empty-text {
        font-size: .88rem;
        color: #94a3b8;
        margin-bottom: 1.25rem;
        max-width: 380px;
        margin-left: auto;
        margin-right: auto;
    }
    .lr-empty-btn {
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
    .lr-empty-btn:hover { background: #0340a0; color: #fff; text-decoration: none; }

    /* ── Animations ── */
    @keyframes lr-fadeDown {
        from { opacity: 0; transform: translateY(-12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes lr-fadeUp {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Responsive ── */
    @media (max-width: 768px) {
        .lr-hero { padding: 1.5rem; border-radius: 14px; }
        .lr-hero-top { flex-direction: column; }
        .lr-hero-kpis { flex-direction: column; }
        .lr-filters { flex-direction: column; align-items: stretch; }
        .lr-filter-group { max-width: 100%; min-width: 100%; }
        .lr-cards { grid-template-columns: 1fr; }
        .lr-card-metrics { grid-template-columns: 1fr 1fr; }
    }
</style>
@endpush

@section('content')
<div class="lr-page">
    <div class="main-content">

        {{-- ══ Hero ══ --}}
        @php
            $totalClasses = $classes->count();
            $totalBulletins = 0;
            $totalEtudiants = 0;
            $tauxSum = 0;
            $tauxCount = 0;
            foreach ($classes as $c) {
                $totalEtudiants += $c->total_etudiants ?? 0;
            }
            foreach ($bulletinCounts ?? [] as $bc) {
                $totalBulletins += $bc['total'] ?? 0;
                if (($bc['total'] ?? 0) > 0) {
                    $taux = ($bc['total_valides'] ?? 0) / ($bc['total'] ?? 1) * 100;
                    $tauxSum += $taux;
                    $tauxCount++;
                }
            }
            $tauxMoyen = $tauxCount > 0 ? $tauxSum / $tauxCount : 0;
        @endphp

        <div class="lr-hero">
            <div class="lr-hero-top">
                <div class="lr-hero-left">
                    <div class="lr-hero-icon"><i class="fas fa-chart-bar"></i></div>
                    <div class="lr-hero-info">
                        <h1>Résultats LMD</h1>
                        <p>Consultez les résultats par classe et par semestre</p>
                    </div>
                </div>
                <div class="lr-hero-actions">
                    <a href="{{ route('esbtp.lmd.bulletins.index') }}" class="lr-hero-btn">
                        <i class="fas fa-file-alt"></i>Bulletins
                    </a>
                    <a href="{{ route('esbtp.lmd.bulletins.select') }}" class="lr-hero-btn--solid lr-hero-btn">
                        <i class="fas fa-plus"></i>Générer
                    </a>
                </div>
            </div>

            {{-- KPIs inside hero --}}
            <div class="lr-hero-kpis">
                <div class="lr-kpi lr-kpi--classes">
                    <div class="lr-kpi-icon"><i class="fas fa-layer-group"></i></div>
                    <div>
                        <div class="lr-kpi-value">{{ $totalClasses }}</div>
                        <div class="lr-kpi-label">Classes LMD</div>
                    </div>
                </div>
                <div class="lr-kpi lr-kpi--etudiants">
                    <div class="lr-kpi-icon"><i class="fas fa-users"></i></div>
                    <div>
                        <div class="lr-kpi-value">{{ $totalEtudiants }}</div>
                        <div class="lr-kpi-label">Étudiants actifs</div>
                    </div>
                </div>
                <div class="lr-kpi lr-kpi--bulletins">
                    <div class="lr-kpi-icon"><i class="fas fa-file-alt"></i></div>
                    <div>
                        <div class="lr-kpi-value">{{ $totalBulletins }}</div>
                        <div class="lr-kpi-label">Bulletins générés</div>
                    </div>
                </div>
                <div class="lr-kpi lr-kpi--taux">
                    <div class="lr-kpi-icon"><i class="fas fa-percentage"></i></div>
                    <div>
                        <div class="lr-kpi-value">{{ $tauxCount > 0 ? number_format($tauxMoyen, 1) . '%' : '—' }}</div>
                        <div class="lr-kpi-label">Taux validation moy.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ Filter ══ --}}
        <form method="GET" action="{{ route('esbtp.lmd.resultats.index') }}" id="lr-filter-form">
            <div class="lr-filters">
                <div class="lr-filter-group">
                    <label class="lr-filter-label">Année universitaire</label>
                    <select class="lr-filter-select" name="annee_universitaire_id" onchange="document.getElementById('lr-filter-form').submit()">
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id }}" {{ ($anneeId ?? null) == $annee->id ? 'selected' : '' }}>
                                {{ $annee->name ?? $annee->libelle ?? $annee->id }}
                                @if($annee->is_current) (en cours) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="lr-filter-info">
                    <i class="fas fa-info-circle"></i>
                    Sélectionnez l'année pour filtrer les résultats
                </div>
            </div>
        </form>

        {{-- ══ Classes grid ══ --}}
        @if($classes->isEmpty())
            <div class="lr-empty-card">
                <div class="lr-empty">
                    <div class="lr-empty-icon"><i class="fas fa-graduation-cap"></i></div>
                    <div class="lr-empty-title">Aucune classe LMD</div>
                    <div class="lr-empty-text">Aucune classe utilisant le système LMD n'a été trouvée pour cette année universitaire.</div>
                    <a href="{{ route('esbtp.lmd.bulletins.select') }}" class="lr-empty-btn">
                        <i class="fas fa-plus"></i>Générer des bulletins
                    </a>
                </div>
            </div>
        @else
            <div class="lr-section-header">
                <div class="lr-section-title">
                    <i class="fas fa-th-large"></i>
                    Classes LMD
                </div>
                <div class="lr-section-count">
                    {{ $totalClasses }} classe{{ $totalClasses > 1 ? 's' : '' }}
                </div>
            </div>

            <div class="lr-cards">
                @foreach($classes as $classe)
                    @php
                        $bc = $bulletinCounts[$classe->id] ?? null;
                        $bcTotal = $bc['total'] ?? 0;
                        $bcMoy = $bc['moy_classe'] ?? 0;
                        $bcValides = $bc['total_valides'] ?? 0;
                        $taux = ($bcTotal > 0) ? ($bcValides / $bcTotal) * 100 : 0;
                        $tauxColor = $taux >= 70 ? 'green' : ($taux >= 50 ? 'orange' : 'red');
                        $nbEtudiants = $classe->total_etudiants ?? 0;
                    @endphp
                    <div class="lr-card">
                        <div class="lr-card-head">
                            <div class="lr-card-icon"><i class="fas fa-layer-group"></i></div>
                            <div>
                                <div class="lr-card-title">{{ $classe->name }}</div>
                                <div class="lr-card-sub">
                                    @if($classe->filiere) {{ $classe->filiere->name }} @endif
                                    @if($classe->niveau) &middot; {{ $classe->niveau->name ?? '' }} @endif
                                </div>
                            </div>
                        </div>

                        <div class="lr-card-metrics">
                            <div class="lr-metric">
                                <span class="lr-metric-label">Étudiants</span>
                                <span class="lr-metric-value">{{ $nbEtudiants }}</span>
                            </div>
                            <div class="lr-metric">
                                <span class="lr-metric-label">Bulletins</span>
                                <span class="lr-metric-value">{{ $bcTotal }}</span>
                            </div>
                            <div class="lr-metric">
                                <span class="lr-metric-label">Moy. classe</span>
                                <span class="lr-metric-value" style="color: {{ $bcMoy >= 10 ? '#059669' : ($bcMoy > 0 ? '#dc2626' : '#94a3b8') }};">
                                    {{ $bcMoy > 0 ? number_format($bcMoy, 2) : '—' }}
                                </span>
                            </div>
                        </div>

                        <div class="lr-card-bar-row">
                            <div class="lr-bar-label">
                                <span class="lr-bar-label-text">Taux de validation</span>
                                <span class="lr-bar-label-value lr-bar-label-value--{{ $tauxColor }}">
                                    {{ $bcTotal > 0 ? number_format($taux, 1) . '%' : '—' }}
                                </span>
                            </div>
                            <div class="lr-bar-track">
                                <div class="lr-bar-fill lr-bar-fill--{{ $tauxColor }}" style="width: {{ min($taux, 100) }}%;"></div>
                            </div>
                        </div>

                        <div class="lr-card-foot">
                            @if($bcTotal > 0)
                                <a href="{{ route('esbtp.lmd.resultats.classe', $classe) }}" class="lr-card-btn lr-card-btn--outline">
                                    <i class="fas fa-chart-bar"></i>Résultats
                                </a>
                            @endif
                            <a href="{{ route('esbtp.lmd.resultats.classe', $classe) }}" class="lr-card-btn lr-card-btn--primary">
                                <i class="fas fa-eye"></i>Voir détails
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>
@endsection
