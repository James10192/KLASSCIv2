@extends('layouts.app')

@section('title', 'Rapport d\'émargement des enseignants - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-clipboard-check me-2"></i>Rapport d'émargement des cours</h1>
                <p class="header-subtitle">Suivi des présences et émargements des enseignants pour les séances de cours uniquement</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn-acasi secondary" data-bs-toggle="modal" data-bs-target="#generateCodeModal">
                    <i class="fas fa-qrcode"></i>Générer Code du Jour
                </button>
                <a href="{{ route('esbtp.admin.attendance.generate-code') }}" class="btn-acasi warning">
                    <i class="fas fa-download"></i>Exporter
                </a>
            </div>
        </div>

        <!-- Année universitaire courante -->
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-calendar-alt"></i>
                    Année universitaire en cours
                </div>
                <div class="main-card-subtitle">{{ $anneeEnCours->name ?? 'Non définie' }}</div>
            </div>
            <div class="main-card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-calendar-check text-primary me-2"></i>
                        <span>Du {{ $anneeEnCours->date_debut ? \Carbon\Carbon::parse($anneeEnCours->date_debut)->format('d/m/Y') : 'N/A' }}</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-calendar-times text-primary me-2"></i>
                        <span>Au {{ $anneeEnCours->date_fin ? \Carbon\Carbon::parse($anneeEnCours->date_fin)->format('d/m/Y') : 'N/A' }}</span>
                    </div>
                    <div class="ms-auto">
                        <span class="badge bg-primary">Année courante</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques KPI -->
        <div class="kpi-grid">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Total Séances Planifiées</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $totalSeances ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-calendar-week"></i>
                    Toutes les séances
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Émargements Effectués</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $totalAttendances ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-clipboard-check"></i>
                    Tous les émargements
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Présents</div>
                <div class="kpi-value" style="color: #10b981; font-size: 2.5rem; font-weight: bold;">{{ $attendancesPresent ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-check-circle"></i>
                    Confirmés présents
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">En Retard</div>
                <div class="kpi-value" style="color: #f59e0b; font-size: 2.5rem; font-weight: bold;">{{ $attendancesLate ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-clock"></i>
                    Retards signalés
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Aujourd'hui</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $attendancesToday ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-calendar-day"></i>
                    Émargements du jour
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-filter"></i>
                    Filtres de recherche
                </div>
                <div class="main-card-subtitle">Affinez votre recherche d'émargements</div>
            </div>
            <div class="main-card-body">
                <form method="GET" action="{{ route('esbtp.teacher-attendance.report') }}" class="filter-form">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" name="date" value="{{ request('date') }}">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Enseignant</label>
                            <select class="form-select" name="teacher_id">
                                <option value="">Tous les enseignants</option>
                                @foreach($teachers ?? [] as $teacher)
                                    <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Matière</label>
                            <select class="form-select" name="matiere_id">
                                <option value="">Toutes les matières</option>
                                @foreach($matieres ?? [] as $matiere)
                                    <option value="{{ $matiere->id }}" {{ request('matiere_id') == $matiere->id ? 'selected' : '' }}>
                                        {{ $matiere->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Classe</label>
                            <select class="form-select" name="classe_id">
                                <option value="">Toutes les classes</option>
                                @foreach($classes ?? [] as $classe)
                                    <option value="{{ $classe->id }}" {{ request('classe_id') == $classe->id ? 'selected' : '' }}>
                                        {{ $classe->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Statut d'émargement</label>
                            <select class="form-select" name="status">
                                <option value="">Tous les statuts</option>
                                <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Présent</option>
                                <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>En retard</option>
                                <option value="not_signed" {{ request('status') == 'not_signed' ? 'selected' : '' }}>Non émargé</option>
                            </select>
                        </div>
                        
                        <div class="col-md-1">
                            <label class="form-label">Emplois</label>
                            <select class="form-select" name="emploi_status">
                                <option value="" {{ request('emploi_status') == '' ? 'selected' : '' }}>Tous</option>
                                <option value="active_only" {{ request('emploi_status') == 'active_only' ? 'selected' : '' }}>Actifs</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn-acasi primary">
                                    <i class="fas fa-search"></i>Filtrer
                                </button>
                                <a href="{{ route('esbtp.teacher-attendance.report') }}" class="btn-acasi secondary">
                                    <i class="fas fa-times"></i>Réinitialiser
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des cours planifiés -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-list"></i>
                    Liste des cours planifiés
                </div>
                <div class="main-card-subtitle">{{ $seances->total() ?? 0 }} cours trouvé(s) - Année {{ $anneeEnCours->name ?? 'Non définie' }}</div>
            </div>
            <div class="main-card-body">
                @if(isset($seances) && $seances->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Enseignant</th>
                                    <th>Matière</th>
                                    <th>Classe</th>
                                    <th>Séance</th>
                                    <th>Date/Heure</th>
                                    <th>Emploi du temps</th>
                                    <th>Statut d'émargement</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($seances as $seance)
                                    @php
                                        // Récupérer l'émargement pour cette séance (s'il existe)
                                        $attendance = $seance->teacherAttendances->first();
                                        $hasAttendance = $attendance !== null;
                                        $attendanceStatus = $hasAttendance ? $attendance->status : 'not_signed';
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-2">
                                                    <i class="fas fa-user-tie"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold">{{ $seance->teacher?->user?->name ?? 'N/A' }}</div>
                                                    <small class="text-muted">{{ $seance->teacher?->user?->email ?? '' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 25px; height: 25px; flex-shrink: 0;">
                                                    <i class="fas fa-book" style="font-size: 10px;"></i>
                                                </div>
                                                <span class="fw-semibold">{{ $seance->matiere?->name ?? 'N/A' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                {{ $seance->emploiTemps?->classe?->name ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <div><i class="fas fa-clock text-muted me-1"></i>
                                                    {{ $seance->heure_debut ? \Carbon\Carbon::parse($seance->heure_debut)->format('H:i') : 'N/A' }} - 
                                                    {{ $seance->heure_fin ? \Carbon\Carbon::parse($seance->heure_fin)->format('H:i') : 'N/A' }}
                                                </div>
                                                @if($seance->salle)
                                                    <div><i class="fas fa-door-open text-muted me-1"></i>{{ $seance->salle }}</div>
                                                @endif
                                                <div><i class="fas fa-calendar text-muted me-1"></i>
                                                    {{ $seance->getDateCompleteFormattee() }}
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($hasAttendance)
                                                <div class="small">
                                                    <div>{{ $attendance->validated_at?->format('d/m/Y') ?? $attendance->created_at?->format('d/m/Y') }}</div>
                                                    <div class="text-muted">{{ $attendance->validated_at?->format('H:i') ?? $attendance->created_at?->format('H:i') }}</div>
                                                </div>
                                            @else
                                                <div class="small text-muted">
                                                    <div>Pas d'émargement</div>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($seance->emploiTemps?->is_active)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Actif
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-pause me-1"></i>Inactif
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($attendanceStatus === 'present')
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Présent
                                                </span>
                                            @elseif($attendanceStatus === 'late')
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-clock me-1"></i>En retard
                                                </span>
                                            @elseif($attendanceStatus === 'not_signed')
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-times me-1"></i>Non émargé
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-question me-1"></i>{{ ucfirst($attendanceStatus) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                @if($hasAttendance)
                                                    <button type="button" class="btn btn-outline-primary btn-sm" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#detailModal{{ $seance->id }}"
                                                            title="Voir détails émargement">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                @endif
                                                <a href="{{ route('esbtp.seances-cours.show', $seance->id) }}" 
                                                   class="btn btn-outline-info btn-sm" 
                                                   title="Voir la séance">
                                                    <i class="fas fa-calendar-day"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted small">
                            Affichage de {{ $seances->firstItem() ?? 0 }} à {{ $seances->lastItem() ?? 0 }} 
                            sur {{ $seances->total() ?? 0 }} résultats
                        </div>
                        {{ $seances->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-week fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucune séance de cours trouvée</h5>
                        <p class="text-muted">Modifiez vos critères de recherche ou vérifiez les filtres appliqués.<br>
                        <small class="text-info">Note: Seuls les cours sont affichés (pas les devoirs, récréations, etc.)</small></p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modals pour les détails -->
@if(isset($seances))
@foreach($seances as $seance)
    @php
        $attendance = $seance->teacherAttendances->first();
    @endphp
    @if($attendance)
    <div class="modal fade" id="detailModal{{ $seance->id }}" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détails de l'émargement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong>Enseignant:</strong> {{ $seance->teacher?->user?->name ?? 'N/A' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Matière:</strong> {{ $seance->matiere?->name ?? 'N/A' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Classe:</strong> {{ $seance->emploiTemps?->classe?->name ?? 'N/A' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Séance:</strong> 
                            {{ $seance->getDateCompleteFormattee() }} {{ $seance->heure_debut ? \Carbon\Carbon::parse($seance->heure_debut)->format('H:i') : '' }}-{{ $seance->heure_fin ? \Carbon\Carbon::parse($seance->heure_fin)->format('H:i') : '' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Date d'émargement:</strong> {{ $attendance->validated_at?->format('d/m/Y H:i') ?? ($attendance->created_at?->format('d/m/Y H:i') ?? 'N/A') }}
                        </div>
                        <div class="col-md-6">
                            <strong>Statut:</strong>
                            @if($attendance->status === 'present')
                                <span class="badge bg-success">Présent</span>
                            @elseif($attendance->status === 'late')
                                <span class="badge bg-warning">En retard</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($attendance->status) }}</span>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <strong>IP:</strong> {{ $attendance->ip_address ?? 'N/A' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Appareil:</strong> {{ $attendance->device_info ?? 'N/A' }}
                        </div>
                        @if($seance->salle)
                        <div class="col-md-6">
                            <strong>Salle:</strong> {{ $seance->salle }}
                        </div>
                        @endif
                        @if($attendance->latitude && $attendance->longitude)
                        <div class="col-md-12">
                            <strong>Localisation:</strong> {{ $attendance->latitude }}, {{ $attendance->longitude }}
                        </div>
                        @endif
                        @if($attendance->notes)
                        <div class="col-md-12">
                            <strong>Notes:</strong>
                            <div class="mt-2 p-2 bg-light rounded">{{ $attendance->notes }}</div>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
    @endif
@endforeach
@endif

<!-- Modal de génération de code -->
<div class="modal fade" id="generateCodeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('esbtp.admin.attendance.generate-code') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Générer un nouveau code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="expires_at" class="form-label">Date d'expiration</label>
                        <input type="datetime-local" class="form-control" id="expires_at" name="expires_at" 
                               value="{{ now()->addDay()->format('Y-m-d\TH:i') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description (optionnel)</label>
                        <textarea class="form-control" id="description" name="description" rows="3" 
                                  placeholder="Ex: Code pour le cours de mathématiques..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Générer le code</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form when filters change (optional)
    const filterSelects = document.querySelectorAll('.filter-form select, .filter-form input[type="date"]');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            // Uncomment to enable auto-submit
            // this.form.submit();
        });
    });
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection