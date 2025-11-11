@extends('layouts.app')

@section('title', 'Détails de la séance de cours - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-calendar-day me-2"></i>Détails de la séance de cours</h1>
                <p class="header-subtitle">Informations complètes sur la séance et son état de progression</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.teacher-attendance.report') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour au rapport
                </a>
            </div>
        </div>

        <!-- Informations principales de la séance -->
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-info-circle"></i>
                    Informations de la séance
                </div>
                <div class="main-card-subtitle">{{ $seancesCour->getDateCompleteFormattee() }}</div>
            </div>
            <div class="main-card-body">
                <div class="row g-4">
                    <!-- Colonne gauche -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Matière</small>
                                    <strong>{{ $seancesCour->matiere?->name ?? 'N/A' }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Enseignant</small>
                                    <strong>{{ $seancesCour->teacher?->user?->name ?? 'N/A' }}</strong>
                                    @if($seancesCour->teacher?->user?->email)
                                        <br><small class="text-muted">{{ $seancesCour->teacher->user->email }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Classe</small>
                                    <strong>{{ $seancesCour->emploiTemps?->classe?->name ?? 'N/A' }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Colonne droite -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Horaires</small>
                                    <strong>
                                        {{ $seancesCour->heure_debut ? \Carbon\Carbon::parse($seancesCour->heure_debut)->format('H:i') : 'N/A' }} -
                                        {{ $seancesCour->heure_fin ? \Carbon\Carbon::parse($seancesCour->heure_fin)->format('H:i') : 'N/A' }}
                                    </strong>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Date</small>
                                    <strong>{{ $seancesCour->getDateCompleteFormattee() }}</strong>
                                </div>
                            </div>
                        </div>

                        @if($seancesCour->salle)
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                                    <i class="fas fa-door-open"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Salle</small>
                                    <strong>{{ $seancesCour->salle }}</strong>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-{{ $seancesCour->emploiTemps?->is_active ? 'success' : 'secondary' }} text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                                    <i class="fas fa-{{ $seancesCour->emploiTemps?->is_active ? 'check' : 'pause' }}"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Emploi du temps</small>
                                    @if($seancesCour->emploiTemps?->is_active)
                                        <span class="badge bg-success">Actif</span>
                                    @else
                                        <span class="badge bg-secondary">Inactif</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @php
                            // Déterminer le statut global de l'enseignant pour cette séance
                            // Priorité : 1) attendance d'aujourd'hui, 2) attendance de la date de séance, 3) la plus récente
                            $today = \Carbon\Carbon::today();
                            $seanceDate = \Carbon\Carbon::parse($seancesCour->date_seance);

                            // Chercher d'abord une attendance d'aujourd'hui (pour les updates manuels)
                            $emargementDebutTemp = $seancesCour->teacherAttendances()
                                ->whereDate('date', $today)
                                ->where('type', 'start')
                                ->first();

                            // Si pas d'attendance aujourd'hui, chercher celle de la date de séance
                            if (!$emargementDebutTemp) {
                                $emargementDebutTemp = $seancesCour->teacherAttendances()
                                    ->whereDate('date', $seanceDate)
                                    ->where('type', 'start')
                                    ->first();
                            }

                            // Même logique pour l'émargement fin
                            $emargementFinTemp = $seancesCour->teacherAttendances()
                                ->whereDate('date', $today)
                                ->where('type', 'end')
                                ->first();

                            if (!$emargementFinTemp) {
                                $emargementFinTemp = $seancesCour->teacherAttendances()
                                    ->whereDate('date', $seanceDate)
                                    ->where('type', 'end')
                                    ->first();
                            }

                            // Statut global basé sur les émargements
                            $teacherGlobalStatus = 'not_signed'; // Par défaut non émargé
                            $statusColor = 'danger';
                            $statusIcon = 'times';
                            $statusLabel = 'Non émargé';

                            if ($emargementDebutTemp || $emargementFinTemp) {
                                // Au moins un émargement existe
                                // Vérifier d'abord si absent
                                $hasAbsent = ($emargementDebutTemp && $emargementDebutTemp->status === 'absent')
                                        || ($emargementFinTemp && $emargementFinTemp->status === 'absent');

                                $hasLate = ($emargementDebutTemp && $emargementDebutTemp->status === 'late')
                                        || ($emargementFinTemp && $emargementFinTemp->status === 'late');

                                if ($hasAbsent) {
                                    $teacherGlobalStatus = 'absent';
                                    $statusColor = 'danger';
                                    $statusIcon = 'user-times';
                                    $statusLabel = 'Absent';
                                } elseif ($hasLate) {
                                    $teacherGlobalStatus = 'late';
                                    $statusColor = 'warning';
                                    $statusIcon = 'clock';
                                    $statusLabel = 'En retard';
                                } else {
                                    $teacherGlobalStatus = 'present';
                                    $statusColor = 'success';
                                    $statusIcon = 'check';
                                    $statusLabel = 'Présent';
                                }
                            }
                        @endphp

                        <div class="mb-3" id="teacher-status-section">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-{{ $statusColor }} text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                                    <i class="fas fa-{{ $statusIcon }}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <small class="text-muted d-block">Statut enseignant</small>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-{{ $statusColor }}" id="teacher-status-badge">
                                            <i class="fas fa-{{ $statusIcon }} me-1"></i>{{ $statusLabel }}
                                        </span>

                                        {{-- Boutons d'action rapide pour coordinateur/admin --}}
                                        <div class="teacher-quick-actions d-flex gap-1">
                                            @if($teacherGlobalStatus !== 'present')
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-success mark-teacher-status-btn"
                                                    data-seance-id="{{ $seancesCour->id }}"
                                                    data-status="present"
                                                    data-type="start"
                                                    title="Marquer présent">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            @endif

                                            @if($teacherGlobalStatus !== 'absent')
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger mark-teacher-status-btn"
                                                    data-seance-id="{{ $seancesCour->id }}"
                                                    data-status="absent"
                                                    data-type="start"
                                                    title="Marquer absent">
                                                <i class="fas fa-user-times"></i>
                                            </button>
                                            @endif
                                        </div>

                                        {{-- Spinner de chargement --}}
                                        <div class="teacher-status-spinner d-none">
                                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                <span class="visually-hidden">Chargement...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            // Récupérer les émargements pour cette séance
            $emargementDebut = $seancesCour->teacherAttendances()
                ->whereDate('date', \Carbon\Carbon::parse($seancesCour->date_seance))
                ->where('type', 'start')
                ->first();

            $emargementFin = $seancesCour->teacherAttendances()
                ->whereDate('date', \Carbon\Carbon::parse($seancesCour->date_seance))
                ->where('type', 'end')
                ->first();

            // Vérifier le workflow (pas de colonne date, seulement seance_cours_id)
            $workflow = \App\Models\ESBTPSessionWorkflow::where('seance_cours_id', $seancesCour->id)
                ->first();
        @endphp

        <!-- État du workflow -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-tasks"></i>
                            État du workflow
                        </div>
                        <div class="main-card-subtitle">Progression: Émargement → Appel → Validation</div>
                    </div>
                    <div class="main-card-body">
                        <div class="row text-center">
                            <!-- Étape 1: Émargement Début -->
                            <div class="col-md-3 mb-3">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="stat-icon-planning {{ $emargementDebut ? 'success' : 'secondary' }}" style="width: 52px; height: 52px; font-size: 20px;">
                                        <i class="fas fa-sign-in-alt"></i>
                                    </div>
                                    <h6 class="mb-1 mt-2">Émargement Début</h6>
                                    @if($emargementDebut)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>{{ ucfirst($emargementDebut->status) }}
                                        </span>
                                        <small class="text-muted mt-1">
                                            {{ $emargementDebut->validated_at?->format('H:i') ?? $emargementDebut->created_at?->format('H:i') }}
                                        </small>
                                    @else
                                        <span class="badge bg-secondary">Non fait</span>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-1 d-none d-md-flex align-items-center justify-content-center">
                                <i class="fas fa-arrow-right text-muted fa-lg"></i>
                            </div>

                            <!-- Étape 2: Appel Début -->
                            <div class="col-md-3 mb-3">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="stat-icon-planning {{ $workflow && $workflow->call_start_done ? 'info' : 'secondary' }}" style="width: 52px; height: 52px; font-size: 20px;">
                                        <i class="fas fa-clipboard-list"></i>
                                    </div>
                                    <h6 class="mb-1 mt-2">Appel Début</h6>
                                    @if($workflow && $workflow->call_start_done)
                                        <span class="badge bg-info">
                                            <i class="fas fa-check me-1"></i>Terminé
                                        </span>
                                        @if($workflow->call_start_at)
                                            <small class="text-muted mt-1">
                                                {{ \Carbon\Carbon::parse($workflow->call_start_at)->format('H:i') }}
                                            </small>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary">En attente</span>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-1 d-none d-md-flex align-items-center justify-content-center">
                                <i class="fas fa-arrow-right text-muted fa-lg"></i>
                            </div>

                            <!-- Étape 3: Émargement Fin -->
                            <div class="col-md-3 mb-3">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="stat-icon-planning {{ $emargementFin ? 'success' : 'secondary' }}" style="width: 52px; height: 52px; font-size: 20px;">
                                        <i class="fas fa-sign-out-alt"></i>
                                    </div>
                                    <h6 class="mb-1 mt-2">Émargement Fin</h6>
                                    @if($emargementFin)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>{{ ucfirst($emargementFin->status) }}
                                        </span>
                                        <small class="text-muted mt-1">
                                            {{ $emargementFin->validated_at?->format('H:i') ?? $emargementFin->created_at?->format('H:i') }}
                                        </small>
                                    @else
                                        <span class="badge bg-secondary">Non fait</span>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-1 d-none d-md-flex align-items-center justify-content-center">
                                <i class="fas fa-arrow-right text-muted fa-lg"></i>
                            </div>

                            <!-- Étape 4: Appel Fin -->
                            <div class="col-md-3 mb-3">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="stat-icon-planning {{ $workflow && $workflow->call_end_done ? 'info' : 'secondary' }}" style="width: 52px; height: 52px; font-size: 20px;">
                                        <i class="fas fa-clipboard-check"></i>
                                    </div>
                                    <h6 class="mb-1 mt-2">Appel Fin</h6>
                                    @if($workflow && $workflow->call_end_done)
                                        <span class="badge bg-info">
                                            <i class="fas fa-check me-1"></i>Terminé
                                        </span>
                                        @if($workflow->call_end_at)
                                            <small class="text-muted mt-1">
                                                {{ \Carbon\Carbon::parse($workflow->call_end_at)->format('H:i') }}
                                            </small>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary">En attente</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Progression globale -->
                        @php
                            $etapesTerminees = 0;
                            if($emargementDebut) $etapesTerminees++;
                            if($workflow && $workflow->call_start_done) $etapesTerminees++;
                            if($emargementFin) $etapesTerminees++;
                            if($workflow && $workflow->call_end_done) $etapesTerminees++;
                            $progressionPct = ($etapesTerminees / 4) * 100;
                        @endphp

                        <div class="mt-4">
                            <div class="d-flex align-items-center justify-content-center">
                                <div class="stat-icon-planning primary" style="width: 32px; height: 32px; font-size: 14px; margin-right: 0.75rem;">
                                    <i class="fas fa-chart-pie"></i>
                                </div>
                                <div class="flex-grow-1 text-center">
                                    <span class="text-muted me-2">Progression globale:</span>
                                    <strong class="text-primary fs-5">{{ number_format($progressionPct, 0) }}%</strong>
                                    <span class="text-muted ms-2">({{ $etapesTerminees }}/4 étapes)</span>
                                </div>
                            </div>
                            <div class="progress mt-2" style="height: 8px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $progressionPct }}%"
                                     aria-valuenow="{{ $progressionPct }}" aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Détails des émargements -->
        @if($emargementDebut || $emargementFin)
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-clipboard-check"></i>
                    Détails des émargements
                </div>
                <div class="main-card-subtitle">Informations techniques d'émargement</div>
            </div>
            <div class="main-card-body">
                <div class="row">
                    @if($emargementDebut)
                    <div class="col-md-6">
                        <h6 class="text-success mb-3"><i class="fas fa-sign-in-alt me-2"></i>Émargement Début</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <tbody>
                                    <tr>
                                        <th style="width: 40%;">Statut</th>
                                        <td>
                                            @if($emargementDebut->status === 'present')
                                                <span class="badge bg-success">Présent</span>
                                            @elseif($emargementDebut->status === 'late')
                                                <span class="badge bg-warning">En retard</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($emargementDebut->status) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Date/Heure</th>
                                        <td>{{ $emargementDebut->validated_at?->format('d/m/Y H:i:s') ?? $emargementDebut->created_at?->format('d/m/Y H:i:s') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Adresse IP</th>
                                        <td>{{ $emargementDebut->ip_address ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Appareil</th>
                                        <td>{{ $emargementDebut->device_info ?? 'N/A' }}</td>
                                    </tr>
                                    @if($emargementDebut->latitude && $emargementDebut->longitude)
                                    <tr>
                                        <th>Localisation</th>
                                        <td>{{ $emargementDebut->latitude }}, {{ $emargementDebut->longitude }}</td>
                                    </tr>
                                    @endif
                                    @if($emargementDebut->notes)
                                    <tr>
                                        <th>Notes</th>
                                        <td>{{ $emargementDebut->notes }}</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    @if($emargementFin)
                    <div class="col-md-6">
                        <h6 class="text-success mb-3"><i class="fas fa-sign-out-alt me-2"></i>Émargement Fin</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <tbody>
                                    <tr>
                                        <th style="width: 40%;">Statut</th>
                                        <td>
                                            @if($emargementFin->status === 'present')
                                                <span class="badge bg-success">Présent</span>
                                            @elseif($emargementFin->status === 'late')
                                                <span class="badge bg-warning">En retard</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($emargementFin->status) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Date/Heure</th>
                                        <td>{{ $emargementFin->validated_at?->format('d/m/Y H:i:s') ?? $emargementFin->created_at?->format('d/m/Y H:i:s') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Adresse IP</th>
                                        <td>{{ $emargementFin->ip_address ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Appareil</th>
                                        <td>{{ $emargementFin->device_info ?? 'N/A' }}</td>
                                    </tr>
                                    @if($emargementFin->latitude && $emargementFin->longitude)
                                    <tr>
                                        <th>Localisation</th>
                                        <td>{{ $emargementFin->latitude }}, {{ $emargementFin->longitude }}</td>
                                    </tr>
                                    @endif
                                    @if($emargementFin->notes)
                                    <tr>
                                        <th>Notes</th>
                                        <td>{{ $emargementFin->notes }}</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Statistiques présences étudiants -->
        @php
            // Calculer les statistiques d'appels depuis esbtp_attendances
            $attendancesStart = \App\Models\ESBTPAttendance::where('seance_cours_id', $seancesCour->id)
                ->where('call_type', 'start')
                ->get();

            $attendancesEnd = \App\Models\ESBTPAttendance::where('seance_cours_id', $seancesCour->id)
                ->where('call_type', 'end')
                ->get();

            $statsStart = [
                'present' => $attendancesStart->where('status', 'present')->count(),
                'absent' => $attendancesStart->where('status', 'absent')->count(),
                'late' => $attendancesStart->where('status', 'late')->count(),
                'excused' => $attendancesStart->where('status', 'excused')->count(),
            ];

            $statsEnd = [
                'present' => $attendancesEnd->where('status', 'present')->count(),
                'absent' => $attendancesEnd->where('status', 'absent')->count(),
                'late' => $attendancesEnd->where('status', 'late')->count(),
                'excused' => $attendancesEnd->where('status', 'excused')->count(),
            ];

            $hasAttendanceData = $attendancesStart->count() > 0 || $attendancesEnd->count() > 0;
        @endphp

        @if($hasAttendanceData)
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-users"></i>
                    Statistiques de présence des étudiants
                </div>
                <div class="main-card-subtitle">État des appels effectués</div>
            </div>
            <div class="main-card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                            <div class="kpi-title" style="color: #000; font-weight: 600;">Présents</div>
                            <div class="kpi-value" style="color: #10b981; font-size: 2rem; font-weight: bold;">
                                {{ $statsStart['present'] + $statsEnd['present'] }}
                            </div>
                            <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                                <i class="fas fa-check-circle"></i>
                                Début: {{ $statsStart['present'] }} | Fin: {{ $statsEnd['present'] }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                            <div class="kpi-title" style="color: #000; font-weight: 600;">Absents</div>
                            <div class="kpi-value" style="color: #ef4444; font-size: 2rem; font-weight: bold;">
                                {{ $statsStart['absent'] + $statsEnd['absent'] }}
                            </div>
                            <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                                <i class="fas fa-user-times"></i>
                                Début: {{ $statsStart['absent'] }} | Fin: {{ $statsEnd['absent'] }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                            <div class="kpi-title" style="color: #000; font-weight: 600;">Retards</div>
                            <div class="kpi-value" style="color: #f59e0b; font-size: 2rem; font-weight: bold;">
                                {{ $statsStart['late'] + $statsEnd['late'] }}
                            </div>
                            <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                                <i class="fas fa-clock"></i>
                                Début: {{ $statsStart['late'] }} | Fin: {{ $statsEnd['late'] }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                            <div class="kpi-title" style="color: #000; font-weight: 600;">Excusés</div>
                            <div class="kpi-value" style="color: #6366f1; font-size: 2rem; font-weight: bold;">
                                {{ $statsStart['excused'] + $statsEnd['excused'] }}
                            </div>
                            <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                                <i class="fas fa-user-check"></i>
                                Début: {{ $statsStart['excused'] }} | Fin: {{ $statsEnd['excused'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<style>
/* Styles pour les boutons de statut enseignant */
#teacher-status-section.is-loading .teacher-quick-actions {
    display: none !important;
}

#teacher-status-section.is-loading .teacher-status-spinner {
    display: flex !important;
}

.teacher-quick-actions .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Animation pour le badge de statut */
@keyframes status-badge-pulse {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.05);
        opacity: 0.9;
    }
}

#teacher-status-badge.updating {
    animation: status-badge-pulse 0.6s ease-in-out;
}
</style>

<script>
(function() {
    debugLog('✅ Scripts show séance initialisés');

    /**
     * Met à jour l'état de chargement de la section statut enseignant
     */
    function setTeacherStatusLoadingState(isLoading) {
        const section = document.getElementById('teacher-status-section');
        if (section) {
            section.classList.toggle('is-loading', Boolean(isLoading));
        }
    }

    /**
     * Met à jour le badge de statut après update
     */
    function updateTeacherStatusBadge(status) {
        const badge = document.getElementById('teacher-status-badge');
        if (!badge) return;

        // Déterminer les couleurs et icônes
        let bgClass, icon, label;
        if (status === 'present') {
            bgClass = 'bg-success';
            icon = 'check';
            label = 'Présent';
        } else if (status === 'absent') {
            bgClass = 'bg-danger';
            icon = 'user-times';
            label = 'Absent';
        } else if (status === 'late') {
            bgClass = 'bg-warning';
            icon = 'clock';
            label = 'En retard';
        } else {
            bgClass = 'bg-secondary';
            icon = 'times';
            label = 'Non émargé';
        }

        // Animer le badge
        badge.classList.add('updating');

        setTimeout(() => {
            // Mettre à jour les classes
            badge.className = `badge ${bgClass}`;
            badge.innerHTML = `<i class="fas fa-${icon} me-1"></i>${label}`;

            // Retirer l'animation
            setTimeout(() => {
                badge.classList.remove('updating');
            }, 600);
        }, 300);
    }

    /**
     * Met à jour les boutons visibles selon le statut
     */
    function updateActionButtons(status) {
        const actionsDiv = document.querySelector('.teacher-quick-actions');
        if (!actionsDiv) return;

        // Reconstruire les boutons
        let buttonsHTML = '';

        if (status !== 'present') {
            buttonsHTML += `
                <button type="button"
                        class="btn btn-sm btn-outline-success mark-teacher-status-btn"
                        data-seance-id="{{ $seancesCour->id }}"
                        data-status="present"
                        data-type="start"
                        title="Marquer présent">
                    <i class="fas fa-check"></i>
                </button>
            `;
        }

        if (status !== 'absent') {
            buttonsHTML += `
                <button type="button"
                        class="btn btn-sm btn-outline-danger mark-teacher-status-btn"
                        data-seance-id="{{ $seancesCour->id }}"
                        data-status="absent"
                        data-type="start"
                        title="Marquer absent">
                    <i class="fas fa-user-times"></i>
                </button>
            `;
        }

        actionsDiv.innerHTML = buttonsHTML;
    }

    /**
     * Initialisation au chargement
     */
    document.addEventListener('DOMContentLoaded', function() {
        debugLog('✅ Event listeners installés pour show séance');

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Event delegation pour les boutons mark-teacher-status
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.mark-teacher-status-btn');
            if (!btn) return;

            e.preventDefault();
            e.stopPropagation();

            const seanceId = btn.getAttribute('data-seance-id');
            const status = btn.getAttribute('data-status');
            const type = btn.getAttribute('data-type') || 'start';

            if (!seanceId || !status) {
                debugError('❌ Pas de seance ID ou status sur le bouton');
                return;
            }

            const actionLabel = status === 'present' ? 'présent' : 'absent';
            if (!confirm(`Êtes-vous sûr de vouloir marquer cet enseignant ${actionLabel} ?`)) {
                return;
            }

            debugLog('🔄 Marquage statut enseignant:', { seanceId, status, type });

            setTeacherStatusLoadingState(true);

            const updateUrl = `{{ url('/esbtp/teacher-attendance/seance') }}/${seanceId}/update-status`;

            fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ status, type })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                debugLog('📦 Réponse serveur:', data);
                if (data.success) {
                    debugLog('✅ Statut mis à jour');

                    // Mettre à jour le badge
                    updateTeacherStatusBadge(status);

                    // Mettre à jour les boutons visibles
                    updateActionButtons(status);

                    // Afficher un message de succès (plus simple qu'alert)
                    debugLog('✅ Statut mis à jour avec succès');

                    // PAS DE RELOAD - juste mise à jour visuelle du badge
                    // Le workflow reste inchangé car le marquage manuel ne l'affecte pas
                } else {
                    alert('Erreur: ' + (data.message || 'Erreur inconnue'));
                }
                setTeacherStatusLoadingState(false);
            })
            .catch(error => {
                debugError('❌ Erreur update statut:', error);
                setTeacherStatusLoadingState(false);
                alert('Erreur lors de la mise à jour: ' + error.message);
            });
        }, true); // Capture phase

        debugLog('✅ Scripts show séance prêts');
    });
})();
</script>
@endpush
