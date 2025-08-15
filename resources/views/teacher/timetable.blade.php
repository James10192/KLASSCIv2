@extends('layouts.app')

@section('title', 'Emploi du temps - Enseignant')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-calendar-alt me-2"></i>Mon emploi du temps</h1>
                <p class="header-subtitle">Consultez vos créneaux de cours de la semaine</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('teacher.dashboard') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour au tableau de bord
                </a>
            </div>
        </div>

        <!-- Emploi du temps -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-calendar-week"></i>
                    Planning hebdomadaire
                </div>
                <div class="main-card-subtitle">Votre emploi du temps de la semaine courante</div>
            </div>
            <div class="main-card-body">
                <div class="timetable-container">
                    <div class="timetable-grid">
                        <!-- En-tête des jours -->
                        <div class="timetable-header">
                            <div class="time-header">Horaire</div>
                            @foreach($joursSemaine as $jour)
                                <div class="day-header">{{ $jour }}</div>
                            @endforeach
                        </div>
                        
                        <!-- Grille des créneaux -->
                        <div class="timetable-body">
                            @foreach($creneaux as $creneau)
                                @php
                                    [$start, $end] = explode('-', $creneau);
                                @endphp
                                <div class="timetable-row">
                                    <div class="time-slot">
                                        <div class="time-display">{{ $start }}</div>
                                        <div class="time-end">{{ $end }}</div>
                                    </div>
                                    @foreach($joursSemaine as $jourIndex => $jourNom)
                                        @php
                                            $seance = $emploiTempsSemaine[$jourIndex]->first(function($s) use ($start, $end) {
                                                $debut = \Carbon\Carbon::parse($s->heure_debut)->format('H:i');
                                                $fin = \Carbon\Carbon::parse($s->heure_fin)->format('H:i');
                                                return ($start >= $debut && $start < $fin);
                                            });
                                        @endphp
                                        <div class="course-slot">
                                            @if($seance)
                                                <div class="course-card course-active">
                                                    <div class="course-subject">{{ $seance->matiere->name ?? 'Matière' }}</div>
                                                    <div class="course-class">{{ $seance->classe->name ?? '' }}</div>
                                                    @if($seance->salle)
                                                        <div class="course-room">
                                                            <i class="fas fa-map-marker-alt"></i>
                                                            {{ $seance->salle }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="course-card course-empty">
                                                    <i class="fas fa-coffee"></i>
                                                    <span>Libre</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Styles spécifiques pour l'emploi du temps moderne */
.timetable-container {
    overflow-x: auto;
    border-radius: var(--radius-medium);
    background: var(--surface);
    box-shadow: var(--shadow-card);
}

.timetable-grid {
    min-width: 800px;
}

.timetable-header {
    display: grid;
    grid-template-columns: 120px repeat(7, 1fr);
    background: linear-gradient(135deg, rgba(30, 58, 138, 0.05), rgba(30, 64, 175, 0.02));
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.time-header {
    padding: var(--space-md);
    font-weight: 700;
    color: var(--primary);
    font-size: var(--text-small);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.day-header {
    padding: var(--space-md);
    font-weight: 600;
    color: var(--text-primary);
    font-size: var(--text-normal);
    text-align: center;
    border-left: 1px solid rgba(0, 0, 0, 0.05);
}

.timetable-body {
    display: flex;
    flex-direction: column;
}

.timetable-row {
    display: grid;
    grid-template-columns: 120px repeat(7, 1fr);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    min-height: 80px;
}

.timetable-row:last-child {
    border-bottom: none;
}

.time-slot {
    padding: var(--space-md);
    background: var(--background);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    border-right: 1px solid rgba(0, 0, 0, 0.05);
}

.time-display {
    font-size: var(--text-normal);
    font-weight: 700;
    color: var(--primary);
    margin-bottom: var(--space-xs);
}

.time-end {
    font-size: var(--text-small);
    color: var(--text-secondary);
}

.course-slot {
    padding: var(--space-sm);
    border-left: 1px solid rgba(0, 0, 0, 0.05);
    display: flex;
    align-items: center;
    justify-content: center;
}

.course-card {
    width: 100%;
    height: 100%;
    padding: var(--space-md);
    border-radius: var(--radius-small);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    transition: all 0.2s ease;
    min-height: 64px;
}

.course-card.course-active {
    background: linear-gradient(135deg, rgba(30, 58, 138, 0.08), rgba(30, 64, 175, 0.05));
    border: 1px solid rgba(30, 58, 138, 0.15);
    border-left: 4px solid var(--primary);
}

.course-card.course-active:hover {
    background: linear-gradient(135deg, rgba(30, 58, 138, 0.12), rgba(30, 64, 175, 0.08));
    border-color: rgba(30, 58, 138, 0.25);
    transform: translateY(-1px);
    box-shadow: var(--shadow-elevated);
}

.course-card.course-empty {
    background: rgba(107, 114, 128, 0.05);
    border: 1px solid rgba(107, 114, 128, 0.1);
    color: var(--text-muted);
}

.course-subject {
    font-size: var(--text-normal);
    font-weight: 600;
    color: var(--primary);
    margin-bottom: var(--space-xs);
    line-height: 1.2;
}

.course-class {
    font-size: var(--text-small);
    color: var(--text-secondary);
    margin-bottom: var(--space-xs);
}

.course-room {
    font-size: var(--text-small);
    color: var(--accent-blue);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: var(--space-xs);
}

.course-empty i {
    font-size: 1.2rem;
    margin-bottom: var(--space-xs);
    opacity: 0.5;
}

.course-empty span {
    font-size: var(--text-small);
    font-weight: 500;
}

/* Responsive pour emploi du temps */
@media (max-width: 768px) {
    .timetable-header,
    .timetable-row {
        grid-template-columns: 100px repeat(7, minmax(120px, 1fr));
    }
    
    .time-slot {
        padding: var(--space-sm);
    }
    
    .course-card {
        padding: var(--space-sm);
        min-height: 56px;
    }
    
    .course-subject {
        font-size: var(--text-small);
    }
    
    .course-class,
    .course-room {
        font-size: calc(var(--text-small) - 1px);
    }
}

@media (max-width: 480px) {
    .timetable-header,
    .timetable-row {
        grid-template-columns: 80px repeat(7, minmax(100px, 1fr));
    }
    
    .course-card {
        padding: var(--space-xs);
        min-height: 48px;
    }
    
    .time-display {
        font-size: var(--text-small);
    }
    
    .time-end {
        font-size: calc(var(--text-small) - 1px);
    }
}
</style>
@endpush
