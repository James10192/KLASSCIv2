@extends('layouts.app')

@section('title', 'Résultats de ' . $etudiant->nom . ' ' . $etudiant->prenoms . ' - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

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
                @if(isset($classe) && $classe)
                    <a href="{{ route('esbtp.resultats.classe', ['classe' => $classe->id]) }}?periode={{ $periode }}&annee_universitaire_id={{ $annee_id }}" 
                       class="btn-acasi secondary">
                        <i class="fas fa-arrow-left"></i>Retour à la classe
                    </a>
                @else
                    <a href="{{ route('esbtp.resultats.index') }}" class="btn-acasi secondary">
                        <i class="fas fa-arrow-left"></i>Retour aux résultats
                    </a>
                @endif
                @if(isset($classe) && $classe)
                    <a href="{{ route('esbtp.resultats.etudiant.preview', ['etudiant' => $etudiant->id]) }}?classe_id={{ $classe->id }}&annee_universitaire_id={{ $annee_id }}" 
                       class="btn-acasi primary">
                        <i class="fas fa-eye"></i>Prévisualiser bulletin
                    </a>
                    <a href="#" class="btn-acasi danger"
                       onclick="window.open('{{ route('esbtp.bulletins.pdf-params', ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $annee_id]) }}', '_blank')">
                        <i class="fas fa-file-pdf"></i>Télécharger PDF
                    </a>
                @endif
            </div>
        </div>
        
        <!-- Messages Flash -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

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
@endsection
