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
                <p class="header-subtitle">
                    {{ $navigation['start_date']->isoFormat('D MMMM YYYY') }} - {{ $navigation['end_date']->isoFormat('D MMMM YYYY') }}
                </p>
            </div>
            <div class="header-actions">
                <a href="{{ route('teacher.dashboard') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour au tableau de bord
                </a>
            </div>
        </div>

        <!-- Navigation période -->
        <div class="period-navigation mb-4">
            <div class="nav-buttons">
                <a href="{{ route('teacher.timetable', ['date' => $navigation['prev_date']->format('Y-m-d'), 'mode' => $navigation['mode']]) }}"
                   class="btn btn-outline-primary">
                    <i class="fas fa-chevron-left"></i>
                    <span class="d-none d-md-inline">
                        {{ $navigation['mode'] === 'month' ? 'Mois précédent' : 'Semaine précédente' }}
                    </span>
                    <span class="d-inline d-md-none">Préc.</span>
                </a>

                <a href="{{ route('teacher.timetable') }}" class="btn btn-primary">
                    <i class="fas fa-calendar-day"></i>
                    <span class="d-none d-md-inline">Aujourd'hui</span>
                    <span class="d-inline d-md-none">Auj.</span>
                </a>

                <a href="{{ route('teacher.timetable', ['date' => $navigation['next_date']->format('Y-m-d'), 'mode' => $navigation['mode']]) }}"
                   class="btn btn-outline-primary">
                    <span class="d-none d-md-inline">
                        {{ $navigation['mode'] === 'month' ? 'Mois suivant' : 'Semaine suivante' }}
                    </span>
                    <span class="d-inline d-md-none">Suiv.</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>

        <!-- KPI Stats de progression -->
        <div class="row g-3 mb-4">
            <div class="col-lg-2 col-md-4 col-6">
                <div class="stat-card-horizontal border-success">
                    <div class="stat-icon-left bg-success text-white">
                        <i class="fas fa-check-double"></i>
                        <span class="stat-number">{{ $stats['complet'] }}</span>
                    </div>
                    <div class="stat-info-right">
                        <div class="stat-label">Complets</div>
                        <div class="stat-sublabel">Début + Fin</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="stat-card-horizontal border-warning">
                    <div class="stat-icon-left bg-warning text-white">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span class="stat-number">{{ $stats['partiel'] }}</span>
                    </div>
                    <div class="stat-info-right">
                        <div class="stat-label">Partiels</div>
                        <div class="stat-sublabel">Fin manquée</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="stat-card-horizontal border-danger">
                    <div class="stat-icon-left bg-danger text-white">
                        <i class="fas fa-times-circle"></i>
                        <span class="stat-number">{{ $stats['absent'] }}</span>
                    </div>
                    <div class="stat-info-right">
                        <div class="stat-label">Absents</div>
                        <div class="stat-sublabel">Non émargés</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="stat-card-horizontal border-secondary">
                    <div class="stat-icon-left bg-secondary text-white">
                        <i class="fas fa-calendar"></i>
                        <span class="stat-number">{{ $stats['a_venir'] }}</span>
                    </div>
                    <div class="stat-info-right">
                        <div class="stat-label">À venir</div>
                        <div class="stat-sublabel">Programmés</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-8 col-12">
                <div class="stat-card-horizontal border-primary">
                    <div class="stat-icon-left bg-primary text-white">
                        <i class="fas fa-chart-line"></i>
                        <span class="stat-number">{{ $stats['taux_presence'] }}%</span>
                    </div>
                    <div class="stat-info-right">
                        <div class="stat-label">Taux de présence</div>
                        <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: {{ $stats['taux_presence'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Légende -->
        <div class="legend-card mb-4">
            <h6 class="legend-title">
                <i class="fas fa-info-circle me-2"></i>Légende des statuts
            </h6>
            <div class="legend-items">
                <span class="legend-item">
                    <span class="legend-color bg-success"></span>
                    <span class="d-none d-md-inline">Complet (début + fin émargés)</span>
                    <span class="d-inline d-md-none">Complet</span>
                </span>
                <span class="legend-item">
                    <span class="legend-color bg-warning"></span>
                    <span class="d-none d-md-inline">Partiel / Fin manquée</span>
                    <span class="d-inline d-md-none">Partiel</span>
                </span>
                <span class="legend-item">
                    <span class="legend-color bg-danger"></span>
                    <span class="d-none d-md-inline">Absent (non émargé)</span>
                    <span class="d-inline d-md-none">Absent</span>
                </span>
                <span class="legend-item">
                    <span class="legend-color bg-secondary"></span>
                    <span class="d-none d-md-inline">À venir (programmé)</span>
                    <span class="d-inline d-md-none">À venir</span>
                </span>
            </div>
            <div class="legend-note mt-2">
                <small class="text-muted">
                    <i class="fas fa-pause-circle"></i>
                    <strong>Emploi inactif</strong> : Séance provenant d'un emploi du temps désactivé (historique)
                </small>
            </div>
        </div>

        <!-- Emploi du temps -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-calendar-week"></i>
                    Planning hebdomadaire
                </div>
                <div class="main-card-subtitle">
                    {{ $stats['total'] }} séance(s) sur la période
                </div>
            </div>
            <div class="main-card-body">
                <!-- Vue LISTE pour mobile -->
                <div class="timetable-list-view d-block d-md-none">
                    @php
                        $allSeances = collect();
                        foreach($emploiTempsSemaine as $jour => $seancesJour) {
                            $allSeances = $allSeances->merge($seancesJour);
                        }
                        $allSeances = $allSeances->sortBy('date_seance');
                    @endphp

                    @if($allSeances->count() > 0)
                        @foreach($allSeances as $seance)
                            @php
                                $statusInfo = $seance->statusInfo;
                                $dateSeance = \Carbon\Carbon::parse($seance->date_seance);
                            @endphp
                            <div class="course-list-card {{ $statusInfo['bgClass'] }} {{ $statusInfo['borderClass'] }} mb-3">
                                <!-- Header avec jour et horaires -->
                                <div class="course-list-header">
                                    <div class="course-list-day">
                                        <i class="fas fa-calendar-day"></i>
                                        {{ $dateSeance->isoFormat('dddd D MMMM') }}
                                    </div>
                                    <div class="course-list-time">
                                        <i class="fas fa-clock"></i>
                                        {{ \Carbon\Carbon::parse($seance->heure_debut)->format('H:i') }} -
                                        {{ \Carbon\Carbon::parse($seance->heure_fin)->format('H:i') }}
                                    </div>
                                </div>

                                <!-- Badges statuts -->
                                <div class="course-list-badges">
                                    <span class="badge {{ $statusInfo['badgeClass'] }}">
                                        <i class="fas {{ $statusInfo['icon'] }}"></i>
                                        {{ $statusInfo['badge'] }}
                                    </span>
                                    @if($seance->emploiTemps && !$seance->emploiTemps->is_active)
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-pause-circle"></i>
                                            Emploi inactif
                                        </span>
                                    @endif
                                </div>

                                <!-- Infos cours -->
                                <div class="course-list-info">
                                    <h6 class="course-list-subject">{{ $seance->matiere->name ?? 'Matière' }}</h6>
                                    <div class="course-list-class">
                                        <i class="fas fa-users"></i>
                                        {{ $seance->classe->name ?? 'Classe' }}
                                    </div>
                                    @if($seance->salle)
                                        <div class="course-list-room">
                                            <i class="fas fa-map-marker-alt"></i>
                                            {{ $seance->salle }}
                                        </div>
                                    @endif
                                </div>

                                <!-- Détails émargement -->
                                @if($statusInfo['showDetails'])
                                    <div class="course-list-details">
                                        @foreach($statusInfo['details'] as $label => $value)
                                            <div class="detail-row">
                                                <span class="detail-label">{{ $label }}:</span>
                                                <span class="detail-value">{{ $value }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- Action si disponible -->
                                @if($statusInfo['badge'] === 'Disponible')
                                    <div class="course-list-action">
                                        <a href="{{ route('esbtp.attendance.mark') }}" class="btn btn-primary btn-sm w-100">
                                            <i class="fas fa-signature"></i> Émarger maintenant
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Aucune séance sur cette période</p>
                        </div>
                    @endif
                </div>

                <!-- Vue GRILLE pour desktop/tablette -->
                <div class="timetable-container d-none d-md-block">
                    <div class="timetable-grid">
                        <!-- En-tête des jours -->
                        <div class="timetable-header">
                            <div class="time-header">Horaire</div>
                            @foreach($joursSemaine as $jour)
                                <div class="day-header">
                                    <span class="d-none d-md-inline">{{ $jour }}</span>
                                    <span class="d-inline d-md-none">{{ substr($jour, 0, 3) }}</span>
                                </div>
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
                                        <div class="time-end d-none d-md-block">{{ $end }}</div>
                                    </div>
                                    @foreach($joursSemaine as $jourIndex => $jourNom)
                                        @php
                                            // Trouver la séance qui correspond à ce créneau et ce jour
                                            $seance = $emploiTempsSemaine[$jourIndex]->first(function($s) use ($start, $end) {
                                                $debut = \Carbon\Carbon::parse($s->heure_debut)->format('H:i');
                                                $fin = \Carbon\Carbon::parse($s->heure_fin)->format('H:i');
                                                return ($start >= $debut && $start < $fin);
                                            });
                                        @endphp
                                        <div class="course-slot">
                                            @if($seance)
                                                @php
                                                    $statusInfo = $seance->statusInfo;
                                                    $tooltipId = 'tooltip-' . $seance->id;
                                                @endphp
                                                <div class="course-card course-active {{ $statusInfo['bgClass'] }} {{ $statusInfo['borderClass'] }}"
                                                     data-bs-toggle="tooltip"
                                                     data-bs-placement="top"
                                                     data-bs-html="true"
                                                     title="<strong>{{ $seance->matiere->name ?? 'Matière' }}</strong><br>{{ $seance->classe->name ?? 'Classe' }}<br><small>{{ $statusInfo['description'] }}</small>">

                                                    <!-- Badge statut en haut à droite -->
                                                    <div class="status-badge-corner">
                                                        <span class="badge {{ $statusInfo['badgeClass'] }}">
                                                            <i class="fas {{ $statusInfo['icon'] }}"></i>
                                                            <span class="d-none d-lg-inline">{{ $statusInfo['badge'] }}</span>
                                                        </span>
                                                    </div>

                                                    <!-- Badge emploi du temps inactif (en bas à gauche) -->
                                                    @if($seance->emploiTemps && !$seance->emploiTemps->is_active)
                                                        <div class="inactive-badge-corner">
                                                            <span class="badge bg-secondary" title="Cet emploi du temps est inactif">
                                                                <i class="fas fa-pause-circle"></i>
                                                                <span class="d-none d-lg-inline">Emploi inactif</span>
                                                            </span>
                                                        </div>
                                                    @endif

                                                    <!-- Infos du cours -->
                                                    <div class="course-subject">{{ $seance->matiere->name ?? 'Matière' }}</div>
                                                    <div class="course-class d-none d-md-block">{{ $seance->classe->name ?? '' }}</div>

                                                    @if($seance->salle)
                                                        <div class="course-room d-none d-lg-block">
                                                            <i class="fas fa-map-marker-alt"></i>
                                                            {{ $seance->salle }}
                                                        </div>
                                                    @endif

                                                    <!-- Horaires (mobile uniquement) -->
                                                    <div class="course-time-detail d-block d-md-none">
                                                        {{ \Carbon\Carbon::parse($seance->heure_debut)->format('H:i') }} -
                                                        {{ \Carbon\Carbon::parse($seance->heure_fin)->format('H:i') }}
                                                    </div>

                                                    <!-- Détails émargement (desktop uniquement avec collapse) -->
                                                    @if($statusInfo['showDetails'])
                                                        <div class="course-details d-none d-lg-block mt-2">
                                                            @foreach($statusInfo['details'] as $label => $value)
                                                                <div class="detail-line">
                                                                    <span class="detail-label">{{ $label }}:</span>
                                                                    <span class="detail-value">{{ $value }}</span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    <!-- Bouton action pour cours disponibles -->
                                                    @if($statusInfo['badge'] === 'Disponible')
                                                        <div class="course-action mt-2 d-none d-md-block">
                                                            <a href="{{ route('esbtp.attendance.mark') }}" class="btn btn-sm btn-primary btn-block">
                                                                <i class="fas fa-signature"></i> Émarger
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="course-card course-empty">
                                                    <i class="fas fa-coffee"></i>
                                                    <span class="d-none d-md-inline">Libre</span>
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
/* Variables CSS pour couleurs */
:root {
    --color-success: #10b981;
    --color-warning: #f59e0b;
    --color-danger: #ef4444;
    --color-info: #06b6d4;
    --color-primary: #1e3a8a;
    --color-secondary: #6b7280;
}

/* Period Navigation */
.period-navigation {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.nav-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    align-items: center;
}

/* KPI Stats Cards - Layout Horizontal */
.stat-card-horizontal {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    border-left: 4px solid;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    display: flex;
    align-items: center;
    gap: 1rem;
    min-height: 80px;
}

.stat-card-horizontal:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.stat-icon-left {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    min-width: 70px;
}

.stat-icon-left i {
    font-size: 1.5rem;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
}

.stat-info-right {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.stat-label {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #1e3a8a;
    margin-bottom: 0.25rem;
}

.stat-sublabel {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 500;
}

/* Légende */
.legend-card {
    background: white;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    border: 1px solid rgba(0,0,0,0.1);
}

.legend-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--color-primary);
    margin-bottom: 0.75rem;
}

.legend-items {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #374151;
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 3px;
    display: inline-block;
}

/* Vue LISTE mobile */
.timetable-list-view {
    padding: 0;
}

.course-list-card {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    border-left: 4px solid;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.course-list-card:active {
    transform: scale(0.98);
}

.course-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.course-list-day {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1e3a8a;
}

.course-list-time {
    font-size: 0.875rem;
    font-weight: 700;
    color: #374151;
}

.course-list-badges {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
    flex-wrap: wrap;
}

.course-list-info {
    margin-bottom: 0.75rem;
}

.course-list-subject {
    font-size: 1rem;
    font-weight: 700;
    color: #1e3a8a;
    margin-bottom: 0.5rem;
}

.course-list-class,
.course-list-room {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 0.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.course-list-details {
    background: rgba(0,0,0,0.02);
    padding: 0.75rem;
    border-radius: 6px;
    margin-bottom: 0.75rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.8125rem;
}

.detail-row:last-child {
    margin-bottom: 0;
}

.detail-row .detail-label {
    color: #6b7280;
    font-weight: 500;
}

.detail-row .detail-value {
    color: #111827;
    font-weight: 600;
    text-align: right;
}

.course-list-action {
    margin-top: 0.75rem;
}

/* Styles spécifiques pour l'emploi du temps moderne (grille desktop) */
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
    grid-template-columns: 120px repeat(6, 1fr);
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
    grid-template-columns: 120px repeat(6, 1fr);
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
    position: relative;
    border-left-width: 4px;
    border-left-style: solid;
}

/* Background colors pour les statuts */
.course-card.bg-success-subtle {
    background: rgba(16, 185, 129, 0.08) !important;
}

.course-card.bg-warning-subtle {
    background: rgba(245, 158, 11, 0.08) !important;
}

.course-card.bg-danger-subtle {
    background: rgba(239, 68, 68, 0.08) !important;
}

.course-card.bg-info-subtle {
    background: rgba(6, 182, 212, 0.08) !important;
}

.course-card.bg-primary-subtle {
    background: rgba(30, 58, 138, 0.08) !important;
}

.course-card.course-active:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-elevated);
}

.course-card.course-empty {
    background: rgba(107, 114, 128, 0.05);
    border: 1px solid rgba(107, 114, 128, 0.1);
    color: var(--text-muted);
}

.status-badge-corner {
    position: absolute;
    top: 4px;
    right: 4px;
    z-index: 10;
}

.status-badge-corner .badge {
    font-size: 0.65rem;
    padding: 0.25rem 0.4rem;
    font-weight: 600;
}

.inactive-badge-corner {
    position: absolute;
    bottom: 4px;
    left: 4px;
    z-index: 10;
}

.inactive-badge-corner .badge {
    font-size: 0.65rem;
    padding: 0.25rem 0.4rem;
    font-weight: 600;
    background-color: #6b7280 !important;
    opacity: 0.9;
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

.course-time-detail {
    font-size: 0.7rem;
    color: #6b7280;
    margin-top: 0.5rem;
    font-weight: 600;
}

.course-details {
    font-size: 0.65rem;
    background: rgba(255,255,255,0.7);
    padding: 0.5rem;
    border-radius: 4px;
    text-align: left;
    width: 100%;
}

.detail-line {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.25rem;
    gap: 0.5rem;
}

.detail-line:last-child {
    margin-bottom: 0;
}

.detail-label {
    color: #6b7280;
    font-weight: 500;
    white-space: nowrap;
}

.detail-value {
    color: #111827;
    font-weight: 600;
    text-align: right;
}

.course-action .btn {
    width: 100%;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
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

/* Responsive pour tablettes */
@media (max-width: 992px) {
    .timetable-header,
    .timetable-row {
        grid-template-columns: 100px repeat(6, minmax(100px, 1fr));
    }

    .stat-card-horizontal {
        padding: 0.75rem;
        gap: 0.75rem;
        min-height: 70px;
    }

    .stat-icon-left {
        min-width: 60px;
        padding: 0.5rem 0.75rem;
    }

    .stat-icon-left i {
        font-size: 1.25rem;
    }

    .stat-number {
        font-size: 1.25rem;
    }

    .stat-label {
        font-size: 0.875rem;
    }

    .stat-sublabel {
        font-size: 0.7rem;
    }
}

/* Responsive pour mobile */
@media (max-width: 768px) {
    .timetable-header,
    .timetable-row {
        grid-template-columns: 80px repeat(6, minmax(90px, 1fr));
    }

    .time-slot {
        padding: var(--space-sm);
    }

    .course-card {
        padding: var(--space-sm);
        min-height: 56px;
    }

    .course-subject {
        font-size: 0.75rem;
    }

    .course-class,
    .course-room {
        font-size: 0.7rem;
    }

    .legend-items {
        gap: 0.75rem;
    }

    .legend-item {
        font-size: 0.75rem;
    }

    .stat-card-horizontal {
        padding: 0.65rem;
        gap: 0.5rem;
        min-height: 65px;
    }

    .stat-icon-left {
        min-width: 55px;
        padding: 0.5rem;
    }

    .stat-icon-left i {
        font-size: 1.1rem;
    }

    .stat-number {
        font-size: 1.1rem;
    }

    .stat-label {
        font-size: 0.8125rem;
    }

    .stat-sublabel {
        font-size: 0.65rem;
    }

    .nav-buttons {
        gap: 0.5rem;
    }

    .nav-buttons .btn {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
}

@media (max-width: 480px) {
    .timetable-header,
    .timetable-row {
        grid-template-columns: 60px repeat(6, minmax(75px, 1fr));
    }

    .course-card {
        padding: var(--space-xs);
        min-height: 48px;
    }

    .time-display {
        font-size: var(--text-small);
    }

    .course-subject {
        font-size: 0.7rem;
    }

    .status-badge-corner .badge {
        font-size: 0.6rem;
        padding: 0.2rem 0.3rem;
    }

    .inactive-badge-corner .badge {
        font-size: 0.55rem;
        padding: 0.15rem 0.25rem;
    }

    .stat-card-horizontal {
        padding: 0.5rem;
        gap: 0.5rem;
        min-height: 60px;
    }

    .stat-icon-left {
        min-width: 50px;
        padding: 0.4rem;
    }

    .stat-icon-left i {
        font-size: 1rem;
    }

    .stat-number {
        font-size: 1rem;
    }

    .stat-label {
        font-size: 0.75rem;
    }

    .stat-sublabel {
        font-size: 0.625rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
// Initialiser Bootstrap tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endpush
