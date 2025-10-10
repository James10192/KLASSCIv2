@extends('layouts.app')

@section('title', 'Mes Absences')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* Styles spécifiques pour la page absences */
    .absences-container {
        --absences-primary: var(--primary);
        --absences-secondary: var(--secondary);
        --absences-surface: var(--surface);
        --absences-border: rgba(0, 0, 0, 0.08);
    }

    .filter-section {
        background: white;
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        margin-bottom: var(--space-xl);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--absences-border);
    }

    .filter-section h5 {
        font-weight: 700;
        font-size: var(--text-lg);
        color: var(--text-primary);
        margin-bottom: var(--space-md);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-xl);
    }

    .stat-card {
        background: white;
        border-radius: var(--radius-large);
        padding: 1.25rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--absences-border);
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
    }

    .stat-card.danger::before {
        background: linear-gradient(135deg, var(--danger), #dc3545);
    }

    .stat-card.success::before {
        background: linear-gradient(135deg, var(--success), #28a745);
    }

    .stat-card.warning::before {
        background: linear-gradient(135deg, var(--warning), #ffc107);
    }

    .stat-card.info::before {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(var(--primary-rgb), 0.15);
    }

    .stat-card .d-flex {
        margin-bottom: 0 !important;
    }

    .stat-card h6 {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-secondary);
        margin-bottom: 0;
        line-height: 1.2;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1;
        margin: 0.5rem 0;
    }

    .stat-card .mt-2 {
        margin-top: 0 !important;
    }

    .stat-card small {
        font-size: 0.75rem;
        line-height: 1.3;
    }

    .stat-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-circle);
    }

    .bg-danger-light {
        background-color: rgba(220, 53, 69, 0.1);
    }

    .bg-success-light {
        background-color: rgba(40, 167, 69, 0.1);
    }

    .bg-warning-light {
        background-color: rgba(255, 193, 7, 0.1);
    }

    .bg-info-light {
        background-color: rgba(23, 162, 184, 0.1);
    }

    .card-moderne {
        background: white;
        border-radius: var(--radius-large);
        padding: var(--space-lg);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--absences-border);
        margin-bottom: var(--space-xl);
    }

    .card-moderne h5 {
        font-weight: 700;
        font-size: var(--text-lg);
        color: var(--text-primary);
        margin-bottom: var(--space-md);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .subject-icon {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-circle);
    }

    .highlighted-row {
        background-color: rgba(242, 148, 0, 0.2) !important;
        animation: highlight-fade 2s ease-in-out;
    }

    @keyframes highlight-fade {
        0%, 100% { background-color: rgba(242, 148, 0, 0.2); }
        50% { background-color: rgba(242, 148, 0, 0.4); }
    }

    .chart-container {
        position: relative;
        width: 100%;
    }

    .table-hover tbody tr:hover {
        background: rgba(var(--primary-rgb), 0.02);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .dashboard-acasi {
            padding: 0 !important;
        }

        .main-content {
            padding: 1rem !important;
            max-width: 100vw;
            overflow-x: hidden;
        }

        .stats-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .stat-card {
            padding: 1rem;
        }

        .stat-card h6 {
            font-size: 0.7rem;
        }

        .stat-value {
            font-size: 1.75rem;
        }

        .stat-icon {
            width: 36px;
            height: 36px;
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

        .filter-section {
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .filter-section h5 {
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }

        .filter-section .row {
            margin: 0 -0.5rem;
        }

        .filter-section .row > div {
            padding: 0 0.5rem;
            margin-bottom: 0.75rem;
        }

        .filter-section label {
            font-size: 0.85rem;
            margin-bottom: 0.35rem;
        }

        .filter-section .form-control,
        .filter-section .form-select,
        .filter-section .btn {
            font-size: 0.85rem;
        }

        .card-moderne {
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .card-moderne h5 {
            font-size: 0.95rem;
            margin-bottom: 0.75rem;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            font-size: 0.8rem;
            min-width: 600px;
        }

        .table th,
        .table td {
            padding: 0.5rem 0.35rem;
            white-space: nowrap;
        }

        .chart-container {
            height: 250px !important;
        }

        .row.mb-4 {
            margin-left: -0.5rem;
            margin-right: -0.5rem;
        }

        .row.mb-4 > [class*='col-'] {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        .alert {
            font-size: 0.85rem;
            padding: 0.75rem;
        }

        .badge {
            font-size: 0.7rem;
            padding: 0.35em 0.6em;
        }

        .btn-sm {
            font-size: 0.75rem;
            padding: 0.35rem 0.6rem;
        }

        /* Hide tables on mobile, show grid cards instead */
        .table-responsive .table {
            display: none;
        }

        .mobile-grid {
            display: grid !important;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .mobile-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-left: 4px solid var(--primary);
        }

        .mobile-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }

        .mobile-card-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--text-primary);
        }

        .mobile-card-body {
            display: grid;
            gap: 0.5rem;
        }

        .mobile-card-row {
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 0.5rem;
            font-size: 0.85rem;
        }

        .mobile-card-label {
            font-weight: 600;
            color: var(--text-secondary);
        }

        .mobile-card-value {
            color: var(--text-primary);
        }
    }

    @media (min-width: 769px) {
        .mobile-grid {
            display: none !important;
        }
    }

    @media (max-width: 400px) {
        .main-content {
            padding: 0.75rem !important;
        }

        .stat-card {
            padding: 0.85rem;
        }

        .stat-value {
            font-size: 1.5rem;
        }

        .student-header h1 {
            font-size: 1.2rem !important;
        }

        .table {
            font-size: 0.75rem;
        }
    }
</style>
@endpush

@section('content')
<div class="dashboard-acasi absences-container">
    <div class="main-content">
        <!-- Header Étudiant Moderne -->
        <div class="student-header">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h1>
                        <i class="fas fa-user-clock me-3"></i>
                        Mes Absences
                    </h1>
                    <p class="header-subtitle">
                        Consultez vos absences, retards et justifications
                    </p>
                </div>
                <div class="text-end">
                    <div class="badge" style="background: rgba(255, 255, 255, 0.2); color: white; padding: var(--space-sm) var(--space-md); border-radius: var(--radius-medium); font-size: var(--text-sm);">
                        <i class="fas fa-calendar me-2"></i>
                        Année {{ date('Y') }}-{{ date('Y')+1 }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification si l'utilisateur vient d'une notification -->
        @if(request()->has('highlight'))
        <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Notification d'absence :</strong> Veuillez justifier votre absence ci-dessous pour éviter les pénalités sur votre note d'assiduité.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- Filtres -->
        <div class="filter-section">
            <h5>
                <i class="fas fa-filter"></i>
                Filtres de recherche
            </h5>
            <form action="{{ route('esbtp.mes-absences.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Date de début</label>
                    <input type="date" name="date_debut" class="form-control" value="{{ $dateDebut ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date de fin</label>
                    <input type="date" name="date_fin" class="form-control" value="{{ $dateFin ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Matière</label>
                    <select name="matiere_id" class="form-select">
                        <option value="">Toutes les matières</option>
                        @foreach($matieres as $id => $nom)
                            <option value="{{ $id }}" {{ request('matiere_id') == $id ? 'selected' : '' }}>
                                {{ $nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Appliquer les filtres
                    </button>
                </div>
            </form>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card danger">
                <div class="d-flex justify-content-between align-items-center">
                    <h6>Total Absences</h6>
                    <div class="stat-icon bg-danger-light">
                        <i class="fas fa-times text-danger"></i>
                    </div>
                </div>
                <div class="stat-value">{{ $absences->count() }}</div>
                <div>
                    <small class="text-muted">Sur la période sélectionnée</small>
                </div>
            </div>

            <div class="stat-card success">
                <div class="d-flex justify-content-between align-items-center">
                    <h6>Absences Justifiées</h6>
                    <div class="stat-icon bg-success-light">
                        <i class="fas fa-check text-success"></i>
                    </div>
                </div>
                <div class="stat-value">{{ $excuses->count() }}</div>
                <div>
                    <small class="text-muted">{{ $absences->count() > 0 ? round(($excuses->count() / max($absences->count(), 1)) * 100) : 0 }}% des absences</small>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="d-flex justify-content-between align-items-center">
                    <h6>Retards</h6>
                    <div class="stat-icon bg-warning-light">
                        <i class="fas fa-clock text-warning"></i>
                    </div>
                </div>
                <div class="stat-value">{{ $retards->count() }}</div>
                <div>
                    <small class="text-muted">Sur {{ $presences->count() + $absences->count() + $retards->count() + $excuses->count() }} séances</small>
                </div>
            </div>

            <div class="stat-card info">
                <div class="d-flex justify-content-between align-items-center">
                    <h6>Taux de Présence</h6>
                    <div class="stat-icon bg-info-light">
                        <i class="fas fa-chart-line text-info"></i>
                    </div>
                </div>
                @php
                    $totalDays = $presences->count() + $absences->count();
                    $presenceRate = $totalDays > 0 ? round(($presences->count() / $totalDays) * 100) : 100;
                @endphp
                <div class="d-flex align-items-center">
                    <div class="progress flex-grow-1 me-2" style="height: 8px; background-color: #f8f9fa;">
                        <div class="progress-bar {{ $presenceRate >= 75 ? 'bg-success' : ($presenceRate >= 50 ? 'bg-warning' : 'bg-danger') }}"
                             role="progressbar"
                             style="width: {{ $presenceRate }}%"
                             aria-valuenow="{{ $presenceRate }}"
                             aria-valuemin="0"
                             aria-valuemax="100">
                        </div>
                    </div>
                    <span class="fw-bold">{{ $presenceRate }}%</span>
                </div>
                <div>
                    <small class="text-muted">Objectif: minimum 75%</small>
                </div>
            </div>
        </div>

        <!-- Graphiques -->
        <div class="row mb-4">
            <div class="col-xl-6 mb-4">
                <div class="card-moderne">
                    <h5>
                        <i class="fas fa-chart-bar"></i>
                        Absences par matière
                    </h5>
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="absencesParMatiereChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 mb-4">
                <div class="card-moderne">
                    <h5>
                        <i class="fas fa-chart-line"></i>
                        Évolution des absences
                    </h5>
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="evolutionAbsencesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques par matière -->
        <div class="card-moderne">
            <h5>
                <i class="fas fa-table"></i>
                Statistiques par matière
            </h5>

            <!-- Table for desktop -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Matière</th>
                            <th>Total séances</th>
                            <th>Présences</th>
                            <th>Absences</th>
                            <th>Retards</th>
                            <th>Excusés</th>
                            <th>Taux présence</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($absencesParMatiere as $matiereId => $statistiques)
                            <tr>
                                <td>{{ $statistiques['nom'] }}</td>
                                <td>{{ $statistiques['total'] }}</td>
                                <td>{{ $statistiques['present'] }}</td>
                                <td>{{ $statistiques['absent'] }}</td>
                                <td>{{ $statistiques['retard'] }}</td>
                                <td>{{ $statistiques['excuse'] }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 5px; width: 100px;">
                                            <div class="progress-bar {{ $statistiques['taux_presence'] >= 75 ? 'bg-success' : ($statistiques['taux_presence'] >= 50 ? 'bg-warning' : 'bg-danger') }}"
                                                 role="progressbar"
                                                 style="width: {{ $statistiques['taux_presence'] }}%"
                                                 aria-valuenow="{{ $statistiques['taux_presence'] }}"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                        <span class="ms-2">{{ $statistiques['taux_presence'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Aucune donnée disponible</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Grid for mobile -->
            <div class="mobile-grid" style="display: none;">
                @forelse($absencesParMatiere as $matiereId => $statistiques)
                    <div class="mobile-card">
                        <div class="mobile-card-header">
                            <div class="mobile-card-title">
                                <i class="fas fa-book text-primary me-2"></i>
                                {{ $statistiques['nom'] }}
                            </div>
                            <span class="badge {{ $statistiques['taux_presence'] >= 75 ? 'bg-success' : ($statistiques['taux_presence'] >= 50 ? 'bg-warning' : 'bg-danger') }}">
                                {{ $statistiques['taux_presence'] }}%
                            </span>
                        </div>
                        <div class="mobile-card-body">
                            <div class="mobile-card-row">
                                <span class="mobile-card-label">Séances</span>
                                <span class="mobile-card-value">{{ $statistiques['total'] }}</span>
                            </div>
                            <div class="mobile-card-row">
                                <span class="mobile-card-label">Présences</span>
                                <span class="mobile-card-value text-success">{{ $statistiques['present'] }}</span>
                            </div>
                            <div class="mobile-card-row">
                                <span class="mobile-card-label">Absences</span>
                                <span class="mobile-card-value text-danger">{{ $statistiques['absent'] }}</span>
                            </div>
                            <div class="mobile-card-row">
                                <span class="mobile-card-label">Retards</span>
                                <span class="mobile-card-value text-warning">{{ $statistiques['retard'] }}</span>
                            </div>
                            <div class="mobile-card-row">
                                <span class="mobile-card-label">Excusés</span>
                                <span class="mobile-card-value text-info">{{ $statistiques['excuse'] }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Aucune donnée disponible
                    </div>
                @endforelse
            </div>
        </div>
    </div>

        <!-- Liste des absences -->
        <div class="card-moderne">
            <h5>
                <i class="fas fa-history"></i>
                Historique des absences
            </h5>
            @if($absences->isEmpty())
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Aucune absence enregistrée sur la période sélectionnée.
                </div>
            @else
                <!-- Table for desktop -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Matière</th>
                                <th>Type de Séance</th>
                                <th>Statut</th>
                                <th>Justification</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($absences as $absence)
                                <tr id="absence_{{ $absence->seanceCours->id ?? 'unknown' }}_{{ $absence->date ? $absence->date->format('Y-m-d') : 'unknown' }}"
                                    class="{{ request('highlight') == 'absence_' . ($absence->seanceCours->id ?? 'unknown') . '_' . ($absence->date ? $absence->date->format('Y-m-d') : 'unknown') ? 'highlighted-row' : '' }}">
                                    <td>{{ $absence->date ? $absence->date->format('d/m/Y') : 'N/A' }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="subject-icon rounded-circle p-2 me-2 bg-light">
                                                <i class="fas fa-book text-primary"></i>
                                            </div>
                                            <span>{{ $absence->seanceCours->matiere->name ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $absence->seanceCours->type_cours ?? 'N/A' }}</td>
                                    <td>
                                        @if($absence->statut == 'excuse')
                                            <span class="badge bg-success-light text-success">Justifiée</span>
                                        @elseif($absence->justified_at && $absence->statut == 'absent')
                                            <span class="badge bg-warning-light text-warning">En attente de validation</span>
                                        @else
                                            <span class="badge bg-danger-light text-danger">Non justifiée</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $hasAdminComment = false;
                                            $adminComment = '';
                                            $studentComment = $absence->commentaire ?? '';

                                            // Check if commentaire contains admin comment
                                            if (strpos($studentComment, "Commentaire de l'administration:") !== false) {
                                                $parts = explode("Commentaire de l'administration:", $studentComment);
                                                $studentComment = trim($parts[0]);
                                                $adminComment = trim($parts[1] ?? '');
                                                $hasAdminComment = true;
                                            }
                                        @endphp

                                        <div class="mb-1">
                                            <strong>Justification :</strong>
                                            <span class="text-muted">{{ Str::limit($studentComment, 100) }}</span>
                                            @if(strlen($studentComment) > 100)
                                                <a href="#" class="small text-primary" data-bs-toggle="modal" data-bs-target="#justificationModal{{ $absence->id }}">
                                                    Voir plus
                                                </a>
                                            @endif
                                        </div>

                                        @if($hasAdminComment)
                                            <div class="mt-2 p-2 border-start border-danger border-3 bg-light">
                                                <strong class="text-danger">Commentaire de l'administration :</strong>
                                                <p class="mb-0">{{ $adminComment }}</p>
                                            </div>
                                        @endif

                                        @if($absence->document_path)
                                            <div class="mt-2">
                                                <a href="{{ asset('storage/' . $absence->document_path) }}" target="_blank" class="text-primary">
                                                    <i class="fas fa-paperclip"></i> Document justificatif
                                                </a>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($absence->statut != 'excuse' && !$absence->justified_at)
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#justifierModal{{ $absence->id }}">
                                                <i class="fas fa-file-alt me-1"></i> Justifier
                                            </button>
                                        @elseif($absence->justified_at && $absence->statut == 'absent' && $hasAdminComment)
                                            <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#resoumettreModal{{ $absence->id }}">
                                                <i class="fas fa-redo me-1"></i> Re-soumettre
                                            </button>
                                        @elseif($absence->justified_at && $absence->statut == 'absent')
                                            <span class="badge bg-secondary">En attente d'examen</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Grid for mobile -->
                <div class="mobile-grid" style="display: none;">
                    @foreach($absences as $absence)
                        @php
                            $hasAdminComment = false;
                            $adminComment = '';
                            $studentComment = $absence->commentaire ?? '';
                            if (strpos($studentComment, "Commentaire de l'administration:") !== false) {
                                $parts = explode("Commentaire de l'administration:", $studentComment);
                                $studentComment = trim($parts[0]);
                                $adminComment = trim($parts[1] ?? '');
                                $hasAdminComment = true;
                            }
                        @endphp
                        <div class="mobile-card" id="mobile_absence_{{ $absence->seanceCours->id ?? 'unknown' }}_{{ $absence->date ? $absence->date->format('Y-m-d') : 'unknown' }}">
                            <div class="mobile-card-header">
                                <div>
                                    <div class="mobile-card-title">{{ $absence->seanceCours->matiere->name ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $absence->date ? $absence->date->format('d/m/Y') : 'N/A' }} • {{ $absence->seanceCours->type_cours ?? 'N/A' }}</small>
                                </div>
                                @if($absence->statut == 'excuse')
                                    <span class="badge bg-success">Justifiée</span>
                                @elseif($absence->justified_at && $absence->statut == 'absent')
                                    <span class="badge bg-warning">En attente</span>
                                @else
                                    <span class="badge bg-danger">Non justifiée</span>
                                @endif
                            </div>
                            <div class="mobile-card-body">
                                @if($studentComment)
                                    <div class="mb-2">
                                        <strong style="font-size: 0.8rem; color: var(--text-secondary);">Justification:</strong>
                                        <p class="mb-0" style="font-size: 0.85rem;">{{ Str::limit($studentComment, 80) }}</p>
                                    </div>
                                @endif

                                @if($hasAdminComment)
                                    <div class="alert alert-danger py-2 px-2 mb-2" style="font-size: 0.8rem;">
                                        <strong>Admin:</strong> {{ Str::limit($adminComment, 60) }}
                                    </div>
                                @endif

                                @if($absence->document_path)
                                    <div class="mb-2">
                                        <a href="{{ asset('storage/' . $absence->document_path) }}" target="_blank" class="text-primary" style="font-size: 0.85rem;">
                                            <i class="fas fa-paperclip"></i> Document
                                        </a>
                                    </div>
                                @endif

                                <div class="mt-2 d-flex gap-2">
                                    @if($absence->statut != 'excuse' && !$absence->justified_at)
                                        <button type="button" class="btn btn-sm btn-primary flex-grow-1" data-bs-toggle="modal" data-bs-target="#justifierModal{{ $absence->id }}">
                                            <i class="fas fa-file-alt"></i> Justifier
                                        </button>
                                    @elseif($absence->justified_at && $absence->statut == 'absent' && $hasAdminComment)
                                        <button type="button" class="btn btn-sm btn-warning flex-grow-1" data-bs-toggle="modal" data-bs-target="#resoumettreModal{{ $absence->id }}">
                                            <i class="fas fa-redo"></i> Re-soumettre
                                        </button>
                                    @elseif($absence->justified_at && $absence->statut == 'absent')
                                        <span class="badge bg-secondary">En attente d'examen</span>
                                    @endif

                                    @if(strlen($studentComment) > 80 || $hasAdminComment)
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#justificationModal{{ $absence->id }}">
                                            <i class="fas fa-eye"></i> Détails
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

<!-- Modals de justification -->
@foreach($absences as $absence)
    @php
        $hasAdminComment = false;
        $adminComment = '';
        $studentComment = $absence->commentaire ?? '';

        // Check if commentaire contains admin comment
        if (strpos($studentComment, "Commentaire de l'administration:") !== false) {
            $parts = explode("Commentaire de l'administration:", $studentComment);
            $studentComment = trim($parts[0]);
            $adminComment = trim($parts[1] ?? '');
            $hasAdminComment = true;
        }
    @endphp

    @if($absence->statut != 'excuse' && !$absence->justified_at)
        <div class="modal fade" id="justifierModal{{ $absence->id }}" tabindex="-1" aria-labelledby="justifierModalLabel{{ $absence->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="justifierModalLabel{{ $absence->id }}">
                            Justifier l'absence du {{ $absence->date ? $absence->date->format('d/m/Y') : 'N/A' }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('esbtp.mes-absences.justify', $absence->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="justification{{ $absence->id }}" class="form-label">Motif de l'absence</label>
                                <textarea class="form-control" id="justification{{ $absence->id }}" name="justification" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="document{{ $absence->id }}" class="form-label">Document justificatif</label>
                                <input type="file" class="form-control" id="document{{ $absence->id }}" name="document" accept=".pdf,.jpg,.jpeg,.png">
                                <small class="text-muted">Formats acceptés: PDF, JPG, PNG. Max: 2MB</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i> Envoyer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($absence->justified_at && $absence->statut == 'absent' && $hasAdminComment)
        <div class="modal fade" id="resoumettreModal{{ $absence->id }}" tabindex="-1" aria-labelledby="resoumettreModalLabel{{ $absence->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="resoumettreModalLabel{{ $absence->id }}">
                            Re-soumettre une justification pour l'absence du {{ $absence->date ? $absence->date->format('d/m/Y') : 'N/A' }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('esbtp.mes-absences.justify', $absence->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Justification rejetée</strong>
                                <p class="mb-0">Votre précédente justification a été rejetée par l'administration. Veuillez fournir des informations complémentaires.</p>
                            </div>

                            <div class="mb-3 p-3 bg-light rounded">
                                <h6 class="text-danger">Commentaire de l'administration :</h6>
                                <p class="mb-0">{{ $adminComment }}</p>
                            </div>

                            <div class="mb-3">
                                <label for="justification{{ $absence->id }}" class="form-label">Nouvelle justification</label>
                                <textarea class="form-control" id="justification{{ $absence->id }}" name="justification" rows="3" required>{{ $studentComment }}</textarea>
                                <small class="text-muted">Vous pouvez modifier votre justification précédente ou en fournir une nouvelle.</small>
                            </div>

                            <div class="mb-3">
                                <label for="document{{ $absence->id }}" class="form-label">Document justificatif</label>
                                <input type="file" class="form-control" id="document{{ $absence->id }}" name="document" accept=".pdf,.jpg,.jpeg,.png">
                                <small class="text-muted">Formats acceptés: PDF, JPG, PNG. Max: 2MB</small>

                                @if($absence->document_path)
                                <div class="mt-2">
                                    <span class="text-muted">Document actuel :</span>
                                    <a href="{{ asset('storage/' . $absence->document_path) }}" target="_blank" class="text-primary">
                                        <i class="fas fa-paperclip"></i> Voir le document
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-redo me-1"></i> Re-soumettre
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach

<!-- Add modal for showing the full justification -->
@foreach($absences as $absence)
    @php
        $hasAdminComment = false;
        $adminComment = '';
        $studentComment = $absence->commentaire ?? '';

        // Check if commentaire contains admin comment
        if (strpos($studentComment, "Commentaire de l'administration:") !== false) {
            $parts = explode("Commentaire de l'administration:", $studentComment);
            $studentComment = trim($parts[0]);
            $adminComment = trim($parts[1] ?? '');
            $hasAdminComment = true;
        }
    @endphp

    <div class="modal fade" id="justificationModal{{ $absence->id }}" tabindex="-1" aria-labelledby="justificationModalLabel{{ $absence->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="justificationModalLabel{{ $absence->id }}">Détails de la justification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h6>Votre justification :</h6>
                        <div class="p-3 bg-light rounded">
                            {{ $studentComment }}
                        </div>
                    </div>

                    @if($hasAdminComment)
                        <div class="mb-3">
                            <h6 class="text-danger">Commentaire de l'administration :</h6>
                            <div class="p-3 bg-light rounded border-start border-danger border-3">
                                {{ $adminComment }}
                            </div>
                        </div>
                    @endif

                    @if($absence->document_path)
                        <div class="mb-3">
                            <h6>Document justificatif :</h6>
                            <a href="{{ asset('storage/' . $absence->document_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-file"></i> Voir le document
                            </a>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configuration des couleurs
    const colors = {
        present: 'rgba(40, 167, 69, 0.7)',
        absent: 'rgba(220, 53, 69, 0.7)',
        retard: 'rgba(255, 193, 7, 0.7)',
        excuse: 'rgba(23, 162, 184, 0.7)',
        border: {
            present: 'rgb(40, 167, 69)',
            absent: 'rgb(220, 53, 69)',
            retard: 'rgb(255, 193, 7)',
            excuse: 'rgb(23, 162, 184)'
        }
    };

    // Graphique des absences par matière
    const absencesParMatiereCtx = document.getElementById('absencesParMatiereChart').getContext('2d');
    const absencesParMatiereData = {
        labels: {!! json_encode(collect($absencesParMatiere)->pluck('nom')->toArray()) !!},
        datasets: [
            {
                label: 'Présences',
                data: {!! json_encode(collect($absencesParMatiere)->pluck('present')->toArray()) !!},
                backgroundColor: colors.present,
                borderColor: colors.border.present,
                borderWidth: 1
            },
            {
                label: 'Absences',
                data: {!! json_encode(collect($absencesParMatiere)->pluck('absent')->toArray()) !!},
                backgroundColor: colors.absent,
                borderColor: colors.border.absent,
                borderWidth: 1
            },
            {
                label: 'Retards',
                data: {!! json_encode(collect($absencesParMatiere)->pluck('retard')->toArray()) !!},
                backgroundColor: colors.retard,
                borderColor: colors.border.retard,
                borderWidth: 1
            },
            {
                label: 'Excusés',
                data: {!! json_encode(collect($absencesParMatiere)->pluck('excuse')->toArray()) !!},
                backgroundColor: colors.excuse,
                borderColor: colors.border.excuse,
                borderWidth: 1
            }
        ]
    };

    new Chart(absencesParMatiereCtx, {
        type: 'bar',
        data: absencesParMatiereData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: true,
                },
                y: {
                    stacked: true,
                    beginAtZero: true
                }
            }
        }
    });

    // Graphique d'évolution des absences
    const evolutionAbsencesCtx = document.getElementById('evolutionAbsencesChart').getContext('2d');
    const absencesMensuelles = {!! json_encode($absencesMensuelles) !!};

    const labels = Object.keys(absencesMensuelles).map(month => {
        const [year, monthNum] = month.split('-');
        const date = new Date(year, monthNum - 1);
        return date.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
    });

    const data = Object.values(absencesMensuelles);

    new Chart(evolutionAbsencesCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Nombre d\'absences',
                data: data,
                fill: false,
                borderColor: colors.border.absent,
                backgroundColor: colors.absent,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Faire défiler automatiquement jusqu'à la ligne en surbrillance
    const highlightedRow = document.querySelector('.highlighted-row');
    if (highlightedRow) {
        setTimeout(() => {
            highlightedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Ouvrir automatiquement le modal de justification après un court délai
            setTimeout(() => {
                const absenceId = highlightedRow.id.split('_')[0]; // Récupérer l'ID de l'absence
                const justifierBtn = highlightedRow.querySelector('button[data-bs-toggle="modal"]');
                if (justifierBtn) {
                    justifierBtn.click();
                }
            }, 1000);
        }, 500);
    }
});
</script>
@endpush
