@extends('layouts.app')

@section('title', 'Résultats des étudiants — KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="{{ asset('css/student-results.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- Hero --}}
        <div class="sr-hero sr-animate">
            <div class="sr-hero-content">
                <div class="sr-hero-left">
                    <div class="sr-hero-avatar"><i class="fas fa-chart-bar"></i></div>
                    <div class="sr-hero-info">
                        <h1>Résultats des étudiants</h1>
                        <p>Consultez et gérez les résultats scolaires de l'établissement</p>
                    </div>
                </div>
                <div class="sr-hero-actions">
                    <a href="{{ route('esbtp.resultats.classes') }}" class="sr-hero-btn">
                        <i class="fas fa-layer-group"></i>Classes
                    </a>
                    @can('bulletins.configure')
                        <a href="{{ route('esbtp.bulletins.configuration') }}" class="sr-hero-btn--solid sr-hero-btn">
                            <i class="fas fa-cog"></i>Configuration
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        {{-- KPIs --}}
        <div class="sr-stats sr-animate sr-animate-delay-1" style="margin-bottom: 1.5rem;">
            <div class="sr-stat sr-stat--primary">
                <div class="sr-stat-icon"><i class="fas fa-users"></i></div>
                <div class="sr-stat-value" id="kpi-total-etudiants">{{ $totalEtudiants }}</div>
                <div class="sr-stat-label">Étudiants</div>
            </div>
            <div class="sr-stat sr-stat--info">
                <div class="sr-stat-icon"><i class="fas fa-calculator"></i></div>
                <div class="sr-stat-value" id="kpi-moyenne-generale">N/A</div>
                <div class="sr-stat-label">Moy. générale</div>
            </div>
            <div class="sr-stat sr-stat--success">
                <div class="sr-stat-icon"><i class="fas fa-percentage"></i></div>
                <div class="sr-stat-value" id="kpi-taux-reussite">N/A</div>
                <div class="sr-stat-label">Réussite</div>
            </div>
            <div class="sr-stat sr-stat--warning">
                <div class="sr-stat-icon"><i class="fas fa-file-alt"></i></div>
                <div class="sr-stat-value" id="kpi-bulletins">0</div>
                <div class="sr-stat-label">Bulletins</div>
            </div>
        </div>

        {{-- Filtres --}}
        <div class="sr-filter-bar sr-animate sr-animate-delay-2">
            <form class="filter-form">
                <div class="sr-filter-row">
                    <div class="sr-filter-group" style="flex: 2;">
                        <label class="sr-filter-label">Classe</label>
                        <select class="sr-filter-select" id="classe_id" name="classe_id">
                            <option value="">Toutes les classes</option>
                            @foreach($classes ?? [] as $c)
                                <option value="{{ $c->id }}" {{ isset($classe_id) && $classe_id == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} {{ $c->filiere ? '(' . $c->filiere->name . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sr-filter-group">
                        <label class="sr-filter-label">Année</label>
                        <select class="sr-filter-select" id="annee_universitaire_id" name="annee_universitaire_id">
                            <option value="">Toutes</option>
                            @foreach($annees_universitaires ?? [] as $annee)
                                <option value="{{ $annee->id }}" {{ isset($annee_universitaire_id) && $annee_universitaire_id == $annee->id ? 'selected' : '' }}>
                                    {{ $annee->name ?? ($annee->annee_debut . '-' . $annee->annee_fin) }}{{ $annee->is_current ? ' *' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sr-filter-group">
                        <label class="sr-filter-label">Période</label>
                        <select class="sr-filter-select" id="semestre" name="semestre">
                            @foreach($periodes ?? [] as $key => $nom)
                                <option value="{{ $key }}" {{ isset($semestre) && $semestre == $key ? 'selected' : '' }}>
                                    {{ $nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sr-filter-group" style="flex: 0 0 auto; min-width: auto;">
                        <label class="sr-filter-label">&nbsp;</label>
                        <button type="submit" class="sr-filter-btn">
                            <i class="fas fa-search"></i>Filtrer
                        </button>
                    </div>
                </div>
                <label class="sr-filter-toggle">
                    <input type="checkbox" id="include_all_statuses" name="include_all_statuses" value="1"
                           {{ isset($include_all_statuses) && $include_all_statuses ? 'checked' : '' }}>
                    <span class="sr-toggle-track"></span>
                    <span>Inclure inscriptions inactives</span>
                </label>
            </form>
        </div>

        {{-- Contenu principal --}}
        <div class="sr-table-card sr-animate sr-animate-delay-3">
            <div class="sr-table-header">
                <div class="sr-table-header-left">
                    <i class="fas fa-list-ol"></i>
                    <h3>Liste des résultats</h3>
                </div>
                <input type="search" class="search-bar sr-filter-select" placeholder="Rechercher..." style="max-width: 220px; font-size: 0.82rem;">
            </div>

            {{-- Instructions initiales --}}
            <div id="initial-instructions" class="{{ (isset($classe_id) || isset($annee_universitaire_id)) ? 'd-none' : '' }}">
                <div class="sr-empty">
                    <i class="fas fa-filter"></i>
                    <h3>Sélectionnez des filtres</h3>
                    <p>Choisissez une classe et/ou une année universitaire pour afficher les résultats.</p>
                </div>
            </div>

            {{-- Spinner de chargement --}}
            <div id="initial-spinner" style="display: none; padding: 3rem; text-align: center;">
                <div class="sr-loading-spinner" style="box-shadow: none; border: none; display: inline-flex;">
                    <div class="sr-loading-spinner-circle"></div>
                    <div class="sr-loading-spinner-text">Chargement des résultats...</div>
                </div>
            </div>

            {{-- Erreur --}}
            <div id="error-state" style="display: none;">
                <div class="sr-empty">
                    <i class="fas fa-exclamation-triangle" style="color: var(--sr-danger);"></i>
                    <h3>Erreur de chargement</h3>
                    <p>Impossible de charger les résultats.</p>
                    <button onclick="reloadResults()" class="sr-filter-btn" style="margin-top: 1rem;">
                        <i class="fas fa-redo"></i>Réessayer
                    </button>
                </div>
            </div>

            {{-- Résultats (rempli par AJAX) --}}
            <div id="results-container" style="display: none;"></div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Select2 si dispo
    if (typeof $.fn.select2 !== 'undefined') {
        $('#classe_id, #annee_universitaire_id').select2({ theme: 'bootstrap-5', width: '100%', allowClear: true, placeholder: 'Sélectionner...' });
    }

    var ajaxUrl = '{{ route("esbtp.resultats.load-etudiants") }}';
    var currentPage = 1;
    var isLoading = false;
    var totalLoadedStudents = 0;

    var currentFilters = {
        classe_id: '{{ $classe_id ?? "" }}' || null,
        annee_universitaire_id: '{{ $annee_universitaire_id ?? "" }}' || null,
        semestre: '{{ $semestre ?? "" }}' || null,
        include_all_statuses: {{ (isset($include_all_statuses) && $include_all_statuses) ? 'true' : 'false' }}
    };

    function shouldLoadResults() {
        return Boolean(currentFilters.classe_id) || Boolean(currentFilters.annee_universitaire_id);
    }

    // Form submit → AJAX
    $('.filter-form').on('submit', function(e) {
        e.preventDefault();
        currentFilters = {
            classe_id: $('#classe_id').val() || null,
            annee_universitaire_id: $('#annee_universitaire_id').val() || null,
            semestre: $('#semestre').val() || null,
            include_all_statuses: $('#include_all_statuses').is(':checked')
        };
        currentPage = 1;
        totalLoadedStudents = 0;
        updateQueryString();
        loadEtudiants(1, { reset: true });
    });

    // Auto-select year when class is selected
    $('#classe_id').change(function() {
        var classeId = $(this).val();
        if (classeId) {
            $.ajax({
                url: '/esbtp/api/classes/' + classeId,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data && data.annee_universitaire_id) {
                        $('#annee_universitaire_id').val(data.annee_universitaire_id).trigger('change');
                    }
                }
            });
        }
    });

    function showInitialSpinner() {
        $('#error-state').hide();
        $('#results-container').hide();
        $('#initial-instructions').addClass('d-none');
        $('#initial-spinner').show();
    }

    function hideInitialSpinner() {
        $('#initial-spinner').hide();
    }

    function updateKpis(kpis) {
        if (!kpis) return;
        if (kpis.hasOwnProperty('total_etudiants')) $('#kpi-total-etudiants').text(kpis.total_etudiants ?? 0);
        if (kpis.hasOwnProperty('moyenne_generale')) $('#kpi-moyenne-generale').text(kpis.moyenne_generale !== null ? kpis.moyenne_generale : 'N/A');
        if (kpis.hasOwnProperty('taux_reussite')) $('#kpi-taux-reussite').text(kpis.taux_reussite !== null ? kpis.taux_reussite + '%' : 'N/A');
        if (kpis.hasOwnProperty('bulletins_count')) $('#kpi-bulletins').text(kpis.bulletins_count ?? 0);
    }

    function updateQueryString() {
        var params = new URLSearchParams();
        if (currentFilters.classe_id) params.set('classe_id', currentFilters.classe_id);
        if (currentFilters.annee_universitaire_id) params.set('annee_universitaire_id', currentFilters.annee_universitaire_id);
        if (currentFilters.semestre) params.set('semestre', currentFilters.semestre);
        if (currentFilters.include_all_statuses) params.set('include_all_statuses', 1);
        var newUrl = params.toString() ? window.location.pathname + '?' + params.toString() : window.location.pathname;
        window.history.replaceState({}, '', newUrl);
    }

    function showEmptyState() {
        $('#results-container').html('<div class="sr-empty"><i class="fas fa-inbox"></i><h3>Aucun étudiant trouvé</h3><p>Aucun étudiant ne correspond aux critères.</p></div>').show();
        $('#error-state').hide();
    }

    function loadEtudiants(page, options) {
        page = page || 1;
        options = options || {};
        var reset = options.reset || false;

        if (isLoading) return;

        if (!shouldLoadResults()) {
            hideInitialSpinner();
            $('#results-container').hide().empty();
            $('#error-state').hide();
            $('#initial-instructions').removeClass('d-none');
            updateKpis({ total_etudiants: 0, moyenne_generale: null, taux_reussite: null, bulletins_count: 0 });
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
                hideInitialSpinner();
                $('#error-state').hide();

                if (page === 1) {
                    if (response.total === 0) {
                        showEmptyState();
                        totalLoadedStudents = 0;
                    } else {
                        $('#results-container').html(response.html);
                        totalLoadedStudents = response.loaded_count;
                    }
                } else {
                    var newRows = $(response.html);
                    $('#results-container tbody').append(newRows);
                    totalLoadedStudents += response.loaded_count;
                }

                $('#results-container').show();
                updateKpis(response.kpis || null);
                updateLoadMoreButton(response);
                initializeEventHandlers();

                isLoading = false;
                currentPage = response.current_page || page;
            },
            error: function() {
                showErrorState();
                isLoading = false;
            }
        });
    }

    function updateLoadMoreButton(response) {
        $('#results-container .load-more-container').remove();
        if (response.has_more) {
            var nextPage = (response.current_page || currentPage) + 1;
            var remaining = Math.max(response.total - totalLoadedStudents, 0);
            $('#results-container').append(
                '<div class="load-more-container" style="text-align:center;padding:1.5rem;">' +
                '<button class="sr-filter-btn" onclick="loadMore(' + nextPage + ')" style="padding:0.6rem 1.5rem;">' +
                '<i class="fas fa-plus"></i> Charger plus (' + totalLoadedStudents + '/' + response.total + ' — ' + remaining + ' restants)' +
                '</button></div>'
            );
        }
    }

    window.loadMore = function(nextPage) { loadEtudiants(nextPage); };

    function showErrorState() {
        hideInitialSpinner();
        $('#results-container').hide();
        $('#initial-instructions').addClass('d-none');
        $('#error-state').show();
    }

    window.reloadResults = function() {
        $('#error-state').hide();
        loadEtudiants(1, { reset: true });
    };

    function initializeEventHandlers() {
        $('#select-all').off('change').on('change', function() {
            $('.student-checkbox').prop('checked', $(this).prop('checked'));
        });
        $('.student-checkbox').off('change').on('change', function() {
            var total = $('.student-checkbox').length;
            var checked = $('.student-checkbox:checked').length;
            $('#select-all').prop('checked', total === checked);
        });
        $('.search-bar').off('input').on('input', function() {
            var term = $(this).val().toLowerCase();
            $('#results-container tbody tr').each(function() {
                var name = $(this).find('td:nth-child(3) .fw-semibold').text().toLowerCase();
                var mat = $(this).find('td:nth-child(2)').text().toLowerCase();
                $(this).toggle(name.includes(term) || mat.includes(term));
            });
        });
    }

    // Chargement initial
    @if(isset($classe_id) || isset($annee_universitaire_id))
        loadEtudiants(1, { reset: true });
    @else
        hideInitialSpinner();
        $('#initial-instructions').removeClass('d-none');
    @endif
});
</script>
@endpush
