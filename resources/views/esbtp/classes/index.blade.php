@extends('layouts.app')

@section('title', 'Liste des classes - KLASSCI')

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
                <button type="button" class="btn-acasi primary" id="btn-open-create-modal">
                    <i class="fas fa-plus-circle"></i>Nouvelle Classe
                </button>
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

{{-- Modal Création Classe AJAX --}}
<div class="modal fade" id="createClasseModal" tabindex="-1" aria-labelledby="createClasseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createClasseModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Nouvelle Classe
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modal-create-body">
                {{-- Chargé via AJAX --}}
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="button" class="btn btn-primary" id="modal-create-submit-btn" disabled>
                    <i class="fas fa-save"></i> Enregistrer la classe
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Édition Classe AJAX --}}
<div class="modal fade" id="editClasseModal" tabindex="-1" aria-labelledby="editClasseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editClasseModalLabel">
                    <i class="fas fa-edit me-2"></i>Modifier la Classe
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modal-edit-body">
                {{-- Chargé via AJAX --}}
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="button" class="btn btn-primary" id="modal-edit-submit-btn" disabled>
                    <i class="fas fa-save"></i> Mettre à jour la classe
                </button>
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

    // ========================================
    // GESTION DES MODALS AJAX (Création + Édition)
    // ========================================

    /**
     * Initialise Select2 sur les selects du formulaire chargé en AJAX
     * @param {string} formId - ID du formulaire
     */
    function initClasseFormScripts(formId) {
        // Initialiser Select2 si disponible
        if (typeof $.fn.select2 !== 'undefined') {
            $(`#${formId}_filiere_id, #${formId}_niveau_etude_id, #${formId}_annee_universitaire_id`).select2({
                theme: 'bootstrap4',
                placeholder: 'Sélectionner une option',
                allowClear: true,
                dropdownParent: formId.includes('modal') ? $(`#${formId}`).closest('.modal') : undefined
            });
        }

        // Auto-génération du code de classe basé sur le nom (seulement si vide)
        $(`#${formId}_name`).on('blur', function() {
            const codeInput = $(`#${formId}_code`);
            if (codeInput.val() === '') {
                const name = $(this).val();
                if (name) {
                    // Extraire les premières lettres de chaque mot et les convertir en majuscules
                    const code = name.split(' ')
                        .map(word => word.charAt(0).toUpperCase())
                        .join('');
                    codeInput.val(code);
                }
            }
        });
    }

    // ========================================
    // MODAL CRÉATION - Ouverture et chargement
    // ========================================

    const btnOpenCreateModal = document.getElementById('btn-open-create-modal');
    const createClasseModal = new bootstrap.Modal(document.getElementById('createClasseModal'));
    const modalCreateBody = document.getElementById('modal-create-body');
    const modalCreateSubmitBtn = document.getElementById('modal-create-submit-btn');

    if (btnOpenCreateModal) {
        btnOpenCreateModal.addEventListener('click', function() {
            console.log('🟢 Ouverture modal création classe');

            // Afficher le spinner
            modalCreateBody.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
            `;

            // Désactiver le bouton submit pendant le chargement
            modalCreateSubmitBtn.disabled = true;

            // Charger le formulaire via AJAX
            fetch('{{ route("esbtp.classes.create") }}?ajax=1', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(html => {
                console.log('✅ Formulaire création chargé');

                // Charger le formulaire partial dans le modal
                modalCreateBody.innerHTML = html;

                // Initialiser Select2 et scripts du formulaire
                initClasseFormScripts('modal-create-classe-form');

                // Activer le bouton submit
                modalCreateSubmitBtn.disabled = false;

                // Ouvrir le modal
                createClasseModal.show();
            })
            .catch(error => {
                console.error('❌ Erreur chargement formulaire création:', error);
                modalCreateBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erreur lors du chargement du formulaire. Veuillez réessayer.
                    </div>
                `;
            });
        });
    }

    // ========================================
    // MODAL CRÉATION - Soumission AJAX
    // ========================================

    // Click sur le bouton submit du modal → déclenche le submit du formulaire
    if (modalCreateSubmitBtn) {
        modalCreateSubmitBtn.addEventListener('click', function() {
            console.log('🔵 Click sur bouton submit création');
            const form = document.getElementById('modal-create-classe-form');
            if (form) {
                console.log('🔵 Déclenchement submit manuel du formulaire');
                form.requestSubmit(); // Déclenche l'événement submit du formulaire
            } else {
                console.error('❌ Formulaire création introuvable');
            }
        });
    }

    // Délégation d'événements pour intercepter le submit du formulaire création
    // IMPORTANT: Phase de CAPTURE (true en 3ème paramètre) pour intercepter AVANT tous les autres handlers
    document.addEventListener('submit', function(e) {
        // Vérifier si c'est le formulaire de création qui est soumis
        if (e.target && e.target.id === 'modal-create-classe-form') {
            // BLOQUER IMMÉDIATEMENT la soumission normale
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation(); // Empêche même les autres listeners sur le même élément

            console.log('📤 Submit formulaire création intercepté');
            console.log('🛑 preventDefault() appelé - la page NE DEVRAIT PAS se recharger');

            const form = e.target;

            // Désactiver le bouton pour éviter les doubles clics
            modalCreateSubmitBtn.disabled = true;
            modalCreateSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enregistrement...';

            // Collecter les données du formulaire
            const formData = new FormData(form);

            // Soumettre via AJAX
            console.log('🌐 Envoi requête AJAX vers:', form.action);
            console.log('📋 Headers:', {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            });

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => {
                console.log('📥 Réponse création reçue');
                console.log('  Status:', response.status);
                console.log('  Content-Type:', response.headers.get('content-type'));
                console.log('  Redirected:', response.redirected);
                console.log('  URL:', response.url);

                // Vérifier si c'est vraiment du JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    console.error('❌ La réponse n\'est pas du JSON! Content-Type:', contentType);
                    throw new Error('La réponse n\'est pas du JSON');
                }

                return response.json();
            })
            .then(data => {
                console.log('📊 Données création reçues:', data);

                if (data.success) {
                    console.log('✅ Classe créée avec succès:', data.classe);

                    // Réactiver le bouton
                    modalCreateSubmitBtn.disabled = false;
                    modalCreateSubmitBtn.innerHTML = '<i class="fas fa-save"></i> Enregistrer la classe';

                    // Fermer le modal
                    createClasseModal.hide();

                    // Ajouter la nouvelle carte directement sans recharger la page
                    addNewClasseCard(data.classe, data.message || 'La classe a été créée avec succès.');
                } else {
                    // Afficher les erreurs de validation
                    console.warn('⚠️ Erreurs de validation:', data.errors);
                    displayValidationErrors(data.errors, 'modal-create-classe-form');

                    // Réactiver le bouton
                    modalCreateSubmitBtn.disabled = false;
                    modalCreateSubmitBtn.innerHTML = '<i class="fas fa-save"></i> Enregistrer la classe';
                }
            })
            .catch(error => {
                console.error('❌ Erreur soumission formulaire:', error);
                alert('Une erreur est survenue lors de l\'enregistrement. Veuillez réessayer.');

                // Réactiver le bouton
                modalCreateSubmitBtn.disabled = false;
                modalCreateSubmitBtn.innerHTML = '<i class="fas fa-save"></i> Enregistrer la classe';
            });

            // IMPORTANT: Retourner false pour garantir qu'aucun submit ne se produise
            return false;
        }
    }, true); // ← Phase de CAPTURE (3ème paramètre = true) pour intercepter AVANT bubbling

    // ========================================
    // MODAL ÉDITION - Ouverture et chargement
    // ========================================

    const editClasseModal = new bootstrap.Modal(document.getElementById('editClasseModal'));
    const modalEditBody = document.getElementById('modal-edit-body');
    const modalEditSubmitBtn = document.getElementById('modal-edit-submit-btn');

    // Délégation d'événement pour les boutons "Modifier" (car générés dynamiquement)
    document.addEventListener('click', function(e) {
        const btnEdit = e.target.closest('.btn-open-edit-modal');
        if (!btnEdit) return;

        e.preventDefault();

        const classeId = btnEdit.getAttribute('data-classe-id');
        if (!classeId) {
            console.error('❌ ID classe manquant sur le bouton');
            return;
        }

        console.log('🟠 Ouverture modal édition classe:', classeId);

        // Afficher le spinner
        modalEditBody.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
        `;

        // Désactiver le bouton submit pendant le chargement
        modalEditSubmitBtn.disabled = true;

        // Stocker l'ID de la classe en cours d'édition
        modalEditSubmitBtn.setAttribute('data-classe-id', classeId);

        // Charger le formulaire d'édition via AJAX
        fetch(`/esbtp/classes/${classeId}/edit?ajax=1`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            console.log('✅ Formulaire édition chargé pour classe:', classeId);

            // Charger le formulaire partial dans le modal
            modalEditBody.innerHTML = html;

            // Initialiser Select2 et scripts du formulaire
            initClasseFormScripts('modal-edit-classe-form');

            // Activer le bouton submit
            modalEditSubmitBtn.disabled = false;

            // Ouvrir le modal
            editClasseModal.show();
        })
        .catch(error => {
            console.error('❌ Erreur chargement formulaire édition:', error);
            modalEditBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Erreur lors du chargement du formulaire. Veuillez réessayer.
                </div>
            `;
        });
    });

    // ========================================
    // MODAL ÉDITION - Soumission AJAX
    // ========================================

    // Click sur le bouton submit du modal édition → déclenche le submit du formulaire
    if (modalEditSubmitBtn) {
        modalEditSubmitBtn.addEventListener('click', function() {
            console.log('🔵 Click sur bouton submit édition');
            const form = document.getElementById('modal-edit-classe-form');
            if (form) {
                console.log('🔵 Déclenchement submit manuel du formulaire édition');
                form.requestSubmit(); // Déclenche l'événement submit du formulaire
            } else {
                console.error('❌ Formulaire édition introuvable');
            }
        });
    }

    // Délégation d'événements pour intercepter le submit du formulaire édition
    // IMPORTANT: Phase de CAPTURE (true en 3ème paramètre) pour intercepter AVANT tous les autres handlers
    document.addEventListener('submit', function(e) {
        // Vérifier si c'est le formulaire d'édition qui est soumis
        if (e.target && e.target.id === 'modal-edit-classe-form') {
            // BLOQUER IMMÉDIATEMENT la soumission normale
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation(); // Empêche même les autres listeners sur le même élément

            console.log('📤 Submit formulaire édition intercepté');
            console.log('🛑 preventDefault() appelé - la page NE DEVRAIT PAS se recharger');

            const form = e.target;
            const classeId = modalEditSubmitBtn.getAttribute('data-classe-id');
            console.log('📤 Soumission formulaire édition pour classe:', classeId);

            // Désactiver le bouton pour éviter les doubles clics
            modalEditSubmitBtn.disabled = true;
            modalEditSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mise à jour...';

            // Collecter les données du formulaire
            const formData = new FormData(form);

            // Soumettre via AJAX
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => {
                console.log('📥 Réponse reçue, status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('📊 Données reçues:', data);

                if (data.success) {
                    console.log('✅ Classe mise à jour avec succès:', data.classe);

                    // Réactiver le bouton IMMÉDIATEMENT
                    modalEditSubmitBtn.disabled = false;
                    modalEditSubmitBtn.innerHTML = '<i class="fas fa-save"></i> Mettre à jour la classe';

                    // Fermer le modal
                    editClasseModal.hide();

                    // Mettre à jour uniquement la carte de la classe modifiée
                    // Le message de succès sera affiché APRÈS le refresh de la carte
                    updateClasseCard(data.classe, data.message || 'La classe a été mise à jour avec succès.');
                } else {
                    console.warn('⚠️ Erreurs de validation:', data.errors);

                    // Afficher les erreurs de validation
                    displayValidationErrors(data.errors, 'modal-edit-classe-form');

                    // Réactiver le bouton
                    modalEditSubmitBtn.disabled = false;
                    modalEditSubmitBtn.innerHTML = '<i class="fas fa-save"></i> Mettre à jour la classe';
                }
            })
            .catch(error => {
                console.error('❌ Erreur soumission formulaire:', error);
                alert('Une erreur est survenue lors de la mise à jour. Veuillez réessayer.');

                // Réactiver le bouton
                modalEditSubmitBtn.disabled = false;
                modalEditSubmitBtn.innerHTML = '<i class="fas fa-save"></i> Mettre à jour la classe';
            });

            // IMPORTANT: Retourner false pour garantir qu'aucun submit ne se produise
            return false;
        }
    }, true); // ← Phase de CAPTURE (3ème paramètre = true) pour intercepter AVANT bubbling

    // ========================================
    // FONCTIONS UTILITAIRES
    // ========================================

    /**
     * Affiche les erreurs de validation dans le formulaire
     * @param {Object} errors - Objet des erreurs retourné par Laravel
     * @param {string} formId - ID du formulaire
     */
    function displayValidationErrors(errors, formId) {
        // Réinitialiser les erreurs précédentes
        $(`#${formId} .is-invalid`).removeClass('is-invalid');
        $(`#${formId} .invalid-feedback`).remove();

        // Afficher les nouvelles erreurs
        for (const [field, messages] of Object.entries(errors)) {
            const input = document.getElementById(`${formId}_${field}`);
            if (input) {
                input.classList.add('is-invalid');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                errorDiv.textContent = messages[0]; // Première erreur seulement
                input.parentNode.appendChild(errorDiv);
            }
        }
    }

    /**
     * Affiche un message de succès en haut de la page
     * @param {string} message - Message à afficher
     */
    function showSuccessMessage(message) {
        const alertHtml = `
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-bottom: 1rem;">
                <i class="fas fa-check-circle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

        // Insérer au début de .main-content
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.insertAdjacentHTML('afterbegin', alertHtml);

            // Auto-dismiss après 5 secondes
            setTimeout(() => {
                const alert = mainContent.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }
    }

    /**
     * Ajoute une nouvelle carte de classe après création
     * Pattern identique à updateClasseCard mais pour insertion au début de la liste
     * @param {Object} classe - Données de la nouvelle classe
     * @param {String} successMessage - Message de succès à afficher après l'ajout
     */
    function addNewClasseCard(classe, successMessage = null) {
        console.log('➕ Ajout nouvelle carte classe:', classe.id);

        const classeId = classe.id;
        const refreshUrl = `/esbtp/classes/${classeId}/refresh-ligne`;

        // Trouver le conteneur de la liste des classes
        const resultsContainer = document.querySelector('#classes-grid');
        console.log('📦 Conteneur trouvé:', resultsContainer ? 'OUI ✅' : 'NON ❌');

        if (!resultsContainer) {
            console.error('❌ Conteneur #classes-grid non trouvé - rechargement complet');
            window.location.reload();
            return;
        }

        // Fetch la nouvelle carte HTML depuis le serveur
        fetch(refreshUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success || !data.html) {
                throw new Error(data.message || 'Réponse serveur invalide');
            }

            console.log('✅ HTML reçu du serveur pour nouvelle classe:', classeId);

            // Créer un élément temporaire pour parser le HTML
            const template = document.createElement('template');
            template.innerHTML = data.html.trim();

            // Récupérer la nouvelle carte depuis le template
            let newCardFragment = template.content.querySelector(`[data-classe-id="${classeId}"]`);

            if (!newCardFragment) {
                // Si pas de data-classe-id, prendre le premier élément
                newCardFragment = template.content.querySelector('.card-moderne');
            }

            if (!newCardFragment) {
                console.error('❌ HTML retourné sans carte valide:', data.html);
                throw new Error('HTML retourné sans carte de classe valide');
            }

            // Cloner la nouvelle carte
            const newCard = newCardFragment.cloneNode(true);

            // Récupérer le modal de suppression si présent
            const modalFragment = template.content.querySelector(`#deleteModal${classeId}`);
            if (modalFragment) {
                const newModal = modalFragment.cloneNode(true);
                document.body.appendChild(newModal);
            }

            // Insérer la nouvelle carte AU DÉBUT de la liste (prepend)
            resultsContainer.insertBefore(newCard, resultsContainer.firstChild);

            console.log('✅ Nouvelle carte classe ajoutée avec succès:', classeId);

            // Animation visuelle (flash vert + slide down)
            newCard.style.opacity = '0';
            newCard.style.transform = 'translateY(-20px)';
            newCard.style.transition = 'opacity 0.3s ease, transform 0.3s ease, background-color 0.3s ease';

            // Forcer le reflow pour que la transition fonctionne
            newCard.offsetHeight;

            newCard.style.opacity = '1';
            newCard.style.transform = 'translateY(0)';
            newCard.style.backgroundColor = 'rgba(16, 185, 129, 0.1)'; // Vert léger

            setTimeout(() => {
                newCard.style.backgroundColor = '';
            }, 1000);

            // Afficher le message de succès APRÈS l'ajout de la carte
            if (successMessage) {
                showSuccessMessage(successMessage);
            }

            // Scroll vers la nouvelle carte
            newCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        })
        .catch(error => {
            console.error('❌ Erreur ajout nouvelle carte classe:', error);
            // En cas d'erreur, on reload la page
            console.log('🔄 Rechargement de la page suite à l\'erreur...');
            window.location.reload();
        });
    }

    /**
     * Met à jour la carte d'une classe après édition
     * @param {Object} classe - Données de la classe mise à jour
     * @param {String} successMessage - Message de succès à afficher après le refresh
     */
    function updateClasseCard(classe, successMessage = null) {
        console.log('🔄 Refresh carte classe:', classe.id);

        const classeId = classe.id;
        const refreshUrl = `/esbtp/classes/${classeId}/refresh-ligne`;
        const existingCard = document.querySelector(`[data-classe-id="${classeId}"]`);

        if (!existingCard) {
            console.warn('⚠️ Carte non trouvée pour ID:', classeId, '- rechargement complet');
            window.location.reload();
            return;
        }

        // Fetch la nouvelle carte HTML depuis le serveur
        fetch(refreshUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success || !data.html) {
                throw new Error(data.message || 'Réponse serveur invalide');
            }

            console.log('✅ HTML reçu du serveur pour classe:', classeId);

            // Créer un élément temporaire pour parser le HTML
            const template = document.createElement('template');
            template.innerHTML = data.html.trim();

            // Récupérer la nouvelle carte depuis le template
            let newCardFragment = template.content.querySelector(`[data-classe-id="${classeId}"]`);

            if (!newCardFragment) {
                // Si pas de data-classe-id, prendre le premier élément
                newCardFragment = template.content.querySelector('.card-moderne');
            }

            if (!newCardFragment) {
                console.error('❌ HTML retourné sans carte valide:', data.html);
                throw new Error('HTML retourné sans carte de classe valide');
            }

            // Cloner la nouvelle carte
            const newCard = newCardFragment.cloneNode(true);

            // Récupérer le modal de suppression si présent
            const modalFragment = template.content.querySelector(`#deleteModal${classeId}`);
            if (modalFragment) {
                const newModal = modalFragment.cloneNode(true);
                const existingModal = document.getElementById(`deleteModal${classeId}`);
                if (existingModal) {
                    existingModal.replaceWith(newModal);
                } else {
                    document.body.appendChild(newModal);
                }
            }

            // Remplacer l'ancienne carte par la nouvelle
            existingCard.replaceWith(newCard);

            console.log('✅ Carte classe rafraîchie avec succès:', classeId);

            // Animation visuelle rapide (flash vert)
            newCard.style.transition = 'background-color 0.3s ease';
            newCard.style.backgroundColor = 'rgba(16, 185, 129, 0.1)'; // Vert léger
            setTimeout(() => {
                newCard.style.backgroundColor = '';
            }, 500);

            // Afficher le message de succès APRÈS le refresh de la carte
            if (successMessage) {
                showSuccessMessage(successMessage);
            }

        })
        .catch(error => {
            console.error('❌ Erreur refresh carte classe:', error);
            // En cas d'erreur, on reload la page
            console.log('🔄 Rechargement de la page suite à l\'erreur...');
            window.location.reload();
        });
    }
</script>
@endpush
