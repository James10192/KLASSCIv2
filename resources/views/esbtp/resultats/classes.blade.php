@extends('layouts.app')

@section('title', 'Sélection de classe — KLASSCI')

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
                    <div class="sr-hero-avatar">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="sr-hero-info">
                        <h1>Sélection des classes</h1>
                        <p>Choisissez une classe pour consulter ses résultats</p>
                    </div>
                </div>
                <div class="sr-hero-actions">
                    <a href="{{ route('esbtp.resultats.index') }}" class="sr-hero-btn">
                        <i class="fas fa-chart-line"></i>Résultats directs
                    </a>
                </div>
            </div>
        </div>

        {{-- KPIs --}}
        <div class="sr-stats sr-animate sr-animate-delay-1" style="margin-bottom: 1.5rem;">
            <div class="sr-stat sr-stat--primary">
                <div class="sr-stat-icon"><i class="fas fa-school"></i></div>
                <div class="sr-stat-value" id="kpi-classes">{{ $totalClasses }}</div>
                <div class="sr-stat-label">Classes</div>
            </div>
            <div class="sr-stat sr-stat--info">
                <div class="sr-stat-icon"><i class="fas fa-sitemap"></i></div>
                <div class="sr-stat-value" id="kpi-filieres">{{ $totalFilieres }}</div>
                <div class="sr-stat-label">Filières</div>
            </div>
            <div class="sr-stat sr-stat--warning">
                <div class="sr-stat-icon"><i class="fas fa-layer-group"></i></div>
                <div class="sr-stat-value" id="kpi-niveaux">{{ $totalNiveaux }}</div>
                <div class="sr-stat-label">Niveaux</div>
            </div>
            <div class="sr-stat sr-stat--success">
                <div class="sr-stat-icon"><i class="fas fa-users"></i></div>
                <div class="sr-stat-value" id="kpi-etudiants">{{ $totalEtudiants }}</div>
                <div class="sr-stat-label">Étudiants</div>
            </div>
        </div>

        {{-- Filtres --}}
        <div class="sr-filter-bar sr-animate sr-animate-delay-2">
            <form id="filter-form" class="filter-form">
                <div class="sr-filter-row">
                    <div class="sr-filter-group">
                        <label class="sr-filter-label">Recherche</label>
                        <input type="text" name="search" id="search" class="sr-filter-select" placeholder="Nom ou code..." value="{{ request('search') }}">
                    </div>
                    <div class="sr-filter-group">
                        <label class="sr-filter-label">Filière</label>
                        <select class="sr-filter-select" name="filiere_id" id="filiere_id">
                            <option value="">Toutes</option>
                            @foreach($filieres as $filiere)
                                <option value="{{ $filiere->id }}" {{ request('filiere_id') == $filiere->id ? 'selected' : '' }}>{{ $filiere->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sr-filter-group">
                        <label class="sr-filter-label">Niveau</label>
                        <select class="sr-filter-select" name="niveau_id" id="niveau_id">
                            <option value="">Tous</option>
                            @foreach($niveaux as $niveau)
                                <option value="{{ $niveau->id }}" {{ request('niveau_id') == $niveau->id ? 'selected' : '' }}>{{ $niveau->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sr-filter-group">
                        <label class="sr-filter-label">Année</label>
                        <select class="sr-filter-select" name="annee_universitaire_id" id="annee_universitaire_id">
                            <option value="">Toutes</option>
                            @foreach($annees_universitaires as $annee)
                                <option value="{{ $annee->id }}" {{ (string) $annee_universitaire_id === (string) $annee->id ? 'selected' : '' }}>
                                    {{ $annee->name ?? ($annee->annee_debut . '-' . $annee->annee_fin) }}{{ $annee->is_current ? ' *' : '' }}
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
            </form>
        </div>

        {{-- Grille classes --}}
        <div class="sr-table-card sr-animate sr-animate-delay-3">
            <div class="sr-table-header">
                <div class="sr-table-header-left">
                    <i class="fas fa-th-large"></i>
                    <h3>Classes disponibles</h3>
                </div>
                <span class="sr-table-count" id="classes-count">{{ $classes->count() }} classes</span>
            </div>
            <div style="padding: 1rem;" id="classes-grid-container">
                @include('esbtp.resultats.partials.classes-grid', ['classes' => $classes])
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let searchTimeout = null;

    function filterClasses() {
        const container = $('#classes-grid-container');
        container.css({ 'opacity': '0.5', 'pointer-events': 'none' });

        const formParams = $('#filter-form').serializeArray().filter(function(item) { return item.value !== ''; });

        $.ajax({
            url: '{{ route("esbtp.resultats.classes") }}',
            method: 'GET',
            data: formParams,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(response) {
                container.css({ 'pointer-events': 'auto', 'opacity': '1' });
                container.html(response.html);

                if (response.kpis) {
                    $('#kpi-classes').text(response.kpis.totalClasses);
                    $('#kpi-filieres').text(response.kpis.totalFilieres);
                    $('#kpi-niveaux').text(response.kpis.totalNiveaux);
                    $('#kpi-etudiants').text(response.kpis.totalEtudiants);
                }

                var queryStr = $.param(formParams);
                var newUrl = queryStr ? '{{ route("esbtp.resultats.classes") }}?' + queryStr : '{{ route("esbtp.resultats.classes") }}';
                window.history.pushState({}, '', newUrl);
            },
            error: function() {
                container.css({ 'pointer-events': 'auto', 'opacity': '1' });
            }
        });
    }

    $('#filter-form').on('submit', function(e) { e.preventDefault(); filterClasses(); });
    $('#filter-form select').on('change', function() { filterClasses(); });
    $('#search').on('input', function() {
        if (searchTimeout) clearTimeout(searchTimeout);
        searchTimeout = setTimeout(filterClasses, 400);
    });
});
</script>
@endpush
