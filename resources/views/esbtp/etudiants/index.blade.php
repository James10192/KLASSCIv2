@extends('layouts.app')

@section('title', 'Gestion des étudiants - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
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
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('search-form');
        const resultsContainer = document.getElementById('etudiants-results');
        const submitButton = form.querySelector('button[type="submit"]');
        const filterInputs = form.querySelectorAll('select');

        if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
            $('#filiere, #niveau, #annee, #status, #affectation_status, #inscrit_annee_courante').select2({
                theme: 'bootstrap4',
                placeholder: 'Sélectionner une option',
                allowClear: true
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
    });
</script>
@endpush
