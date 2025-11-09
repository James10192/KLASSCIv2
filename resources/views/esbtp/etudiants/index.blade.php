@extends('layouts.app')

@section('title', 'Gestion des étudiants - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    .modal-modern .modal-dialog {
        width: clamp(1024px, 80vw, 1800px);
        max-width: 80vw;
        height: 80vh;
        max-height: 80vh;
        position: relative;
        margin: 10vh auto;
    }

    .modal-modern .modal-content {
        border-radius: 24px;
        border: none;
        box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25);
        background: linear-gradient(135deg, #fdfdfd 0%, #f3f4f6 35%, #ffffff 100%);
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .modal-modern .modal-header {
        border-bottom: none;
        padding: 20px 28px 12px 28px;
        background: transparent;
        flex-shrink: 0;
    }

    .modal-modern .modal-body {
        padding: 8px 28px 24px 28px;
        overflow: hidden;
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .student-tabs-container {
        position: relative;
        margin-bottom: 0;
        flex-shrink: 0;
    }

    .student-tabs-container .nav-tabs {
        border: none;
        margin-bottom: 0;
        position: relative;
        z-index: 10;
        display: flex;
        gap: 8px;
        padding-left: 0;
    }

    .student-tabs-container .nav-link {
        border: none !important;
        border-radius: 16px 16px 0 0 !important;
        padding: 14px 24px !important;
        color: #6b7280 !important;
        background: #f8fafc !important;
        font-weight: 500 !important;
        transition: all 0.3s ease !important;
        box-shadow: 0 8px 16px rgba(15, 23, 42, 0.12) !important;
        border-bottom: 1px solid #e5e7eb !important;
    }

    .student-tabs-container .nav-link:hover {
        background: #eef2ff !important;
        color: #1f2937 !important;
        transform: translateY(-2px) !important;
    }

    .student-tabs-container .nav-link.active {
        background: #ffffff !important;
        color: #111827 !important;
        font-weight: 700 !important;
        box-shadow: 0 -2px 20px rgba(15, 23, 42, 0.12) !important;
        border-bottom: none !important;
    }

    .student-tabs-container .nav-link .tab-label {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .student-tabs-container .nav-link .tab-label i {
        font-size: 14px;
    }

    .modern-tab-content {
        position: relative;
        z-index: 5;
        background: #ffffff;
        border-radius: 0 16px 16px 16px;
        margin-top: -1px;
        box-shadow: inset 0 1px 0 rgba(229, 231, 235, 0.8), 0 20px 35px rgba(15, 23, 42, 0.15);
        padding: 0;
        border: 1px solid #e5e7eb;
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        min-height: 0;
    }

    .modern-tab-content .tab-pane {
        padding: 0;
        border: none;
        background: transparent;
        display: none;
        overflow: hidden;
        min-height: 0;
    }

    .modern-tab-content .tab-pane.show.active {
        display: flex;
        flex: 1;
        flex-direction: column;
    }

    .category-card {
        position: relative;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(248,250,252,1) 100%);
        border-radius: 20px;
        border: 1px solid rgba(15, 23, 42, 0.06);
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.12);
        padding: 24px;
    }

    .category-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 25px 55px rgba(15, 23, 42, 0.18);
    }

    .category-card .modal-card-body {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .modal-iframe-wrapper {
        border-radius: 0;
        overflow: hidden;
        border: none;
        background: #ffffff;
        width: 100%;
        height: 100%;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .modal-iframe-wrapper iframe {
        width: 100%;
        height: 100%;
        flex: 1;
        border: none;
    }

    #inscriptions-accordion-container {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        min-height: 0;
    }

    #etudiants-table th button.table-sort {
        color: inherit;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    #etudiants-table th button.table-sort:hover {
        opacity: 0.85;
    }

    .accordion-modern .accordion-item {
        border: none;
        border-radius: 16px;
        margin-bottom: 12px;
        overflow: hidden;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12);
    }

    .accordion-modern .accordion-button {
        background: #f8fafc;
        border: none;
        font-weight: 600;
        color: #0f172a;
        padding: 16px 20px;
    }

    .accordion-modern .accordion-body {
        background: #ffffff;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .accordion-modern .accordion-body .modal-iframe-wrapper {
        min-height: 500px;
        height: 60vh;
    }

    .accordion-modern .accordion-body .modal-iframe-wrapper iframe {
        width: 100%;
        height: 100%;
    }

    #editStudentTabContent {
        transition: min-height 0.3s ease;
    }

    .modal-modern .modal-dialog::after {
        content: '';
        position: absolute;
        top: -20px;
        right: -20px;
        width: 120px;
        height: 120px;
        background: radial-gradient(circle, rgba(255,255,255,0.45), rgba(99,102,241,0.08));
        filter: blur(20px);
        z-index: -1;
    }

    @media (max-width: 1400px) {
        .modal-modern .modal-dialog {
            width: 85vw;
            max-width: 85vw;
        }
    }

    @media (max-width: 1200px) {
        .modal-modern .modal-dialog {
            width: 90vw;
            max-width: 90vw;
            height: 85vh;
            max-height: 85vh;
            margin: 7.5vh auto;
        }
    }

    @media (max-width: 992px) {
        .modal-modern .modal-dialog {
            width: 95vw;
            max-width: 95vw;
            height: 90vh;
            max-height: 90vh;
            margin: 5vh auto;
        }

        .accordion-modern .accordion-body .modal-iframe-wrapper {
            min-height: 400px;
            height: 50vh;
        }

        .category-card {
            padding: 16px;
        }

        #search-form .row > [class*='col-'] {
            width: 100%;
        }

        #search-form .row {
            row-gap: 1rem;
        }

        .dashboard-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
    }

    @media (max-width: 576px) {
        .header-actions a {
            width: 100%;
        }

        .student-tabs-container .nav-tabs {
            flex-direction: column;
        }
    }

    /* Select2 Custom Styles */
    .select2-container--bootstrap-5 .select2-selection {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        min-height: 42px;
        padding: 4px 8px;
    }

    .select2-container--bootstrap-5 .select2-selection:focus {
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4, 83, 203, 0.1);
    }

    .select2-container--bootstrap-5 .select2-dropdown {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.1);
    }

    .select2-container--bootstrap-5 .select2-search__field {
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 8px 12px;
        outline: none;
    }

    .select2-container--bootstrap-5 .select2-search__field:focus {
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4, 83, 203, 0.1);
    }

    .select2-container--bootstrap-5 .select2-results__option--highlighted {
        background-color: #0453cb;
        color: white;
    }

    .select2-container--bootstrap-5 .select2-results__option[aria-selected="true"] {
        background-color: #e8f2ff;
        color: #0453cb;
    }

    /* Animation pour Select2 */
    .select2-dropdown {
        animation: slideDown 0.2s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Étudiants</h1>
                <p class="header-subtitle">Gestion des étudiants de l'établissement</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.inscriptions.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus-circle"></i>Ajouter un étudiant
                </a>
                @if(auth()->user()->hasRole(['superAdmin', 'secretaire', 'coordinateur']))
                <a href="{{ route('esbtp.reinscription.index') }}" class="btn-acasi success">
                    <i class="fas fa-user-graduate"></i>Réinscriptions
                </a>
                @endif
            </div>
        </div>

        <div class="card-moderne">
            <div class="p-lg">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif


                <!-- Filtres de recherche -->
                <div class="section-title mb-md">
                    <i class="fas fa-filter me-2"></i>Filtres de recherche
                </div>
                            <form method="GET" action="{{ route('esbtp.etudiants.index') }}" id="search-form">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="search" class="form-label">Recherche</label>
                                        <input type="text" class="form-control search-bar" id="search" name="search" value="{{ $search ?? '' }}" placeholder="Matricule, nom, prénom, téléphone...">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="filiere" class="form-label">Filière</label>
                                        <select class="form-select year-selector" id="filiere" name="filiere">
                                            <option value="">Toutes les filières</option>
                                            @foreach($filieres as $f)
                                                <option value="{{ $f->id }}" {{ isset($filiere) && $filiere == $f->id ? 'selected' : '' }}>
                                                    {{ $f->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="niveau" class="form-label">Niveau d'études</label>
                                        <select class="form-select year-selector" id="niveau" name="niveau">
                                            <option value="">Tous les niveaux</option>
                                            @foreach($niveaux as $n)
                                                <option value="{{ $n->id }}" {{ isset($niveau) && $niveau == $n->id ? 'selected' : '' }}>
                                                    {{ $n->name }} ({{ $n->type }} - Année {{ $n->year }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="classe" class="form-label">Classe</label>
                                        <select class="form-select year-selector" id="classe" name="classe">
                                            <option value="">Toutes les classes</option>
                                            @foreach($classes as $classeOption)
                                                <option value="{{ $classeOption->id }}" {{ isset($classe) && $classe == $classeOption->id ? 'selected' : '' }}>
                                                    {{ $classeOption->name }}
                                                    @if($classeOption->filiere || $classeOption->niveauEtude)
                                                        ({{ $classeOption->filiere->name ?? 'Filière N/A' }} - {{ $classeOption->niveauEtude->name ?? 'Niveau N/A' }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="annee" class="form-label">Année universitaire</label>
                                        <select class="form-select year-selector" id="annee" name="annee">
                                            <option value="">Toutes les années</option>
                                            @foreach($annees as $a)
                                                <option value="{{ $a->id }}" {{ isset($annee) && $annee == $a->id ? 'selected' : '' }}>
                                                    {{ $a->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="status" class="form-label">Statut</label>
                                        <select class="form-select year-selector" id="status" name="status">
                                            <option value="">Tous les statuts</option>
                                            <option value="actif" {{ isset($status) && $status == 'actif' ? 'selected' : '' }}>Actif</option>
                                            <option value="inactif" {{ isset($status) && $status == 'inactif' ? 'selected' : '' }}>Inactif</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="affectation_status" class="form-label">Statut d'affectation ({{ $anneeCourante?->name ?? 'N/A' }})</label>
                                        <select class="form-select year-selector" id="affectation_status" name="affectation_status">
                                            <option value="">Tous les statuts d'affectation</option>
                                            <option value="affecté" {{ isset($affectationStatus) && $affectationStatus == 'affecté' ? 'selected' : '' }}>Affecté</option>
                                            <option value="réaffecté" {{ isset($affectationStatus) && $affectationStatus == 'réaffecté' ? 'selected' : '' }}>Réaffecté</option>
                                            <option value="non_affecté" {{ isset($affectationStatus) && $affectationStatus == 'non_affecté' ? 'selected' : '' }}>Non affecté</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="inscrit_annee_courante" class="form-label">Inscription validée ({{ $anneeCourante?->name ?? 'N/A' }})</label>
                                        <select class="form-select year-selector" id="inscrit_annee_courante" name="inscrit_annee_courante">
                                            <option value="">Tous</option>
                                            <option value="validee" {{ isset($inscritAnneeCourante) && $inscritAnneeCourante == 'validee' ? 'selected' : '' }}>Oui (Validée)</option>
                                            <option value="en_attente" {{ isset($inscritAnneeCourante) && $inscritAnneeCourante == 'en_attente' ? 'selected' : '' }}>En attente</option>
                                            <option value="absente" {{ isset($inscritAnneeCourante) && $inscritAnneeCourante == 'absente' ? 'selected' : '' }}>Absente</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="est_transfert" class="form-label">Transfert</label>
                                        <select class="form-select year-selector" id="est_transfert" name="est_transfert">
                                            <option value="">Tous</option>
                                            <option value="1" {{ isset($estTransfert) && $estTransfert == '1' ? 'selected' : '' }}>Oui (Transferts)</option>
                                            <option value="0" {{ isset($estTransfert) && $estTransfert == '0' ? 'selected' : '' }}>Non (Locaux)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end mb-3">
                                        <button type="submit" class="btn-acasi primary me-2">
                                            <i class="fas fa-search"></i>Filtrer
                                        </button>
                                        <a href="{{ route('esbtp.etudiants.index') }}" class="btn-acasi secondary">
                                            <i class="fas fa-redo-alt"></i>Réinitialiser
                                        </a>
                                    </div>
                                </div>
                            </form>
            </div>
        </div>

        <!-- Tableau des étudiants -->
        <div class="card-moderne">
            <div class="p-lg">
                <div class="section-title mb-md">
                    <i class="fas fa-list"></i>Liste des étudiants
                </div>
                <div id="etudiants-results">
                    @include('esbtp.etudiants.partials.results', ['etudiants' => $etudiants])
</div>
</div>
</div>
</div>
</div>

<!-- Modal d'édition rapide -->
<div class="modal fade modal-modern" id="etudiantEditModal" tabindex="-1" aria-labelledby="etudiantEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <p class="text-uppercase text-muted small mb-1">Edition rapide</p>
                    <h5 class="modal-title" id="etudiantEditModalLabel">Modifier l'étudiant</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="student-tabs-container">
                    <ul class="nav nav-tabs" id="editStudentTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-etudiant-link" data-bs-toggle="tab" data-bs-target="#tab-etudiant" type="button" role="tab">
                                <span class="tab-label">
                                    <i class="fas fa-user-edit"></i>
                                    Étudiant
                                </span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-inscriptions-link" data-bs-toggle="tab" data-bs-target="#tab-inscriptions" type="button" role="tab">
                                <span class="tab-label">
                                    <i class="fas fa-graduation-cap"></i>
                                    Inscriptions
                                </span>
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="tab-content modern-tab-content" id="editStudentTabContent">
                    <div class="tab-pane fade show active" id="tab-etudiant" role="tabpanel">
                        <div class="modal-iframe-wrapper">
                            <iframe id="student-edit-frame" src="about:blank" title="Édition étudiant" loading="lazy" class="border-0"></iframe>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab-inscriptions" role="tabpanel">
                        <div id="inscriptions-accordion-container" class="accordion-modern text-muted w-100">
                            Sélectionnez un étudiant pour afficher ses inscriptions.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('search-form');
        const resultsContainer = document.getElementById('etudiants-results');
        const submitButton = form.querySelector('button[type="submit"]');
        const filterInputs = form.querySelectorAll('select');
        const modalElement = document.getElementById('etudiantEditModal');
        const inscriptionsContainer = document.getElementById('inscriptions-accordion-container');
        const studentFrame = document.getElementById('student-edit-frame');
        let editModal = null;

        // Configuration Select2 avec recherche optimisée pour 50+ éléments
        if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
            // Select Classe avec recherche toujours visible (50+ classes)
            $('#classe').select2({
                theme: 'bootstrap-5',
                placeholder: 'Rechercher une classe...',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 0,  // Toujours afficher la recherche
                language: {
                    noResults: function() {
                        return "Aucune classe trouvée";
                    },
                    searching: function() {
                        return "Recherche en cours...";
                    },
                    inputTooShort: function() {
                        return "Veuillez saisir au moins 1 caractère";
                    }
                }
            });

            // Autres selects (filière, niveau, année, etc.)
            $('#filiere, #niveau, #annee, #status, #affectation_status, #inscrit_annee_courante').select2({
                theme: 'bootstrap-5',
                placeholder: 'Sélectionner une option',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 10,  // Recherche si > 10 options
                language: {
                    noResults: function() {
                        return "Aucun résultat trouvé";
                    }
                }
            });
        }

        function setLoading(isLoading) {
            if (submitButton) {
                submitButton.disabled = isLoading;
            }
            if (isLoading) {
                resultsContainer.classList.add('opacity-50');
            } else {
                resultsContainer.classList.remove('opacity-50');
            }
        }

        function bindPagination() {
            resultsContainer.querySelectorAll('.pagination a').forEach((link) => {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    fetchResults(this.href, { pushState: true });
                });
            });
        }

        function initTableSorting(scope = document) {
            const table = scope.querySelector('#etudiants-table');
            if (!table) {
                return;
            }

            scope.querySelectorAll('.table-sort').forEach((button) => {
                if (button.dataset.sortInit === '1') {
                    return;
                }
                button.dataset.sortInit = '1';
                button.addEventListener('click', function () {
                    const column = this.dataset.column;
                    if (!column) {
                        return;
                    }
                    const dataKey = 'sort' + column.charAt(0).toUpperCase() + column.slice(1);
                    const currentDirection = this.dataset.sortDirection === 'asc' ? 'desc' : 'asc';
                    this.dataset.sortDirection = currentDirection;

                    scope.querySelectorAll('.table-sort').forEach((other) => {
                        if (other !== this) {
                            delete other.dataset.sortDirection;
                        }
                    });

                    const rows = Array.from(table.querySelectorAll('tbody tr'));
                    const multiplier = currentDirection === 'asc' ? 1 : -1;

                    rows.sort((a, b) => {
                        const rawA = a.dataset[dataKey] || '';
                        const rawB = b.dataset[dataKey] || '';
                        if (column === 'date') {
                            if (rawA === rawB) {
                                return 0;
                            }
                            return (rawA > rawB ? 1 : -1) * multiplier;
                        }

                        const aVal = rawA.toUpperCase();
                        const bVal = rawB.toUpperCase();
                        return aVal.localeCompare(bVal) * multiplier;
                    });

                    const tbody = table.querySelector('tbody');
                    rows.forEach((row) => tbody.appendChild(row));
                }, { once: false });
            });
        }

        function formatStatusLabel(status) {
            if (!status) {
                return '';
            }
            const normalized = status.replace(/_/g, ' ');
            const classes = {
                'active': 'bg-success',
                'en attente': 'bg-warning text-dark',
                'en_attente': 'bg-warning text-dark',
                'annulée': 'bg-danger',
                'terminée': 'bg-secondary',
            };
            const key = status.toLowerCase();
            const badgeClass = classes[key] || 'bg-primary';
            return `<span class="badge ${badgeClass} text-uppercase">${normalized}</span>`;
        }

        function attachAccordionListeners(container) {
            if (!container) {
                return;
            }
            container.querySelectorAll('.accordion-collapse').forEach((collapseEl) => {
                collapseEl.addEventListener('show.bs.collapse', function () {
                    const iframe = this.querySelector('iframe[data-src]');
                    if (iframe && !iframe.src) {
                        const separator = iframe.dataset.src.includes('?') ? '&' : '?';
                        iframe.src = `${iframe.dataset.src}${separator}_=${Date.now()}`;
                    }
                }, { once: true });
            });

            const firstVisible = container.querySelector('.accordion-collapse.show');
            if (firstVisible) {
                const iframe = firstVisible.querySelector('iframe[data-src]');
                if (iframe && !iframe.src) {
                    const separator = iframe.dataset.src.includes('?') ? '&' : '?';
                    iframe.src = `${iframe.dataset.src}${separator}_=${Date.now()}`;
                }
            }
        }

        function renderInscriptionsAccordion(payload) {
            if (!inscriptionsContainer) {
                return;
            }

            const inscriptions = payload?.inscriptions ?? [];
            if (!inscriptions.length) {
                inscriptionsContainer.innerHTML = '<div class="alert alert-info mb-0">Aucune inscription disponible pour cet étudiant.</div>';
                return;
            }

            const accordionId = 'inscriptionsAccordion';
            const items = inscriptions.map((inscription, index) => {
                const collapseId = `inscription-collapse-${inscription.id}`;
                const headingId = `inscription-heading-${inscription.id}`;
                const affectation = inscription.affectation_status ? `<span class="badge bg-secondary ms-2 text-uppercase">${inscription.affectation_status}</span>` : '';
                const statusBadge = formatStatusLabel(inscription.status);
                const typeBadge = inscription.type ? `<span class="badge bg-info text-dark text-uppercase ms-2">${inscription.type}</span>` : '';
                const currentYearBadge = inscription.is_current_year ? `<span class="badge bg-primary text-white ms-2">Année courante</span>` : '';
                const dateChip = inscription.date_label ? `<span class="badge bg-light text-dark border ms-2"><i class="far fa-calendar-alt me-1"></i>${inscription.date_label}</span>` : '';

                return `
<div class="accordion-item mb-2">
    <h2 class="accordion-header" id="${headingId}">
        <button class="accordion-button ${index === 0 ? '' : 'collapsed'}" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="${index === 0}" aria-controls="${collapseId}">
            <div class="d-flex flex-column flex-md-row w-100 justify-content-between">
                <div>
                    <strong>${inscription.annee}</strong> ${currentYearBadge} — ${inscription.classe}
                    ${dateChip}
                </div>
                <div>
                    ${statusBadge || ''}
                    ${affectation}
                    ${typeBadge}
                </div>
            </div>
        </button>
    </h2>
    <div id="${collapseId}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" data-bs-parent="#${accordionId}">
        <div class="accordion-body">
            <div class="mb-3 row g-3 text-muted small">
                ${inscription.filiere ? `<div class=\"col-md-4\"><i class=\"fas fa-book me-2 text-primary\"></i>${inscription.filiere}</div>` : ''}
                ${inscription.niveau ? `<div class=\"col-md-4\"><i class=\"fas fa-layer-group me-2 text-primary\"></i>${inscription.niveau}</div>` : ''}
                ${inscription.affectation_status ? `<div class=\"col-md-4\"><i class=\"fas fa-map-marker-alt me-2 text-primary\"></i>${inscription.affectation_status}</div>` : ''}
            </div>
            <div class="modal-iframe-wrapper">
                <iframe class="border-0 inscription-frame" data-src="${inscription.edit_url}" title="Inscription #${inscription.id}" loading="lazy"></iframe>
            </div>
        </div>
    </div>
</div>`;
            }).join('');

            inscriptionsContainer.innerHTML = `<div class="accordion accordion-modern" id="${accordionId}">${items}</div>`;
            attachAccordionListeners(inscriptionsContainer);
        }

        function openEditModal(datasetString) {
            if (!modalElement || !datasetString) {
                return;
            }
            if (!editModal) {
                editModal = new bootstrap.Modal(modalElement);
                modalElement.addEventListener('hidden.bs.modal', () => {
                    if (studentFrame) {
                        studentFrame.src = 'about:blank';
                    }
                    if (inscriptionsContainer) {
                        inscriptionsContainer.innerHTML = '<div class="text-muted">Sélectionnez un étudiant pour afficher ses inscriptions.</div>';
                    }
                });
            }

            let payload;
            try {
                payload = JSON.parse(datasetString);
            } catch (error) {
                console.error('Impossible de parser les données de l\'étudiant', error);
                return;
            }

            const modalTitle = document.getElementById('etudiantEditModalLabel');
            if (modalTitle) {
                const identifiant = payload.matricule ? ` (#${payload.matricule})` : '';
                modalTitle.textContent = `Modifier ${payload.name ?? 'l\'étudiant'}${identifiant}`;
            }

            if (studentFrame && payload.edit_url) {
                studentFrame.classList.add('opacity-50');
                const separator = payload.edit_url.includes('?') ? '&' : '?';
                studentFrame.src = `${payload.edit_url}${separator}_=${Date.now()}`;
                studentFrame.addEventListener('load', function handleLoad() {
                    studentFrame.classList.remove('opacity-50');
                    studentFrame.removeEventListener('load', handleLoad);
                });
            }

            renderInscriptionsAccordion(payload);
            const studentTab = document.getElementById('tab-etudiant-link');
            if (studentTab) {
                const tabInstance = bootstrap.Tab.getOrCreateInstance(studentTab);
                tabInstance.show();
            }
            editModal.show();
        }

        if (resultsContainer) {
            resultsContainer.addEventListener('click', function (event) {
                const trigger = event.target.closest('.btn-open-edit-modal');
                if (!trigger) {
                    return;
                }
                event.preventDefault();
                openEditModal(trigger.getAttribute('data-student'));
            });
        }

        function fetchResults(url, options = {}) {
            if (!url) {
                return;
            }

            setLoading(true);

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors du chargement des étudiants.');
                }
                return response.json();
            })
            .then(data => {
                resultsContainer.innerHTML = data.html;
                if (options.pushState !== false) {
                    window.history.pushState({ url: data.url }, '', data.url);
                }
                bindPagination();
                initTableSorting(resultsContainer);
            })
            .catch(error => {
                console.error(error);
                alert('Impossible de charger les étudiants. Veuillez réessayer.');
            })
            .finally(() => setLoading(false));
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            event.stopPropagation();
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            const targetUrl = `${form.action}?${params.toString()}`;
            fetchResults(targetUrl, { pushState: true });
            return false;
        });

        filterInputs.forEach((input) => {
            input.addEventListener('change', () => {
                if (!form) {
                    return;
                }
                const formData = new FormData(form);
                const params = new URLSearchParams(formData);
                const targetUrl = `${form.action}?${params.toString()}`;
                fetchResults(targetUrl, { pushState: true });
            });
        });

        if (window.history && window.history.replaceState) {
            window.history.replaceState({ url: window.location.href }, '', window.location.href);
        }

        window.addEventListener('popstate', function (event) {
            const targetUrl = event.state?.url || window.location.href;
            fetchResults(targetUrl, { pushState: false });
        });

        bindPagination();
        initTableSorting(resultsContainer);

    });
</script>
@endpush
