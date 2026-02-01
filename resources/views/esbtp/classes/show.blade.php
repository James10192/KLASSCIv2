@extends('layouts.app')

@section('title', 'Détails de la classe ' . $classe->name . ' - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .planning-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        border: 1px solid var(--border);
        padding: var(--space-lg);
        margin-bottom: var(--space-sm);
        position: relative;
        overflow: hidden;
        box-shadow: var(--shadow-card);
    }

    .planning-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: var(--primary);
    }

    .planning-title {
        font-weight: 600;
        color: var(--primary);
        margin-bottom: var(--space-xs);
    }

    .planning-stats {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: var(--space-lg);
        flex-wrap: wrap;
    }

    .planning-stat {
        text-align: center;
        min-width: 120px;
    }

    .planning-value {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--primary);
    }

    .planning-label {
        font-size: 0.85rem;
        color: var(--text-secondary);
    }

    .planning-progress {
        height: 6px;
        background: var(--border);
        border-radius: var(--radius-full);
        overflow: hidden;
        margin-top: var(--space-sm);
    }

    .planning-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary), var(--primary-light));
    }

    .planning-teachers {
        margin-top: var(--space-md);
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-xs);
    }

    .teacher-chip {
        display: flex;
        flex-direction: column;
        gap: 2px;
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        border: 1px solid var(--border);
        background: #f8f9fb;
        text-decoration: none;
        color: var(--text-primary);
        transition: all 0.2s ease;
    }

    .teacher-chip:hover {
        box-shadow: var(--shadow-hover);
        transform: translateY(-1px);
    }

    .teacher-name {
        font-weight: 600;
        font-size: 0.85rem;
    }

    .teacher-hours {
        font-size: 0.75rem;
        color: var(--text-secondary);
    }

    .planning-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
    }

    .planning-summary-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        border: 1px solid var(--border);
        text-align: center;
    }

    .planning-summary-card .value {
        font-weight: 700;
        font-size: 1.3rem;
        color: var(--primary);
    }

    .planning-summary-card .label {
        font-size: 0.85rem;
        color: var(--text-secondary);
    }

    @media (max-width: 768px) {
        .planning-stats {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-chalkboard-teacher me-2"></i>Détails de la classe</h1>
                <p class="header-subtitle">{{ $classe->name }}</p>
            </div>
            <div class="header-actions">
                @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire') || auth()->user()->hasRole('coordinateur'))
                <a href="{{ route('esbtp.classes.matieres', ['classe' => $classe->id]) }}" class="btn-acasi primary">
                    <i class="fas fa-book"></i>Gérer les matières
                </a>
                @endif

                @if(auth()->user()->hasRole('superAdmin'))
                {{-- Lien "Modifier" avec return_url vers show actuelle --}}
                <a href="{{ route('esbtp.classes.edit', array_merge(['classe' => $classe->id], ['return_url' => request()->fullUrl()])) }}" class="btn-acasi warning">
                    <i class="fas fa-edit"></i>Modifier
                </a>
                @endif

                {{-- Bouton "Retour à la liste" avec préservation des filtres si présents dans l'URL --}}
                @php
                    $queryParams = request()->query();
                    // Retirer le paramètre 'classe' si présent
                    unset($queryParams['classe']);
                    $returnToIndexUrl = route('esbtp.student.classes.index', $queryParams);
                @endphp
                <a href="{{ $returnToIndexUrl }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Filtre année académique -->
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-filter"></i>
                    Filtres d'affichage
                </div>
                <div class="main-card-subtitle">Année académique courante</div>
            </div>
            <div class="main-card-body">
                <div class="row align-items-end">
                    <div class="col-md-8">
                        <label for="annee_academique" class="form-label text-muted text-uppercase" style="font-size: 12px; font-weight: 600;">Année Académique Courante</label>
                        <select name="annee_academique" id="annee_academique" class="form-select" style="background-color: #f8f9fa; cursor: not-allowed;" disabled>
                            <option value="{{ $anneeAcademique }}" selected>
                                {{ $anneeAcademique }} (Année en cours)
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn-acasi info" onclick="showYearChangeInfo()" title="Comment changer d'année ?">
                            <i class="fas fa-info-circle"></i>Changer d'année
                        </button>
                    </div>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Les étudiants affichés dans cette classe correspondent à l'année académique courante.
                    </small>
                </div>
            </div>
        </div>

        <!-- Statistiques KPI -->
        <div class="kpi-grid mb-4">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Capacité Total</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $classe->places_totales }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-users"></i>
                    Places disponibles
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Étudiants Inscrits</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $classe->etudiants->count() }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-user-graduate"></i>
                    Année courante
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Taux d'Occupation</div>
                @php
                    $nombreEtudiants = $classe->etudiants->count();
                    $pourcentage = $classe->places_totales > 0 ? round(($nombreEtudiants / $classe->places_totales) * 100, 1) : 0;
                    $placesLibres = max(0, $classe->places_totales - $nombreEtudiants);
                @endphp
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $pourcentage }}%</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-chart-pie"></i>
                    {{ $placesLibres }} places libres
                </div>
            </div>
        </div>

        <!-- Informations de la classe -->
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-info-circle"></i>
                    Informations générales
                </div>
                <div class="main-card-subtitle">Détails et caractéristiques de la classe</div>
            </div>
            <div class="main-card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-hover align-middle mb-0">
                            <tr>
                                <th style="width: 30%">Code</th>
                                <td>{{ $classe->code }}</td>
                            </tr>
                            <tr>
                                <th>Nom</th>
                                <td>{{ $classe->name }}</td>
                            </tr>
                            <tr>
                                <th>Filière</th>
                                <td>
                                    @if ($classe->filiere)
                                        {{ $classe->filiere->name }}
                                        @if ($classe->filiere->parent)
                                            <br><small class="text-muted">Option de {{ $classe->filiere->parent->name }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">Non assignée</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Niveau d'études</th>
                                <td>
                                    @if ($classe->niveau)
                                        {{ $classe->niveau->name }} ({{ $classe->niveau->type }} - Année {{ $classe->niveau->year }})
                                    @else
                                        <span class="text-muted">Non assigné</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Statut</th>
                                <td>
                                    @if ($classe->is_active)
                                        <span class="badge bg-success px-3 py-2">Active</span>
                                    @else
                                        <span class="badge bg-danger px-3 py-2">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td>{{ $classe->description ?: 'Aucune description' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Informations Complémentaires</h6>
                            <ul class="mb-0">
                                <li><strong>Code classe :</strong> {{ $classe->code }}</li>
                                <li><strong>Création :</strong> {{ $classe->created_at->format('d/m/Y') }}</li>
                                <li><strong>Dernière modification :</strong> {{ $classe->updated_at->format('d/m/Y') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Matières enseignées -->
        <div class="main-card mb-4">
            <div class="main-card-header d-flex align-items-start justify-content-between">
                <div>
                    <div class="main-card-title">
                        <i class="fas fa-book"></i>
                        Matières prévues pour cette formation
                    </div>
                    <div class="main-card-subtitle">
                        Synthèse des matières du catalogue rattachées à {{ optional($classe->filiere)->name ?? 'cette filière' }} / {{ optional($classe->niveau)->name ?? 'ce niveau' }}
                    </div>
                </div>
                <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#classeMatieresCollapse"
                        aria-expanded="true"
                        aria-controls="classeMatieresCollapse">
                    <i class="fas fa-chevron-up"></i>
                </button>
            </div>
            <div id="classeMatieresCollapse" class="collapse show">
                <div class="main-card-body">
                @if($combinationMatieres->isNotEmpty())
                    <div class="alert alert-info d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-info-circle me-2"></i>
                            Les matières affichées proviennent du catalogue configuré pour cette filière / ce niveau et sont automatiquement prises en compte pour {{ $classe->name }}.
                        </div>
                        @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire') || auth()->user()->hasRole('coordinateur'))
                            <a href="{{ route('esbtp.classes.matieres', ['classe' => $classe->id]) }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-sliders-h me-1"></i>Gérer les matières de la classe
                            </a>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Matière</th>
                                    <th>Coefficient catalogue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($combinationMatieres as $matiere)
                                    <tr>
                                        <td>{{ $matiere->code }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $matiere->name }}</div>
                                            @if($matiere->description)
                                                <small class="text-muted">{{ \Illuminate\Support\Str::limit($matiere->description, 90) }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">
                                                {{ number_format($matiere->classe_coefficient ?? $matiere->coefficient ?? $matiere->coefficient_default ?? 1, 2) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>Aucune matière n'est encore configurée dans le catalogue pour cette filière / ce niveau.
                        @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire'))
                            <a href="{{ route('esbtp.matieres.index') }}" class="alert-link">Compléter le paramétrage global</a>
                            <span class="mx-1">·</span>
                            <a href="{{ route('esbtp.classes.matieres', ['classe' => $classe->id]) }}" class="alert-link">Ajuster pour {{ $classe->name }}</a>
                        @else
                            <a href="{{ route('esbtp.classes.matieres', ['classe' => $classe->id]) }}" class="alert-link">Gérer les matières de {{ $classe->name }}</a>
                        @endif
                    </div>
                @endif
            </div>
            </div>
        </div>

        <!-- Suivi des heures par matière (emploi du temps) -->
        <div class="main-card mb-4">
            <div class="main-card-header d-flex align-items-start justify-content-between">
                <div>
                    <div class="main-card-title">
                        <i class="fas fa-chart-line"></i>
                        Suivi des heures par matière
                    </div>
                    <div class="main-card-subtitle">
                        Heures planifiées vs réalisées et enseignants présents sur l'emploi du temps (année courante)
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <form method="GET" action="{{ route('esbtp.classes.show', ['classe' => $classe->id]) }}" class="d-flex gap-2">
                        <button type="submit" name="periode" value="semestre1" class="btn btn-sm btn-outline-primary {{ ($periode ?? 'annee') === 'semestre1' ? 'active' : '' }}">S1</button>
                        <button type="submit" name="periode" value="semestre2" class="btn btn-sm btn-outline-primary {{ ($periode ?? 'annee') === 'semestre2' ? 'active' : '' }}">S2</button>
                        <button type="submit" name="periode" value="annee" class="btn btn-sm btn-outline-primary {{ ($periode ?? 'annee') === 'annee' ? 'active' : '' }}">Année</button>
                    </form>
                    <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#classePlanningMatiereCollapse"
                            aria-expanded="true"
                            aria-controls="classePlanningMatiereCollapse">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                </div>
            </div>
            <div id="classePlanningMatiereCollapse" class="collapse show">
                <div class="main-card-body">
                    <div class="planning-summary">
                        <div class="planning-summary-card">
                            <div class="value">{{ number_format($planningMatiere['stats']['heures_planifiees'] ?? 0, 1) }}h</div>
                            <div class="label">Heures planifiées</div>
                        </div>
                        <div class="planning-summary-card">
                            <div class="value">{{ number_format($planningMatiere['stats']['heures_realisees'] ?? 0, 1) }}h</div>
                            <div class="label">Heures réalisées</div>
                        </div>
                        <div class="planning-summary-card">
                            <div class="value">{{ $planningMatiere['stats']['nb_seances'] ?? 0 }}</div>
                            <div class="label">Séances comptabilisées</div>
                        </div>
                        <div class="planning-summary-card">
                            <div class="value">{{ $planningMatiere['stats']['taux_realisation'] ?? 0 }}%</div>
                            <div class="label">Taux de réalisation</div>
                        </div>
                    </div>

                    <div class="planning-teachers">
                        @if(!empty($planningMatiere['enseignants']) && $planningMatiere['enseignants']->isNotEmpty())
                            @foreach($planningMatiere['enseignants'] as $enseignant)
                                <a href="{{ route('esbtp.enseignants.show', ['enseignant' => $enseignant['id']]) }}" class="teacher-chip">
                                    <span class="teacher-name">{{ $enseignant['name'] }}</span>
                                    <span class="teacher-hours">{{ number_format($enseignant['heures_realisees'], 1) }}h • {{ $enseignant['nb_seances'] }} séances</span>
                                </a>
                            @endforeach
                        @else
                            <span class="text-muted">Aucun enseignant trouvé sur l'emploi du temps.</span>
                        @endif
                    </div>

                    @if(!empty($planningMatiere['matieres']) && $planningMatiere['matieres']->isNotEmpty())
                        @foreach($planningMatiere['matieres'] as $item)
                            <div class="planning-card">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <div class="planning-title">{{ $item['matiere']->name ?? 'Matière inconnue' }}</div>
                                        <small class="text-muted">{{ $item['matiere']->code ?? 'N/A' }}</small>
                                    </div>
                                    <span class="badge {{ $item['est_configure'] ? 'bg-success' : 'bg-warning' }}">
                                        {{ $item['est_configure'] ? ($item['pourcentage_realise'] . '%') : 'Non configuré' }}
                                    </span>
                                </div>
                                <div class="planning-stats">
                                    <div class="planning-stat">
                                        <div class="planning-value">{{ number_format($item['heures_realisees'], 1) }}h</div>
                                        <div class="planning-label">Réalisées</div>
                                    </div>
                                    <div class="planning-stat">
                                        <div class="planning-value">{{ number_format($item['heures_planifiees'], 1) }}h</div>
                                        <div class="planning-label">Planifiées</div>
                                    </div>
                                    <div class="planning-stat">
                                        <div class="planning-value">{{ number_format($item['heures_restantes'], 1) }}h</div>
                                        <div class="planning-label">Restantes</div>
                                    </div>
                                    <div class="planning-stat">
                                        <div class="planning-value">{{ $item['nb_seances'] }}</div>
                                        <div class="planning-label">Séances</div>
                                    </div>
                                </div>
                                <div class="planning-progress">
                                    <div class="planning-progress-fill" style="width: {{ min($item['pourcentage_realise'], 100) }}%"></div>
                                </div>
                                <div class="text-center mt-1">
                                    <small class="text-muted">{{ $item['pourcentage'] ?? 0 }}% des heures réalisées de la classe</small>
                                </div>

                                <div class="planning-teachers">
                                    @if($item['enseignants']->isNotEmpty())
                                        @foreach($item['enseignants'] as $enseignant)
                                            <a href="{{ route('esbtp.enseignants.show', ['enseignant' => $enseignant['id']]) }}" class="teacher-chip">
                                                <span class="teacher-name">{{ $enseignant['name'] }}</span>
                                                <span class="teacher-hours">{{ number_format($enseignant['heures_realisees'], 1) }}h • {{ $enseignant['nb_seances'] }} séances</span>
                                            </a>
                                        @endforeach
                                    @else
                                        <span class="text-muted">Aucun enseignant trouvé sur l'emploi du temps.</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">Aucune donnée d'emploi du temps trouvée pour cette classe.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Liste des étudiants -->
        <div class="main-card mb-4">
            <div class="main-card-header d-flex align-items-start justify-content-between">
                <div>
                    <div class="main-card-title">
                        <i class="fas fa-users"></i>
                        Liste des étudiants inscrits
                    </div>
                    <div class="main-card-subtitle">{{ $classe->etudiants->count() }} étudiant(s) inscrit(s) dans cette classe pour l'année courante</div>
                </div>
                <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#classeEtudiantsCollapse"
                        aria-expanded="true"
                        aria-controls="classeEtudiantsCollapse">
                    <i class="fas fa-chevron-up"></i>
                </button>
            </div>

            <div id="classeEtudiantsCollapse" class="collapse show">

            <!-- Actions d'export -->
            @if($classe->etudiants->count() > 0)
            <div class="main-card-body" style="border-bottom: 1px solid #e5e7eb; background: #f8f9fa;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1" style="font-weight: 600; color: #374151;">
                            <i class="fas fa-download me-2" style="color: #6366f1;"></i>Documents d'export
                        </h6>
                        <p class="mb-0 text-muted" style="font-size: 0.875rem;">Générer et télécharger les listes pour cette classe</p>
                    </div>
                    <div class="d-flex gap-3">
                        @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire') || auth()->user()->hasRole('enseignant') || auth()->user()->hasRole('coordinateur'))

                        <!-- Dropdown pour Liste d'Appel -->
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2"
                                    style="border-color: #6366f1; color: #6366f1; font-weight: 500; padding: 0.5rem 1rem;">
                                <i class="fas fa-clipboard-list"></i>
                                <span>Liste d'Appel</span>
                            </button>
                            <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split"
                                    data-bs-toggle="dropdown" aria-expanded="false"
                                    style="border-color: #6366f1; color: #6366f1;">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu" style="min-width: 180px;">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2"
                                       href="{{ route('esbtp.classes.liste-appel', ['classe' => $classe->id]) }}"
                                       target="_blank">
                                        <i class="fas fa-eye text-primary"></i>
                                        <span>Aperçu</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2"
                                       href="{{ route('esbtp.classes.liste-appel.pdf', ['classe' => $classe->id]) }}">
                                        <i class="fas fa-file-pdf text-danger"></i>
                                        <span>Télécharger PDF</span>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <!-- Dropdown pour Liste Complète -->
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-success d-flex align-items-center gap-2"
                                    style="border-color: #10b981; color: #10b981; font-weight: 500; padding: 0.5rem 1rem;">
                                <i class="fas fa-users"></i>
                                <span>Liste Complète</span>
                            </button>
                            <button type="button" class="btn btn-outline-success dropdown-toggle dropdown-toggle-split"
                                    data-bs-toggle="dropdown" aria-expanded="false"
                                    style="border-color: #10b981; color: #10b981;">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu" style="min-width: 180px;">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2"
                                       href="{{ route('esbtp.classes.liste-complete', ['classe' => $classe->id]) }}"
                                       target="_blank">
                                        <i class="fas fa-eye text-primary"></i>
                                        <span>Aperçu</span>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2"
                                       href="{{ route('esbtp.classes.liste-complete.pdf', ['classe' => $classe->id]) }}">
                                        <i class="fas fa-file-pdf text-danger"></i>
                                        <span>Télécharger PDF</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2"
                                       href="{{ route('esbtp.classes.liste-complete.excel', ['classe' => $classe->id]) }}">
                                        <i class="fas fa-file-excel text-success"></i>
                                        <span>Télécharger Excel</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
            <div class="main-card-body">
                @if($classe->etudiants->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 datatable">
                            <thead class="bg-light">
                                <tr>
                                    <th>Matricule</th>
                                    <th>Nom complet</th>
                                    <th>Genre</th>
                                    <th>Date de naissance</th>
                                    <th>Contact</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($classe->etudiants as $etudiant)
                                    <tr>
                                        <td>{{ $etudiant->matricule }}</td>
                                        <td>{{ $etudiant->nom }} {{ $etudiant->prenoms }}</td>
                                        <td>{{ $etudiant->genre == 'M' ? 'Masculin' : 'Féminin' }}</td>
                                        <td>{{ $etudiant->date_naissance ? $etudiant->date_naissance->format('d/m/Y') : 'Non renseigné' }}</td>
                                        <td>
                                            {{ $etudiant->telephone }}<br>
                                            <small>{{ $etudiant->email }}</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('esbtp.etudiants.show', ['etudiant' => $etudiant->id]) }}" class="btn btn-info btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" title="Voir détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>Aucun étudiant inscrit dans cette classe pour l'année courante.
                    </div>
                @endif
            </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.datatable').DataTable({
            "responsive": true,
            "autoWidth": false,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.22/i18n/French.json"
            }
        });
    });
    
    // Fonction pour afficher le modal d'information sur le changement d'année
    function showYearChangeInfo() {
        $('#yearChangeModal').modal('show');
    }
</script>

<!-- Modal pour les instructions de changement d'année -->
<div class="modal fade" id="yearChangeModal" tabindex="-1" role="dialog" aria-labelledby="yearChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="yearChangeModalLabel">Comment changer l'année académique ?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Pour consulter les données d'une autre année :</strong></p>
                <ol style="padding-left: 20px; line-height: 1.6; margin: 15px 0;">
                    <li><strong>Aller dans</strong> : Menu → Années Universitaires</li>
                    <li><strong>Trouver l'année souhaitée</strong> (ex: 2023-2024)</li>
                    <li><strong>Cliquer sur "Activer"</strong> pour la définir comme année courante</li>
                    <li><strong>Revenir ici</strong> : Les étudiants affichés dans cette classe se mettront à jour automatiquement</li>
                </ol>
                <hr style="margin: 15px 0;">
                <p style="color: #6b7280; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Note :</strong> Seule une année peut être "courante" à la fois. 
                    Changer l'année courante affecte l'affichage des étudiants dans toute l'application.
                </p>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong>Exemple pour cette classe :</strong><br>
                    • Année courante = 2024-2025 → Voir {{ $classe->etudiants->count() }} étudiants<br>
                    • Changement vers 2023-2024 → Voir les étudiants inscrits en 2023-2024
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <a href="{{ route('esbtp.annees-universitaires.index') }}" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt"></i> Aller aux Années
                </a>
            </div>
        </div>
    </div>
</div>

@endsection
