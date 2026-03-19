@extends('layouts.app')

@section('title', 'Résultats LMD — ' . $classe->name . ' — KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ══════════════════════════════════════════════
       LMD Résultats Classe — Premium Redesign
       Prefix: rc- (resultats-classe)
       ══════════════════════════════════════════════ */

    .rc-page { max-width: 1440px; margin: 0 auto; padding: 0 1rem 2rem; }

    /* ── Hero ── */
    .rc-hero {
        position: relative;
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        color: #fff;
        margin-bottom: 1.5rem;
        overflow: hidden;
        animation: rc-fadeDown .5s ease-out;
    }
    .rc-hero::before {
        content: ''; position: absolute; top: -60%; right: -10%;
        width: 420px; height: 420px;
        background: radial-gradient(circle, rgba(255,255,255,.07) 0%, transparent 70%);
        pointer-events: none;
    }
    .rc-hero::after {
        content: ''; position: absolute; bottom: -40%; left: 5%;
        width: 300px; height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,.04) 0%, transparent 70%);
        pointer-events: none;
    }
    .rc-hero-top {
        display: flex; align-items: flex-start; justify-content: space-between;
        flex-wrap: wrap; gap: 1rem; position: relative; z-index: 1;
    }
    .rc-hero-left { display: flex; align-items: center; gap: 1rem; }
    .rc-hero-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; border: 1px solid rgba(255,255,255,.15); flex-shrink: 0;
    }
    .rc-hero-info h1 { font-size: 1.45rem; font-weight: 700; margin: 0 0 .2rem; color: #fff; letter-spacing: -.02em; }
    .rc-hero-info p { margin: 0; opacity: .8; font-size: .88rem; }
    .rc-breadcrumb {
        display: flex; align-items: center; gap: .4rem;
        margin-top: .4rem; font-size: .78rem; opacity: .7;
    }
    .rc-breadcrumb a { color: #fff; text-decoration: underline; }
    .rc-breadcrumb a:hover { opacity: 1; }
    .rc-hero-actions { display: flex; gap: .5rem; position: relative; z-index: 1; flex-wrap: wrap; }
    .rc-hero-btn {
        display: inline-flex; align-items: center; gap: .4rem; padding: .55rem 1.1rem;
        border-radius: 10px; font-size: .84rem; font-weight: 600;
        border: 1.5px solid rgba(255,255,255,.3); color: #fff;
        background: rgba(255,255,255,.08); text-decoration: none; transition: all .2s;
        backdrop-filter: blur(4px); cursor: pointer;
    }
    .rc-hero-btn:hover { background: rgba(255,255,255,.18); color: #fff; text-decoration: none; }
    .rc-hero-btn--solid {
        background: #fff; color: #0453cb; border-color: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,.12);
    }
    .rc-hero-btn--solid:hover { background: #edf2fc; color: #0453cb; }

    /* KPIs in hero */
    .rc-hero-kpis {
        display: flex; gap: .75rem; margin-top: 1.5rem;
        position: relative; z-index: 1; flex-wrap: wrap;
    }
    .rc-kpi {
        flex: 1; min-width: 140px;
        background: rgba(255,255,255,.1); backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,.15); border-radius: 12px;
        padding: .9rem 1rem; display: flex; align-items: center; gap: .75rem;
        transition: background .2s;
    }
    .rc-kpi:hover { background: rgba(255,255,255,.15); }
    .rc-kpi-icon {
        width: 38px; height: 38px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem; flex-shrink: 0;
    }
    .rc-kpi--effectif .rc-kpi-icon  { background: rgba(255,255,255,.18); color: #fff; }
    .rc-kpi--moyenne .rc-kpi-icon   { background: rgba(129,140,248,.25); color: #a5b4fc; }
    .rc-kpi--min .rc-kpi-icon       { background: rgba(16,185,129,.25); color: #6ee7b7; }
    .rc-kpi--max .rc-kpi-icon       { background: rgba(251,191,36,.25); color: #fcd34d; }
    .rc-kpi--taux .rc-kpi-icon      { background: rgba(244,63,94,.25); color: #fda4af; }
    .rc-kpi-value { font-size: 1.35rem; font-weight: 700; line-height: 1; color: #fff; }
    .rc-kpi-label { font-size: .75rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

    /* ── Filter bar ── */
    .rc-filters {
        background: #fff; border-radius: 14px; padding: 1rem 1.5rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
        border: 1px solid #e8ecf1;
        display: flex; align-items: flex-end; gap: .85rem; flex-wrap: wrap;
        animation: rc-fadeUp .45s ease-out .1s both;
    }
    .rc-filter-group { display: flex; flex-direction: column; gap: .3rem; flex: 1; min-width: 140px; }
    .rc-filter-label {
        font-size: .72rem; font-weight: 700; color: #94a3b8;
        text-transform: uppercase; letter-spacing: .06em;
    }
    .rc-filter-control {
        padding: .5rem .75rem; border: 1.5px solid #e2e8f0; border-radius: 9px;
        font-size: .86rem; color: #1e293b; background: #f8fafc;
        transition: all .2s; width: 100%;
    }
    .rc-filter-control:focus {
        outline: none; border-color: #0453cb; background: #fff;
        box-shadow: 0 0 0 3px rgba(4,83,203,.08);
    }

    /* ── Table card ── */
    .rc-table-card {
        background: #fff; border-radius: 14px; border: 1px solid #e8ecf1;
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
        overflow: hidden;
        animation: rc-fadeUp .45s ease-out .2s both;
    }
    .rc-table-header {
        padding: 1.15rem 1.5rem; border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: .5rem;
    }
    .rc-table-title {
        font-size: 1rem; font-weight: 700; color: #1e293b;
        display: flex; align-items: center; gap: .5rem;
    }
    .rc-table-title i { color: #0453cb; font-size: .9rem; }
    .rc-table-count {
        font-size: .8rem; color: #94a3b8; font-weight: 500;
        background: #f1f5f9; padding: .25rem .6rem; border-radius: 20px;
    }
    .rc-table-wrapper { overflow-x: auto; }

    .rc-table { width: 100%; border-collapse: collapse; }
    .rc-table thead th {
        padding: .75rem 1rem; font-size: .72rem; font-weight: 700;
        color: #94a3b8; text-transform: uppercase; letter-spacing: .06em;
        background: #fafbfc; border-bottom: 1px solid #f1f5f9; white-space: nowrap;
    }
    .rc-table tbody tr {
        transition: background .15s; border-bottom: 1px solid #f8fafc;
    }
    .rc-table tbody tr:hover { background: #f8fbff; }
    .rc-table tbody td {
        padding: .8rem 1rem; font-size: .87rem; color: #475569; vertical-align: middle;
    }

    /* Rang badge */
    .rc-rang {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 32px; padding: .2rem .5rem; border-radius: 8px;
        font-size: .82rem; font-weight: 700; background: #f1f5f9; color: #334155;
    }
    .rc-rang--top3 { background: rgba(4,83,203,.1); color: #0453cb; }

    .rc-matricule {
        font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace;
        font-size: .82rem; color: #64748b; letter-spacing: .02em;
    }
    .rc-student-name { font-weight: 600; color: #1e293b; }

    /* Moyenne colors */
    .rc-moy { font-weight: 700; font-size: .92rem; }
    .rc-moy--pass { color: #059669; }
    .rc-moy--good { color: #0453cb; }
    .rc-moy--fail { color: #dc2626; }

    /* Credits progress */
    .rc-credits { display: flex; align-items: center; gap: .5rem; }
    .rc-credits-bar {
        flex: 1; height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden;
        min-width: 50px; max-width: 80px;
    }
    .rc-credits-fill { height: 100%; border-radius: 3px; transition: width .3s ease; }
    .rc-credits-fill--high { background: linear-gradient(90deg, #10b981, #059669); }
    .rc-credits-fill--mid  { background: linear-gradient(90deg, #3b82f6, #0453cb); }
    .rc-credits-fill--low  { background: linear-gradient(90deg, #f87171, #dc2626); }
    .rc-credits-text { font-size: .82rem; font-weight: 600; color: #334155; white-space: nowrap; }

    /* Badges */
    .rc-badge {
        display: inline-flex; align-items: center; padding: .2rem .55rem;
        border-radius: 20px; font-size: .72rem; font-weight: 700; letter-spacing: .02em;
    }
    .rc-badge--success { background: #ecfdf5; color: #059669; }
    .rc-badge--warning { background: #fffbeb; color: #d97706; }
    .rc-badge--danger  { background: #fef2f2; color: #dc2626; }
    .rc-badge--info    { background: #eff6ff; color: #0453cb; }

    /* Action btn */
    .rc-act {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; border-radius: 8px;
        border: 1px solid #e8ecf1; background: #fff; color: #64748b;
        font-size: .8rem; cursor: pointer; transition: all .2s; text-decoration: none;
    }
    .rc-act:hover { background: #0453cb; color: #fff; border-color: #0453cb; text-decoration: none; }

    /* Empty state */
    .rc-empty { text-align: center; padding: 4rem 2rem; }
    .rc-empty-icon {
        width: 76px; height: 76px; border-radius: 20px; background: #f1f5f9;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 2rem; color: #cbd5e1; margin-bottom: 1.15rem;
    }
    .rc-empty-title { font-size: 1.1rem; font-weight: 700; color: #334155; margin-bottom: .4rem; }
    .rc-empty-text { font-size: .88rem; color: #94a3b8; max-width: 400px; margin: 0 auto; }

    /* ── Animations ── */
    @keyframes rc-fadeDown { from { opacity: 0; transform: translateY(-12px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes rc-fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    @media (max-width: 768px) {
        .rc-hero { padding: 1.5rem; border-radius: 14px; }
        .rc-hero-top { flex-direction: column; }
        .rc-hero-kpis { flex-direction: column; }
        .rc-filters { flex-direction: column; align-items: stretch; }
    }
</style>
@endpush

@section('content')
<div class="rc-page">

    {{-- ══ Hero ══ --}}
    @php
        $effectif = $stats['effectif'] ?? 0;
        $moyClasse = isset($stats['moyenne_classe']) && $stats['moyenne_classe'] > 0 ? $stats['moyenne_classe'] : null;
        $minNote = isset($stats['min']) && $stats['min'] < 20 ? $stats['min'] : null;
        $maxNote = isset($stats['max']) && $stats['max'] > 0 ? $stats['max'] : null;
        $tauxVal = $stats['taux_validation'] ?? null;
    @endphp

    <div class="rc-hero">
        <div class="rc-hero-top">
            <div class="rc-hero-left">
                <div class="rc-hero-icon"><i class="fas fa-chart-bar"></i></div>
                <div class="rc-hero-info">
                    <h1>{{ $classe->name }}</h1>
                    <p>
                        @if($classe->filiere) {{ $classe->filiere->name }} @endif
                        @if($classe->niveau) &middot; {{ $classe->niveau->name ?? '' }} @endif
                        &middot; Semestre {{ $semestre ?? 1 }}
                    </p>
                    <div class="rc-breadcrumb">
                        <a href="{{ route('esbtp.lmd.resultats.index') }}">Résultats LMD</a>
                        <i class="fas fa-chevron-right"></i>
                        <span>{{ $classe->name }}</span>
                    </div>
                </div>
            </div>
            <div class="rc-hero-actions">
                <a href="{{ route('esbtp.lmd.resultats.index') }}" class="rc-hero-btn">
                    <i class="fas fa-arrow-left"></i>Retour
                </a>
                <form method="POST" action="{{ route('esbtp.lmd.bulletins.generer-classe') }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="classe_id" value="{{ $classe->id }}">
                    <input type="hidden" name="annee_universitaire_id" value="{{ $anneeId }}">
                    <input type="hidden" name="semestre" value="{{ $semestre ?? 1 }}">
                    <button type="submit" class="rc-hero-btn rc-hero-btn--solid">
                        <i class="fas fa-file-export"></i>Générer bulletins
                    </button>
                </form>
            </div>
        </div>

        <div class="rc-hero-kpis">
            <div class="rc-kpi rc-kpi--effectif">
                <div class="rc-kpi-icon"><i class="fas fa-users"></i></div>
                <div>
                    <div class="rc-kpi-value">{{ $effectif }}</div>
                    <div class="rc-kpi-label">Effectif</div>
                </div>
            </div>
            <div class="rc-kpi rc-kpi--moyenne">
                <div class="rc-kpi-icon"><i class="fas fa-calculator"></i></div>
                <div>
                    <div class="rc-kpi-value">{{ $moyClasse !== null ? number_format($moyClasse, 2) : '—' }}</div>
                    <div class="rc-kpi-label">Moy. classe</div>
                </div>
            </div>
            <div class="rc-kpi rc-kpi--min">
                <div class="rc-kpi-icon"><i class="fas fa-arrow-down"></i></div>
                <div>
                    <div class="rc-kpi-value">{{ $minNote !== null ? number_format($minNote, 2) : '—' }}</div>
                    <div class="rc-kpi-label">Min</div>
                </div>
            </div>
            <div class="rc-kpi rc-kpi--max">
                <div class="rc-kpi-icon"><i class="fas fa-arrow-up"></i></div>
                <div>
                    <div class="rc-kpi-value">{{ $maxNote !== null ? number_format($maxNote, 2) : '—' }}</div>
                    <div class="rc-kpi-label">Max</div>
                </div>
            </div>
            <div class="rc-kpi rc-kpi--taux">
                <div class="rc-kpi-icon"><i class="fas fa-percentage"></i></div>
                <div>
                    <div class="rc-kpi-value">{{ $tauxVal !== null ? number_format($tauxVal, 1) . '%' : '—' }}</div>
                    <div class="rc-kpi-label">Validation</div>
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
    <form method="GET" action="{{ route('esbtp.lmd.resultats.classe', $classe) }}" id="rc-filter-form">
        <div class="rc-filters">
            <div class="rc-filter-group">
                <label class="rc-filter-label">Semestre</label>
                <select class="rc-filter-control" name="semestre" onchange="document.getElementById('rc-filter-form').submit()">
                    @for($s = 1; $s <= 10; $s++)
                        <option value="{{ $s }}" {{ ($semestre ?? 1) == $s ? 'selected' : '' }}>
                            Semestre {{ $s }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="rc-filter-group">
                <label class="rc-filter-label">Année universitaire</label>
                <select class="rc-filter-control" name="annee_universitaire_id" onchange="document.getElementById('rc-filter-form').submit()">
                    @foreach($annees as $annee)
                        <option value="{{ $annee->id }}" {{ ($anneeId ?? null) == $annee->id ? 'selected' : '' }}>
                            {{ $annee->name ?? $annee->libelle ?? $annee->id }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    {{-- ══ Table ══ --}}
    <div class="rc-table-card">
        <div class="rc-table-header">
            <div class="rc-table-title">
                <i class="fas fa-list-ol"></i>
                Classement — Semestre {{ $semestre ?? 1 }}
            </div>
            <span class="rc-table-count">{{ $bulletins->count() }} étudiant{{ $bulletins->count() > 1 ? 's' : '' }}</span>
        </div>

        @if($bulletins->count())
            <div class="rc-table-wrapper">
                <table class="rc-table">
                    <thead>
                        <tr>
                            <th style="width:60px; text-align:center;">Rang</th>
                            <th>Matricule</th>
                            <th>Nom & Prénom</th>
                            <th style="text-align:center;">Moyenne</th>
                            <th style="text-align:center;">Crédits</th>
                            <th>Mention</th>
                            <th>Décision</th>
                            <th style="width:60px; text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bulletins as $bulletin)
                            @php
                                $moy = $bulletin->moyenne_generale ?? 0;
                                $moyClass = $moy >= 14 ? 'rc-moy--pass' : ($moy >= 10 ? 'rc-moy--good' : 'rc-moy--fail');
                                $creditsObtenus = 0;
                                $creditsTotal = 0;
                                if ($bulletin->resultatsUEs) {
                                    foreach ($bulletin->resultatsUEs as $ue) {
                                        $creditsTotal += $ue->credits ?? 0;
                                        if ($ue->valide ?? false) {
                                            $creditsObtenus += $ue->credits ?? 0;
                                        }
                                    }
                                }
                                $creditsPct = $creditsTotal > 0 ? round(($creditsObtenus / $creditsTotal) * 100) : 0;
                                $creditsFill = $creditsPct >= 80 ? 'rc-credits-fill--high' : ($creditsPct >= 50 ? 'rc-credits-fill--mid' : 'rc-credits-fill--low');

                                $mentionClass = $moy >= 14 ? 'rc-badge--success' : ($moy >= 12 ? 'rc-badge--info' : ($moy >= 10 ? 'rc-badge--warning' : 'rc-badge--danger'));
                                $decisionClass = str_contains(strtolower($bulletin->decision ?? ''), 'valid') ? 'rc-badge--success' : 'rc-badge--danger';
                                $rang = $bulletin->rang ?? null;
                            @endphp
                            <tr>
                                <td style="text-align:center;">
                                    <span class="rc-rang {{ $rang && $rang <= 3 ? 'rc-rang--top3' : '' }}">
                                        {{ $rang ?? '—' }}
                                    </span>
                                </td>
                                <td><span class="rc-matricule">{{ $bulletin->etudiant->matricule ?? '—' }}</span></td>
                                <td><span class="rc-student-name">{{ $bulletin->etudiant->nom ?? '' }} {{ $bulletin->etudiant->prenoms ?? $bulletin->etudiant->prenom ?? '' }}</span></td>
                                <td style="text-align:center;">
                                    <span class="rc-moy {{ $moyClass }}">{{ number_format($moy, 2) }}</span>
                                </td>
                                <td style="text-align:center;">
                                    <div class="rc-credits">
                                        <div class="rc-credits-bar">
                                            <div class="rc-credits-fill {{ $creditsFill }}" style="width:{{ $creditsPct }}%;"></div>
                                        </div>
                                        <span class="rc-credits-text">{{ $creditsObtenus }}/{{ $creditsTotal }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if($bulletin->mention)
                                        <span class="rc-badge {{ $mentionClass }}">{{ $bulletin->mention }}</span>
                                    @else
                                        <span style="color:#94a3b8;">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($bulletin->decision)
                                        <span class="rc-badge {{ $decisionClass }}">{{ $bulletin->decision }}</span>
                                    @else
                                        <span style="color:#94a3b8;">—</span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    <a href="{{ route('esbtp.lmd.bulletins.show', $bulletin) }}" class="rc-act" title="Voir le bulletin">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="rc-empty">
                <div class="rc-empty-icon"><i class="fas fa-inbox"></i></div>
                <div class="rc-empty-title">Aucun bulletin trouvé</div>
                <div class="rc-empty-text">Aucun bulletin n'a été généré pour ce semestre. Utilisez le bouton "Générer bulletins" ci-dessus.</div>
            </div>
        @endif
    </div>

</div>
@endsection
