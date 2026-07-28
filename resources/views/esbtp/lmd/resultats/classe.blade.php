@extends('layouts.app')

@section('title', 'Résultats LMD - ' . $classe->name . ' - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .rc-page { max-width: 1440px; margin: 0 auto; padding: 0 1rem 2rem; }
    .rc-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 45%, #5e91de 100%);
        border-radius: 16px;
        color: #fff;
        padding: 1.5rem 2rem;
        margin-bottom: 1rem;
    }
    .rc-hero-top, .rc-actions, .rc-kpis, .rc-filters { display: flex; gap: .75rem; flex-wrap: wrap; align-items: center; }
    .rc-hero-top { justify-content: space-between; align-items: flex-start; }
    .rc-title { font-size: 1.35rem; font-weight: 800; margin: 0; }
    .rc-subtitle { opacity: .82; font-size: .9rem; margin-top: .25rem; }
    .rc-btn {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .55rem 1rem; border-radius: 9px; border: 1px solid rgba(255,255,255,.28);
        color: #fff; background: rgba(255,255,255,.1); text-decoration: none; font-weight: 700; font-size: .84rem;
    }
    .rc-btn:hover { color: #fff; background: rgba(255,255,255,.18); text-decoration: none; }
    .rc-btn--solid { background: #fff; color: #0453cb; border-color: #fff; }
    .rc-btn--solid:hover { background: #edf2fc; color: #0453cb; }
    .rc-kpis { margin-top: 1rem; }
    .rc-kpi {
        min-width: 145px; flex: 1; background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.16); border-radius: 12px; padding: .8rem 1rem;
    }
    .rc-kpi-value { font-size: 1.25rem; font-weight: 800; line-height: 1; }
    .rc-kpi-label { font-size: .72rem; opacity: .72; margin-top: .2rem; }
    .rc-card {
        background: #fff; border: 1px solid #e8ecf1; border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
        margin-bottom: 1rem; overflow: hidden;
    }
    .rc-card-body { padding: 1rem 1.25rem; }
    .rc-filters { align-items: flex-end; }
    .rc-field { min-width: 190px; flex: 1; }
    .rc-label { display: block; font-size: .72rem; text-transform: uppercase; color: #64748b; font-weight: 800; margin-bottom: .3rem; }
    .rc-select, .rc-textarea {
        width: 100%; border: 1.5px solid #e2e8f0; background: #f8fafc;
        border-radius: 9px; padding: .55rem .75rem; color: #1e293b;
    }
    .rc-table { width: 100%; border-collapse: collapse; }
    .rc-table th { background: #f8fafc; color: #64748b; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; padding: .75rem; }
    .rc-table td { padding: .75rem; border-top: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }
    .rc-table tr:hover td { background: #f8fbff; }
    .rc-student { font-weight: 800; color: #1e293b; }
    .rc-muted { color: #94a3b8; }
    .rc-mono { font-family: Consolas, 'SF Mono', monospace; font-size: .82rem; }
    .rc-badge { display: inline-flex; align-items: center; border-radius: 999px; padding: .22rem .6rem; font-size: .72rem; font-weight: 800; }
    .rc-badge--success { background: #ecfdf5; color: #047857; }
    .rc-badge--warning { background: #fffbeb; color: #b45309; }
    .rc-badge--danger { background: #fef2f2; color: #b91c1c; }
    .rc-badge--info { background: #eff6ff; color: #0453cb; }
    .rc-badge--muted { background: #f1f5f9; color: #64748b; }
    .rc-action { color: #0453cb; font-weight: 800; text-decoration: none; }
    .rc-action:hover { text-decoration: underline; }
    .rc-modal-backdrop {
        position: fixed; inset: 0; background: rgba(15,23,42,.45);
        display: none; align-items: center; justify-content: center; z-index: 1050; padding: 1rem;
    }
    .rc-modal-backdrop.is-open { display: flex; }
    .rc-modal { background: #fff; border-radius: 12px; max-width: 640px; width: 100%; box-shadow: 0 24px 70px rgba(15,23,42,.25); }
    .rc-modal-header, .rc-modal-footer { padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; }
    .rc-modal-footer { border-top: 1px solid #f1f5f9; border-bottom: 0; display: flex; justify-content: flex-end; gap: .5rem; }
    .rc-modal-body { padding: 1rem 1.25rem; max-height: 65vh; overflow: auto; }
    .rc-alert { border-radius: 10px; padding: .8rem 1rem; margin-bottom: .75rem; background: #eff6ff; color: #1e40af; }
    .rc-alert--danger { background: #fef2f2; color: #991b1b; }
    .rc-list { margin: .75rem 0 0; padding-left: 1rem; }
    .rc-form-btn { border: 0; border-radius: 9px; padding: .55rem 1rem; font-weight: 800; }
    .rc-form-btn--primary { background: #0453cb; color: #fff; }
    .rc-form-btn--secondary { background: #f1f5f9; color: #334155; }
    @media (max-width: 768px) {
        .rc-hero-top, .rc-actions, .rc-kpis, .rc-filters { flex-direction: column; align-items: stretch; }
    }
</style>
@endpush

@section('content')
<div class="rc-page">
    @php
        $effectif = $stats['effectif'] ?? 0;
        $moyClasse = $stats['moyenne_classe'] ?? null;
        $minNote = $stats['min'] ?? null;
        $maxNote = $stats['max'] ?? null;
        $tauxVal = $stats['taux_validation'] ?? null;
    @endphp

    <div class="rc-hero">
        <div class="rc-hero-top">
            <div>
                <h1 class="rc-title">{{ $classe->name }}</h1>
                <div class="rc-subtitle">
                    @if($classe->filiere) {{ $classe->filiere->name }} @endif
                    @if($classe->niveau) - {{ $classe->niveau->name ?? '' }} @endif
                    - Semestre {{ $semestre }}
                </div>
            </div>
            <div class="rc-actions">
                <a href="{{ route('esbtp.lmd.resultats.index', ['annee_universitaire_id' => $anneeId]) }}" class="rc-btn">
                    <i class="fas fa-arrow-left"></i>Retour
                </a>
                <form id="rc-generate-form" method="POST" action="{{ route('esbtp.lmd.bulletins.generer-classe') }}" style="display:none;">
                    @csrf
                    <input type="hidden" name="classe_id" value="{{ $classe->id }}">
                    <input type="hidden" name="annee_universitaire_id" value="{{ $anneeId }}">
                    <input type="hidden" name="semestre" value="{{ $semestre }}">
                    <input type="hidden" name="incomplete_reason" data-lmd-reason-field>
                </form>
                <button type="button" class="rc-btn rc-btn--solid" data-lmd-preflight data-mode="classe" data-form-id="rc-generate-form">
                    <i class="fas fa-file-export"></i>Générer bulletins
                </button>
            </div>
        </div>

        <div class="rc-kpis">
            <div class="rc-kpi"><div class="rc-kpi-value">{{ $effectif }}</div><div class="rc-kpi-label">Effectif live</div></div>
            <div class="rc-kpi"><div class="rc-kpi-value">{{ $moyClasse !== null ? number_format($moyClasse, 2) : '-' }}</div><div class="rc-kpi-label">Moyenne classe</div></div>
            <div class="rc-kpi"><div class="rc-kpi-value">{{ $minNote !== null ? number_format($minNote, 2) : '-' }}</div><div class="rc-kpi-label">Min</div></div>
            <div class="rc-kpi"><div class="rc-kpi-value">{{ $maxNote !== null ? number_format($maxNote, 2) : '-' }}</div><div class="rc-kpi-label">Max</div></div>
            <div class="rc-kpi"><div class="rc-kpi-value">{{ $tauxVal !== null ? number_format($tauxVal, 1).'%' : '-' }}</div><div class="rc-kpi-label">Validation</div></div>
        </div>
    </div>

    @foreach(['success' => 'check-circle', 'error' => 'exclamation-circle'] as $type => $icon)
        @if(session($type))
            <div class="alert alert-{{ $type === 'error' ? 'danger' : $type }} alert-dismissible fade show" role="alert" style="border-radius:10px;">
                <i class="fas fa-{{ $icon }} me-2"></i>{{ session($type) }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    @endforeach

    <form method="GET" action="{{ route('esbtp.lmd.resultats.classe', $classe) }}" id="rc-filter-form" class="rc-card">
        <div class="rc-card-body rc-filters">
            <div class="rc-field">
                <label class="rc-label">Semestre</label>
                <select class="rc-select" name="semestre" onchange="document.getElementById('rc-filter-form').submit()">
                    @foreach($semestresAutorises ?? [1, 2] as $s)
                        <option value="{{ $s }}" {{ (int) $semestre === (int) $s ? 'selected' : '' }}>Semestre {{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="rc-field">
                <label class="rc-label">Année universitaire</label>
                <select class="rc-select" name="annee_universitaire_id" onchange="document.getElementById('rc-filter-form').submit()">
                    @foreach($annees as $annee)
                        <option value="{{ $annee->id }}" {{ (int) $anneeId === (int) $annee->id ? 'selected' : '' }}>
                            {{ $annee->name ?? $annee->libelle ?? $annee->id }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    <div class="rc-card">
        <div class="rc-card-body" style="display:flex;justify-content:space-between;gap:.75rem;align-items:center;flex-wrap:wrap;">
            <div style="font-weight:800;color:#1e293b;"><i class="fas fa-list-ol me-1" style="color:#0453cb;"></i>Résultats live - Semestre {{ $semestre }}</div>
            <span class="rc-badge rc-badge--info">{{ $resultats->count() }} étudiant{{ $resultats->count() > 1 ? 's' : '' }}</span>
        </div>
        @if($resultats->count())
            <div style="overflow-x:auto;">
                <table class="rc-table">
                    <thead>
                        <tr>
                            <th>Rang</th>
                            <th>Matricule</th>
                            <th>Nom et prénoms</th>
                            <th style="text-align:center;">Moyenne</th>
                            <th style="text-align:center;">Crédits</th>
                            <th>Mention</th>
                            <th>Etat</th>
                            <th>Bulletin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($resultats as $resultat)
                            @php
                                $student = $resultat['etudiant'];
                                $bulletin = $resultat['bulletin'];
                                $moy = $resultat['moyenne_generale'];
                                $mention = $resultat['mention_generale'] ?? null;
                                $status = $resultat['status'] ?? 'incomplete';
                                $statusLabel = match ($status) {
                                    'complete' => 'Complet',
                                    'configuration_missing' => 'Configuration manquante',
                                    default => 'Incomplet',
                                };
                                $statusClass = match ($status) {
                                    'complete' => 'rc-badge--success',
                                    'configuration_missing' => 'rc-badge--danger',
                                    default => 'rc-badge--warning',
                                };
                                $mentionClass = ($moy ?? 0) >= 14 ? 'rc-badge--success' : (($moy ?? 0) >= 10 ? 'rc-badge--info' : 'rc-badge--warning');
                            @endphp
                            <tr>
                                <td>{{ $resultat['rang'] ?? '-' }}</td>
                                <td><span class="rc-mono">{{ $student->matricule ?? '-' }}</span></td>
                                <td><span class="rc-student">{{ $student->nom ?? '' }} {{ $student->prenoms ?? $student->prenom ?? '' }}</span></td>
                                <td style="text-align:center;font-weight:800;color:{{ $moy === null ? '#94a3b8' : ($moy >= 10 ? '#047857' : '#b91c1c') }};">
                                    {{ $moy !== null ? number_format($moy, 2) : '-' }}
                                </td>
                                <td style="text-align:center;font-weight:800;">{{ $resultat['credits_capitalises'] }}/{{ $resultat['credits_totaux'] }}</td>
                                <td>
                                    @if($mention)
                                        <span class="rc-badge {{ $mentionClass }}">{{ $mention }}</span>
                                    @else
                                        <span class="rc-muted">-</span>
                                    @endif
                                </td>
                                <td><span class="rc-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                <td>
                                    @if($bulletin)
                                        <a class="rc-action" href="{{ route('esbtp.lmd.bulletins.show', $bulletin) }}">Voir</a>
                                    @else
                                        <span class="rc-badge rc-badge--muted">À générer</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="rc-card-body" style="text-align:center;padding:3rem 1rem;">
                <i class="fas fa-inbox fa-2x rc-muted"></i>
                <div style="font-weight:800;margin-top:.75rem;color:#334155;">Aucun étudiant actif</div>
                <div class="rc-muted">Aucun résultat live ne peut être calculé pour cette classe, cette année et ce semestre.</div>
            </div>
        @endif
    </div>
</div>

@include('esbtp.lmd.bulletins.partials.preflight-modal', ['canGenerateIncomplete' => $canGenerateIncomplete ?? false])
@endsection
