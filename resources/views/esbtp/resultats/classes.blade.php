@extends('layouts.app')

@section('title', 'Sélection de classe - ESBTP')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
.class-card {
    border: 1px solid #e5e7eb;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.class-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 15px 30px rgba(15, 23, 42, 0.15);
}

.icon-bubble {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.2rem;
}

.search-bar {
    max-width: 260px;
}

.badge-light-muted {
    background-color: #f1f5f9;
    color: #0f172a;
    border: 1px solid #e2e8f0;
}
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-layer-group me-2"></i>Sélection des classes</h1>
                <p class="header-subtitle">Choisissez une classe pour consulter rapidement ses résultats.</p>
            </div>
            <div class="header-actions">
                <input type="search" class="search-bar" id="classe-search" placeholder="Rechercher une classe...">
                <a href="{{ route('esbtp.resultats.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-chart-line"></i>Voir les résultats
                </a>
            </div>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Total Classes</div>
                <div class="kpi-value" id="kpi-classes" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $totalClasses }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-school me-1"></i>Classes actives{{ $annee_universitaire_id ? ' filtrées' : '' }}
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Filières représentées</div>
                <div class="kpi-value" id="kpi-filieres" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $totalFilieres }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-layer-group me-1"></i>Filières uniques
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Niveaux couverts</div>
                <div class="kpi-value" id="kpi-niveaux" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ $totalNiveaux }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-graduation-cap me-1"></i>Niveaux académiques
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Étudiants actifs</div>
                <div class="kpi-value" id="kpi-etudiants" style="color: #10b981; font-size: 2.5rem; font-weight: bold;">{{ $totalEtudiants }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-calendar me-1"></i>
                    <span id="annee-label">
                        @if($selectedAnnee)
                            Année {{ $selectedAnnee->name ?? ($selectedAnnee->annee_debut . '-' . $selectedAnnee->annee_fin) }}
                        @else
                            Toutes années confondues
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-filter"></i>
                    Filtrer les classes
                </div>
                <div class="main-card-subtitle">Affinez la liste des classes disponibles</div>
            </div>
            <div class="main-card-body">
                <form id="filter-form" class="row g-3">
                    <div class="col-6 col-md-4">
                        <label class="form-label">Recherche</label>
                        <input type="text" name="search" id="search" class="form-select" placeholder="Nom ou code..." value="{{ request('search') }}">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Filière</label>
                        <select class="form-select" name="filiere_id" id="filiere_id">
                            <option value="">Toutes les filières</option>
                            @foreach($filieres as $filiere)
                                <option value="{{ $filiere->id }}" {{ request('filiere_id') == $filiere->id ? 'selected' : '' }}>{{ $filiere->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Niveau</label>
                        <select class="form-select" name="niveau_id" id="niveau_id">
                            <option value="">Tous les niveaux</option>
                            @foreach($niveaux as $niveau)
                                <option value="{{ $niveau->id }}" {{ request('niveau_id') == $niveau->id ? 'selected' : '' }}>{{ $niveau->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Statut</label>
                        <select class="form-select" name="statut" id="statut">
                            <option value="">Tous les statuts</option>
                            <option value="active" {{ request('statut') == 'active' ? 'selected' : '' }}>Actives</option>
                            <option value="inactive" {{ request('statut') == 'inactive' ? 'selected' : '' }}>Inactives</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Année universitaire</label>
                        <select class="form-select select2" name="annee_universitaire_id" id="annee_universitaire_id">
                            <option value="">Toutes les années</option>
                            @foreach($annees_universitaires as $annee)
                                <option value="{{ $annee->id }}" {{ (string) $annee_universitaire_id === (string) $annee->id ? 'selected' : '' }}>
                                    {{ $annee->name ?? ($annee->annee_debut . '-' . $annee->annee_fin) }}
                                    {{ $annee->is_current ? ' (Année courante)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i>Filtrer
                        </button>
                    </div>
                    <div class="col-6 col-md-2 d-flex align-items-end">
                        <button type="button" id="reset-btn" class="btn btn-light w-100">
                            <i class="fas fa-undo me-1"></i>Réinitialiser
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-bars"></i>
                    Classes disponibles
                </div>
                <div class="main-card-subtitle">
                    @if($annee_universitaire_id)
                        Année universitaire sélectionnée
                    @else
                        Toutes les années universitaires
                    @endif
                </div>
            </div>
            <div class="main-card-body" id="classes-grid-container">
                @include('esbtp.resultats.partials.classes-grid', ['classes' => $classes])
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({ width: '100%' });
    }

    let searchTimeout = null;

    function filterClasses() {
        const container = $('#classes-grid-container');
        container.css({ 'position': 'relative', 'pointer-events': 'none', 'opacity': '0.5' });

        $('#loading-overlay').remove();
        const loadingOverlay = $('<div>')
            .attr('id', 'loading-overlay')
            .css({ 'position': 'absolute', 'top': '50%', 'left': '50%', 'transform': 'translate(-50%, -50%)', 'z-index': '1000' })
            .html('<div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"><span class="visually-hidden">Chargement...</span></div>');
        container.append(loadingOverlay);

        // Serialiser tous les champs du formulaire (exclure les valeurs vides pour une URL propre)
        const formParams = $('#filter-form').serializeArray().filter(function(item) {
            return item.value !== '';
        });

        $.ajax({
            url: '{{ route("esbtp.resultats.classes") }}',
            method: 'GET',
            data: formParams,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(response) {
                $('#loading-overlay').remove();
                container.css({ 'pointer-events': 'auto', 'opacity': '1' });

                container.html(response.html);

                if (response.kpis) {
                    $('#kpi-classes').text(response.kpis.totalClasses);
                    $('#kpi-filieres').text(response.kpis.totalFilieres);
                    $('#kpi-niveaux').text(response.kpis.totalNiveaux);
                    $('#kpi-etudiants').text(response.kpis.totalEtudiants);
                }

                if (response.selectedAnnee) {
                    const anneeLabel = response.selectedAnnee.name
                        || response.selectedAnnee.label
                        || (response.selectedAnnee.annee_debut + '-' + response.selectedAnnee.annee_fin);
                    $('#annee-label').text('Année ' + anneeLabel);
                } else {
                    $('#annee-label').text('Toutes années confondues');
                }

                const queryStr = $.param(formParams);
                const newUrl = queryStr
                    ? '{{ route("esbtp.resultats.classes") }}?' + queryStr
                    : '{{ route("esbtp.resultats.classes") }}';
                window.history.pushState({}, '', newUrl);

                bindLocalSearch();
            },
            error: function(xhr, status, error) {
                $('#loading-overlay').remove();
                container.css({ 'pointer-events': 'auto', 'opacity': '1' });
                alert('Une erreur est survenue lors du filtrage.');
            }
        });
    }

    function bindLocalSearch() {
        $('#classe-search').off('input').on('input', function() {
            const term = $(this).val().toLowerCase();
            $('.class-card-wrapper').each(function() {
                const name = ($(this).data('name') || '').toString().toLowerCase();
                if (!term || name.includes(term)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    }

    // Soumission du formulaire
    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        filterClasses();
    });

    // Auto-filtrage sur changement des selects
    $('#filter-form select').on('change', function() {
        filterClasses();
    });

    // Auto-filtrage sur la recherche avec débounce 400ms
    $('#search').on('input', function() {
        if (searchTimeout) clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            filterClasses();
        }, 400);
    });

    // Réinitialiser tous les filtres
    $('#reset-btn').on('click', function() {
        $('#search').val('');
        $('#filiere_id, #niveau_id, #statut, #annee_universitaire_id').val('');
        filterClasses();
    });

    bindLocalSearch();
});
</script>
@endpush
