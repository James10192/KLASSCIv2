@extends('layouts.app')

@section('title', 'Résultats de ' . $etudiant->nom . ' ' . $etudiant->prenoms . ' - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@php
    $coeffContext = session('coefficient_missing_context');
    $reason = $coeffContext['reason'] ?? null;
    $matiereName = $coeffContext['matiere']['name'] ?? null;
    $matiereCode = $coeffContext['matiere']['code'] ?? null;
    $classeName = $coeffContext['classe']['name'] ?? null;
    $filiereName = $coeffContext['classe']['filiere_name'] ?? null;
    $niveauName = $coeffContext['classe']['niveau_name'] ?? null;
    $configUrl = $coeffContext['config_url'] ?? route('esbtp.evaluations.index', ['open_coefficients' => 1]);
    $classeMatieresUrl = $coeffContext['classe_matieres_url'] ?? (isset($classe) && $classe ? route('classes.matieres', ['classe' => $classe->id]) : null);
    $evaluationsUrl = $coeffContext['evaluations_url'] ?? route('esbtp.evaluations.index');
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

        @if($coeffContext)
            <div class="modal fade" id="bulletinIssueModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content bulletin-issue-modal">
                        <div class="modal-header">
                            <div class="issue-header">
                                <div class="issue-badge">
                                    <i class="fas fa-triangle-exclamation"></i>
                                </div>
                                <div>
                                    <h5 class="modal-title">Bulletin bloqué</h5>
                                    <div class="issue-subtitle">Une configuration est nécessaire pour continuer.</div>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="issue-card">
                                @if($reason === 'matiere_hors_combinaison')
                                    <div class="issue-title">Matière hors combinaison filière / niveau</div>
                                    <p class="issue-text">
                                        La matière <strong>{{ $matiereName ?? 'sélectionnée' }}</strong>
                                        n'est pas rattachée à la combinaison <strong>{{ $filiereName ?? 'filière' }}</strong> /
                                        <strong>{{ $niveauName ?? 'niveau' }}</strong> de la classe
                                        <strong>{{ $classeName ?? 'sélectionnée' }}</strong>.
                                    </p>
                                    <div class="issue-note">
                                        Les coefficients se configurent uniquement sur les matières liées à cette combinaison.
                                    </div>
                                @elseif($reason === 'matiere_introuvable')
                                    <div class="issue-title">Matière introuvable</div>
                                    <p class="issue-text">
                                        La matière associée aux notes n'a pas été retrouvée. Veuillez vérifier la configuration des évaluations.
                                    </p>
                                @else
                                    <div class="issue-title">Coefficient manquant</div>
                                    <p class="issue-text">
                                        Aucun coefficient n'est défini pour
                                        <strong>{{ $matiereName ?? 'cette matière' }}</strong> sur
                                        <strong>{{ $classeName ?? 'cette classe' }}</strong>.
                                    </p>
                                    <div class="issue-note">
                                        Configurez les coefficients pour générer la preview et le PDF.
                                    </div>
                                @endif

                                <div class="issue-grid">
                                    <div class="issue-item">
                                        <span>Matière</span>
                                        <strong>{{ $matiereName ?? 'Non renseignée' }}{{ $matiereCode ? ' · '.$matiereCode : '' }}</strong>
                                    </div>
                                    <div class="issue-item">
                                        <span>Classe</span>
                                        <strong>{{ $classeName ?? 'Non renseignée' }}</strong>
                                    </div>
                                    <div class="issue-item">
                                        <span>Combinaison</span>
                                        <strong>{{ $filiereName ?? '—' }} · {{ $niveauName ?? '—' }}</strong>
                                    </div>
                                </div>

                                <div class="issue-actions">
                                    @if($reason === 'matiere_hors_combinaison' && $classeMatieresUrl)
                                        <a href="{{ $classeMatieresUrl }}" class="btn-acasi primary">
                                            <i class="fas fa-list-check"></i>Gérer matières de la classe
                                        </a>
                                        <a href="{{ $configUrl }}" class="btn-acasi secondary">
                                            <i class="fas fa-sliders-h"></i>Configurer coefficients
                                        </a>
                                    @else
                                        <a href="{{ $configUrl }}" class="btn-acasi primary">
                                            <i class="fas fa-sliders-h"></i>Configurer coefficients
                                        </a>
                                        @if($classeMatieresUrl)
                                            <a href="{{ $classeMatieresUrl }}" class="btn-acasi secondary">
                                                <i class="fas fa-list-check"></i>Matières de la classe
                                            </a>
                                        @endif
                                    @endif
                                    <a href="{{ $evaluationsUrl }}" class="btn-acasi info">
                                        <i class="fas fa-clipboard-list"></i>Vérifier évaluations
                                    </a>
                                </div>

                                <div class="issue-footnote">
                                    Besoin d'aide ? Vérifiez que les évaluations utilisent des matières rattachées à la classe.
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
        const modalElement = document.getElementById('bulletinIssueModal');
        if (modalElement && typeof bootstrap !== 'undefined') {
            const modalInstance = new bootstrap.Modal(modalElement);
            modalInstance.show();
        }
    });
    </script>
@endif
@endsection

@push('styles')
<style>
.bulletin-issue-modal .modal-content {
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid rgba(4, 83, 203, 0.15);
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);
}

.bulletin-issue-modal .modal-header {
    background: linear-gradient(135deg, rgba(4, 83, 203, 0.12), rgba(94, 145, 222, 0.18));
    border-bottom: 1px solid rgba(4, 83, 203, 0.18);
}

.issue-header {
    display: flex;
    align-items: center;
    gap: 0.9rem;
}

.issue-badge {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    background: rgba(239, 68, 68, 0.12);
    color: #dc2626;
    font-size: 1.25rem;
}

.issue-subtitle {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.issue-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid rgba(148, 163, 184, 0.3);
    padding: 1.5rem;
    display: grid;
    gap: 1rem;
}

.issue-title {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--text-primary);
}

.issue-text {
    color: var(--text-secondary);
    margin: 0;
}

.issue-note {
    background: rgba(4, 83, 203, 0.08);
    border-left: 4px solid var(--primary);
    padding: 0.75rem 1rem;
    border-radius: 10px;
    color: var(--text-primary);
    font-size: 0.9rem;
}

.issue-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 0.75rem;
}

.issue-item {
    background: var(--background-secondary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 0.75rem 0.9rem;
    display: grid;
    gap: 0.25rem;
}

.issue-item span {
    text-transform: uppercase;
    font-size: 0.7rem;
    letter-spacing: 0.08em;
    color: var(--text-secondary);
}

.issue-item strong {
    font-size: 0.9rem;
    color: var(--text-primary);
}

.issue-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.issue-footnote {
    font-size: 0.8rem;
    color: var(--text-secondary);
}

@media (max-width: 576px) {
    .issue-actions .btn-acasi {
        width: 100%;
        justify-content: center;
    }
}
</style>
@endpush
