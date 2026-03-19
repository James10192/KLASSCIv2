@extends('layouts.app')

@section('title', 'Bulletin LMD — {{ $etudiant->nom }} {{ $etudiant->prenoms }} | KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .lmd-preview-hero { background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0453cb 100%); border-radius: 20px; padding: 2rem; color: white; margin-bottom: 1.5rem; position: relative; overflow: hidden; }
    .lmd-preview-hero::before { content: ''; position: absolute; top: -50%; right: -20%; width: 400px; height: 400px; background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%); border-radius: 50%; }
    .lmd-preview-hero-title { font-size: 1.5rem; font-weight: 800; position: relative; z-index: 1; }
    .lmd-preview-hero-sub { opacity: 0.8; margin-top: 0.25rem; font-size: 0.9rem; position: relative; z-index: 1; }
    .lmd-preview-info { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 1.25rem; position: relative; z-index: 1; }
    .lmd-preview-info-item { font-size: 0.85rem; }
    .lmd-preview-info-label { opacity: 0.65; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .lmd-preview-stats { display: flex; gap: 1.5rem; margin-top: 1.5rem; position: relative; z-index: 1; }
    .lmd-preview-stat { background: rgba(255,255,255,0.12); border-radius: 12px; padding: 0.75rem 1.25rem; text-align: center; backdrop-filter: blur(4px); }
    .lmd-preview-stat-value { font-size: 1.6rem; font-weight: 900; }
    .lmd-preview-stat-label { font-size: 0.7rem; opacity: 0.75; margin-top: 2px; }

    .lmd-preview-table { width: 100%; border-collapse: collapse; }
    .lmd-preview-table thead th { background: #f1f5f9; padding: 10px 12px; font-size: 0.75rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 2px solid #0453cb; }
    .lmd-preview-ue td { background: #eff6ff; font-weight: 700; padding: 10px 12px; border-bottom: 1px solid #dbeafe; color: #0453cb; font-size: 0.88rem; }
    .lmd-preview-ecue td { padding: 8px 12px 8px 28px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: #374151; }
    .lmd-preview-ecue:hover td { background: #f8fafc; }
    .lmd-badge-aq { background: #d1fae5; color: #065f46; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; }
    .lmd-badge-apc { background: #fef3c7; color: #92400e; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; }
    .lmd-badge-naq { background: #fee2e2; color: #991b1b; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; }
    .lmd-mention-badge { background: #f3f4f6; color: #374151; padding: 2px 8px; border-radius: 8px; font-size: 0.75rem; font-weight: 600; }

    .lmd-footer-box { background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 14px; padding: 1.25rem; margin-top: 1.5rem; }
    .lmd-footer-row { display: flex; justify-content: space-between; align-items: center; }
</style>
@endpush

@section('content')
<div class="lmd-page">

    {{-- Actions bar --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <a href="{{ route('esbtp.lmd.bulletins.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Retour aux bulletins
        </a>
        <div style="display: flex; gap: 0.75rem;">
            <a href="{{ route('esbtp.lmd.bulletins.pdf', $bulletin) }}" class="btn-acasi primary">
                <i class="fas fa-file-pdf me-1"></i> Télécharger PDF
            </a>
            <form action="{{ route('esbtp.lmd.bulletins.toggle-publication', $bulletin) }}" method="POST" style="display: inline;">
                @csrf @method('PUT')
                <button type="submit" class="btn-acasi {{ $bulletin->is_published ? 'secondary' : 'primary' }}">
                    <i class="fas fa-{{ $bulletin->is_published ? 'eye-slash' : 'eye' }} me-1"></i>
                    {{ $bulletin->is_published ? 'Dépublier' : 'Publier' }}
                </button>
            </form>
        </div>
    </div>

    {{-- Dark Hero --}}
    <div class="lmd-preview-hero">
        <div class="lmd-preview-hero-title">
            {{ $etudiant->nom }} {{ $etudiant->prenoms }}
        </div>
        <div class="lmd-preview-hero-sub">
            Matricule {{ $etudiant->matricule }} · {{ $niveau ?? '' }} · Semestre {{ $semestre }}
        </div>

        <div class="lmd-preview-info">
            <div>
                <div class="lmd-preview-info-label">Domaine</div>
                <div class="lmd-preview-info-item">{{ $domaine ?? '--' }}</div>
            </div>
            <div>
                <div class="lmd-preview-info-label">Mention</div>
                <div class="lmd-preview-info-item">{{ $mention ?? '--' }}</div>
            </div>
            <div>
                <div class="lmd-preview-info-label">Parcours</div>
                <div class="lmd-preview-info-item">{{ $parcours_label ?? '--' }}</div>
            </div>
            <div>
                <div class="lmd-preview-info-label">Année universitaire</div>
                <div class="lmd-preview-info-item">{{ $annee->name ?? '--' }}</div>
            </div>
        </div>

        <div class="lmd-preview-stats">
            <div class="lmd-preview-stat">
                <div class="lmd-preview-stat-value" style="color: {{ ($moyenne_generale ?? 0) >= 10 ? '#6ee7b7' : '#fca5a5' }};">
                    {{ $moyenne_generale !== null ? number_format($moyenne_generale, 2) : '--' }}
                </div>
                <div class="lmd-preview-stat-label">Moyenne Générale</div>
            </div>
            <div class="lmd-preview-stat">
                <div class="lmd-preview-stat-value">{{ $credits_capitalises }}/{{ $credits_totaux }}</div>
                <div class="lmd-preview-stat-label">Crédits Capitalisés</div>
            </div>
            <div class="lmd-preview-stat">
                <div class="lmd-preview-stat-value">{{ $rang ?? '--' }}<sup style="font-size: 0.6em;">e</sup></div>
                <div class="lmd-preview-stat-label">Rang / {{ $effectif ?? '--' }}</div>
            </div>
            <div class="lmd-preview-stat">
                <div class="lmd-preview-stat-value">{{ $mention_generale ?? '--' }}</div>
                <div class="lmd-preview-stat-label">Mention</div>
            </div>
        </div>
    </div>

    {{-- Results table --}}
    <div class="main-card" style="padding: 0; overflow: hidden;">
        <table class="lmd-preview-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Intitulé</th>
                    <th style="text-align: center;">Moy /20</th>
                    <th style="text-align: center;">Statut</th>
                    <th style="text-align: center;">Mention</th>
                    <th style="text-align: center;">CECT</th>
                    <th style="text-align: center;">Min</th>
                    <th style="text-align: center;">Moy</th>
                    <th style="text-align: center;">Max</th>
                    <th>Enseignant</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resultats_ues as $resUE)
                    @php $ue = $resUE->uniteEnseignement; @endphp
                    <tr class="lmd-preview-ue">
                        <td>{{ $ue->code ?? '' }}</td>
                        <td>{{ $ue->name ?? '' }}</td>
                        <td style="text-align: center;">{{ $resUE->moyenne !== null ? number_format($resUE->moyenne, 2) : '' }}</td>
                        <td style="text-align: center;">
                            <span class="lmd-badge-{{ strtolower($resUE->statut) }}">{{ $resUE->statut }}</span>
                        </td>
                        <td style="text-align: center;">
                            @if($resUE->mention)
                                <span class="lmd-mention-badge">{{ $resUE->mention }}</span>
                            @endif
                        </td>
                        <td style="text-align: center; font-weight: 700;">{{ $resUE->credit }}</td>
                        <td style="text-align: center; color: #94a3b8; font-size: 0.8rem;">{{ $resUE->stat_min !== null ? number_format($resUE->stat_min, 2) : '' }}</td>
                        <td style="text-align: center; color: #94a3b8; font-size: 0.8rem;">{{ $resUE->stat_moy !== null ? number_format($resUE->stat_moy, 2) : '' }}</td>
                        <td style="text-align: center; color: #94a3b8; font-size: 0.8rem;">{{ $resUE->stat_max !== null ? number_format($resUE->stat_max, 2) : '' }}</td>
                        <td></td>
                    </tr>
                    @foreach($resUE->resultatsECUEs as $resECUE)
                        <tr class="lmd-preview-ecue">
                            <td>{{ $resECUE->matiere->code ?? '' }}</td>
                            <td>{{ $resECUE->matiere->name ?? '' }}</td>
                            <td style="text-align: center;">{{ $resECUE->moyenne !== null ? number_format($resECUE->moyenne, 2) : '' }}</td>
                            <td></td>
                            <td></td>
                            <td style="text-align: center; color: #94a3b8;">{{ $resECUE->credit > 0 ? $resECUE->credit : '' }}</td>
                            <td style="text-align: center; color: #94a3b8; font-size: 0.8rem;">{{ $resECUE->stat_min !== null ? number_format($resECUE->stat_min, 2) : '' }}</td>
                            <td style="text-align: center; color: #94a3b8; font-size: 0.8rem;">{{ $resECUE->stat_moy !== null ? number_format($resECUE->stat_moy, 2) : '' }}</td>
                            <td style="text-align: center; color: #94a3b8; font-size: 0.8rem;">{{ $resECUE->stat_max !== null ? number_format($resECUE->stat_max, 2) : '' }}</td>
                            <td style="font-size: 0.8rem; color: #64748b;">{{ $resECUE->enseignant->name ?? '' }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Footer decision --}}
    <div class="lmd-footer-box">
        <div class="lmd-footer-row">
            <div>
                <strong style="color: #1e293b;">Décision du conseil :</strong>
                <span style="font-size: 1.05rem; font-weight: 700; color: #0453cb; margin-left: 0.5rem;">
                    {{ $decision ?? $bulletin->decision_deliberation ?? '--' }}
                </span>
            </div>
            @if($deliberation)
                <div style="font-size: 0.85rem; color: #64748b;">
                    Jury du {{ $deliberation->jury_date?->format('d/m/Y') ?? '' }}
                    @if($deliberation->president_jury)
                        — Président : {{ $deliberation->president_jury }}
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
