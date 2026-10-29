@extends('layouts.app')

@section('title', 'Rapport de cours')

@push('styles')
<style>
    .rc-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem;
        color: #fff;
        margin-bottom: 1.25rem;
    }
    .rc-hero-top {
        display: flex; align-items: flex-start; justify-content: space-between;
        flex-wrap: wrap; gap: 1rem;
    }
    .rc-hero-left { display: flex; align-items: center; gap: 1rem; }
    .rc-hero-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; flex-shrink: 0; color: #fff;
    }
    .rc-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .rc-hero p { color: rgba(255,255,255,.75); font-size: .88rem; margin: .15rem 0 0; }

    .rc-btn--glass {
        background: rgba(255,255,255,.15); color: #fff;
        border: 1px solid rgba(255,255,255,.2); border-radius: 10px;
        padding: .5rem 1rem; font-size: .82rem; font-weight: 600;
        text-decoration: none; display: inline-flex; align-items: center; gap: .4rem;
        transition: all .2s ease;
    }
    .rc-btn--glass:hover { background: rgba(255,255,255,.25); color: #fff; }

    .rc-meta {
        display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1.25rem;
    }
    .rc-meta-chip {
        background: rgba(255,255,255,.12); color: #fff;
        border: 1px solid rgba(255,255,255,.18);
        border-radius: 999px; padding: .35rem .85rem;
        font-size: .78rem; font-weight: 500;
        display: inline-flex; align-items: center; gap: .4rem;
    }

    .rc-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
        padding: 1.5rem 1.75rem;
        margin-bottom: 1rem;
    }

    .rc-section {
        background: #f8fafc;
        border-left: 4px solid #3b82f6;
        border-radius: 0 10px 10px 0;
        padding: 1rem 1.25rem;
        margin-bottom: 1rem;
    }
    .rc-section h6 {
        font-size: .85rem; font-weight: 700; margin: 0 0 .5rem;
        display: flex; align-items: center; gap: .4rem;
    }
    .rc-section p {
        margin: 0; color: #334155; font-size: .92rem; line-height: 1.55;
        white-space: pre-wrap;
    }

    .rc-badge {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .25rem .7rem; border-radius: 999px;
        font-size: .75rem; font-weight: 600;
    }
    .rc-badge--excellent { background: #d1fae5; color: #065f46; }
    .rc-badge--good { background: #dbeafe; color: #1e40af; }
    .rc-badge--satisfactory { background: #fef3c7; color: #92400e; }
    .rc-badge--difficult { background: #fee2e2; color: #991b1b; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    @php
        $seance = $report->seanceCours;
        $dateSeance = $seance?->date_seance ? \Carbon\Carbon::parse($seance->date_seance)->format('d/m/Y') : null;
        $heureDebut = $seance?->heure_debut ? \Carbon\Carbon::parse($seance->heure_debut)->format('H:i') : null;
        $heureFin = $seance?->heure_fin ? \Carbon\Carbon::parse($seance->heure_fin)->format('H:i') : null;
    @endphp

    <div class="rc-hero">
        <div class="rc-hero-top">
            <div class="rc-hero-left">
                <div class="rc-hero-icon"><i class="fas fa-file-alt"></i></div>
                <div>
                    <h1>{{ $seance?->matiere?->name ?? 'Rapport de cours' }}</h1>
                    <p>{{ $seance?->classe?->name ?? '—' }}{{ $dateSeance ? ' · ' . $dateSeance : '' }}</p>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @if($seance)
                    <a href="{{ route('esbtp.seances-cours.show', $seance->id) }}" class="rc-btn--glass">
                        <i class="fas fa-calendar-day"></i>Voir la séance
                    </a>
                @endif
                <a href="{{ route('esbtp.rapports-cours.index') }}" class="rc-btn--glass">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        <div class="rc-meta">
            <span class="rc-meta-chip">
                <i class="fas fa-user-tie"></i>{{ $report->teacher?->name ?? '—' }}
            </span>
            @if($heureDebut)
                <span class="rc-meta-chip">
                    <i class="fas fa-clock"></i>{{ $heureDebut }}{{ $heureFin ? ' – ' . $heureFin : '' }}
                </span>
            @endif
            @if($report->submitted_at)
                <span class="rc-meta-chip">
                    <i class="fas fa-check-circle"></i>Soumis le {{ $report->submitted_at->format('d/m/Y à H:i') }}
                </span>
            @endif
            <span class="rc-badge rc-badge--{{ $report->student_behavior }}" style="border:1px solid rgba(255,255,255,.3);">
                Comportement : {{ $report->student_behavior_label }}
            </span>
        </div>
    </div>

    <div class="rc-card">
        @if($report->content_summary)
        <div class="rc-section" style="border-color:#3b82f6;">
            <h6 style="color:#1d4ed8;"><i class="fas fa-book-open"></i>Contenu enseigné</h6>
            <p>{{ $report->content_summary }}</p>
        </div>
        @endif

        @if($report->teaching_methods)
        <div class="rc-section" style="border-color:#06b6d4;">
            <h6 style="color:#0e7490;"><i class="fas fa-chalkboard-teacher"></i>Méthodes pédagogiques</h6>
            <p>{{ $report->teaching_methods }}</p>
        </div>
        @endif

        @if($report->difficulties_encountered)
        <div class="rc-section" style="border-color:#f59e0b;">
            <h6 style="color:#92400e;"><i class="fas fa-exclamation-triangle"></i>Difficultés rencontrées</h6>
            <p>{{ $report->difficulties_encountered }}</p>
        </div>
        @endif

        @if($report->homework_assigned)
        <div class="rc-section" style="border-color:#10b981;">
            <h6 style="color:#065f46;"><i class="fas fa-tasks"></i>Devoirs assignés</h6>
            <p>{{ $report->homework_assigned }}</p>
        </div>
        @endif

        @if($report->next_session_preparation)
        <div class="rc-section" style="border-color:#8b5cf6;">
            <h6 style="color:#5b21b6;"><i class="fas fa-forward"></i>Préparation prochaine séance</h6>
            <p>{{ $report->next_session_preparation }}</p>
        </div>
        @endif

        @if(!$report->teaching_methods && !$report->difficulties_encountered && !$report->homework_assigned && !$report->next_session_preparation)
            <p class="text-muted mb-0" style="font-size:.85rem;">
                <i class="fas fa-info-circle me-1"></i>Aucune section facultative renseignée par l'enseignant.
            </p>
        @endif
    </div>
</div>
@endsection
