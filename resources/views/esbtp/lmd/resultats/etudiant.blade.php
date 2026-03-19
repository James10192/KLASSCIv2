@extends('layouts.app')

@section('title', 'Résultats LMD — {{ $etudiant->nom }} {{ $etudiant->prenoms }} | KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .lmd-hero { background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); border-radius: 16px; padding: 1.5rem 2rem; color: white; margin-bottom: 1.5rem; }
    .lmd-hero-title { font-size: 1.35rem; font-weight: 700; }
    .lmd-hero-subtitle { opacity: 0.85; margin-top: 0.25rem; font-size: 0.9rem; }
    .lmd-hero-kpis { display: flex; gap: 1rem; margin-top: 1rem; flex-wrap: wrap; }
    .lmd-hero-kpi { background: rgba(255,255,255,0.15); border-radius: 10px; padding: 0.6rem 1rem; text-align: center; min-width: 100px; }
    .lmd-hero-kpi-value { font-size: 1.4rem; font-weight: 800; }
    .lmd-hero-kpi-label { font-size: 0.75rem; opacity: 0.85; margin-top: 2px; }
    .lmd-semester-card { border: 1px solid #e2e8f0; border-radius: 14px; margin-bottom: 1.5rem; overflow: hidden; }
    .lmd-semester-header { background: #f8fafc; padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .lmd-semester-title { font-weight: 700; color: #1e293b; font-size: 1.05rem; }
    .lmd-semester-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
    .lmd-semester-body { padding: 0; }
    .lmd-ue-table { width: 100%; border-collapse: collapse; }
    .lmd-ue-table th { background: #f1f5f9; padding: 8px 12px; font-size: 0.75rem; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 2px solid #e2e8f0; }
    .lmd-ue-row td { background: #f8fafc; font-weight: 700; padding: 8px 12px; border-bottom: 1px solid #e2e8f0; font-size: 0.85rem; }
    .lmd-ecue-row td { padding: 6px 12px 6px 24px; border-bottom: 1px solid #f1f5f9; font-size: 0.83rem; color: #475569; }
    .lmd-statut-aq { background: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: 700; }
    .lmd-statut-apc { background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: 700; }
    .lmd-statut-naq { background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: 700; }
    .lmd-credits-progress { height: 8px; border-radius: 4px; background: #e2e8f0; overflow: hidden; margin-top: 4px; }
    .lmd-credits-fill { height: 100%; border-radius: 4px; background: linear-gradient(90deg, #0453cb, #10b981); transition: width 0.5s ease; }
</style>
@endpush

@section('content')
<div class="lmd-page">

    {{-- Hero --}}
    <div class="lmd-hero">
        <div class="lmd-hero-title">
            <i class="fas fa-user-graduate me-2"></i> {{ $etudiant->nom }} {{ $etudiant->prenoms }}
        </div>
        <div class="lmd-hero-subtitle">
            Matricule: {{ $etudiant->matricule }}
            @if($bulletins->isNotEmpty())
                — {{ $bulletins->first()->parcours_label ?? '' }}
            @endif
        </div>
        <div class="lmd-hero-kpis">
            <div class="lmd-hero-kpi">
                <div class="lmd-hero-kpi-value">{{ $creditsCumules }}</div>
                <div class="lmd-hero-kpi-label">Crédits validés</div>
            </div>
            <div class="lmd-hero-kpi">
                <div class="lmd-hero-kpi-value">{{ $creditsTotauxCumules }}</div>
                <div class="lmd-hero-kpi-label">Crédits totaux</div>
            </div>
            <div class="lmd-hero-kpi">
                <div class="lmd-hero-kpi-value">
                    {{ $creditsTotauxCumules > 0 ? round(($creditsCumules / $creditsTotauxCumules) * 100) : 0 }}%
                </div>
                <div class="lmd-hero-kpi-label">Progression</div>
            </div>
            <div class="lmd-hero-kpi">
                <div class="lmd-hero-kpi-value">{{ $bulletins->count() }}</div>
                <div class="lmd-hero-kpi-label">Semestres</div>
            </div>
        </div>
        {{-- Credits progress bar --}}
        <div class="lmd-credits-progress" style="margin-top: 1rem; height: 10px;">
            <div class="lmd-credits-fill" style="width: {{ $creditsTotauxCumules > 0 ? min(($creditsCumules / $creditsTotauxCumules) * 100, 100) : 0 }}%;"></div>
        </div>
    </div>

    {{-- Bulletins par semestre --}}
    @forelse($bulletins as $bulletin)
        <div class="lmd-semester-card">
            <div class="lmd-semester-header">
                <div>
                    <span class="lmd-semester-title">
                        {{ $bulletin->anneeUniversitaire->name ?? '' }} — Semestre {{ $bulletin->semestre }}
                    </span>
                    <span style="font-size: 0.85rem; color: #64748b; margin-left: 1rem;">
                        {{ $bulletin->classe->name ?? '' }} · {{ $bulletin->niveau ?? '' }}
                    </span>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="text-align: center;">
                        <div style="font-size: 1.3rem; font-weight: 800; color: {{ $bulletin->moyenne_generale >= 10 ? '#10b981' : '#ef4444' }};">
                            {{ $bulletin->moyenne_generale !== null ? number_format($bulletin->moyenne_generale, 2) : '--' }}
                        </div>
                        <div style="font-size: 0.7rem; color: #94a3b8;">Moyenne</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 1.1rem; font-weight: 700; color: #0453cb;">
                            {{ $bulletin->credits_capitalises }}/{{ $bulletin->credits_totaux }}
                        </div>
                        <div style="font-size: 0.7rem; color: #94a3b8;">Crédits</div>
                    </div>
                    @if($bulletin->rang)
                        <div style="text-align: center;">
                            <div style="font-size: 1.1rem; font-weight: 700;">{{ $bulletin->rang }}<sup>e</sup>/{{ $bulletin->effectif }}</div>
                            <div style="font-size: 0.7rem; color: #94a3b8;">Rang</div>
                        </div>
                    @endif
                    <a href="{{ route('esbtp.lmd.bulletins.pdf', $bulletin) }}" class="btn-acasi primary" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </a>
                </div>
            </div>
            <div class="lmd-semester-body">
                <table class="lmd-ue-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Intitulé</th>
                            <th style="text-align: center;">Moy /20</th>
                            <th style="text-align: center;">Statut</th>
                            <th style="text-align: center;">Crédits</th>
                            <th style="text-align: center;">Rang</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bulletin->resultatsUEs as $resUE)
                            <tr class="lmd-ue-row">
                                <td>{{ $resUE->uniteEnseignement->code ?? '' }}</td>
                                <td>{{ $resUE->uniteEnseignement->name ?? '' }}</td>
                                <td style="text-align: center;">{{ $resUE->moyenne !== null ? number_format($resUE->moyenne, 2) : '--' }}</td>
                                <td style="text-align: center;">
                                    <span class="lmd-statut-{{ strtolower($resUE->statut) }}">{{ $resUE->statut }}</span>
                                </td>
                                <td style="text-align: center;">{{ $resUE->credit }}</td>
                                <td style="text-align: center;"></td>
                            </tr>
                            @foreach($resUE->resultatsECUEs as $resECUE)
                                <tr class="lmd-ecue-row">
                                    <td>{{ $resECUE->matiere->code ?? '' }}</td>
                                    <td>{{ $resECUE->matiere->name ?? '' }}</td>
                                    <td style="text-align: center;">{{ $resECUE->moyenne !== null ? number_format($resECUE->moyenne, 2) : '--' }}</td>
                                    <td></td>
                                    <td style="text-align: center; color: #94a3b8;">{{ $resECUE->credit > 0 ? $resECUE->credit : '' }}</td>
                                    <td style="text-align: center; color: #94a3b8;">{{ $resECUE->rang ? $resECUE->rang : '' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($bulletin->deliberation)
                <div style="padding: 0.75rem 1.5rem; background: #f0fdf4; border-top: 1px solid #d1fae5; font-size: 0.85rem;">
                    <strong>Délibération :</strong> {{ $bulletin->deliberation->decision ?? $bulletin->decision_deliberation ?? '' }}
                    @if($bulletin->deliberation->mention_honorifique)
                        — <em>{{ $bulletin->deliberation->mention_honorifique }}</em>
                    @endif
                </div>
            @endif
        </div>
    @empty
        <div class="main-card" style="text-align: center; padding: 3rem;">
            <i class="fas fa-inbox fa-3x" style="color: #d1d5db; margin-bottom: 1rem;"></i>
            <p style="color: #94a3b8;">Aucun bulletin LMD généré pour cet étudiant.</p>
        </div>
    @endforelse

    <div style="margin-top: 1rem;">
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Retour
        </a>
    </div>
</div>
@endsection
