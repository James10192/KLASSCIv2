@extends('layouts.app')

@section('title', 'Résultats de ' . $etudiant->nom . ' ' . $etudiant->prenoms . ' - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@php
    $coeffContext = session('coefficient_missing_context');

    // ── Données pour le modal auto-suffisant de coefficients ──
    $coeffFiliere       = null;
    $coeffNiveau        = null;
    $coeffAnneeId       = $annee_id ?? null;
    $coeffMatieresLiees = collect();
    $coeffMatieresEvals = collect();
    $coefficients       = collect();

    if (isset($classe) && $classe && $classe->filiere && $classe->niveau) {
        $coeffFiliere = $classe->filiere;
        $coeffNiveau  = $classe->niveau;

        // Groupe 1 : matières formellement liées à la combinaison filière/niveau
        $coeffMatieresLiees = \App\Models\ESBTPMatiere::where('is_active', true)
            ->whereHas('filieres', fn($q) => $q->where('esbtp_filieres.id', $coeffFiliere->id))
            ->whereHas('niveaux',  fn($q) => $q->where('esbtp_niveau_etudes.id', $coeffNiveau->id))
            ->orderBy('name')
            ->get();

        $idsLiees = $coeffMatieresLiees->pluck('id');

        // Groupe 2 : matières avec évaluations dans la classe, hors combinaison
        $coeffMatieresEvals = \App\Models\ESBTPMatiere::where('is_active', true)
            ->whereHas('evaluations', fn($q) => $q->where('classe_id', $classe->id))
            ->whereNotIn('id', $idsLiees)
            ->orderBy('name')
            ->get();

        // Coefficients existants pour la combinaison
        if ($coeffAnneeId) {
            $coefficients = \App\Models\ESBTPMatiereCoefficient::where('filiere_id', $coeffFiliere->id)
                ->where('niveau_etude_id', $coeffNiveau->id)
                ->where('annee_universitaire_id', $coeffAnneeId)
                ->get()
                ->keyBy('matiere_id');
        }
    }
@endphp

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-user-graduate me-2"></i>Résultats de {{ $etudiant->nom }} {{ $etudiant->prenoms }}</h1>
                <p class="header-subtitle">Détail complet des notes et moyennes - {{ isset($classe) && $classe ? $classe->name : 'Toutes classes' }}</p>
            </div>
            <div class="header-actions">
                @if(isset($classe) && $classe && auth()->user()->hasRole('superAdmin'))
                    <a href="{{ route('esbtp.resultats.classe.edit', $classe->id) }}?annee_universitaire_id={{ $annee_id }}&semestre={{ isset($periode) ? str_replace('semestre', '', $periode) : '' }}"
                       class="btn-acasi warning">
                        <i class="fas fa-edit"></i>Éditer classe
                    </a>
                @endif
                <a href="{{ route('esbtp.resultats.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour aux résultats
                </a>
                @if(isset($classe) && $classe)
                    <a href="{{ route('esbtp.resultats.etudiant.preview', ['etudiant' => $etudiant->id]) }}?classe_id={{ $classe->id }}&annee_universitaire_id={{ $annee_id }}&periode={{ $periode }}"
                       class="btn-acasi primary">
                        <i class="fas fa-eye"></i>Prévisualiser bulletin
                    </a>
                    <a href="{{ route('esbtp.bulletins.pdf-params', ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $annee_id]) }}" class="btn-acasi danger">
                        <i class="fas fa-file-pdf"></i>Télécharger PDF
                    </a>
                @endif
            </div>
        </div>

        <!-- Filtres -->
        @include('components.student-results.filters-section')

        <!-- Layout en deux colonnes -->
        <div class="row mb-4">
            <div class="col-lg-4">
                @include('components.student-results.student-info-card')
            </div>
            <div class="col-lg-8">
                @include('components.student-results.results-overview-card')
            </div>
        </div>

        <!-- Tableau des matières -->
        @include('components.student-results.subjects-table')

        <!-- Détail des évaluations -->
        @include('components.student-results.evaluations-detail')

        <!-- Actions et navigation -->
        @include('components.student-results.action-buttons')

        {{-- Modal auto-suffisant de configuration des coefficients --}}
        @include('esbtp.resultats.partials.student-coefficients-modal')

    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form when filters change
    const filterForm = document.querySelector('.filter-form');
    if (filterForm) {
        const filterSelects = filterForm.querySelectorAll('select');
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                // Optional: Uncomment to enable auto-submit
                // filterForm.submit();
            });
        });
    }
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Smooth scroll for internal links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
</script>

@if($coeffContext)
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modalElement = document.getElementById('studentCoeffModal');
        if (modalElement && typeof bootstrap !== 'undefined') {
            const modalInstance = new bootstrap.Modal(modalElement, { backdrop: 'static', keyboard: false });
            modalInstance.show();
        }
    });
    </script>
@endif
@endsection
