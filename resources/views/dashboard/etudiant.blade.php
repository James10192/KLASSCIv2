@extends('layouts.app')

@section('title', 'Tableau de bord Étudiant')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Étudiant - même style que superadmin -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Bienvenue, {{ $user->name }}</h1>
                <p class="header-subtitle">Votre espace étudiant ESBTP-yAKRO</p>
            </div>
            <div class="header-actions">
                @if(isset($student))
                    <div class="year-selector">
                        <i class="fas fa-calendar me-1"></i>
                        Année {{ date('Y') }}-{{ date('Y')+1 }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Statistiques Étudiant - style moderne admin-stats -->
        <div class="admin-stats" style="margin-bottom: var(--space-xl);">
            <!-- Matricule -->
            <div class="stat-card" style="padding: var(--space-xl); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: var(--space-lg); flex: 1;">
                    <div class="stat-icon primary">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="stat-value" style="margin-bottom: var(--space-xs);">{{ $student->matricule ?? 'N/A' }}</div>
                        <div class="stat-label" style="margin: 0;">Numéro Matricule</div>
                    </div>
                </div>
                <div>
                    <a href="{{ isset($student) ? route('esbtp.etudiants.show', ['etudiant' => $student->id]) : '#' }}" class="btn-acasi primary" style="font-size: var(--text-small); padding: var(--space-sm) var(--space-md); white-space: nowrap;">
                        <i class="fas fa-user" style="margin-right: var(--space-xs);"></i>
                        Voir mon profil
                    </a>
                </div>
            </div>

            <!-- Taux de Présence -->
            @if(isset($attendanceStats))
            <div class="stat-card {{ $attendanceStats['rate'] >= 75 ? 'success' : ($attendanceStats['rate'] >= 50 ? 'warning' : 'danger') }}" style="padding: var(--space-xl); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: var(--space-lg); flex: 1;">
                    <div class="stat-icon {{ $attendanceStats['rate'] >= 75 ? 'success' : ($attendanceStats['rate'] >= 50 ? 'warning' : 'danger') }}">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="stat-value" style="margin-bottom: var(--space-xs);">{{ $attendanceStats['rate'] }}%</div>
                        <div class="stat-label" style="margin: 0;">Taux de Présence</div>
                    </div>
                </div>
                <div>
                    <a href="{{ route('esbtp.mes-absences.index') }}" class="btn-acasi primary" style="font-size: var(--text-small); padding: var(--space-sm) var(--space-md); white-space: nowrap;">
                        <i class="fas fa-chart-line" style="margin-right: var(--space-xs);"></i>
                        Mes présences
                    </a>
                </div>
            </div>
            @endif

            <!-- Classe -->
            @if(isset($classe))
            <div class="stat-card success" style="padding: var(--space-xl); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: var(--space-lg); flex: 1;">
                    <div class="stat-icon success">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="stat-value" style="margin-bottom: var(--space-xs);">{{ $classe->nom }}</div>
                        <div class="stat-label" style="margin: 0;">Ma Classe</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: var(--space-xs); color: var(--success);">
                    <i class="fas fa-users"></i>
                    <span style="font-size: var(--text-small); font-weight: 500;">Formation active</span>
                </div>
            </div>
            @endif

            <!-- Filière -->
            @if(isset($filiere))
            <div class="stat-card" style="padding: var(--space-xl); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: var(--space-lg); flex: 1;">
                    <div class="stat-icon primary">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="stat-value" style="margin-bottom: var(--space-xs);">{{ $filiere->nom }}</div>
                        <div class="stat-label" style="margin: 0;">Filière</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: var(--space-xs); color: var(--neutral);">
                    <i class="fas fa-certificate"></i>
                    <span style="font-size: var(--text-small); font-weight: 500;">Spécialisation</span>
                </div>
            </div>
            @endif

            <!-- Notifications -->
            @if(isset($unreadNotifications))
            <div class="stat-card warning" style="padding: var(--space-xl); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: var(--space-lg); flex: 1;">
                    <div class="stat-icon warning">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="stat-value" style="margin-bottom: var(--space-xs);">{{ $unreadNotifications }}</div>
                        <div class="stat-label" style="margin: 0;">Notifications</div>
                    </div>
                </div>
                <div>
                    <a href="{{ route('esbtp.mes-notifications.index') }}" class="btn-acasi warning" style="font-size: var(--text-small); padding: var(--space-sm) var(--space-md); white-space: nowrap;">
                        <i class="fas fa-envelope" style="margin-right: var(--space-xs);"></i>
                        Voir notifications
                    </a>
                </div>
            </div>
            @endif

            <!-- Niveau -->
            @if(isset($niveau))
            <div class="stat-card" style="padding: var(--space-xl); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: var(--space-lg); flex: 1;">
                    <div class="stat-icon primary">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="stat-value" style="margin-bottom: var(--space-xs);">{{ $niveau->nom }}</div>
                        <div class="stat-label" style="margin: 0;">Niveau d'Étude</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: var(--space-xs); color: var(--neutral);">
                    <i class="fas fa-stairs"></i>
                    <span style="font-size: var(--text-small); font-weight: 500;">Progression</span>
                </div>
            </div>
            @endif
        </div>

        <!-- Planning Cours d'Aujourd'hui - même style que main-card -->
        @if(isset($todayClasses) && $todayClasses->count() > 0)
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-calendar-day"></i>
                    Cours d'Aujourd'hui
                </div>
                <div class="main-card-subtitle">Planning du {{ date('d/m/Y') }}</div>
            </div>
            <div class="main-card-body">
                <div class="course-list">
                    @foreach($todayClasses as $cours)
                    <div class="course-item">
                        <div class="course-time">
                            <div class="time-display">{{ $cours->heure_debut->format('H:i') }} - {{ $cours->heure_fin->format('H:i') }}</div>
                            <div class="course-day">{{ \Carbon\Carbon::parse($cours->heure_debut)->diffInHours(\Carbon\Carbon::parse($cours->heure_fin)) }}h</div>
                        </div>
                        <div class="course-info">
                            <div class="course-subject">{{ $cours->matiere->nom ?? 'N/A' }}</div>
                            <div class="course-class">{{ $cours->enseignant ?? 'Enseignant non défini' }}</div>
                            <div class="course-type">{{ $cours->salle ?? 'Salle non définie' }}</div>
                        </div>
                        <div class="course-status">
                            <span class="badge success">Programmé</span>
                        </div>
                        <div class="course-actions">
                            <i class="fas fa-chevron-right color-primary"></i>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Examens à venir - même style que main-card urgent -->
        @if(isset($upcomingExams) && $upcomingExams->count() > 0)
        <div class="main-card urgent">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-file-alt"></i>
                    Examens à Venir
                </div>
                <div class="main-card-subtitle">{{ $upcomingExams->count() }} examen(s) programmé(s)</div>
            </div>
            <div class="main-card-body">
                <div class="urgent-list">
                    @foreach($upcomingExams as $examen)
                    <div class="urgent-item">
                        <div class="urgent-info">
                            <div class="urgent-title">{{ $examen->matiere->nom ?? 'N/A' }} - {{ $examen->type }}</div>
                            <div class="urgent-time">
                                <i class="fas fa-calendar-day"></i> {{ $examen->date->format('d/m/Y') }}
                                <i class="fas fa-clock ml-3"></i> {{ $examen->heure }}
                            </div>
                        </div>
                        <div class="urgent-countdown">
                            @php
                                $daysUntil = now()->diffInDays($examen->date, false);
                            @endphp
                            @if($daysUntil > 0)
                                <span class="badge warning">{{ $daysUntil }} jour(s)</span>
                            @elseif($daysUntil == 0)
                                <span class="badge danger">Aujourd'hui</span>
                            @else
                                <span class="badge neutral">Passé</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Notes récentes - même style que resultats -->
        @if(isset($recentGrades) && $recentGrades->count() > 0)
        <div class="resultats-grid">
            <div class="resultat-card card-moderne">
                <div class="resultat-title">
                    <i class="fas fa-chart-line"></i>
                    Notes Récentes
                </div>
                <div class="resultat-montant color-primary">{{ $recentGrades->count() }} note(s)</div>
                <div class="resultat-details">
                    @foreach($recentGrades as $note)
                    @php
                        // Récupérer le nom de la matière
                        $matiereName = $note->matiere->name ?? 'Matière non définie';
                        
                        // Récupérer la valeur de la note (convertir string en float)
                        $noteValue = floatval($note->note ?? 0);
                        
                        // Gestion des étudiants absents
                        $isAbsent = $note->is_absent ?? false;
                        $statusText = $isAbsent ? 'Absent' : '';
                    @endphp
                    <div class="resultat-detail">
                        <span>{{ $matiereName }}</span>
                        @if($isAbsent)
                            <span class="color-warning">
                                <i class="fas fa-user-times" style="margin-right: 4px;"></i>Absent
                            </span>
                        @else
                            <span class="color-{{ $noteValue >= 10 ? 'success' : 'danger' }}">
                                {{ number_format($noteValue, 2) }}/20
                            </span>
                        @endif
                    </div>
                    @endforeach
                </div>
                <div class="mt-md">
                    <a href="{{ route('esbtp.mes-notes.index') }}" class="btn-acasi primary">
                        <i class="fas fa-chart-bar"></i>
                        Voir toutes mes notes
                    </a>
                </div>
            </div>
        </div>
        @endif

        <!-- Emploi du temps - même style que table-moderne -->
        <div class="table-moderne" style="margin-bottom: var(--space-xl);">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-calendar-week"></i>
                    Emploi du Temps
                </div>
                <div class="main-card-subtitle">
                    @if(isset($classe) && $classe)
                        {{ $classe->nom ?? $classe->name ?? 'Ma Classe' }}
                    @elseif(isset($student) && isset($student->classe))
                        {{ $student->classe->nom ?? $student->classe->name ?? 'Ma Classe' }}
                    @else
                        Classe non définie
                    @endif
                </div>
            </div>
            
            <div class="empty-state" style="padding: var(--space-xl); text-align: center;">
                <i class="fas fa-calendar-times" style="font-size: 3rem; color: var(--text-muted); margin-bottom: var(--space-lg);"></i>
                <h4 style="color: var(--text-secondary); margin-bottom: var(--space-md);">Emploi du temps</h4>
                <p style="color: var(--text-muted);">
                    Consultez votre emploi du temps complet avec toutes les séances programmées.
                </p>
                <a href="{{ route('esbtp.mon-emploi-temps.index') }}" class="btn-acasi primary" style="margin-top: var(--space-md);">
                    <i class="fas fa-calendar-plus" style="margin-right: var(--space-xs);"></i>
                    Voir l'emploi du temps complet
                </a>
            </div>
        </div>

        <!-- Statistiques de présence détaillées - style moderne admin-stats -->
        <div class="admin-stats" style="margin-bottom: var(--space-xl);">
            @php
                // Calculer le taux de présence en utilisant les données du contrôleur
                $totalAttendances = isset($presences) && isset($absences) ?
                    $presences->count() + $absences->count() +
                    (isset($retards) ? $retards->count() : 0) +
                    (isset($excuses) ? $excuses->count() : 0) : 0;

                $present = isset($presences) ? $presences->count() : 0;
                $retard = isset($retards) ? $retards->count() : 0;
                $excuse = isset($excuses) ? $excuses->count() : 0;

                $presenceRate = $totalAttendances > 0 ?
                    round((($present + $retard + $excuse) / $totalAttendances) * 100) : 100;
            @endphp

            <div class="stat-card success" style="padding: var(--space-xl); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: var(--space-lg); flex: 1;">
                    <div class="stat-icon success">
                        <i class="fas fa-check"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="stat-value" style="margin-bottom: var(--space-xs);">{{ $present }}</div>
                        <div class="stat-label" style="margin: 0;">Présences</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: var(--space-xs); color: var(--success);">
                    <i class="fas fa-user-check"></i>
                    <span style="font-size: var(--text-small); font-weight: 500;">Cours présent</span>
                </div>
            </div>
            
            <div class="stat-card danger" style="padding: var(--space-xl); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: var(--space-lg); flex: 1;">
                    <div class="stat-icon danger">
                        <i class="fas fa-times"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="stat-value" style="margin-bottom: var(--space-xs);">{{ isset($absences) ? $absences->count() : 0 }}</div>
                        <div class="stat-label" style="margin: 0;">Absences</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: var(--space-xs); color: var(--danger);">
                    <i class="fas fa-user-times"></i>
                    <span style="font-size: var(--text-small); font-weight: 500;">Cours manqués</span>
                </div>
            </div>
            
            <div class="stat-card warning" style="padding: var(--space-xl); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: var(--space-lg); flex: 1;">
                    <div class="stat-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="stat-value" style="margin-bottom: var(--space-xs);">{{ $retard }}</div>
                        <div class="stat-label" style="margin: 0;">Retards</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: var(--space-xs); color: var(--warning);">
                    <i class="fas fa-stopwatch"></i>
                    <span style="font-size: var(--text-small); font-weight: 500;">Arrivées tardives</span>
                </div>
            </div>
            
            <div class="stat-card" style="padding: var(--space-xl); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: var(--space-lg); flex: 1;">
                    <div class="stat-icon primary">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="stat-value" style="margin-bottom: var(--space-xs);">{{ $presenceRate }}%</div>
                        <div class="stat-label" style="margin: 0;">Taux Global</div>
                    </div>
                </div>
                <div>
                    <a href="{{ route('esbtp.mes-absences.index') }}" class="btn-acasi primary" style="font-size: var(--text-small); padding: var(--space-sm) var(--space-md); white-space: nowrap;">
                        <i class="fas fa-chart-line" style="margin-right: var(--space-xs);"></i>
                        Voir détails
                    </a>
                </div>
            </div>
        </div>

        <!-- Actions rapides - même style que quick-actions-section -->
        <div class="quick-actions-section">
            <div class="section-header">
                <div class="section-title">
                    <i class="fas fa-bolt"></i>
                    Actions Rapides
                </div>
            </div>
            <div class="quick-actions-grid">
                <a href="{{ route('esbtp.mes-notes.index') }}" class="quick-action-card">
                    <i class="fas fa-chart-line"></i>
                    <span>Mes Notes</span>
                </a>

                <a href="{{ route('esbtp.mes-absences.index') }}" class="quick-action-card">
                    <i class="fas fa-calendar-check"></i>
                    <span>Mes Présences</span>
                </a>

                <a href="{{ route('esbtp.mon-emploi-temps.index') }}" class="quick-action-card">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Emploi du Temps</span>
                </a>

                <a href="{{ route('esbtp.mes-evaluations.index') }}" class="quick-action-card">
                    <i class="fas fa-tasks"></i>
                    <span>Mes Évaluations</span>
                </a>

                <a href="{{ route('esbtp.mes-notifications.index') }}" class="quick-action-card">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </a>

                @if(isset($student))
                <a href="{{ route('esbtp.etudiants.show', ['etudiant' => $student->id]) }}" class="quick-action-card">
                    <i class="fas fa-user-circle"></i>
                    <span>Mon Profil</span>
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
