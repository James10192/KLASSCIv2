@extends('layouts.app')

@section('title', 'Édition des professeurs - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-user-edit me-2"></i>Édition des professeurs</h1>
                <p class="header-subtitle">Configurez les enseignants pour chaque matière du bulletin</p>
            </div>
            <div class="header-actions">
                <span class="badge bg-primary fs-6">
                    <i class="fas fa-graduation-cap me-1"></i>
                    {{ $etudiant->nom }} {{ $etudiant->prenom }}
                </span>
            </div>
        </div>

        <!-- Statistiques KPI -->
        <div class="kpi-grid mb-4">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Classe</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 1.5rem; font-weight: bold;">{{ $classe->libelle ?? $classe->name ?? 'N/A' }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-users"></i>
                    {{ $classe->filiere->nom ?? $classe->filiere->name ?? 'N/A' }}
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Matières générales</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $resultatsGeneraux->count() ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-graduation-cap"></i>
                    Enseignement général
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Matières techniques</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $resultatsTechniques->count() ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-tools"></i>
                    Enseignement technique
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Professeurs assignés</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ !empty($professeurs) ? count($professeurs) : 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-chalkboard-teacher"></i>
                    Configurés
                </div>
            </div>
        </div>

        <!-- Guide d'utilisation -->
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-lightbulb"></i>
                    Guide d'utilisation
                </div>
            </div>
            <div class="main-card-body">
                <div class="alert alert-info mb-0">
                    <div class="row">
                        <div class="col-md-6">
                            <p><i class="fas fa-user-plus me-2"></i>Saisissez le nom des enseignants pour chaque matière</p>
                            <p><i class="fas fa-eye-slash me-2"></i>Les champs vides n'afficheront pas de nom d'enseignant</p>
                        </div>
                        <div class="col-md-6">
                            <p><i class="fas fa-info-circle me-2"></i>Les noms apparaîtront sur le bulletin final</p>
                            <p><i class="fas fa-save me-2"></i>N'oubliez pas d'enregistrer vos modifications</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form id="professeursForm" action="{{ route('esbtp.bulletins.save-professeurs') }}" method="POST">
            @csrf
            <input type="hidden" name="etudiant_id" value="{{ $etudiant->id }}">
            <input type="hidden" name="classe_id" value="{{ $classe->id }}">
            <input type="hidden" name="periode" value="{{ $periode }}">
            <input type="hidden" name="annee_universitaire_id" value="{{ $anneeUniversitaire->id }}">

            @if(isset($resultatsGeneraux) && $resultatsGeneraux->count() > 0)
            <div class="main-card mb-4">
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-graduation-cap"></i>
                        Enseignement général
                    </div>
                    <div class="main-card-subtitle">{{ $resultatsGeneraux->count() }} matière(s) d'enseignement général</div>
                </div>
                <div class="main-card-body">
                    <div class="row g-3">
                        @foreach($resultatsGeneraux as $resultat)
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="professeur_{{ $resultat->matiere_id }}" class="form-label fw-medium">
                                    <i class="fas fa-book text-primary me-2"></i>
                                    {{ $resultat->matiere->name ?? $resultat->matiere->nom ?? 'Matière #'.$resultat->matiere_id }}
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                    </span>
                                    <input type="text"
                                           class="form-control"
                                           id="professeur_{{ $resultat->matiere_id }}"
                                           name="professeurs[{{ $resultat->matiere_id }}]"
                                           value="{{ $professeurs[$resultat->matiere_id] ?? '' }}"
                                           placeholder="Nom de l'enseignant">
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @if(isset($resultatsTechniques) && $resultatsTechniques->count() > 0)
            <div class="main-card mb-4">
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-tools"></i>
                        Enseignement technique
                    </div>
                    <div class="main-card-subtitle">{{ $resultatsTechniques->count() }} matière(s) d'enseignement technique</div>
                </div>
                <div class="main-card-body">
                    <div class="row g-3">
                        @foreach($resultatsTechniques as $resultat)
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="professeur_{{ $resultat->matiere_id }}" class="form-label fw-medium">
                                    <i class="fas fa-cog text-success me-2"></i>
                                    {{ $resultat->matiere->name ?? $resultat->matiere->nom ?? 'Matière #'.$resultat->matiere_id }}
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                    </span>
                                    <input type="text"
                                           class="form-control"
                                           id="professeur_{{ $resultat->matiere_id }}"
                                           name="professeurs[{{ $resultat->matiere_id }}]"
                                           value="{{ $professeurs[$resultat->matiere_id] ?? '' }}"
                                           placeholder="Nom de l'enseignant">
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @if((!isset($resultatsGeneraux) || $resultatsGeneraux->isEmpty()) && (!isset($resultatsTechniques) || $resultatsTechniques->isEmpty()))
            <div class="main-card">
                <div class="main-card-body">
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3 d-block"></i>
                        <h5>Aucune matière configurée</h5>
                        <p>Veuillez d'abord configurer les matières pour cet étudiant.</p>
                        <a href="{{ route('esbtp.bulletins.config-matieres') }}?classe_id={{ $classe->id }}&periode={{ $periode }}&annee_universitaire_id={{ $anneeUniversitaire->id }}&bulletin={{ $etudiant->id }}" class="btn-acasi primary">
                            <i class="fas fa-cogs"></i> Configurer les matières
                        </a>
                    </div>
                </div>
            </div>
            @endif

            <!-- Actions -->
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div>
                    <a href="{{ route('esbtp.resultats.etudiant', [
                        'etudiant' => $etudiant->id,
                        'classe_id' => $classe->id,
                        'periode' => $periode,
                        'annee_universitaire_id' => $anneeUniversitaire->id
                    ]) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Retour aux résultats
                    </a>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn-acasi success" name="action" value="edit">
                        <i class="fas fa-save"></i> Enregistrer et continuer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<!-- JavaScript removed to prevent any potential interference with form submission -->
@endsection