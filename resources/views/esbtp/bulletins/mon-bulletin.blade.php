@extends('layouts.app')

@section('title', 'Mes Bulletins')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* Styles spécifiques pour la page bulletins */
    .bulletins-container {
        --bulletins-primary: var(--primary);
        --bulletins-secondary: var(--secondary);
        --bulletins-surface: var(--surface);
        --bulletins-border: rgba(0, 0, 0, 0.08);
    }

    .bulletin-card {
        background: white;
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--bulletins-border);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .bulletin-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
    }

    .bulletin-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(var(--primary-rgb), 0.15);
    }

    .bulletin-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--bulletins-border);
    }

    .bulletin-title {
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--text-primary);
        margin-bottom: 0.25rem;
    }

    .bulletin-subtitle {
        font-size: 0.875rem;
        color: var(--text-secondary);
    }

    .bulletin-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .bulletin-stat {
        text-align: center;
        padding: 0.75rem;
        background: rgba(var(--primary-rgb), 0.05);
        border-radius: var(--radius-medium);
    }

    .bulletin-stat-label {
        font-size: 0.75rem;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.25rem;
    }

    .bulletin-stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    .bulletin-actions {
        display: flex;
        gap: 0.5rem;
    }

    .empty-state {
        background: white;
        border-radius: var(--radius-large);
        padding: 3rem 2rem;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .empty-state-icon {
        width: 80px;
        height: 80px;
        border-radius: var(--radius-circle);
        background: linear-gradient(135deg, var(--neutral), var(--text-muted));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto 1.5rem;
    }

    .empty-state-title {
        font-size: var(--text-xl);
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .empty-state-text {
        color: var(--text-secondary);
        font-size: var(--text-base);
        line-height: 1.6;
        margin: 0;
    }

    .bulletins-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: var(--space-lg);
    }

    .mention-badge {
        padding: 0.5rem 1rem;
        border-radius: var(--radius-medium);
        font-weight: 600;
        font-size: 0.875rem;
    }

    .mention-excellent { background: #4c51bf; color: white; }
    .mention-tres-bien { background: #667eea; color: white; }
    .mention-bien { background: #4299e1; color: white; }
    .mention-assez-bien { background: #48bb78; color: white; }
    .mention-passable { background: #ed8936; color: white; }
    .mention-echec { background: #f56565; color: white; }

    /* Responsive Design */
    @media (max-width: 768px) {
        .dashboard-acasi {
            padding: 0 !important;
            max-width: 100vw;
            overflow-x: hidden;
        }

        .main-content {
            padding: 1rem !important;
            max-width: 100%;
            overflow-x: hidden;
            margin: 0 auto;
            width: 100%;
        }

        * {
            max-width: 100%;
        }

        .student-header .d-flex {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: var(--space-md);
        }

        .student-header h1 {
            font-size: 1.5rem !important;
        }

        .student-header .header-subtitle {
            font-size: 0.875rem !important;
        }

        .student-header .text-end {
            text-align: left !important;
            width: 100%;
        }

        .student-header .badge {
            display: inline-block;
            width: auto;
        }

        .bulletin-stats {
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }

        .bulletin-stat-value {
            font-size: 1.25rem;
        }

        .bulletin-actions {
            flex-direction: column;
        }

        .bulletin-actions .btn {
            width: 100%;
        }

        .bulletin-card {
            padding: 1rem;
        }

        .mention-badge {
            font-size: 0.75rem;
            padding: 0.4rem 0.8rem;
        }
    }

    @media (max-width: 400px) {
        .main-content {
            padding: 0.75rem !important;
        }

        .student-header h1 {
            font-size: 1.3rem !important;
        }

        .bulletin-stat-value {
            font-size: 1.1rem;
        }
    }
</style>
@endpush

@section('content')
<div class="dashboard-acasi bulletins-container">
    <div class="main-content">
        <!-- Header Étudiant Moderne -->
        <div class="student-header">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h1>
                        <i class="fas fa-file-alt me-3"></i>
                        Mes Bulletins
                    </h1>
                    <p class="header-subtitle">
                        Consultez et téléchargez vos bulletins de notes
                    </p>
                </div>
                <div class="text-end">
                    <div class="badge" style="background: rgba(255, 255, 255, 0.2); color: white; padding: var(--space-sm) var(--space-md); border-radius: var(--radius-medium); font-size: var(--text-sm);">
                        <i class="fas fa-calendar me-2"></i>
                        Année {{ $anneeCourante->annee_debut ?? ($anneeCourante->name ?? date('Y')) }}-{{ $anneeCourante->annee_fin ?? (isset($anneeCourante->annee_debut) ? $anneeCourante->annee_debut + 1 : date('Y')+1) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(!isset($inscription) || !$inscription)
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-exclamation-triangle" style="color: var(--warning);"></i>
                </div>
                <div class="empty-state-title">Aucune inscription active</div>
                <p class="empty-state-text">
                    Vous n'avez pas d'inscription active pour l'année universitaire <strong>{{ $anneeCourante->name ?? 'en cours' }}</strong>.<br>
                    Veuillez contacter l'administration pour régulariser votre situation.
                </p>
                <a href="{{ route('esbtp.mon-profil.index') }}" class="btn-acasi primary" style="margin-top: var(--space-lg);">
                    <i class="fas fa-user me-2"></i>
                    Voir mon profil
                </a>
            </div>
        @else
        <!-- Contenu -->
        @if($bulletins->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="empty-state-title">Aucun bulletin disponible</div>
                <p class="empty-state-text">
                    Vos bulletins apparaîtront ici une fois qu'ils seront publiés par l'administration.<br>
                    Veuillez contacter l'administration pour plus d'informations.
                </p>
            </div>
        @else
            <div class="bulletins-grid">
                @foreach($bulletins as $bulletin)
                    <div class="bulletin-card">
                        <div class="bulletin-header">
                            <div>
                                <div class="bulletin-title">
                                    <i class="fas fa-calendar-alt text-primary me-2"></i>
                                    @if($bulletin->anneeUniversitaire && ($bulletin->anneeUniversitaire->annee_debut || $bulletin->anneeUniversitaire->annee_fin))
                                        {{ $bulletin->anneeUniversitaire->annee_debut ?? '' }}-{{ $bulletin->anneeUniversitaire->annee_fin ?? '' }}
                                    @elseif($bulletin->anneeUniversitaire && $bulletin->anneeUniversitaire->name)
                                        {{ $bulletin->anneeUniversitaire->name }}
                                    @else
                                        Année non définie
                                    @endif
                                </div>
                                <div class="bulletin-subtitle">
                                    @if($bulletin->periode == 'semestre1')
                                        Premier Semestre
                                    @elseif($bulletin->periode == 'semestre2')
                                        Deuxième Semestre
                                    @elseif($bulletin->periode == 'annuel')
                                        Annuel
                                    @else
                                        {{ ucfirst($bulletin->periode) }}
                                    @endif
                                    • {{ $bulletin->classe->name ?? $bulletin->classe->libelle ?? 'N/A' }}
                                </div>
                            </div>
                            <div>
                                @if($bulletin->mention)
                                    @php
                                        $mentionClass = 'mention-echec';
                                        if($bulletin->mention == 'Très Bien' || $bulletin->mention == 'Excellent') $mentionClass = 'mention-excellent';
                                        elseif($bulletin->mention == 'Bien') $mentionClass = 'mention-bien';
                                        elseif($bulletin->mention == 'Assez Bien') $mentionClass = 'mention-assez-bien';
                                        elseif($bulletin->mention == 'Passable') $mentionClass = 'mention-passable';
                                    @endphp
                                    <span class="mention-badge {{ $mentionClass }}">{{ $bulletin->mention }}</span>
                                @else
                                    <span class="badge bg-secondary">Non définie</span>
                                @endif
                            </div>
                        </div>

                        <div class="bulletin-stats">
                            <div class="bulletin-stat">
                                <div class="bulletin-stat-label">Moyenne</div>
                                <div class="bulletin-stat-value" style="color: {{ isset($bulletin->moyenne_generale) && $bulletin->moyenne_generale >= 10 ? 'var(--success)' : 'var(--danger)' }}">
                                    @if(isset($bulletin->moyenne_generale))
                                        {{ number_format($bulletin->moyenne_generale, 2) }}/20
                                    @else
                                        N/A
                                    @endif
                                </div>
                            </div>
                            <div class="bulletin-stat">
                                <div class="bulletin-stat-label">Rang</div>
                                <div class="bulletin-stat-value">
                                    {{ $bulletin->rang ?? 'N/A' }}<small style="font-size: 0.875rem; color: var(--text-secondary);">/{{ $bulletin->effectif_classe ?? 'N/A' }}</small>
                                </div>
                            </div>
                            <div class="bulletin-stat">
                                <div class="bulletin-stat-label">Progression</div>
                                <div class="bulletin-stat-value">
                                    @php
                                        $progression = null;
                                        $prevBulletin = $bulletins->where('periode', '<', $bulletin->periode)->sortByDesc('periode')->first();
                                        if($prevBulletin && isset($prevBulletin->moyenne_generale) && isset($bulletin->moyenne_generale)) {
                                            $progression = $bulletin->moyenne_generale - $prevBulletin->moyenne_generale;
                                        }
                                    @endphp
                                    @if($progression !== null)
                                        @if($progression > 0)
                                            <span style="color: var(--success)">
                                                <i class="fas fa-arrow-up"></i>
                                                +{{ number_format($progression, 2) }}
                                            </span>
                                        @elseif($progression < 0)
                                            <span style="color: var(--danger)">
                                                <i class="fas fa-arrow-down"></i>
                                                {{ number_format($progression, 2) }}
                                            </span>
                                        @else
                                            <span style="color: var(--text-secondary)">
                                                <i class="fas fa-equals"></i> 0
                                            </span>
                                        @endif
                                    @else
                                        <span style="color: var(--text-secondary); font-size: 1rem;">-</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="bulletin-actions">
                            <a href="{{ route('esbtp.mon-bulletin.show', $bulletin->id) }}" class="btn btn-primary">
                                <i class="fas fa-eye me-2"></i>Consulter
                            </a>
                            <a href="{{ route('esbtp.bulletins.download', $bulletin->id) }}" class="btn btn-success">
                                <i class="fas fa-download me-2"></i>Télécharger PDF
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
        @endif
    </div>
</div>
@endsection
