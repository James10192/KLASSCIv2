@extends('layouts.app')

@section('title', 'Liste des classes - ESBTP-yAKRO')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Gestion des Classes</h1>
                <p class="header-subtitle">Organisation et suivi des classes par filière et niveau</p>
            </div>
            <div class="header-actions">
                @if(auth()->user()->hasRole('superAdmin'))
                <a href="{{ route('esbtp.classes.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus-circle"></i>Nouvelle Classe
                </a>
                @endif
            </div>
        </div>
        <!-- Messages d'état -->
        @if(session('success'))
            <div class="card-moderne" style="background: rgba(16, 185, 129, 0.1); border-left: 4px solid var(--success); margin-bottom: var(--space-lg);">
                <div style="padding: var(--space-md);">
                    <div class="color-success font-semibold">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="card-moderne" style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid var(--danger); margin-bottom: var(--space-lg);">
                <div style="padding: var(--space-md);">
                    <div class="color-danger font-semibold">
                        <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                    </div>
                </div>
            </div>
        @endif

        <!-- Information année académique courante -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <div class="section-title mb-md">
                    <i class="fas fa-calendar me-2"></i>Contexte d'affichage
                </div>
                <div style="display: flex; gap: var(--space-md); align-items: end;">
                    <div style="flex: 1; max-width: 300px;">
                        <label for="annee_academique" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Année Académique Courante</label>
                        <select name="annee_academique" id="annee_academique" class="year-selector" style="width: 100%; background-color: #f8f9fa; cursor: not-allowed;" disabled>
                            <option value="{{ $anneeAcademique }}" selected>
                                {{ $anneeAcademique }} (Année en cours)
                            </option>
                        </select>
                    </div>
                    <button type="button" class="btn-acasi secondary" onclick="showYearChangeInfo()" title="Comment changer d'année ?">
                        <i class="fas fa-info-circle"></i>Changer d'année
                    </button>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Les classes sont visibles pour toutes les années, mais les étudiants affichés correspondent à l'année courante.
                    </small>
                </div>
            </div>
        </div>

        <!-- Filtres avancés -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <div class="section-title mb-md">
                    <i class="fas fa-filter me-2"></i>Filtres de recherche
                </div>
                <form method="GET" action="{{ route('esbtp.classes.index') }}" id="filtersForm">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-md); margin-bottom: var(--space-md);">
                        <!-- Recherche générale -->
                        <div>
                            <label for="search" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Recherche</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nom ou code de classe..." class="form-control" style="width: 100%;">
                        </div>
                        
                        <!-- Filière -->
                        <div>
                            <label for="filiere_id" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Filière</label>
                            <select name="filiere_id" id="filiere_id" class="form-control" style="width: 100%;">
                                <option value="">Toutes les filières</option>
                                @foreach($filieres as $filiere)
                                    <option value="{{ $filiere->id }}" {{ request('filiere_id') == $filiere->id ? 'selected' : '' }}>
                                        {{ $filiere->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Niveau -->
                        <div>
                            <label for="niveau_id" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Niveau</label>
                            <select name="niveau_id" id="niveau_id" class="form-control" style="width: 100%;">
                                <option value="">Tous les niveaux</option>
                                @foreach($niveaux as $niveau)
                                    <option value="{{ $niveau->id }}" {{ request('niveau_id') == $niveau->id ? 'selected' : '' }}>
                                        {{ $niveau->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        
                        <!-- Statut -->
                        <div>
                            <label for="statut" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Statut</label>
                            <select name="statut" id="statut" class="form-control" style="width: 100%;">
                                <option value="">Tous les statuts</option>
                                <option value="active" {{ request('statut') == 'active' ? 'selected' : '' }}>Actives</option>
                                <option value="inactive" {{ request('statut') == 'inactive' ? 'selected' : '' }}>Inactives</option>
                            </select>
                        </div>
                        
                        <!-- Capacité -->
                        <div>
                            <label for="capacite" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Capacité</label>
                            <select name="capacite" id="capacite" class="form-control" style="width: 100%;">
                                <option value="">Toutes</option>
                                <option value="disponible" {{ request('capacite') == 'disponible' ? 'selected' : '' }}>Disponibles</option>
                                <option value="pleine" {{ request('capacite') == 'pleine' ? 'selected' : '' }}>Pleines</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: var(--space-md); align-items: center; flex-wrap: wrap;">
                        <button type="submit" class="btn-acasi primary">
                            <i class="fas fa-search me-1"></i>Filtrer
                        </button>
                        <button type="button" id="reset-filters-btn" class="btn-acasi secondary">
                            <i class="fas fa-times me-1"></i>Réinitialiser
                        </button>

                        <!-- Boutons d'export -->
                        <div class="dropup" style="display: inline-block;">
                            <button type="button" class="btn-acasi secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-download"></i>Exporter
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="#" onclick="exportClasses('excel'); return false;">
                                        <i class="fas fa-file-excel text-success me-2"></i>Excel (.xlsx)
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" onclick="exportClasses('csv'); return false;">
                                        <i class="fas fa-file-csv text-info me-2"></i>CSV
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" onclick="exportClasses('pdf'); return false;">
                                        <i class="fas fa-file-pdf text-danger me-2"></i>PDF
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div style="margin-left: auto; font-size: var(--text-small); color: var(--text-muted);">
                            <i class="fas fa-list me-1"></i><span id="classes-count">{{ $classes->count() }}</span> classe(s) trouvée(s)
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistiques KPI -->
        <div class="kpi-grid">
            <div class="card-moderne kpi-card animate-slide-up">
                <div class="kpi-title">
                    <i class="fas fa-graduation-cap me-1"></i>Total Classes Actives
                </div>
                <div class="kpi-value color-primary">{{ $kpiStats['totalClasses'] }}</div>
                <div class="kpi-trend {{ $kpiStats['classesActives'] == $kpiStats['totalClasses'] ? 'positive' : 'negative' }}">
                    <i class="fas fa-{{ $kpiStats['classesActives'] == $kpiStats['totalClasses'] ? 'check' : 'exclamation' }}-circle"></i>
                    {{ $kpiStats['classesActives'] }} actives
                </div>
            </div>

            <div class="card-moderne kpi-card animate-slide-up">
                <div class="kpi-title">
                    <i class="fas fa-users me-1"></i>Étudiants Inscrits
                </div>
                <div class="kpi-value color-accent">{{ $kpiStats['totalEtudiants'] }}</div>
                <div class="kpi-trend positive">
                    <i class="fas fa-chart-line"></i>
                    Année {{ $anneeAcademique }}
                </div>
            </div>

            <div class="card-moderne kpi-card animate-slide-up">
                <div class="kpi-title">
                    <i class="fas fa-chair me-1"></i>Places Disponibles
                </div>
                <div class="kpi-value color-{{ $kpiStats['placesDisponibles'] > 0 ? 'success' : 'danger' }}">{{ $kpiStats['placesDisponibles'] }}</div>
                <div class="kpi-trend {{ $kpiStats['placesDisponibles'] > 0 ? 'positive' : 'negative' }}">
                    <i class="fas fa-{{ $kpiStats['placesDisponibles'] > 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                    sur {{ $kpiStats['totalPlaces'] }} places
                </div>
            </div>

            <div class="card-moderne kpi-card animate-slide-up">
                <div class="kpi-title">
                    <i class="fas fa-percentage me-1"></i>Taux Occupation
                </div>
                <div class="kpi-value color-{{ $kpiStats['tauxOccupation'] > 90 ? 'danger' : ($kpiStats['tauxOccupation'] > 70 ? 'warning' : 'success') }}">{{ $kpiStats['tauxOccupation'] }}%</div>
                <div class="kpi-trend {{ $kpiStats['tauxOccupation'] < 100 ? 'positive' : 'negative' }}">
                    <i class="fas fa-chart-pie"></i>
                    Occupation globale
                </div>
            </div>
        </div>

        <!-- Liste des classes en grid moderne -->
        <div class="card-moderne" style="padding: var(--space-lg);">
            <div class="section-title">
                <i class="fas fa-list me-2"></i>Classes par Filière et Niveau
            </div>

            <div id="classes-results">
                @include('esbtp.classes.partials.results', ['classes' => $classes])
            </div>
        </div>

    </div>
</div>
@endsection

@push('styles')
<style>
/* Styles spécifiques pour améliorer l'intégration avec dashboard-moderne.css */
.me-1 {
    margin-right: 0.25rem;
}

.me-2 {
    margin-right: 0.5rem;
}

/* Amélioration responsive pour les grilles */
@media (max-width: 768px) {
    .resultats-grid {
        grid-template-columns: 1fr !important;
    }
    
    .kpi-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

@media (max-width: 480px) {
    .kpi-grid {
        grid-template-columns: 1fr !important;
    }
}

/* Effets hover pour les cards de classe */
.resultat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}
</style>
@endpush

<!-- Modal pour les instructions de changement d'année -->
<div class="modal fade" id="yearChangeModal" tabindex="-1" role="dialog" aria-labelledby="yearChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="yearChangeModalLabel">Comment changer l'année académique ?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; font-weight: bold; color: #999; cursor: pointer;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Pour consulter les données d'une autre année :</strong></p>
                <ol style="padding-left: 20px; line-height: 1.6; margin: 15px 0;">
                    <li><strong>Aller dans</strong> : Menu → Années Universitaires</li>
                    <li><strong>Trouver l'année souhaitée</strong> (ex: 2023-2024)</li>
                    <li><strong>Cliquer sur "Activer"</strong> pour la définir comme année courante</li>
                    <li><strong>Revenir ici</strong> : Les étudiants affichés dans chaque classe se mettront à jour automatiquement</li>
                </ol>
                <hr style="margin: 15px 0;">
                <p style="color: #6b7280; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Note :</strong> Seule une année peut être "courante" à la fois. 
                    Changer l'année courante affecte l'affichage des étudiants dans toute l'application.
                </p>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong>Exemple :</strong><br>
                    • Année courante = 2024-2025 → Voir les étudiants inscrits en 2024-2025<br>
                    • Année courante = 2023-2024 → Voir les étudiants inscrits en 2023-2024
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="$('#yearChangeModal').modal('hide');">Fermer</button>
                <a href="{{ route('esbtp.annees-universitaires.index') }}" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt"></i> Aller aux Années
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function showYearChangeInfo() {
    $('#yearChangeModal').modal('show');
}

// Gérer la fermeture de la modal d'info année
$(document).ready(function() {
    // Gérer la fermeture avec le bouton X
    $('#yearChangeModal .close[data-dismiss="modal"]').on('click', function() {
        $('#yearChangeModal').modal('hide');
    });

    // Gérer la fermeture avec le bouton Fermer
    $('#yearChangeModal button[data-dismiss="modal"]').on('click', function() {
        $('#yearChangeModal').modal('hide');
    });
});
</script>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('filtersForm');
        const resultsContainer = document.getElementById('classes-results');
        const classesGrid = document.getElementById('classes-grid');
        const submitButton = form.querySelector('button[type="submit"]');
        const filterInputs = form.querySelectorAll('select, input[name="search"]');
        const resetBtn = document.getElementById('reset-filters-btn');
        const classesCountSpan = document.getElementById('classes-count');

        let currentPage = 1;
        let hasMorePages = false;
        let isLoading = false;
        let currentFilters = {};

        // Fonctions helper pour récupérer les éléments dynamiques
        function getLoadMoreBtn() {
            return document.getElementById('load-more-btn');
        }

        function getLoadMoreSpinner() {
            return document.getElementById('load-more-spinner');
        }

        // Initialiser Select2 si disponible
        if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
            $('#filiere_id, #niveau_id, #statut, #capacite').select2({
                theme: 'bootstrap4',
                placeholder: 'Sélectionner une option',
                allowClear: true
            });
        }

        function setLoading(loading) {
            isLoading = loading;
            if (submitButton) {
                submitButton.disabled = loading;
            }
        }

        function updateLoadMoreButton(hasMore) {
            hasMorePages = hasMore;
            const btn = getLoadMoreBtn();
            const spinner = getLoadMoreSpinner();

            if (btn && spinner) {
                if (hasMore) {
                    btn.style.display = 'inline-flex';
                    spinner.classList.add('d-none');
                } else {
                    btn.style.display = 'none';
                    spinner.classList.add('d-none');
                }
            }
        }

        function fetchResults(reset = true) {
            if (isLoading) return;

            setLoading(true);

            if (reset) {
                currentPage = 1;
                if (classesGrid) {
                    classesGrid.innerHTML = '';
                }
            }

            const formData = new FormData(form);
            formData.set('page', currentPage);
            const params = new URLSearchParams(formData);
            const targetUrl = `${form.action}?${params.toString()}`;

            // Sauvegarder les filtres courants
            currentFilters = Object.fromEntries(formData);

            fetch(targetUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors du chargement des classes.');
                }
                return response.json();
            })
            .then(data => {
                if (reset) {
                    // Remplacer tout le contenu
                    resultsContainer.innerHTML = `
                        <div class="resultats-grid" id="classes-grid" style="margin-top: var(--space-lg);">
                            ${data.html}
                        </div>
                        <div id="load-more-container" class="text-center" style="margin-top: var(--space-lg);">
                            <button type="button" id="load-more-btn" class="btn-acasi primary" style="display: none;">
                                <i class="fas fa-angle-down me-2"></i>Charger plus de classes
                            </button>
                            <div id="load-more-spinner" class="d-none" style="padding: var(--space-md);">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    // Ajouter à la fin
                    const grid = document.getElementById('classes-grid');
                    if (grid) {
                        grid.insertAdjacentHTML('beforeend', data.html);
                    }
                }

                // Toujours rebinder et mettre à jour après chargement
                bindLoadMore();
                updateLoadMoreButton(data.hasMore);

                // Mettre à jour le compteur si disponible
                if (data.total && classesCountSpan) {
                    classesCountSpan.textContent = data.total;
                }

                setLoading(false);
            })
            .catch(error => {
                debugError(error);
                alert('Impossible de charger les classes. Veuillez réessayer.');
                setLoading(false);
            });
        }

        function bindLoadMore() {
            const btn = getLoadMoreBtn();
            const spinner = getLoadMoreSpinner();

            if (btn && spinner) {
                // Supprimer ancien listener si existe
                const newBtn = btn.cloneNode(true);
                btn.parentNode.replaceChild(newBtn, btn);

                newBtn.addEventListener('click', function() {
                    if (isLoading || !hasMorePages) return;

                    newBtn.style.display = 'none';
                    spinner.classList.remove('d-none');

                    currentPage++;
                    fetchResults(false); // false = ne pas reset, juste ajouter
                });
            }
        }

        // Event listeners
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            event.stopPropagation();
            fetchResults(true); // true = reset
            return false;
        });

        filterInputs.forEach((input) => {
            input.addEventListener('change', () => {
                fetchResults(true); // true = reset
            });
        });

        // Bouton Réinitialiser
        if (resetBtn) {
            resetBtn.addEventListener('click', function(e) {
                e.preventDefault();

                // Réinitialiser le formulaire
                form.reset();

                // Réinitialiser les Select2 si présents
                if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
                    $('#filiere_id, #niveau_id, #statut, #capacite').val(null).trigger('change');
                }

                // Recharger les résultats
                fetchResults(true);
            });
        }

        // Bind initial load more button
        bindLoadMore();

        // Initialiser hasMorePages depuis l'attribut data du bouton
        const initialBtn = getLoadMoreBtn();
        if (initialBtn) {
            const initialHasMore = initialBtn.getAttribute('data-has-more') === 'true';
            updateLoadMoreButton(initialHasMore);
        }
    });

    /**
     * Fonction pour exporter les classes selon le format choisi
     * @param {string} format - 'excel', 'csv' ou 'pdf'
     */
    function exportClasses(format) {
        // Récupérer les paramètres de filtrage actuels depuis l'URL
        const urlParams = new URLSearchParams(window.location.search);

        // Construction de l'URL d'export selon le format
        let exportUrl = '';
        switch(format) {
            case 'excel':
                exportUrl = '{{ route("esbtp.classes.export.excel") }}';
                break;
            case 'csv':
                exportUrl = '{{ route("esbtp.classes.export.csv") }}';
                break;
            case 'pdf':
                exportUrl = '{{ route("esbtp.classes.export.pdf") }}';
                break;
            default:
                debugError('Format d\'export non reconnu:', format);
                return;
        }

        // Ajouter les paramètres de filtrage à l'URL d'export
        const exportParams = new URLSearchParams();
        if (urlParams.has('filiere_id')) exportParams.set('filiere_id', urlParams.get('filiere_id'));
        if (urlParams.has('niveau_id')) exportParams.set('niveau_id', urlParams.get('niveau_id'));
        if (urlParams.has('statut')) exportParams.set('statut', urlParams.get('statut'));
        if (urlParams.has('capacite')) exportParams.set('capacite', urlParams.get('capacite'));
        if (urlParams.has('search')) exportParams.set('search', urlParams.get('search'));

        // Construire l'URL finale avec les paramètres
        const finalUrl = exportParams.toString() ? `${exportUrl}?${exportParams.toString()}` : exportUrl;

        // Rediriger vers l'URL d'export (déclenche le téléchargement)
        window.location.href = finalUrl;
    }
</script>
@endpush
