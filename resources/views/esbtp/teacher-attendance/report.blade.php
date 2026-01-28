@extends('layouts.app')

@section('title', 'Rapport d\'émargement des enseignants - KLASSCI')

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
                        <span>
                            Du {{ $anneeEnCours?->start_date ? $anneeEnCours->start_date->format('d/m/Y') : 'N/A' }}
                        </span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-calendar-times text-primary me-2"></i>
                        <span>
                            Au {{ $anneeEnCours?->end_date ? $anneeEnCours->end_date->format('d/m/Y') : 'N/A' }}
                        </span>
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
                <div class="kpi-title" style="color: #000; font-weight: 600;">Émargements validés</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $totalAttendances ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-clipboard-check"></i>
                    Présences confirmées (hors absents)
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Présents (retards inclus)</div>
                <div class="kpi-value" style="color: #10b981; font-size: 2.5rem; font-weight: bold;">{{ $attendancesPresent ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-check-circle"></i>
                    Dont {{ $attendancesLate ?? 0 }} retard(s)
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">En Retard</div>
                <div class="kpi-value" style="color: #f59e0b; font-size: 2.5rem; font-weight: bold;">{{ $attendancesLate ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-clock"></i>
                    Retards signalés (inclus dans les présents)
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Absents</div>
                <div class="kpi-value" style="color: #ef4444; font-size: 2.5rem; font-weight: bold;">{{ $attendancesAbsent ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-user-times"></i>
                    Séances clôturées sans émargement
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

        @if(isset($teacherStats) && $teacherStats->count() > 0)
            <div class="main-card mb-4">
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-user-check"></i>
                        Statistiques par enseignant
                    </div>
                    <div class="main-card-subtitle">Résumé individuel des émargements</div>
                </div>
                <div class="main-card-body">
                    <div class="row g-3">
                        @foreach($teacherStats as $stat)
                            @php
                                $parts = preg_split('/\s+/', trim($stat['name']));
                                $initials = strtoupper(collect($parts)->filter()->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode(''));
                            @endphp
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="card p-3 border-0 shadow-sm h-100" style="background: #ffffff;">
                                    <div class="d-flex gap-3 align-items-center">
                                        <div style="width:52px; height:52px; border-radius:50%; background: rgba(4,83,203,0.1); color:#0453cb; display:flex; align-items:center; justify-content:center; font-weight:700;">
                                            {{ $initials }}
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">{{ $stat['name'] }}</div>
                                            <div class="text-muted small">{{ $stat['total'] }} séance(s)</div>
                                        </div>
                                        <span class="badge bg-primary">{{ $stat['taux'] }}%</span>
                                    </div>
                                    <div class="mt-3 d-flex flex-wrap gap-2">
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>{{ $stat['present'] }}</span>
                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>{{ $stat['late'] }}</span>
                                        <span class="badge bg-danger"><i class="fas fa-user-times me-1"></i>{{ $stat['absent'] }}</span>
                                        <span class="badge bg-secondary"><i class="fas fa-times me-1"></i>{{ $stat['not_signed'] }}</span>
                                    </div>
                                    <div class="mt-3">
                                        <a href="{{ route('esbtp.teacher-attendance.teacher-report', ['teacher' => $stat['teacher_id']]) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-chart-line me-1"></i>Voir détail
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

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
                                <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent</option>
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
                                    @include('esbtp.teacher-attendance.partials.seance-row', ['seance' => $seance])
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

@push('scripts')
<style>
/* Animation travelling light pour les lignes de séance */
tr[data-seance-id] {
    position: relative;
    overflow: hidden;
}

tr[data-seance-id].is-loading {
    opacity: 0.85;
}

.seance-actions-wrapper {
    position: relative;
}

.seance-actions-wrapper.is-loading .seance-status-badges,
.seance-actions-wrapper.is-loading .seance-quick-actions {
    display: none !important;
}

.seance-actions-wrapper.is-loading .seance-actions-spinner {
    display: flex !important;
    align-items: center;
    justify-content: center;
}

/* Travelling light effect */
.seance-row-highlight {
    position: absolute;
    top: 0;
    left: -80%;
    width: 160%;
    height: 100%;
    opacity: 0;
    pointer-events: none;
    transform: translateX(-65%) skewX(-12deg);
    background: linear-gradient(90deg, rgba(40, 167, 69, 0) 0%, rgba(40, 167, 69, 0.75) 50%, rgba(40, 167, 69, 0) 100%);
    transition: opacity 0.2s ease;
    z-index: 5;
}

.seance-row-highlight.absent {
    background: linear-gradient(90deg, rgba(220, 53, 69, 0) 0%, rgba(220, 53, 69, 0.75) 50%, rgba(220, 53, 69, 0) 100%);
}

.seance-row-highlight.animate {
    animation: seance-row-highlight-move 3.2s ease-out forwards;
}

.seance-row-flash {
    animation: seance-row-flash 0.8s ease-in-out;
}

@keyframes seance-row-highlight-move {
    0% {
        opacity: 0;
        transform: translateX(-65%) skewX(-12deg);
    }
    18% {
        opacity: 0.92;
    }
    55% {
        opacity: 0.72;
    }
    100% {
        opacity: 0;
        transform: translateX(115%) skewX(-12deg);
    }
}

@keyframes seance-row-flash {
    0% {
        background-color: transparent;
    }
    25% {
        background-color: rgba(40, 167, 69, 0.12);
    }
    100% {
        background-color: transparent;
    }
}

.seance-row-flash.absent {
    animation-name: seance-row-flash-absent;
}

@keyframes seance-row-flash-absent {
    0% {
        background-color: transparent;
    }
    25% {
        background-color: rgba(220, 53, 69, 0.12);
    }
    100% {
        background-color: transparent;
    }
}
</style>

<script>
(function() {
    const SEANCE_HIGHLIGHT_DURATION = 3200;
    const SEANCE_STATUS_PASS_RATIO = 0.8;

    /**
     * Met à jour l'état de chargement d'une ligne de séance
     */
    function setSeanceRowLoadingState(seanceId, isLoading) {
        const row = document.querySelector(`tr[data-seance-id="${seanceId}"]`);
        if (!row) return;

        const actionsWrapper = row.querySelector('.seance-actions-wrapper');
        if (actionsWrapper) {
            actionsWrapper.classList.toggle('is-loading', Boolean(isLoading));
        }
        row.classList.toggle('is-loading', Boolean(isLoading));
    }

    /**
     * Déclenche l'animation travelling light sur une ligne de séance
     */
    function triggerSeanceRowHighlight(row, actionType, options = {}) {
        if (!row) return;

        const { onStatusPassed } = options;

        row.classList.remove('seance-row-flash', 'absent');
        void row.offsetWidth; // Force reflow

        const highlight = document.createElement('div');
        highlight.className = 'seance-row-highlight';
        if (actionType === 'absent') {
            highlight.classList.add('absent');
        }

        row.appendChild(highlight);

        requestAnimationFrame(() => {
            highlight.classList.add('animate');
        });

        if (typeof onStatusPassed === 'function') {
            setTimeout(() => {
                onStatusPassed(highlight);
            }, SEANCE_HIGHLIGHT_DURATION * SEANCE_STATUS_PASS_RATIO);
        }

        const cleanup = () => {
            highlight.removeEventListener('animationend', cleanup);
            highlight.remove();
        };

        highlight.addEventListener('animationend', cleanup);

        row.classList.add('seance-row-flash');
        if (actionType === 'absent') {
            row.classList.add('absent');
        }

        setTimeout(() => {
            row.classList.remove('seance-row-flash', 'absent');
        }, 1200);
    }

    /**
     * Rafraîchit une ligne de séance après update statut
     */
    window.refreshSeanceLigne = function(seanceId, actionType = 'present') {
        debugLog('🔄 Refresh ligne séance:', seanceId, 'action:', actionType);

        const refreshUrl = `{{ url('/esbtp/teacher-attendance/seance') }}/${seanceId}/refresh-ligne`;
        const existingRow = document.querySelector(`tr[data-seance-id="${seanceId}"]`);

        setSeanceRowLoadingState(seanceId, true);

        fetch(refreshUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success || !data.html) {
                throw new Error(data.message || 'Réponse serveur invalide');
            }

            const template = document.createElement('template');
            template.innerHTML = data.html.trim();

            let rowFragment = template.content.querySelector(`tr[data-seance-id="${seanceId}"]`);
            if (!rowFragment) {
                rowFragment = template.content.querySelector('tr[data-seance-id]');
            }

            if (!rowFragment) {
                throw new Error('HTML retourné sans ligne de séance valide');
            }

            const newRow = rowFragment.cloneNode(true);
            const clonedCells = Array.from(newRow.children).map(cell => cell.cloneNode(true));

            if (!existingRow || !existingRow.parentNode) {
                const tbody = document.querySelector('tbody');
                if (tbody) {
                    tbody.appendChild(newRow);
                }
                setSeanceRowLoadingState(seanceId, false);
                triggerSeanceRowHighlight(newRow, actionType);
                debugLog('🎉 Ligne rafraîchie (nouvelle ligne ajoutée):', seanceId);
                return;
            }

            let contentUpdated = false;

            const applyUpdatedContent = (highlightEl = null) => {
                if (contentUpdated) return;
                contentUpdated = true;

                const highlightNode = highlightEl && highlightEl instanceof Node ? highlightEl : existingRow.querySelector('.seance-row-highlight');
                const existingCells = Array.from(existingRow.children).filter(child => child !== highlightNode);

                existingCells.forEach((cell, index) => {
                    const replacement = clonedCells[index];
                    if (replacement) {
                        cell.replaceWith(replacement);
                    } else {
                        cell.remove();
                    }
                });

                const extraCells = clonedCells.slice(existingCells.length);
                if (extraCells.length > 0) {
                    const fragment = document.createDocumentFragment();
                    extraCells.forEach(node => fragment.appendChild(node));

                    if (highlightNode && highlightNode.parentNode) {
                        highlightNode.parentNode.insertBefore(fragment, highlightNode);
                    } else {
                        existingRow.appendChild(fragment);
                    }
                }

                if (highlightNode && highlightNode.parentNode !== existingRow) {
                    existingRow.appendChild(highlightNode);
                }

                setSeanceRowLoadingState(seanceId, false);

                existingRow.classList.add('seance-row-flash');
                if (actionType === 'absent') {
                    existingRow.classList.add('absent');
                }
                setTimeout(() => {
                    existingRow.classList.remove('seance-row-flash', 'absent');
                }, 1200);
            };

            triggerSeanceRowHighlight(existingRow, actionType, {
                onStatusPassed: (highlightEl) => {
                    applyUpdatedContent(highlightEl);
                }
            });

            // Fallback
            setTimeout(() => {
                if (!contentUpdated) {
                    applyUpdatedContent();
                }
            }, SEANCE_HIGHLIGHT_DURATION + 100);

            debugLog('🎉 Ligne rafraîchie avec succès:', seanceId);
        })
        .catch(error => {
            debugError('❌ Erreur refresh ligne:', error);
            setSeanceRowLoadingState(seanceId, false);
            alert('Erreur lors de la mise à jour: ' + error.message);
        });
    };

    /**
     * Initialisation au chargement
     */
    document.addEventListener('DOMContentLoaded', function() {
        debugLog('✅ Scripts séances initialisés');

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

        // Event delegation pour les boutons mark-status
        document.addEventListener('click', function(e) {
            debugLog('🖱️ Click détecté sur:', e.target);
            const btn = e.target.closest('.mark-status-btn');
            debugLog('🔍 Bouton trouvé:', btn);
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

            debugLog('🔄 Marquage statut:', { seanceId, status, type });

            setSeanceRowLoadingState(seanceId, true);

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
                    debugLog('✅ Statut mis à jour, refresh ligne');
                    // Rafraîchir la ligne avec animation
                    window.refreshSeanceLigne(seanceId, status === 'absent' ? 'absent' : 'present');
                } else {
                    setSeanceRowLoadingState(seanceId, false);
                    alert('Erreur: ' + (data.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                debugError('❌ Erreur update statut:', error);
                setSeanceRowLoadingState(seanceId, false);
                alert('Erreur lors de la mise à jour: ' + error.message);
            });
        }, true); // Capture phase

        debugLog('✅ Event listeners installés');
    });
})();
</script>
@endpush
