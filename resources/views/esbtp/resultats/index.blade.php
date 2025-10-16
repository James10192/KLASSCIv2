@extends('layouts.app')

@section('title', 'Résultats des étudiants - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* SPINNER ISOLÉ POUR RESULTATS - Force tous les styles */
.resultats-spinner {
    position: relative !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    width: 100% !important;
    min-height: 200px !important;
    text-align: center !important;
    padding: 40px !important;
}

.resultats-spinner.hidden {
    display: none !important;
}

.resultats-spinner-icon {
    display: block !important;
    margin-bottom: 20px !important;
    text-align: center !important;
}

.resultats-spinner-icon i {
    font-size: 48px !important;
    color: #3b82f6 !important;
    animation: resultats-spin 1s linear infinite !important;
    transform-origin: center center !important;
}

.resultats-spinner-text {
    display: block !important;
    position: static !important;
    animation: none !important;
    transform: none !important;
    color: #64748b !important;
    margin: 0 !important;
    padding: 0 !important;
    font-size: 14px !important;
    font-weight: normal !important;
    text-align: center !important;
}

@keyframes resultats-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Container pour le contenu */
.content-container {
    width: 100% !important;
    min-height: 200px;
}

/* S'assurer que les tables prennent toute la largeur */
.content-container .table-responsive {
    width: 100% !important;
    margin: 0;
}

.content-container .table-responsive table {
    width: 100% !important;
    margin: 0;
}

/* État d'erreur */
.error-state {
    width: 100% !important;
    min-height: 200px;
}

.error-state.hidden {
    display: none !important;
}
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-chart-bar me-2"></i>Résultats des étudiants</h1>
                <p class="header-subtitle">Consultez et gérez les résultats scolaires de l'établissement</p>
            </div>
            <div class="header-actions">
                <input type="search" class="search-bar" placeholder="Rechercher un étudiant...">
                <a href="{{ route('esbtp.resultats.classes') }}" class="btn-acasi secondary">
                    <i class="fas fa-layer-group"></i>Sélectionner une classe
                </a>
                @can('edit_bulletins')
                    <a href="{{ route('esbtp.bulletins.configuration') }}" class="btn-acasi primary">
                        <i class="fas fa-cogs"></i>Configuration bulletins
                    </a>
                @endcan
                <a href="{{ route('esbtp.bulletins.select') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour sélection
                </a>
            </div>
        </div>

        <!-- Statistiques KPI -->
        <div class="kpi-grid">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Total Étudiants</div>
                <div class="kpi-value" id="kpi-total-etudiants" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $totalEtudiants ?? 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-users"></i>
                    Chargement via lazy loading
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Moyenne Générale</div>
                <div class="kpi-value" id="kpi-moyenne-generale" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">
                    @if(isset($moyennes) && count($moyennes) > 0)
                        {{ number_format(array_sum($moyennes) / count($moyennes), 1) }}
                    @else
                        N/A
                    @endif
                </div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-calculator"></i>
                    Sur 20 points
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Taux de Réussite</div>
                <div class="kpi-value" id="kpi-taux-reussite" style="color: #10b981; font-size: 2.5rem; font-weight: bold;">
                    @if(isset($moyennes) && count($moyennes) > 0)
                        {{ number_format((count(array_filter($moyennes, function($m) { return $m >= 10; })) / count($moyennes)) * 100, 1) }}%
                    @else
                        N/A
                    @endif
                </div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-graduation-cap"></i>
                    Moyenne ≥ 10/20
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Bulletins Générés</div>
                <div class="kpi-value" id="kpi-bulletins" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ isset($bulletins) ? count($bulletins) : 0 }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-file-alt"></i>
                    Bulletins disponibles
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
                <form action="{{ route('esbtp.resultats.index') }}" method="GET" class="filter-form">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Classe</label>
                            <select class="form-select select2" id="classe_id" name="classe_id">
                                <option value="">Sélectionnez une classe</option>
                                @foreach($classes ?? [] as $classeItem)
                                    <option value="{{ $classeItem->id }}" {{ isset($classe_id) && $classe_id == $classeItem->id ? 'selected' : '' }}>
                                        {{ $classeItem->name }} ({{ $classeItem->filiere->name ?? 'N/A' }} - {{ $classeItem->niveau->name ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Année Universitaire</label>
                            <select class="form-select select2" id="annee_universitaire_id" name="annee_universitaire_id">
                                <option value="">Sélectionnez une année</option>
                                @foreach($annees_universitaires ?? [] as $annee)
                                    <option value="{{ $annee->id }}" {{ isset($annee_universitaire_id) && $annee_universitaire_id == $annee->id ? 'selected' : '' }}>
                                        {{ $annee->name ?? ($annee->annee_debut . '-' . $annee->annee_fin) }}{{ $annee->is_current ? ' (En cours)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Période</label>
                            <select class="form-select" id="semestre" name="semestre">
                                <option value="">Toutes les périodes</option>
                                @foreach($periodes as $key => $periodeName)
                                    <option value="{{ $key }}" {{ isset($semestre) && $semestre == $key ? 'selected' : '' }}>
                                        {{ $periodeName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-2">
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
                                    Inclure tous les étudiants (même ceux avec des inscriptions inactives)
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <!-- Section principale des résultats -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-list"></i>
                    Liste des résultats
                </div>
                <div class="main-card-subtitle">
                    @if(isset($etudiants) && $etudiants->count() > 0)
                        {{ $classe->name ?? 'Tous les étudiants' }} - {{ $semestre ? 'Semestre '.$semestre : 'Toutes les périodes' }}
                        @if(isset($anneeUniversitaire))
                            - Année {{ $anneeUniversitaire->annee_debut }}-{{ $anneeUniversitaire->annee_fin }}
                        @endif
                    @else
                        Aucun résultat trouvé
                    @endif
                </div>
                @if(isset($classe) && isset($etudiants) && $etudiants->count() > 0)
                <div class="main-card-actions">
                    <a href="{{ route('esbtp.resultats.classe', ['classe' => $classe->id, 'annee_universitaire_id' => $annee_id, 'periode' => $semestre]) }}" class="btn-acasi primary">
                        <i class="fas fa-chart-bar"></i>Résultats détaillés
                    </a>
                </div>
                @endif
            </div>

            <div class="main-card-body">
                {{-- Nouveau système lazy loading --}}
                <div class="resultats-spinner {{ (isset($classe_id) || isset($annee_universitaire_id)) ? '' : 'hidden' }}" id="initial-spinner">
                    <div class="resultats-spinner-icon">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="resultats-spinner-text">Chargement des résultats...</div>
                </div>

                {{-- Container pour le contenu lazy-loadé --}}
                <div class="content-container" id="results-container" style="display: none;">
                    {{-- Le contenu sera injecté ici par JavaScript --}}
                </div>

                {{-- Message d'erreur en cas d'échec --}}
                <div class="error-state text-center py-5 hidden" id="error-state">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h5 class="text-muted">Erreur de chargement</h5>
                    <p class="text-muted">Une erreur est survenue lors du chargement des résultats.</p>
                    <button class="btn btn-primary" onclick="reloadResults()">
                        <i class="fas fa-refresh me-1"></i>Réessayer
                    </button>
                </div>

                {{-- Avertissement si aucune note --}}
                <div class="alert alert-warning mt-4" id="no-notes-warning" style="display: none;">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Aucune note trouvée.</strong> Vérifiez que :
                    <ul class="mb-0 mt-2">
                        <li>Les évaluations sont bien créées pour cette période</li>
                        <li>Les notes sont saisies et liées aux évaluations</li>
                        <li>Les coefficients des évaluations sont > 0</li>
                    </ul>
                </div>

                {{-- État initial : sélection de critères --}}
                <div class="text-center py-5 {{ (isset($classe_id) || isset($annee_universitaire_id)) ? 'd-none' : '' }}" id="initial-instructions">
                    <i class="fas fa-filter fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Sélectionnez vos critères</h5>
                    <p class="text-muted">Veuillez sélectionner une classe, une année universitaire et une période pour afficher les résultats.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2 only if available
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            width: '100%'
        });
    } else {
        console.log('Select2 not available, skipping initialization');
    }

    const ajaxUrl = '{{ route("esbtp.resultats.load-etudiants") }}';
    let currentFilters = {
        classe_id: @json($classe_id ?? null),
        annee_universitaire_id: @json($annee_universitaire_id ?? null),
        semestre: @json($semestre ?? null),
        include_all_statuses: @json(isset($include_all_statuses) ? (bool) $include_all_statuses : true)
    };
    let currentPage = 1;
    let isLoading = false;
    let totalLoadedStudents = 0;

    function shouldLoadResults() {
        return Boolean(currentFilters.classe_id) || Boolean(currentFilters.annee_universitaire_id);
    }

    // Intercepter la soumission du formulaire de filtres pour AJAX
    $('.filter-form').on('submit', function(e) {
        e.preventDefault();

        // Mettre à jour les filtres depuis le formulaire
        currentFilters = {
            classe_id: $('#classe_id').val() || null,
            annee_universitaire_id: $('#annee_universitaire_id').val() || null,
            semestre: $('#semestre').val() || null,
            include_all_statuses: $('#include_all_statuses').is(':checked')
        };

        // Réinitialiser la pagination
        currentPage = 1;
        totalLoadedStudents = 0;

        // Mettre à jour l'URL
        updateQueryString();

        // Charger les résultats
        loadEtudiants(1, { reset: true });
    });

    // Auto-select academic year when class is selected
    $('#classe_id').change(function() {
        const classeId = $(this).val();
        if (classeId) {
            // Make an AJAX request to get class details
            $.ajax({
                url: '/esbtp/api/classes/' + classeId,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data && data.annee_universitaire_id) {
                        $('#annee_universitaire_id').val(data.annee_universitaire_id).trigger('change');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching class data:', error);
                }
            });
        }
    });

    function showInitialSpinner() {
        $('#error-state').addClass('hidden');
        $('#no-notes-warning').hide();
        $('#results-container').hide();
        $('#initial-instructions').addClass('d-none');
        $('#initial-spinner').removeClass('hidden');
    }

    function hideInitialSpinner() {
        $('#initial-spinner').addClass('hidden');
    }

    function updateKpis(kpis) {
        if (!kpis) {
            return;
        }

        if (Object.prototype.hasOwnProperty.call(kpis, 'total_etudiants')) {
            $('#kpi-total-etudiants').text(kpis.total_etudiants ?? 0);
        }

        if (Object.prototype.hasOwnProperty.call(kpis, 'moyenne_generale')) {
            $('#kpi-moyenne-generale').text(kpis.moyenne_generale !== null ? kpis.moyenne_generale : 'N/A');
        }

        if (Object.prototype.hasOwnProperty.call(kpis, 'taux_reussite')) {
            $('#kpi-taux-reussite').text(kpis.taux_reussite !== null ? kpis.taux_reussite + '%' : 'N/A');
        }

        if (Object.prototype.hasOwnProperty.call(kpis, 'bulletins_count')) {
            $('#kpi-bulletins').text(kpis.bulletins_count ?? 0);
        }
    }

    function updateQueryString() {
        const params = new URLSearchParams();

        if (currentFilters.classe_id) {
            params.set('classe_id', currentFilters.classe_id);
        }

        if (currentFilters.annee_universitaire_id) {
            params.set('annee_universitaire_id', currentFilters.annee_universitaire_id);
        }

        if (currentFilters.semestre) {
            params.set('semestre', currentFilters.semestre);
        }

        if (currentFilters.include_all_statuses) {
            params.set('include_all_statuses', 1);
        }

        const newUrl = params.toString()
            ? `${window.location.pathname}?${params.toString()}`
            : window.location.pathname;

        window.history.replaceState({}, '', newUrl);
    }

    function showEmptyState() {
        const emptyHtml = `
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-info-circle fa-3x text-muted"></i>
                </div>
                <h5 class="text-muted">Aucun étudiant trouvé</h5>
                <p class="text-muted">Aucun étudiant ne correspond aux critères sélectionnés.</p>
            </div>
        `;
        $('#results-container').html(emptyHtml).show();
        $('#no-notes-warning').hide();
        $('#error-state').addClass('hidden');
    }

    // Fonction pour charger les étudiants (lazy loading)
    function loadEtudiants(page = 1, options = {}) {
        if (isLoading) {
            return;
        }

        const { reset = false } = options;

        if (!shouldLoadResults()) {
            hideInitialSpinner();
            $('#results-container').hide().empty();
            $('#error-state').addClass('hidden');
            $('#no-notes-warning').hide();
            $('#initial-instructions').removeClass('d-none');
            updateKpis({
                total_etudiants: 0,
                moyenne_generale: null,
                taux_reussite: null,
                bulletins_count: 0
            });
            return;
        }

        $('#initial-instructions').addClass('d-none');

        if (reset) {
            currentPage = 1;
            totalLoadedStudents = 0;
            showInitialSpinner();
        }

        isLoading = true;

        $.ajax({
            url: ajaxUrl,
            method: 'GET',
            data: {
                page: page,
                per_page: 50,
                classe_id: currentFilters.classe_id,
                semestre: currentFilters.semestre,
                annee_universitaire_id: currentFilters.annee_universitaire_id,
                include_all_statuses: currentFilters.include_all_statuses ? 1 : 0
            },
            success: function(response) {
                // Masquer le spinner avec la même méthode que les reinscriptions
                hideInitialSpinner();
                $('#error-state').addClass('hidden');
                
                if (page === 1) {
                    // Page 1: Remplacer tout le contenu
                    if (response.total === 0) {
                        showEmptyState();
                        totalLoadedStudents = 0;
                    } else {
                        $('#results-container').html(response.html);
                        totalLoadedStudents = response.loaded_count; // Initialiser le compteur
                    }
                } else {
                    // Pages suivantes: Ajouter les TR au tableau existant
                    const newRows = $(response.html);
                    $('#results-container tbody').append(newRows);
                    totalLoadedStudents += response.loaded_count; // Ajouter au compteur
                }
                
                // Forcer l'affichage du container avec la même approche que reinscriptions
                $('#results-container').show();
                $('#results-container').css({
                    'display': 'block !important',
                    'width': '100% !important',
                    'visibility': 'visible !important'
                });

                updateKpis(response.kpis || null);
                
                // Mettre à jour le bouton "Charger plus"
                updateLoadMoreButton(response);
                
                // Réinitialiser les event handlers pour les nouveaux éléments
                initializeEventHandlers();
                
                isLoading = false;
                currentPage = response.current_page || page;
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', error);
                showErrorState();
                isLoading = false;
            }
        });
    }

    // Fonction pour mettre à jour le bouton "Charger plus"
    function updateLoadMoreButton(response) {
        // Supprimer l'ancien bouton
        $('#results-container .load-more-container').remove();
        
        if (response.has_more) {
            const nextPage = (response.current_page || currentPage) + 1;
            const remainingStudents = Math.max(response.total - totalLoadedStudents, 0);
            const loadMoreHtml = `
                <div class="load-more-container" style="text-align: center; margin: 20px 0;">
                    <button class="btn btn-primary" onclick="loadMore(${nextPage})" style="padding: 12px 24px; border-radius: 8px;">
                        <i class="fas fa-plus"></i> Charger plus d'étudiants (${totalLoadedStudents}/${response.total} - ${remainingStudents} restants)
                    </button>
                </div>
            `;
            $('#results-container').append(loadMoreHtml);
        }
    }

    // Fonction pour charger plus d'étudiants
    window.loadMore = function(nextPage) {
        loadEtudiants(nextPage);
    };

    // Fonction pour afficher l'état d'erreur
    function showErrorState() {
        hideInitialSpinner();
        $('#results-container').hide();
        $('#initial-instructions').addClass('d-none');
        $('#error-state').removeClass('hidden');
    }

    // Fonction pour recharger les résultats
    window.reloadResults = function() {
        $('#error-state').addClass('hidden');
        loadEtudiants(1, { reset: true });
    };

    // Fonction pour initialiser les event handlers sur les nouveaux éléments
    function initializeEventHandlers() {
        // Handle bulk selection
        $('#select-all').off('change').on('change', function() {
            $('.student-checkbox').prop('checked', $(this).prop('checked'));
        });

        $('.student-checkbox').off('change').on('change', function() {
            const totalCheckboxes = $('.student-checkbox').length;
            const checkedCheckboxes = $('.student-checkbox:checked').length;
            $('#select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
        });

        // Search functionality
        $('.search-bar').off('input').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            $('#results-container tbody tr').each(function() {
                const studentName = $(this).find('td:nth-child(3) .fw-semibold').text().toLowerCase();
                const matricule = $(this).find('td:nth-child(2)').text().toLowerCase();
                
                if (studentName.includes(searchTerm) || matricule.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    }

    // Chargement initial automatique si des critères sont présents
    @if(isset($classe_id) || isset($annee_universitaire_id))
        loadEtudiants(1, { reset: true });
    @else
        hideInitialSpinner();
        $('#initial-instructions').removeClass('d-none');
    @endif

    // Rechargement lors du changement des filtres
    $('.filter-form').on('submit', function(event) {
        event.preventDefault();
        currentFilters = {
            classe_id: $('#classe_id').val() || null,
            annee_universitaire_id: $('#annee_universitaire_id').val() || null,
            semestre: $('#semestre').val() || null,
            include_all_statuses: $('#include_all_statuses').is(':checked')
        };
        updateQueryString();
        loadEtudiants(1, { reset: true });
    });
});
</script>
@endpush
