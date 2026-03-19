@extends('layouts.app')

@section('title', 'Résultats LMD — ' . $classe->name . ' — KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ── LMD Resultats Classe ── */
    .lmd-hero {
        background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
        border-radius: 16px;
        padding: 2rem 2.5rem;
        color: #fff;
        margin-bottom: 1.5rem;
    }
    .lmd-hero-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .lmd-hero-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .lmd-hero-avatar {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        background: rgba(255,255,255,.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .lmd-hero-info h1 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0 0 .25rem;
        color: #fff;
    }
    .lmd-hero-info p {
        margin: 0;
        opacity: .85;
        font-size: .9rem;
    }
    .lmd-hero-breadcrumb {
        display: flex;
        align-items: center;
        gap: .4rem;
        margin-top: .4rem;
        font-size: .8rem;
        opacity: .75;
    }
    .lmd-hero-breadcrumb a { color: #fff; text-decoration: underline; }
    .lmd-hero-actions {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
    }
    .lmd-hero-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .55rem 1.1rem;
        border-radius: 10px;
        font-size: .85rem;
        font-weight: 600;
        border: 1.5px solid rgba(255,255,255,.35);
        color: #fff;
        background: transparent;
        text-decoration: none;
        transition: all .2s;
    }
    .lmd-hero-btn:hover { background: rgba(255,255,255,.15); color: #fff; text-decoration: none; }
    .lmd-hero-btn--solid {
        background: #fff;
        color: #0453cb;
        border-color: #fff;
    }
    .lmd-hero-btn--solid:hover { background: #e8effa; color: #0453cb; }

    /* KPIs */
    .lmd-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .lmd-stat {
        background: #fff;
        border-radius: 12px;
        padding: 1.1rem;
        display: flex;
        align-items: center;
        gap: .85rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
        border: 1px solid #e2e8f0;
    }
    .lmd-stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }
    .lmd-stat--primary .lmd-stat-icon { background: rgba(4,83,203,.1); color: #0453cb; }
    .lmd-stat--info .lmd-stat-icon { background: rgba(59,130,246,.1); color: #3b82f6; }
    .lmd-stat--success .lmd-stat-icon { background: rgba(16,185,129,.1); color: #10b981; }
    .lmd-stat--warning .lmd-stat-icon { background: rgba(245,158,11,.1); color: #d97706; }
    .lmd-stat--danger .lmd-stat-icon { background: rgba(239,68,68,.1); color: #dc2626; }
    .lmd-stat-value {
        font-size: 1.35rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1;
    }
    .lmd-stat-label {
        font-size: .78rem;
        color: #64748b;
        margin-top: .1rem;
    }

    /* Filter bar */
    .lmd-filter-bar {
        background: #fff;
        border-radius: 12px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: flex-end;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .lmd-filter-group { display: flex; flex-direction: column; gap: .3rem; }
    .lmd-filter-label { font-size: .78rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .03em; }
    .lmd-filter-select {
        padding: .5rem .75rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        font-size: .88rem;
        color: #1e293b;
        background: #fff;
        min-width: 160px;
        transition: border-color .2s;
    }
    .lmd-filter-select:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.1); }

    /* Table card */
    .lmd-table-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
        overflow: hidden;
    }
    .lmd-table-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .5rem;
    }
    .lmd-table-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1e293b;
    }
    .lmd-table-wrapper { overflow-x: auto; }

    /* Moyenne colors */
    .lmd-moy-green { color: #10b981; font-weight: 700; }
    .lmd-moy-blue { color: #0453cb; font-weight: 700; }
    .lmd-moy-red { color: #dc2626; font-weight: 700; }

    .lmd-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #64748b;
        transition: all .2s;
        text-decoration: none;
    }
    .lmd-action-btn:hover { background: #0453cb; color: #fff; border-color: #0453cb; }

    @media (max-width: 768px) {
        .lmd-hero { padding: 1.5rem; }
        .lmd-hero-content { flex-direction: column; align-items: flex-start; }
        .lmd-filter-bar { flex-direction: column; align-items: stretch; }
        .lmd-filter-select { min-width: 100%; }
        .lmd-stats { grid-template-columns: repeat(2, 1fr); }
    }
</style>
@endpush

@section('content')
<div class="lmd-page">
    <div class="main-content">

        {{-- Hero --}}
        <div class="lmd-hero">
            <div class="lmd-hero-content">
                <div class="lmd-hero-left">
                    <div class="lmd-hero-avatar"><i class="fas fa-chart-bar"></i></div>
                    <div class="lmd-hero-info">
                        <h1>{{ $classe->name }}</h1>
                        <p>
                            @if($classe->filiere) {{ $classe->filiere->name }} @endif
                            @if($classe->niveau) &middot; {{ $classe->niveau->name ?? '' }} @endif
                            &middot; Semestre {{ $semestre ?? 1 }}
                        </p>
                        <div class="lmd-hero-breadcrumb">
                            <a href="{{ route('esbtp.lmd.resultats.index') }}">Résultats LMD</a>
                            <i class="fas fa-chevron-right"></i>
                            <span>{{ $classe->name }}</span>
                        </div>
                    </div>
                </div>
                <div class="lmd-hero-actions">
                    <a href="{{ route('esbtp.lmd.resultats.index') }}" class="lmd-hero-btn">
                        <i class="fas fa-arrow-left"></i>Retour
                    </a>
                    <form method="POST" action="{{ route('esbtp.lmd.bulletins.generer-classe') }}" style="display:inline;">
                        @csrf
                        <input type="hidden" name="classe_id" value="{{ $classe->id }}">
                        <input type="hidden" name="annee_universitaire_id" value="{{ $anneeId }}">
                        <input type="hidden" name="semestre" value="{{ $semestre ?? 1 }}">
                        <button type="submit" class="lmd-hero-btn--solid lmd-hero-btn">
                            <i class="fas fa-file-export"></i>Générer bulletins
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- KPIs --}}
        <div class="lmd-stats">
            <div class="lmd-stat lmd-stat--primary">
                <div class="lmd-stat-icon"><i class="fas fa-users"></i></div>
                <div>
                    <div class="lmd-stat-value">{{ $stats['effectif'] ?? 0 }}</div>
                    <div class="lmd-stat-label">Effectif</div>
                </div>
            </div>
            <div class="lmd-stat lmd-stat--info">
                <div class="lmd-stat-icon"><i class="fas fa-calculator"></i></div>
                <div>
                    <div class="lmd-stat-value">{{ isset($stats['moyenne_classe']) && $stats['moyenne_classe'] > 0 ? number_format($stats['moyenne_classe'], 2) : '—' }}</div>
                    <div class="lmd-stat-label">Moy. classe</div>
                </div>
            </div>
            <div class="lmd-stat lmd-stat--success">
                <div class="lmd-stat-icon"><i class="fas fa-arrow-down"></i></div>
                <div>
                    <div class="lmd-stat-value">{{ isset($stats['min']) && $stats['min'] < 20 ? number_format($stats['min'], 2) : '—' }}</div>
                    <div class="lmd-stat-label">Min</div>
                </div>
            </div>
            <div class="lmd-stat lmd-stat--warning">
                <div class="lmd-stat-icon"><i class="fas fa-arrow-up"></i></div>
                <div>
                    <div class="lmd-stat-value">{{ isset($stats['max']) && $stats['max'] > 0 ? number_format($stats['max'], 2) : '—' }}</div>
                    <div class="lmd-stat-label">Max</div>
                </div>
            </div>
            <div class="lmd-stat lmd-stat--danger">
                <div class="lmd-stat-icon"><i class="fas fa-percentage"></i></div>
                <div>
                    <div class="lmd-stat-value">{{ isset($stats['taux_validation']) ? number_format($stats['taux_validation'], 1) . '%' : '—' }}</div>
                    <div class="lmd-stat-label">Taux validation</div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="lmd-filter-bar">
            <form method="GET" action="{{ route('esbtp.lmd.resultats.classe', $classe) }}" id="lmd-filter-form" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end; width:100%;">
                <div class="lmd-filter-group">
                    <label class="lmd-filter-label">Semestre</label>
                    <select class="lmd-filter-select" name="semestre" onchange="document.getElementById('lmd-filter-form').submit()">
                        @for($s = 1; $s <= 10; $s++)
                            <option value="{{ $s }}" {{ ($semestre ?? 1) == $s ? 'selected' : '' }}>
                                Semestre {{ $s }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="lmd-filter-group">
                    <label class="lmd-filter-label">Année</label>
                    <select class="lmd-filter-select" name="annee_universitaire_id" onchange="document.getElementById('lmd-filter-form').submit()">
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id }}" {{ ($anneeId ?? null) == $annee->id ? 'selected' : '' }}>
                                {{ $annee->name ?? $annee->libelle ?? $annee->id }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="lmd-table-card">
            <div class="lmd-table-header">
                <div class="lmd-table-title">
                    <i class="fas fa-list me-1"></i>Résultats — Semestre {{ $semestre ?? 1 }}
                </div>
                <span style="font-size:.82rem; color:#64748b;">
                    {{ $bulletins->count() }} étudiant(s)
                </span>
            </div>
            <div class="lmd-table-wrapper">
                <table class="table-modern" style="width:100%;">
                    <thead>
                        <tr>
                            <th style="width:60px;">Rang</th>
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
                        @forelse($bulletins as $bulletin)
                            @php
                                $moy = $bulletin->moyenne_generale ?? 0;
                                $moyClass = $moy >= 14 ? 'lmd-moy-green' : ($moy >= 10 ? 'lmd-moy-blue' : 'lmd-moy-red');
                                $creditsObtenus = 0;
                                $creditsTotal = 0;
                                if ($bulletin->resultatsUEs) {
                                    foreach ($bulletin->resultatsUEs as $ue) {
                                        $creditsTotal += $ue->credits ?? 0;
                                        if (($ue->valide ?? false)) {
                                            $creditsObtenus += $ue->credits ?? 0;
                                        }
                                    }
                                }
                            @endphp
                            <tr>
                                <td style="text-align:center; font-weight:700; color:#64748b;">
                                    {{ $bulletin->rang ?? '—' }}
                                </td>
                                <td style="font-family:monospace; font-size:.85rem;">
                                    {{ $bulletin->etudiant->matricule ?? '—' }}
                                </td>
                                <td style="font-weight:600; color:#1e293b;">
                                    {{ $bulletin->etudiant->nom ?? '' }} {{ $bulletin->etudiant->prenoms ?? $bulletin->etudiant->prenom ?? '' }}
                                </td>
                                <td style="text-align:center;">
                                    <span class="{{ $moyClass }}">{{ number_format($moy, 2) }}</span>
                                </td>
                                <td style="text-align:center;">
                                    <span style="font-weight:600;">{{ $creditsObtenus }}</span>
                                    <span style="color:#64748b;">/{{ $creditsTotal }}</span>
                                </td>
                                <td>
                                    @if($bulletin->mention)
                                        <span class="status-badge-{{ $moy >= 14 ? 'success' : ($moy >= 10 ? 'warning' : 'danger') }}">
                                            {{ $bulletin->mention }}
                                        </span>
                                    @else
                                        <span style="color:#94a3b8;">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($bulletin->decision)
                                        <span class="status-badge-{{ str_contains(strtolower($bulletin->decision), 'valid') ? 'success' : 'danger' }}">
                                            {{ $bulletin->decision }}
                                        </span>
                                    @else
                                        <span style="color:#94a3b8;">—</span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    <a href="{{ route('esbtp.lmd.bulletins.show', $bulletin) }}" class="lmd-action-btn" title="Voir le bulletin">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center; padding:3rem; color:#64748b;">
                                    <i class="fas fa-inbox" style="font-size:2rem; opacity:.4; display:block; margin-bottom:.75rem;"></i>
                                    Aucun bulletin trouvé pour ce semestre.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
