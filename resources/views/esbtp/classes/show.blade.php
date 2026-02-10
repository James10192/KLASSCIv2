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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('classe-periode-form');
    const content = document.getElementById('classe-planning-content');
    if (!container || !content) {
        return;
    }

    const fetchPlanning = (periode) => {
        const baseUrl = container.dataset.url;
        const url = new URL(baseUrl);
        url.searchParams.set('periode', periode);
        return fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const nextContent = doc.querySelector('#classe-planning-content');
                if (nextContent) {
                    content.innerHTML = nextContent.innerHTML;
                }
                window.history.replaceState({}, '', url.toString());
            })
            .catch(() => {
                window.location.href = url.toString();
            });
    };

    container.addEventListener('click', (event) => {
        const button = event.target.closest('.periode-btn');
        if (!button) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        container.querySelectorAll('.periode-btn').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
        fetchPlanning(button.dataset.periode);
    });
});
</script>
@endpush

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
                    <div class="d-flex gap-2" id="classe-periode-form" data-url="{{ route('esbtp.classes.show', ['classe' => $classe->id]) }}">
                        <button type="button" class="btn btn-sm btn-outline-primary periode-btn {{ ($periode ?? 'annee') === 'semestre1' ? 'active' : '' }}" data-periode="semestre1">S1</button>
                        <button type="button" class="btn btn-sm btn-outline-primary periode-btn {{ ($periode ?? 'annee') === 'semestre2' ? 'active' : '' }}" data-periode="semestre2">S2</button>
                        <button type="button" class="btn btn-sm btn-outline-primary periode-btn {{ ($periode ?? 'annee') === 'annee' ? 'active' : '' }}" data-periode="annee">Année</button>
                    </div>
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
                <div class="main-card-body" id="classe-planning-content">
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
                    <div class="main-card-subtitle" id="studentCountSubtitle">{{ $classe->etudiants->count() }} étudiant(s) inscrit(s) dans cette classe pour l'année courante</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire') || auth()->user()->hasRole('coordinateur'))
                    <button type="button" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addStudentsModal" title="Ajouter des étudiants">
                        <i class="fas fa-user-plus"></i>
                        <span class="d-none d-md-inline">Ajouter</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-warning d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#removeStudentsModal" title="Retirer / Transférer des étudiants">
                        <i class="fas fa-user-minus"></i>
                        <span class="d-none d-md-inline">Retirer / Transférer</span>
                    </button>
                    @endif
                    <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#classeEtudiantsCollapse"
                            aria-expanded="true"
                            aria-controls="classeEtudiantsCollapse">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                </div>
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
            <div class="main-card-body" id="studentTableContainer">
                @include('esbtp.classes.partials.student-table-rows', ['classe' => $classe])
            </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialiser DataTables sur la table étudiants
        function initStudentDataTable() {
            if ($.fn.DataTable.isDataTable('#studentsDataTable')) {
                $('#studentsDataTable').DataTable().destroy();
            }
            var table = document.getElementById('studentsDataTable');
            if (table) {
                $('#studentsDataTable').DataTable({
                    "responsive": true,
                    "autoWidth": false,
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.10.22/i18n/French.json"
                    }
                });
            }
        }
        initStudentDataTable();

        // ==========================================
        // RAFRAICHIR LA TABLE ÉTUDIANTS (AJAX)
        // ==========================================
        function refreshStudentTable() {
            var container = document.getElementById('studentTableContainer');
            container.style.opacity = '0.5';

            fetch("{{ route('esbtp.classes.student-table-html', ['classe' => $classe->id]) }}", {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    container.innerHTML = data.html;
                    container.style.opacity = '1';
                    // Mettre à jour le compteur dans le header
                    document.getElementById('studentCountSubtitle').textContent =
                        data.count + ' étudiant(s) inscrit(s) dans cette classe pour l\'année courante';
                    // Réinitialiser DataTables
                    initStudentDataTable();
                    // Mettre à jour la liste dans le modal Retirer
                    refreshRemoveModalStudentList(data.html);
                }
            })
            .catch(function() {
                container.style.opacity = '1';
            });
        }

        // Mettre à jour la liste étudiants dans le modal "Retirer"
        function refreshRemoveModalStudentList(tableHtml) {
            var parser = new DOMParser();
            var doc = parser.parseFromString(tableHtml, 'text/html');
            var rows = doc.querySelectorAll('tbody tr[data-etudiant-id]');
            var listContainer = document.getElementById('removeStudentsList');
            if (!listContainer) return;

            if (rows.length === 0) {
                listContainer.innerHTML = '<div class="text-muted text-center py-3">Aucun étudiant dans cette classe.</div>';
                return;
            }

            var html = '';
            rows.forEach(function(row) {
                var id = row.getAttribute('data-etudiant-id');
                var cells = row.querySelectorAll('td');
                var matricule = cells[0] ? cells[0].textContent.trim() : '';
                var nom = cells[1] ? cells[1].textContent.trim() : '';
                html += '<label class="list-group-item d-flex align-items-center gap-2" style="cursor: pointer;">' +
                    '<input type="checkbox" class="form-check-input remove-student-checkbox" value="' + id + '" style="margin: 0;">' +
                    '<span class="badge bg-light text-dark" style="font-family: monospace; font-size: 0.8rem;">' + matricule + '</span>' +
                    '<span>' + nom + '</span>' +
                    '</label>';
            });
            listContainer.innerHTML = html;
            updateRemoveSelectedCount();
        }

        // ==========================================
        // MODAL AJOUTER ÉTUDIANTS
        // ==========================================
        var addSearchTimer = null;
        var addSelectedStudents = {};

        // Recherche avec debounce
        document.getElementById('addStudentSearchInput').addEventListener('input', function() {
            clearTimeout(addSearchTimer);
            var query = this.value;
            addSearchTimer = setTimeout(function() {
                searchAvailableStudents(query);
            }, 300);
        });

        function searchAvailableStudents(query) {
            var resultsContainer = document.getElementById('addSearchResults');
            var loadingEl = document.getElementById('addSearchLoading');
            loadingEl.style.display = 'block';
            resultsContainer.innerHTML = '';

            var url = "{{ route('esbtp.classes.search-available-students', ['classe' => $classe->id]) }}?q=" + encodeURIComponent(query);

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                loadingEl.style.display = 'none';
                if (!data.success) {
                    resultsContainer.innerHTML = '<div class="text-danger p-3">' + (data.message || 'Erreur') + '</div>';
                    return;
                }
                if (data.etudiants.length === 0) {
                    resultsContainer.innerHTML = '<div class="text-muted text-center py-3"><i class="fas fa-search me-2"></i>Aucun étudiant trouvé.</div>';
                    return;
                }
                var html = '';
                data.etudiants.forEach(function(etudiant) {
                    var isChecked = addSelectedStudents[etudiant.id] ? 'checked' : '';
                    html += '<label class="list-group-item d-flex align-items-center gap-2" style="cursor: pointer;">' +
                        '<input type="checkbox" class="form-check-input add-student-checkbox" value="' + etudiant.id + '" ' + isChecked + ' style="margin: 0;">' +
                        '<div class="flex-grow-1">' +
                        '<div class="d-flex align-items-center gap-2">' +
                        '<span class="badge bg-light text-dark" style="font-family: monospace; font-size: 0.8rem;">' + (etudiant.matricule || 'N/A') + '</span>' +
                        '<strong>' + etudiant.nom_complet + '</strong>' +
                        '</div>' +
                        '<small class="text-muted">Classe actuelle : ' + etudiant.classe_actuelle + '</small>' +
                        '</div>' +
                        '</label>';
                });
                resultsContainer.innerHTML = html;
            })
            .catch(function() {
                loadingEl.style.display = 'none';
                resultsContainer.innerHTML = '<div class="text-danger p-3">Erreur de connexion.</div>';
            });
        }

        // Gérer la sélection/désélection
        document.getElementById('addSearchResults').addEventListener('change', function(e) {
            if (e.target.classList.contains('add-student-checkbox')) {
                var id = e.target.value;
                var label = e.target.closest('label');
                var nameEl = label.querySelector('strong');
                if (e.target.checked) {
                    addSelectedStudents[id] = nameEl ? nameEl.textContent : 'Étudiant ' + id;
                } else {
                    delete addSelectedStudents[id];
                }
                updateAddSelectedCount();
            }
        });

        function updateAddSelectedCount() {
            var count = Object.keys(addSelectedStudents).length;
            document.getElementById('addSelectedCount').textContent = count;
            document.getElementById('addSubmitBtn').disabled = count === 0;

            // Mettre à jour le tag area
            var tagsContainer = document.getElementById('addSelectedTags');
            if (count === 0) {
                tagsContainer.innerHTML = '<span class="text-muted">Aucun étudiant sélectionné</span>';
            } else {
                var html = '';
                for (var id in addSelectedStudents) {
                    html += '<span class="badge bg-primary me-1 mb-1" style="font-size: 0.8rem;">' +
                        addSelectedStudents[id] +
                        ' <i class="fas fa-times ms-1" style="cursor:pointer;" data-remove-id="' + id + '"></i>' +
                        '</span>';
                }
                tagsContainer.innerHTML = html;
            }
        }

        // Supprimer un tag
        document.getElementById('addSelectedTags').addEventListener('click', function(e) {
            var removeBtn = e.target.closest('[data-remove-id]');
            if (removeBtn) {
                var id = removeBtn.getAttribute('data-remove-id');
                delete addSelectedStudents[id];
                // Décocher la checkbox si visible
                var checkbox = document.querySelector('.add-student-checkbox[value="' + id + '"]');
                if (checkbox) checkbox.checked = false;
                updateAddSelectedCount();
            }
        });

        // Soumettre l'ajout
        document.getElementById('addSubmitBtn').addEventListener('click', function() {
            var ids = Object.keys(addSelectedStudents).map(Number);
            if (ids.length === 0) return;

            var btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Ajout en cours...';

            fetch("{{ route('esbtp.classes.add-students', ['classe' => $classe->id]) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ etudiant_ids: ids })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    // Fermer le modal
                    var addModal = bootstrap.Modal.getInstance(document.getElementById('addStudentsModal'));
                    if (addModal) addModal.hide();
                    // Réinitialiser
                    addSelectedStudents = {};
                    updateAddSelectedCount();
                    document.getElementById('addStudentSearchInput').value = '';
                    document.getElementById('addSearchResults').innerHTML = '';
                    // Rafraîchir la table
                    refreshStudentTable();
                    // Notification
                    showNotification('success', data.message);
                } else {
                    showNotification('danger', data.message || 'Erreur lors de l\'ajout.');
                }
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-plus me-1"></i>Ajouter <span class="badge bg-light text-success" id="addSelectedCount">0</span> étudiant(s)';
            })
            .catch(function() {
                showNotification('danger', 'Erreur de connexion.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-plus me-1"></i>Ajouter <span class="badge bg-light text-success" id="addSelectedCount">0</span> étudiant(s)';
            });
        });

        // Charger les résultats au premier affichage du modal
        document.getElementById('addStudentsModal').addEventListener('shown.bs.modal', function() {
            document.getElementById('addStudentSearchInput').focus();
            if (document.getElementById('addSearchResults').innerHTML === '') {
                searchAvailableStudents('');
            }
        });

        // ==========================================
        // MODAL RETIRER / TRANSFÉRER ÉTUDIANTS
        // ==========================================

        // Charger la liste des étudiants actuels à l'ouverture du modal
        document.getElementById('removeStudentsModal').addEventListener('shown.bs.modal', function() {
            populateRemoveStudentsList();
        });

        function populateRemoveStudentsList() {
            var tableRows = document.querySelectorAll('#studentTableContainer tr[data-etudiant-id]');
            var listContainer = document.getElementById('removeStudentsList');

            if (tableRows.length === 0) {
                listContainer.innerHTML = '<div class="text-muted text-center py-3">Aucun étudiant dans cette classe.</div>';
                return;
            }

            var html = '';
            tableRows.forEach(function(row) {
                var id = row.getAttribute('data-etudiant-id');
                var cells = row.querySelectorAll('td');
                var matricule = cells[0] ? cells[0].textContent.trim() : '';
                var nom = cells[1] ? cells[1].textContent.trim() : '';
                html += '<label class="list-group-item d-flex align-items-center gap-2" style="cursor: pointer;">' +
                    '<input type="checkbox" class="form-check-input remove-student-checkbox" value="' + id + '" style="margin: 0;">' +
                    '<span class="badge bg-light text-dark" style="font-family: monospace; font-size: 0.8rem;">' + matricule + '</span>' +
                    '<span>' + nom + '</span>' +
                    '</label>';
            });
            listContainer.innerHTML = html;
            updateRemoveSelectedCount();
        }

        // Sélectionner / Désélectionner tout
        document.getElementById('removeSelectAll').addEventListener('change', function() {
            var checkboxes = document.querySelectorAll('.remove-student-checkbox');
            var checked = this.checked;
            checkboxes.forEach(function(cb) { cb.checked = checked; });
            updateRemoveSelectedCount();
        });

        // Recherche dans la liste retirer
        document.getElementById('removeSearchInput').addEventListener('input', function() {
            var query = this.value.toLowerCase();
            var items = document.querySelectorAll('#removeStudentsList .list-group-item');
            items.forEach(function(item) {
                var text = item.textContent.toLowerCase();
                item.style.display = text.includes(query) ? '' : 'none';
            });
        });

        // Mettre à jour le compteur de sélection
        document.getElementById('removeStudentsList').addEventListener('change', function() {
            updateRemoveSelectedCount();
        });

        function updateRemoveSelectedCount() {
            var count = document.querySelectorAll('.remove-student-checkbox:checked').length;
            document.getElementById('removeSelectedCount').textContent = count;
            document.getElementById('removeSubmitBtn').disabled = count === 0;
        }

        // Soumettre le retrait/transfert
        document.getElementById('removeSubmitBtn').addEventListener('click', function() {
            var checkboxes = document.querySelectorAll('.remove-student-checkbox:checked');
            var ids = [];
            checkboxes.forEach(function(cb) { ids.push(parseInt(cb.value)); });

            if (ids.length === 0) return;

            var destinationSelect = document.getElementById('destinationClasseId');
            var destinationClasseId = destinationSelect.value || null;

            // Confirmation
            var actionText = destinationClasseId
                ? 'transférer ' + ids.length + ' étudiant(s) vers "' + destinationSelect.options[destinationSelect.selectedIndex].text + '"'
                : 'retirer ' + ids.length + ' étudiant(s) de cette classe (ils seront marqués comme non affectés)';

            if (!confirm('Confirmer : ' + actionText + ' ?')) return;

            var btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Traitement...';

            var body = { etudiant_ids: ids };
            if (destinationClasseId) {
                body.destination_classe_id = parseInt(destinationClasseId);
            }

            fetch("{{ route('esbtp.classes.remove-students', ['classe' => $classe->id]) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(body)
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    // Fermer le modal
                    var removeModal = bootstrap.Modal.getInstance(document.getElementById('removeStudentsModal'));
                    if (removeModal) removeModal.hide();
                    // Réinitialiser
                    document.getElementById('removeSelectAll').checked = false;
                    document.getElementById('removeSearchInput').value = '';
                    // Rafraîchir la table
                    refreshStudentTable();
                    // Notification
                    showNotification('success', data.message);
                } else {
                    showNotification('danger', data.message || 'Erreur lors du retrait.');
                }
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-exchange-alt me-1"></i>Retirer / Transférer <span class="badge bg-light text-warning" id="removeSelectedCount">0</span> étudiant(s)';
            })
            .catch(function() {
                showNotification('danger', 'Erreur de connexion.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-exchange-alt me-1"></i>Retirer / Transférer <span class="badge bg-light text-warning" id="removeSelectedCount">0</span> étudiant(s)';
            });
        });

        // ==========================================
        // NOTIFICATION TOAST
        // ==========================================
        function showNotification(type, message) {
            var alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show';
            alertDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
            alertDiv.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + ' me-2"></i>' +
                message +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            document.body.appendChild(alertDiv);
            setTimeout(function() {
                if (alertDiv.parentNode) {
                    alertDiv.classList.remove('show');
                    setTimeout(function() { alertDiv.remove(); }, 300);
                }
            }, 5000);
        }
    });

    // Fonction pour afficher le modal d'information sur le changement d'année
    function showYearChangeInfo() {
        var el = document.getElementById('yearChangeModal');
        if (el) {
            bootstrap.Modal.getOrCreateInstance(el).show();
        }
    }
</script>

<!-- ==========================================
     MODAL : AJOUTER DES ÉTUDIANTS
     ========================================== -->
<div class="modal fade" id="addStudentsModal" tabindex="-1" aria-labelledby="addStudentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                <h5 class="modal-title" id="addStudentsModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Ajouter des étudiants à {{ $classe->name }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <!-- Barre de recherche -->
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="addStudentSearchInput" class="form-control" placeholder="Rechercher par nom, matricule, téléphone...">
                    </div>
                    <small class="text-muted">Recherche parmi les étudiants inscrits cette année mais pas dans cette classe.</small>
                </div>

                <!-- Tags des étudiants sélectionnés -->
                <div class="mb-3 p-2" style="background: #f8f9fa; border-radius: 6px; min-height: 36px;" id="addSelectedTags">
                    <span class="text-muted">Aucun étudiant sélectionné</span>
                </div>

                <!-- Loading -->
                <div id="addSearchLoading" style="display: none;" class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ms-2 text-muted">Recherche en cours...</span>
                </div>

                <!-- Résultats de recherche -->
                <div class="list-group" id="addSearchResults" style="max-height: 400px; overflow-y: auto;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" id="addSubmitBtn" disabled>
                    <i class="fas fa-plus me-1"></i>Ajouter <span class="badge bg-light text-success" id="addSelectedCount">0</span> étudiant(s)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
     MODAL : RETIRER / TRANSFÉRER DES ÉTUDIANTS
     ========================================== -->
<div class="modal fade" id="removeStudentsModal" tabindex="-1" aria-labelledby="removeStudentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">
                <h5 class="modal-title" id="removeStudentsModalLabel">
                    <i class="fas fa-user-minus me-2"></i>Retirer / Transférer des étudiants
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <!-- Destination -->
                <div class="mb-3 p-3" style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px;">
                    <label for="destinationClasseId" class="form-label fw-bold">
                        <i class="fas fa-exchange-alt me-1 text-warning"></i>Classe de destination
                    </label>
                    <select id="destinationClasseId" class="form-select">
                        <option value="">-- Non affecté (retirer sans transférer) --</option>
                        @isset($autresClasses)
                        @php
                            $grouped = $autresClasses->groupBy(function($c) {
                                return optional($c->filiere)->name ?? 'Sans filière';
                            });
                        @endphp
                        @foreach($grouped as $filiereName => $classes)
                            <optgroup label="{{ $filiereName }}">
                                @foreach($classes as $autreClasse)
                                    <option value="{{ $autreClasse->id }}">
                                        {{ $autreClasse->name }} ({{ optional($autreClasse->niveau)->name ?? '' }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                        @endisset
                    </select>
                    <small class="text-muted mt-1 d-block">
                        <i class="fas fa-info-circle me-1"></i>Si aucune classe n'est sélectionnée, les étudiants seront marqués "non affectés".
                    </small>
                </div>

                <!-- Sélection rapide + Recherche -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="removeSelectAll">
                        <label class="form-check-label fw-bold" for="removeSelectAll">Tout sélectionner</label>
                    </div>
                    <div style="width: 250px;">
                        <input type="text" id="removeSearchInput" class="form-control form-control-sm" placeholder="Filtrer la liste...">
                    </div>
                </div>

                <!-- Liste des étudiants actuels -->
                <div class="list-group" id="removeStudentsList" style="max-height: 400px; overflow-y: auto;">
                    <div class="text-muted text-center py-3">Chargement...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-warning" id="removeSubmitBtn" disabled>
                    <i class="fas fa-exchange-alt me-1"></i>Retirer / Transférer <span class="badge bg-light text-warning" id="removeSelectedCount">0</span> étudiant(s)
                </button>
            </div>
        </div>
    </div>
</div>

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

@endpush
