@extends('layouts.app')

@section('title', 'Résultats LMD - ' . $etudiant->nom . ' ' . $etudiant->prenoms . ' | KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .lmd-page { max-width: 1200px; margin: 0 auto; padding: 0 1rem 2rem; }
    .lmd-hero { background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); border-radius: 16px; padding: 1.5rem 2rem; color: #fff; margin-bottom: 1rem; }
    .lmd-hero-title { font-size: 1.35rem; font-weight: 800; }
    .lmd-hero-subtitle { opacity: .86; margin-top: .25rem; font-size: .9rem; }
    .lmd-hero-kpis, .lmd-filters { display: flex; gap: .75rem; flex-wrap: wrap; }
    .lmd-hero-kpis { margin-top: 1rem; }
    .lmd-hero-kpi { min-width: 130px; flex: 1; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.18); border-radius: 10px; padding: .7rem 1rem; }
    .lmd-hero-kpi-value { font-size: 1.35rem; font-weight: 800; }
    .lmd-hero-kpi-label { font-size: .74rem; opacity: .78; }
    .lmd-card { background: #fff; border: 1px solid #e8ecf1; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.04); margin-bottom: 1rem; overflow: hidden; }
    .lmd-card-body { padding: 1rem 1.25rem; }
    .lmd-field { min-width: 220px; flex: 1; }
    .lmd-label { display: block; font-size: .72rem; text-transform: uppercase; color: #64748b; font-weight: 800; margin-bottom: .3rem; }
    .lmd-select { width: 100%; border: 1.5px solid #e2e8f0; background: #f8fafc; border-radius: 9px; padding: .55rem .75rem; }
    .lmd-semester-header { background: #f8fafc; padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; gap: .75rem; flex-wrap: wrap; align-items: center; }
    .lmd-semester-title { font-weight: 800; color: #1e293b; }
    .lmd-badge { display: inline-flex; align-items: center; border-radius: 999px; padding: .22rem .6rem; font-size: .72rem; font-weight: 800; }
    .lmd-badge--success { background: #ecfdf5; color: #047857; }
    .lmd-badge--warning { background: #fffbeb; color: #b45309; }
    .lmd-badge--danger { background: #fef2f2; color: #b91c1c; }
    .lmd-badge--muted { background: #f1f5f9; color: #64748b; }
    .lmd-ue-table { width: 100%; border-collapse: collapse; }
    .lmd-ue-table th { background: #f1f5f9; padding: .55rem .75rem; font-size: .72rem; color: #475569; text-transform: uppercase; }
    .lmd-ue-table td { padding: .55rem .75rem; border-top: 1px solid #f1f5f9; font-size: .85rem; color: #334155; }
    .lmd-ue-row td { background: #fbfdff; font-weight: 800; }
    .lmd-ecue-row td:first-child { padding-left: 1.5rem; }
    .lmd-actions a { margin-left: .35rem; }
    .lmd-muted { color: #94a3b8; }
    @media (max-width: 768px) {
        .lmd-semester-header, .lmd-hero-kpis, .lmd-filters { flex-direction: column; align-items: stretch; }
    }
</style>
@endpush

@section('content')
<div class="lmd-page">
    <div class="lmd-hero">
        <div class="lmd-hero-title"><i class="fas fa-user-graduate me-2"></i>{{ $etudiant->nom }} {{ $etudiant->prenoms }}</div>
        <div class="lmd-hero-subtitle">Matricule: {{ $etudiant->matricule }}</div>
        <div class="lmd-hero-kpis">
            <div class="lmd-hero-kpi"><div class="lmd-hero-kpi-value">{{ $creditsCumules }}</div><div class="lmd-hero-kpi-label">Crédits validés</div></div>
            <div class="lmd-hero-kpi"><div class="lmd-hero-kpi-value">{{ $creditsTotauxCumules }}</div><div class="lmd-hero-kpi-label">Crédits totaux</div></div>
            <div class="lmd-hero-kpi"><div class="lmd-hero-kpi-value">{{ $creditsTotauxCumules > 0 ? round(($creditsCumules / $creditsTotauxCumules) * 100) : 0 }}%</div><div class="lmd-hero-kpi-label">Progression</div></div>
            <div class="lmd-hero-kpi"><div class="lmd-hero-kpi-value">{{ $resultats->count() }}</div><div class="lmd-hero-kpi-label">Semestres live</div></div>
        </div>
    </div>

    <form method="GET" action="{{ route('esbtp.lmd.resultats.etudiant', $etudiant) }}" class="lmd-card">
        <div class="lmd-card-body lmd-filters">
            <div class="lmd-field">
                <label class="lmd-label">Année universitaire</label>
                <select class="lmd-select" name="annee_universitaire_id" onchange="this.form.submit()">
                    @foreach($annees as $annee)
                        <option value="{{ $annee->id }}" {{ (int) $anneeId === (int) $annee->id ? 'selected' : '' }}>
                            {{ $annee->name ?? $annee->libelle ?? $annee->id }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    @forelse($resultats as $resultat)
        @php
            $bulletin = $resultat['bulletin'];
            $moyenne = $resultat['moyenne_generale'];
            $status = $resultat['status'];
            $statusLabel = match ($status) {
                'complete' => 'Complet',
                'configuration_missing' => 'Configuration manquante',
                default => 'Incomplet',
            };
            $statusClass = match ($status) {
                'complete' => 'lmd-badge--success',
                'configuration_missing' => 'lmd-badge--danger',
                default => 'lmd-badge--warning',
            };
        @endphp
        <div class="lmd-card">
            <div class="lmd-semester-header">
                <div>
                    <span class="lmd-semester-title">{{ $resultat['classe']->name ?? '' }} - Semestre {{ $resultat['semestre'] }}</span>
                    <span class="lmd-muted" style="margin-left:.5rem;">{{ $resultat['classe']->niveau->name ?? '' }}</span>
                </div>
                <div class="lmd-actions" style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
                    <strong style="color:{{ $moyenne === null ? '#94a3b8' : ($moyenne >= 10 ? '#047857' : '#b91c1c') }};">{{ $moyenne !== null ? number_format($moyenne, 2) : '-' }}/20</strong>
                    <span class="lmd-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    <span class="lmd-badge lmd-badge--muted">{{ $resultat['credits_capitalises'] }}/{{ $resultat['credits_totaux'] }} crédits</span>
                    @if($bulletin)
                        <a href="{{ route('esbtp.lmd.bulletins.pdf-preview', $bulletin) }}" class="btn-acasi secondary" target="_blank">Aperçu</a>
                        <a href="{{ route('esbtp.lmd.bulletins.pdf', $bulletin) }}" class="btn-acasi primary">PDF</a>
                    @else
                        <span class="lmd-badge lmd-badge--muted">Bulletin a generer</span>
                    @endif
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="lmd-ue-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Intitule</th>
                            <th style="text-align:center;">Moy /20</th>
                            <th style="text-align:center;">Statut</th>
                            <th style="text-align:center;">Crédits</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($resultat['resultats_ues'] as $resUE)
                            <tr class="lmd-ue-row">
                                <td>{{ $resUE['unite_enseignement']->code ?? '' }}</td>
                                <td>{{ $resUE['unite_enseignement']->name ?? '' }}</td>
                                <td style="text-align:center;">{{ $resUE['moyenne'] !== null ? number_format($resUE['moyenne'], 2) : '-' }}</td>
                                <td style="text-align:center;">{{ $resUE['statut'] }}</td>
                                <td style="text-align:center;">{{ $resUE['credit'] }}</td>
                            </tr>
                            @foreach($resUE['resultats_ecues'] as $resECUE)
                                <tr class="lmd-ecue-row">
                                    <td>{{ $resECUE['matiere']->code ?? '' }}</td>
                                    <td>{{ $resECUE['matiere']->name ?? '' }}</td>
                                    <td style="text-align:center;">{{ $resECUE['moyenne'] !== null ? number_format($resECUE['moyenne'], 2) : '-' }}</td>
                                    <td></td>
                                    <td style="text-align:center;">{{ $resECUE['credit'] ?: '' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="lmd-card">
            <div class="lmd-card-body" style="text-align:center;padding:3rem 1rem;">
                <i class="fas fa-inbox fa-3x lmd-muted"></i>
                <p class="lmd-muted" style="margin-top:1rem;">Aucun résultat LMD live pour cet étudiant sur cette année universitaire.</p>
            </div>
        </div>
    @endforelse

    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Retour</a>
</div>
@endsection
