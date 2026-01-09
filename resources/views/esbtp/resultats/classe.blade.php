@extends('layouts.app')

@section('title', 'Résultats de la classe ' . $classe->name . ' - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* Styles pour la page de résultats de classe */
.content-container {
    width: 100% !important;
    min-height: 200px;
}

.content-container .table-responsive {
    width: 100% !important;
    margin: 0;
}

.content-container .table-responsive table {
    width: 100% !important;
    margin: 0;
}

.badge-success-custom {
    background-color: #10b981;
    color: white;
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
    font-weight: 500;
}

.badge-danger-custom {
    background-color: #ef4444;
    color: white;
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
    font-weight: 500;
}

.badge-warning-custom {
    background-color: #f59e0b;
    color: white;
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
    font-weight: 500;
}
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-chart-bar me-2"></i>Résultats de la classe {{ $classe->name }}</h1>
                <p class="header-subtitle">Consultez les résultats scolaires de la classe</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.resultats.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Statistiques KPI -->
        @php
            $totalMoyennes = 0;
            $countMoyennes = 0;
            $min = 20;
            $max = 0;
            $countSucces = 0;
            $countEchec = 0;

            foreach ($resultats as $resultat) {
                if ($resultat['notes_count'] > 0) {
                    $totalMoyennes += $resultat['moyenne'];
                    $countMoyennes++;

                    $min = min($min, $resultat['moyenne']);
                    $max = max($max, $resultat['moyenne']);

                    if ($resultat['moyenne'] >= 10) {
                        $countSucces++;
                    } else {
                        $countEchec++;
                    }
                }
            }

            $moyenneClasse = $countMoyennes > 0 ? $totalMoyennes / $countMoyennes : 0;
            $tauxReussite = $countMoyennes > 0 ? ($countSucces / $countMoyennes) * 100 : 0;
        @endphp

        <div class="kpi-grid">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Total Étudiants</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ count($resultats) }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-users"></i>
                    Dans la classe {{ $classe->name }}
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Moyenne Générale</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">
                    {{ $countMoyennes > 0 ? number_format($moyenneClasse, 2) : 'N/A' }}
                </div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-calculator"></i>
                    Sur 20 points
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Taux de Réussite</div>
                <div class="kpi-value" style="color: #10b981; font-size: 2.5rem; font-weight: bold;">
                    {{ $countMoyennes > 0 ? number_format($tauxReussite, 1) : '0' }}%
                </div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-graduation-cap"></i>
                    Moyenne ≥ 10/20
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Meilleure Moyenne</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">
                    {{ $countMoyennes > 0 ? number_format($max, 2) : 'N/A' }}
                </div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-trophy"></i>
                    Note maximale
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
                <div class="main-card-subtitle">Affinez votre recherche de résultats</div>
            </div>
            <div class="main-card-body">
                <form action="{{ route('esbtp.resultats.classe', $classe) }}" method="GET" class="filter-form">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Année Universitaire</label>
                            <select class="form-select select2" id="annee_universitaire_id" name="annee_universitaire_id">
                                @foreach($anneesUniversitaires ?? [] as $annee)
                                    <option value="{{ $annee->id }}" {{ isset($annee_universitaire_id) && $annee_universitaire_id == $annee->id ? 'selected' : '' }}>
                                        {{ $annee->name ?? ($annee->annee_debut . '-' . $annee->annee_fin) }}{{ $annee->is_current ? ' (En cours)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Période</label>
                            <select class="form-select" id="periode" name="periode">
                                @foreach($periodes ?? [] as $key => $nom)
                                    <option value="{{ $key }}" {{ isset($periode) && $periode == $key ? 'selected' : '' }}>
                                        {{ $nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Filtrer
                            </button>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="include_all_statuses" name="include_all_statuses" value="1" {{ isset($include_all_statuses) && $include_all_statuses ? 'checked' : '' }}>
                                <label class="form-check-label" for="include_all_statuses">
                                    Inclure tous les statuts d'inscription (en attente, validée, rejetée, etc.)
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section principale des résultats -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-list"></i>
                    Liste des résultats
                </div>
                <div class="main-card-subtitle">
                    {{ $classe->name }} -
                    @if(isset($periode))
                        @foreach($periodes ?? [] as $key => $nom)
                            @if($periode == $key)
                                {{ $nom }}
                            @endif
                        @endforeach
                    @else
                        Toutes les périodes
                    @endif
                    @if(isset($anneeUniversitaire))
                        - Année {{ $anneeUniversitaire->name ?? ($anneeUniversitaire->annee_debut . '-' . $anneeUniversitaire->annee_fin) }}
                    @endif
                </div>
            </div>

            <div class="main-card-body">
                <div class="content-container">
                    @if(count($resultats) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%;">Rang</th>
                                        <th style="width: 10%;">Matricule</th>
                                        <th style="width: 25%;">Nom & Prénom</th>
                                        <th style="width: 15%;" class="text-center">Moyenne</th>
                                        <th style="width: 15%;" class="text-center">Nombre de notes</th>
                                        <th style="width: 15%;" class="text-center">Statut</th>
                                        <th style="width: 15%;" class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($resultats as $index => $resultat)
                                        @php
                                            $etudiant = $resultat['etudiant'];
                                            $moyenne = $resultat['moyenne'];
                                            $notesCount = $resultat['notes_count'];

                                            if ($moyenne >= 10) {
                                                $badgeClass = 'badge-success-custom';
                                                $badgeText = 'Admis';
                                            } elseif ($moyenne >= 8) {
                                                $badgeClass = 'badge-warning-custom';
                                                $badgeText = 'Rattrapage';
                                            } else {
                                                $badgeClass = 'badge-danger-custom';
                                                $badgeText = 'Échec';
                                            }
                                        @endphp
                                        <tr>
                                            <td class="fw-bold">
                                                @if($index == 0)
                                                    <i class="fas fa-trophy text-warning"></i>
                                                @endif
                                                {{ $index + 1 }}
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $etudiant->matricule }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-circle bg-primary text-white me-2" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold;">
                                                        {{ strtoupper(substr($etudiant->nom ?? 'N', 0, 1)) }}{{ strtoupper(substr($etudiant->prenoms ?? 'A', 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold">{{ $etudiant->nom ?? 'N/A' }} {{ $etudiant->prenoms ?? '' }}</div>
                                                        <div class="text-muted small">{{ $etudiant->email ?? 'N/A' }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="fw-bold fs-5" style="color: var(--primary);">
                                                    {{ $notesCount > 0 ? number_format($moyenne, 2) : 'N/A' }}
                                                </div>
                                                <small class="text-muted">/ 20</small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info">{{ $notesCount }} note(s)</span>
                                            </td>
                                            <td class="text-center">
                                                @if($notesCount > 0)
                                                    <span class="badge {{ $badgeClass }}">{{ $badgeText }}</span>
                                                @else
                                                    <span class="badge bg-secondary">Aucune note</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('esbtp.resultats.etudiant', [
                                                    'etudiant' => $etudiant->id,
                                                    'classe_id' => $classe->id,
                                                    'periode' => $periode ?? '',
                                                    'annee_universitaire_id' => $annee_universitaire_id ?? ''
                                                ]) }}" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye me-1"></i>Voir détails
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Aucun résultat trouvé</h5>
                            <p class="text-muted">Aucun étudiant ne correspond aux critères sélectionnés.</p>
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
    // Initialize Select2 for better select dropdowns
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }
});
</script>
@endsection
