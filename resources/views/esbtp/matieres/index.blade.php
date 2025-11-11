@extends('layouts.app')

@section('title', 'Liste des matières')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="{{ asset('css/modal-force-fix.css') }}">
@endsection

@push('styles')
<style>
.gap-1 {
    gap: 0.25rem !important;
}

.form-check:hover {
    background-color: rgba(var(--primary-rgb), 0.05) !important;
    border-radius: 6px;
}

.form-check-input:checked {
    background-color: var(--primary);
    border-color: var(--primary);
}

#combinations-preview .badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.badge-link {
    cursor: pointer;
    transition: all 0.2s ease;
}

.badge-link:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.table td {
    vertical-align: middle;
}

.btn-group .btn,
.matiere-actions-buttons .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child,
.matiere-actions-buttons .btn:last-child {
    margin-right: 0;
}

.modal-xl {
    max-width: 1200px;
}

.matieres-bulk-bar {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    color: white;
    padding: 15px 30px;
    border-radius: 50px;
    box-shadow: 0 10px 40px rgba(4, 83, 203, 0.4);
    z-index: 1050;
    animation: slideUp 0.3s ease-out;
}

.matieres-bulk-bar .btn {
    border-radius: 999px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.matiere-actions-wrapper {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    min-height: 32px;
}

.matiere-actions-spinner {
    display: none;
    min-width: 32px;
    align-items: center;
    justify-content: center;
}

.matiere-actions-wrapper.is-loading .matiere-actions-buttons {
    display: none !important;
}

.matiere-actions-wrapper.is-loading .matiere-actions-spinner {
    display: inline-flex !important;
}

tr[data-matiere-id] {
    position: relative;
    overflow: hidden;
    transition: background-color 0.3s ease;
}

.matiere-row-highlight {
    position: absolute;
    top: 0;
    left: -65%;
    width: 150%;
    height: 100%;
    pointer-events: none;
    opacity: 0;
    transform: translateX(-65%) skewX(-12deg);
    background: linear-gradient(90deg, rgba(4, 83, 203, 0) 0%, rgba(4, 83, 203, 0.65) 50%, rgba(4, 83, 203, 0) 100%);
    z-index: 5;
}

.matiere-row-highlight.reject {
    background: linear-gradient(90deg, rgba(220, 53, 69, 0) 0%, rgba(220, 53, 69, 0.65) 50%, rgba(220, 53, 69, 0) 100%);
}

.matiere-row-highlight.animate {
    animation: matiere-row-highlight-move 2.8s ease-out forwards;
}

.matiere-row-flash {
    animation: matiere-row-flash 0.8s ease-in-out;
}

.matiere-row-flash.reject {
    animation-name: matiere-row-flash-reject;
}

@keyframes matiere-row-highlight-move {
    0% {
        opacity: 0;
        transform: translateX(-65%) skewX(-12deg);
    }
    18% {
        opacity: 0.9;
    }
    55% {
        opacity: 0.7;
    }
    100% {
        opacity: 0;
        transform: translateX(115%) skewX(-12deg);
    }
}

@keyframes matiere-row-flash {
    0% {
        background-color: transparent;
    }
    25% {
        background-color: rgba(4, 83, 203, 0.15);
    }
    100% {
        background-color: transparent;
    }
}

@keyframes matiere-row-flash-reject {
    0% {
        background-color: transparent;
    }
    25% {
        background-color: rgba(220, 53, 69, 0.15);
    }
    100% {
        background-color: transparent;
    }
}

@keyframes slideUp {
    from {
        bottom: -100px;
        opacity: 0;
    }
    to {
        bottom: 20px;
        opacity: 1;
    }
}
</style>
@endpush

@section('content')
<div class="main-content">
    <div class="dashboard-header">
        <div class="header-left">
            <h1><i class="fas fa-book me-2"></i>Gestion des Matières</h1>
            <p class="header-subtitle">Liste des matières disponibles dans votre établissement</p>
        </div>
        <div class="header-actions">
            <input type="search"
                   class="search-bar"
                   placeholder="Rechercher une matière..."
                   value="{{ $filters['search'] ?? '' }}"
                   id="matieres-header-search">
            <div class="d-flex gap-2">
                <a href="{{ route('esbtp.matieres.attach-to-classes') }}" class="btn-acasi secondary">
                    <i class="fas fa-link"></i> Attacher aux classes
                </a>
                <a href="{{ route('esbtp.matieres.create') }}" class="btn-acasi primary">
                    <i class="fas fa-plus"></i> Ajouter une matière
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="card-moderne mb-lg" style="border-left: 4px solid var(--success);">
            <div class="p-lg">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle color-success me-2"></i>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        </div>
    @endif

    <div class="main-card mb-4">
        <div class="main-card-header">
            <div class="main-card-title">
                <i class="fas fa-filter"></i>
                Filtres avancés
            </div>
            <div class="main-card-subtitle">Filtrer et rechercher parmi les matières</div>
        </div>
        <div class="main-card-body">
            <form id="matieres-filter-form" method="GET" action="{{ route('esbtp.matieres.index') }}" class="row g-3">
                <div class="col-12">
                    <div class="form-group-moderne">
                        <label for="filter-search" class="form-label-moderne">
                            <i class="fas fa-search me-1"></i>Recherche globale
                        </label>
                        <input type="text"
                               id="filter-search"
                               name="search"
                               class="form-input-moderne"
                               value="{{ $filters['search'] ?? '' }}"
                               placeholder="Tapez pour rechercher dans toutes les colonnes (code, nom, coefficient, heures, filières, niveaux...)">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group-moderne">
                        <label for="filter-filiere" class="form-label-moderne">
                            <i class="fas fa-graduation-cap me-1"></i>Filière
                        </label>
                        <select name="filiere_filter" id="filter-filiere" class="form-select-moderne">
                            <option value="">Toutes les filières</option>
                            @foreach($filieres as $filiere)
                                <option value="{{ $filiere->id }}" @selected($filters['filiere_filter'] == $filiere->id)>{{ $filiere->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group-moderne">
                        <label for="filter-niveau" class="form-label-moderne">
                            <i class="fas fa-layer-group me-1"></i>Niveau
                        </label>
                        <select name="niveau_filter" id="filter-niveau" class="form-select-moderne">
                            <option value="">Tous les niveaux</option>
                            @foreach($niveaux as $niveau)
                                <option value="{{ $niveau->id }}" @selected($filters['niveau_filter'] == $niveau->id)>{{ $niveau->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group-moderne">
                        <label for="filter-statut" class="form-label-moderne">
                            <i class="fas fa-toggle-on me-1"></i>Statut
                        </label>
                        <select name="statut_filter" id="filter-statut" class="form-select-moderne">
                            <option value="">Tous les statuts</option>
                            <option value="1" @selected($filters['statut_filter'] === '1')>Actif</option>
                            <option value="0" @selected($filters['statut_filter'] === '0')>Inactif</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group-moderne">
                        <label for="filter-coefficient-min" class="form-label-moderne">
                            <i class="fas fa-sort-numeric-up me-1"></i>Coefficient min.
                        </label>
                        <input type="number"
                               id="filter-coefficient-min"
                               name="coefficient_min"
                               class="form-input-moderne"
                               min="0"
                               step="0.1"
                               value="{{ $filters['coefficient_min'] }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group-moderne">
                        <label for="filter-coefficient-max" class="form-label-moderne">
                            <i class="fas fa-sort-numeric-down me-1"></i>Coefficient max.
                        </label>
                        <input type="number"
                               id="filter-coefficient-max"
                               name="coefficient_max"
                               class="form-input-moderne"
                               min="0"
                               step="0.1"
                               value="{{ $filters['coefficient_max'] }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group-moderne">
                        <label for="filter-heures-min" class="form-label-moderne">
                            <i class="fas fa-clock me-1"></i>Heures min.
                        </label>
                        <input type="number"
                               id="filter-heures-min"
                               name="heures_min"
                               class="form-input-moderne"
                               min="0"
                               value="{{ $filters['heures_min'] }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group-moderne">
                        <label for="filter-heures-max" class="form-label-moderne">
                            <i class="fas fa-clock me-1"></i>Heures max.
                        </label>
                        <input type="number"
                               id="filter-heures-max"
                               name="heures_max"
                               class="form-input-moderne"
                               min="0"
                               value="{{ $filters['heures_max'] }}">
                    </div>
                </div>

                <div class="col-12">
                    <div class="d-flex gap-2 justify-content-between align-items-center mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            <span id="results-count">
                                @if(($summary['total'] ?? 0) > 0)
                                    {{ $summary['from'] ?? 0 }} - {{ $summary['to'] ?? 0 }} sur {{ $summary['total'] }} matière(s)
                                @else
                                    Aucun résultat pour le moment.
                                @endif
                            </span>
                        </small>
                        <div class="d-flex gap-2">
                            <button type="button" id="matieres-clear-filters" class="btn-acasi secondary">
                                <i class="fas fa-eraser me-1"></i>Effacer les filtres
                            </button>
                            <button type="submit" class="btn-acasi primary" id="matieres-apply-filters">
                                <i class="fas fa-search me-1"></i>Appliquer les filtres
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="matieres-results"
         data-summary='@json($summary)'
         data-refresh-url="{{ route('esbtp.matieres.refresh') }}">
        @include('esbtp.matieres.partials.results', ['matieres' => $matieres])
    </div>
</div>

<div id="matieres-bulk-bar" class="matieres-bulk-bar" style="display: none;">
    <div class="d-flex align-items-center gap-4">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-check-circle fa-lg"></i>
            <span>
                <strong id="matieres-selected-count">0</strong>
                matière(s) sélectionnée(s)
            </span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-light btn-sm" id="matieres-bulk-attach">
                <i class="fas fa-link me-1"></i>Attacher aux combinaisons
            </button>
            <button type="button" class="btn btn-outline-light btn-sm" id="matieres-bulk-configure">
                <i class="fas fa-sliders-h me-1"></i>Configurer
            </button>
            <button type="button" class="btn btn-outline-light btn-sm" id="matieres-bulk-delete">
                <i class="fas fa-trash me-1"></i>Supprimer
            </button>
            <button type="button" class="btn btn-outline-light btn-sm" id="matieres-bulk-clear">
                <i class="fas fa-times me-1"></i>Annuler
            </button>
        </div>
    </div>
</div>

<div class="modal fade" id="configureModal" tabindex="-1" aria-labelledby="configureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; border-radius: 12px 12px 0 0; padding: 1.5rem;">
                <div>
                    <h4 class="modal-title mb-1" id="configureModalLabel" style="font-weight: 600;">
                        <i class="fas fa-link me-2"></i>Configuration des liaisons
                    </h4>
                    <p class="mb-0" style="opacity: 0.9; font-size: 0.9rem;">
                        Matière : <span id="modal-matiere-name" style="font-weight: 500;"></span>
                    </p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer" style="filter: brightness(0) invert(1);"></button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <form id="configureLiaisonsForm">
                    @csrf
                    <input type="hidden" id="modal-matiere-id" name="matiere_id">
                    <input type="hidden" id="modal-mode" value="single">
                    <input type="hidden" id="modal-selected-ids" value="[]">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card-moderne">
                                <div class="main-card-header">
                                    <h3 class="main-card-title">
                                        <i class="fas fa-graduation-cap"></i>Filières
                                    </h3>
                                    <p class="main-card-subtitle">Sélectionnez les filières concernées</p>
                                </div>
                                <div class="main-card-body">
                                    <div id="filieres-list" style="max-height: 250px; overflow-y: auto; border: 1px solid var(--border-light); border-radius: 8px; padding: 1rem; background: var(--bg-light);">
                                        @foreach($filieres as $filiere)
                                            <div class="form-check mb-3 p-2" style="border-radius: 6px; transition: all 0.2s ease;">
                                                <input class="form-check-input filiere-checkbox" type="checkbox"
                                                       value="{{ $filiere->id }}" id="filiere-{{ $filiere->id }}"
                                                       data-label="{{ $filiere->name }}">
                                                <label class="form-check-label" for="filiere-{{ $filiere->id }}" style="cursor: pointer; width: 100%;">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <span class="font-semibold color-dark">{{ $filiere->name }}</span>
                                                            @if($filiere->code)
                                                                <span class="badge secondary ms-2">{{ $filiere->code }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card-moderne">
                                <div class="main-card-header">
                                    <h3 class="main-card-title">
                                        <i class="fas fa-layer-group"></i>Niveaux
                                    </h3>
                                    <p class="main-card-subtitle">Sélectionnez les niveaux concernés</p>
                                </div>
                                <div class="main-card-body">
                                    <div id="niveaux-list" style="max-height: 250px; overflow-y: auto; border: 1px solid var(--border-light); border-radius: 8px; padding: 1rem; background: var(--bg-light);">
                                        @foreach($niveaux as $niveau)
                                            <div class="form-check mb-3 p-2" style="border-radius: 6px; transition: all 0.2s ease;">
                                                <input class="form-check-input niveau-checkbox" type="checkbox"
                                                       value="{{ $niveau->id }}" id="niveau-{{ $niveau->id }}"
                                                       data-label="{{ $niveau->name }}">
                                                <label class="form-check-label" for="niveau-{{ $niveau->id }}" style="cursor: pointer; width: 100%;">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <span class="font-semibold color-dark">{{ $niveau->name }}</span>
                                                            @if($niveau->code)
                                                                <span class="badge secondary ms-2">{{ $niveau->code }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-moderne mt-4" id="matieres-selection-container" style="display: none;">
                        <div class="main-card-header">
                            <h3 class="main-card-title">
                                <i class="fas fa-book-open"></i>Matières disponibles
                            </h3>
                            <p class="main-card-subtitle">Sélectionnez les matières à attacher à la combinaison</p>
                        </div>
                        <div class="main-card-body" id="matieres-list"></div>
                    </div>

                    <div class="card-moderne mt-4">
                        <div class="main-card-header">
                            <h3 class="main-card-title">
                                <i class="fas fa-lightbulb"></i>Aperçu des combinaisons
                            </h3>
                            <p class="main-card-subtitle">Visualisez les liaisons générées</p>
                        </div>
                        <div class="main-card-body" id="combinations-preview">
                            <div class="d-flex align-items-center" style="color: #0369a1;">
                                <i class="fas fa-info-circle me-2"></i>
                                <span>Sélectionnez au moins une filière et un niveau pour voir les combinaisons possibles.</span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="padding: 1.5rem;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Annuler
                </button>
                <button type="button" class="btn btn-primary" id="save-liaisons-btn">
                    <i class="fas fa-save me-1"></i>Enregistrer les liaisons
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const FILTER_DEBOUNCE = 350;

    const filtersForm = document.getElementById('matieres-filter-form');
    const headerSearch = document.getElementById('matieres-header-search');
    const summaryElement = document.getElementById('results-count');
    const resultsContainer = document.getElementById('matieres-results');
    const clearFiltersBtn = document.getElementById('matieres-clear-filters');
    const applyFiltersBtn = document.getElementById('matieres-apply-filters');
    const bulkBar = document.getElementById('matieres-bulk-bar');
    const bulkCount = document.getElementById('matieres-selected-count');
    const bulkAttachBtn = document.getElementById('matieres-bulk-attach');
    const bulkConfigureBtn = document.getElementById('matieres-bulk-configure');
    const bulkDeleteBtn = document.getElementById('matieres-bulk-delete');
    const bulkClearBtn = document.getElementById('matieres-bulk-clear');
    let filterTimer = null;
    const selectedIds = new Set();

    function showToast(type, message) {
        if (window.toastr && typeof window.toastr[type] === 'function') {
            window.toastr[type](message);
        } else {
            const logMethod = type === 'error' ? 'error' : 'log';
            console[logMethod](message);
        }
    }

    function getSelectedIdsArray() {
        return Array.from(selectedIds);
    }

    function updateSummary(summary) {
        if (!summaryElement || !summary) {
            return;
        }

        if (summary.total && summary.total > 0) {
            summaryElement.textContent = `${summary.from ?? 0} - ${summary.to ?? 0} sur ${summary.total} matière(s)`;
        } else {
            summaryElement.textContent = 'Aucun résultat pour le moment.';
        }
    }

    function syncHeaderSearchFromForm() {
        if (!filtersForm || !headerSearch) {
            return;
        }

        const formSearch = filtersForm.querySelector('#filter-search');
        if (formSearch) {
            headerSearch.value = formSearch.value || '';
        }
    }

    function syncFormSearchFromHeader() {
        if (!filtersForm || !headerSearch) {
            return;
        }

        const formSearch = filtersForm.querySelector('#filter-search');
        if (formSearch) {
            formSearch.value = headerSearch.value || '';
        }
    }

    function toggleBulkBar() {
        const count = selectedIds.size;
        if (!bulkBar || !bulkCount) {
            return;
        }

        bulkCount.textContent = count;
        bulkBar.style.display = count > 0 ? 'block' : 'none';

        if (bulkConfigureBtn) {
            bulkConfigureBtn.style.display = count === 1 ? 'inline-flex' : 'none';
        }
    }

    function clearSelection() {
        const tableCheckboxes = resultsContainer
            ? resultsContainer.querySelectorAll('.matiere-checkbox')
            : [];

        selectedIds.clear();
        tableCheckboxes.forEach((checkbox) => {
            checkbox.checked = false;
        });
        const selectAll = document.getElementById('matieres-select-all');
        if (selectAll) {
            selectAll.checked = false;
        }
        toggleBulkBar();
    }

    function applyQueryToForm(url) {
        if (!filtersForm) {
            return;
        }

        const parsedUrl = new URL(url, window.location.origin);
        const params = parsedUrl.searchParams;

        filtersForm.querySelectorAll('[name]').forEach((field) => {
            const name = field.getAttribute('name');
            if (!name) {
                return;
            }
            const value = params.get(name) ?? '';
            if (field.tagName === 'SELECT') {
                field.value = value;
            } else if (field.type === 'number' && value !== '') {
                field.value = Number(value);
            } else {
                field.value = value;
            }
        });
    }

    function setMatiereRowLoadingState(matiereId, isLoading) {
        const row = document.querySelector(`tr[data-matiere-id="${matiereId}"]`);
        if (!row) {
            return;
        }
        row.classList.toggle('is-loading', Boolean(isLoading));
        const actionsWrapper = row.querySelector('.matiere-actions-wrapper');
        if (actionsWrapper) {
            actionsWrapper.classList.toggle('is-loading', Boolean(isLoading));
        }
    }
    window.setMatiereRowLoadingState = setMatiereRowLoadingState;

    function triggerMatiereRowHighlight(row, actionType = 'update') {
        if (!row) {
            return;
        }

        const isReject = ['reject', 'delete', 'danger'].includes(actionType);
        row.classList.remove('matiere-row-flash', 'reject');
        void row.offsetWidth;

        const highlight = document.createElement('div');
        highlight.className = 'matiere-row-highlight';
        if (isReject) {
            highlight.classList.add('reject');
        }
        row.appendChild(highlight);

        requestAnimationFrame(() => {
            highlight.classList.add('animate');
        });

        const cleanup = () => {
            highlight.removeEventListener('animationend', cleanup);
            highlight.remove();
        };

        highlight.addEventListener('animationend', cleanup);

        row.classList.add('matiere-row-flash');
        if (isReject) {
            row.classList.add('reject');
        }

        setTimeout(() => {
            row.classList.remove('matiere-row-flash', 'reject');
        }, 1200);
    }
    window.triggerMatiereRowHighlight = triggerMatiereRowHighlight;

    function refreshMatiereRow(matiereId, actionType = 'update') {
        const url = `{{ route('esbtp.matieres.refresh-ligne', ['matiere' => '__ID__']) }}`.replace('__ID__', matiereId);

        setMatiereRowLoadingState(matiereId, true);

        return fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                if (!data.success || !data.html) {
                    throw new Error(data.message || 'Réponse invalide');
                }

                const template = document.createElement('template');
                template.innerHTML = data.html.trim();
                const newRow = template.content.querySelector(`tr[data-matiere-id="${matiereId}"]`);
                const existingRow = document.querySelector(`tr[data-matiere-id="${matiereId}"]`);

                if (existingRow && newRow) {
                    existingRow.replaceWith(newRow);
                    triggerMatiereRowHighlight(newRow, actionType);
                }
            })
            .catch((error) => {
                debugError('Erreur lors du rafraîchissement de la matière:', error);
            })
            .finally(() => {
                setMatiereRowLoadingState(matiereId, false);
                initTableInteractions();
            });
    }
    window.refreshMatiereRow = refreshMatiereRow;

    function submitFilterForm(pushHistory = true) {
        if (!filtersForm || !resultsContainer) {
            return;
        }

        const formData = new FormData(filtersForm);
        const params = new URLSearchParams(formData);
        const refreshUrl = resultsContainer.dataset.refreshUrl;
        const url = `${refreshUrl}?${params.toString()}`;

        resultsContainer.classList.add('position-relative');
        const overlay = document.createElement('div');
        overlay.className = 'd-flex align-items-center justify-content-center';
        overlay.style.position = 'absolute';
        overlay.style.top = 0;
        overlay.style.left = 0;
        overlay.style.width = '100%';
        overlay.style.height = '100%';
        overlay.style.background = 'rgba(255,255,255,0.8)';
        overlay.style.zIndex = '5';
        overlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div>';
        resultsContainer.appendChild(overlay);

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                if (!data.html) {
                    throw new Error('Template manquant dans la réponse');
                }
                resultsContainer.innerHTML = data.html;
                resultsContainer.dataset.summary = JSON.stringify(data.summary || {});
                if (pushHistory && data.url) {
                    history.pushState({}, '', data.url);
                }
                updateSummary(data.summary || {});
                clearSelection();
            })
            .catch((error) => {
                debugError('Erreur lors du rafraîchissement des matières:', error);
            })
            .finally(() => {
                overlay.remove();
                resultsContainer.classList.remove('position-relative');
                initTableInteractions();
            });
    }

    function initTableInteractions() {
        const tableCheckboxes = resultsContainer
            ? resultsContainer.querySelectorAll('.matiere-checkbox')
            : [];

        tableCheckboxes.forEach((checkbox) => {
            checkbox.removeEventListener('change', handleRowSelection);
            checkbox.addEventListener('change', handleRowSelection);
            if (selectedIds.has(Number(checkbox.value))) {
                checkbox.checked = true;
            }
        });

        const selectAll = document.getElementById('matieres-select-all');
        if (selectAll) {
            selectAll.addEventListener('change', () => {
                const checked = selectAll.checked;
                const tableCheckboxes = resultsContainer
                    ? resultsContainer.querySelectorAll('.matiere-checkbox')
                    : [];

                tableCheckboxes.forEach((checkbox) => {
                    checkbox.checked = checked;
                    const id = Number(checkbox.value);
                    if (checked) {
                        selectedIds.add(id);
                    } else {
                        selectedIds.delete(id);
                    }
                });
                toggleBulkBar();
            });
        }

        resultsContainer.querySelectorAll('.pagination a').forEach((link) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                const href = link.getAttribute('href');
                if (!href || href === '#') {
                    return;
                }
                fetch(href, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }
                        return response.json();
                    })
                    .then((data) => {
                        if (!data.html) {
                            throw new Error('Template manquant dans la réponse');
                        }
                        resultsContainer.innerHTML = data.html;
                        resultsContainer.dataset.summary = JSON.stringify(data.summary || {});
                        if (data.url) {
                            history.pushState({}, '', data.url);
                        }
                        updateSummary(data.summary || {});
                        clearSelection();
                        initTableInteractions();
                    })
                    .catch((error) => {
                        debugError('Erreur pagination matières:', error);
                    });
            });
        });
    }

    function handleRowSelection(event) {
        const checkbox = event.target;
        const id = Number(checkbox.value);
        if (checkbox.checked) {
            selectedIds.add(id);
        } else {
            selectedIds.delete(id);
        }
        toggleBulkBar();
    }

    if (headerSearch) {
        headerSearch.addEventListener('input', () => {
            syncFormSearchFromHeader();
            clearTimeout(filterTimer);
            filterTimer = setTimeout(() => submitFilterForm(), FILTER_DEBOUNCE);
        });

        headerSearch.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                syncFormSearchFromHeader();
                submitFilterForm();
            }
        });
    }

    if (filtersForm) {
        filtersForm.addEventListener('submit', (event) => {
            event.preventDefault();
            submitFilterForm();
        });

        filtersForm.querySelectorAll('select, input').forEach((input) => {
            input.addEventListener('change', () => {
                if (input.id === 'filter-search') {
                    return;
                }
                clearTimeout(filterTimer);
                filterTimer = setTimeout(() => submitFilterForm(), FILTER_DEBOUNCE);
            });
        });
    }

    if (clearFiltersBtn && filtersForm) {
        clearFiltersBtn.addEventListener('click', () => {
            filtersForm.reset();
            if (headerSearch) {
                headerSearch.value = '';
            }
            submitFilterForm();
        });
    }

    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', (event) => {
            event.preventDefault();
            submitFilterForm();
        });
    }

    if (bulkClearBtn) {
        bulkClearBtn.addEventListener('click', clearSelection);
    }

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', () => {
            const ids = getSelectedIdsArray();
            if (ids.length === 0) {
                return;
            }
            if (!confirm('Supprimer les matières sélectionnées ? Cette action est irréversible.')) {
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('esbtp.matieres.bulk-delete') }}';

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            form.appendChild(csrfInput);

            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);

            ids.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'matieres[]';
                input.value = id;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        });
    }

    if (bulkAttachBtn) {
        bulkAttachBtn.addEventListener('click', () => {
            const ids = getSelectedIdsArray();
            if (ids.length === 0) {
                alert('Sélectionnez au moins une matière à attacher.');
                return;
            }
            openConfigureModal({
                mode: 'bulk',
                matiereName: `${ids.length} matière(s) sélectionnée(s)`,
                selectedIds: ids
            });
        });
    }

    if (bulkConfigureBtn) {
        bulkConfigureBtn.addEventListener('click', () => {
            const ids = getSelectedIdsArray();
            if (ids.length !== 1) {
                return;
            }
            const row = document.querySelector(`tr[data-matiere-id="${ids[0]}"]`);
            const name = row ? row.querySelector('td:nth-child(3) .font-semibold')?.textContent?.trim() : 'Matière';
            openConfigureModal({
                mode: 'single',
                matiereId: ids[0],
                matiereName: name || 'Matière'
            });
        });
    }

    function openConfigureModal(options) {
        const mode = options.mode || 'single';
        const matiereNameElement = document.getElementById('modal-matiere-name');
        const matiereIdInput = document.getElementById('modal-matiere-id');
        const modalModeInput = document.getElementById('modal-mode');
        const modalSelectedIdsInput = document.getElementById('modal-selected-ids');
        const saveBtn = document.getElementById('save-liaisons-btn');
        const combinationsPreview = document.getElementById('combinations-preview');
        const matieresContainer = document.getElementById('matieres-selection-container');
        const matieresList = document.getElementById('matieres-list');

        document.querySelectorAll('.filiere-checkbox, .niveau-checkbox').forEach((checkbox) => {
            checkbox.checked = false;
        });

        if (combinationsPreview) {
            combinationsPreview.innerHTML = `
                <div class="d-flex align-items-center" style="color: #0369a1;">
                    <i class="fas fa-info-circle me-2"></i>
                    <span>Sélectionnez au moins une filière et un niveau pour voir les combinaisons possibles.</span>
                </div>
            `;
        }

        if (matieresContainer) {
            matieresContainer.style.display = 'none';
        }
        if (matieresList) {
            matieresList.innerHTML = '';
        }

        if (matiereNameElement) {
            matiereNameElement.textContent = options.matiereName || '';
        }
        if (matiereIdInput) {
            matiereIdInput.value = mode === 'single' ? options.matiereId : '';
        }
        if (modalModeInput) {
            modalModeInput.value = mode;
        }
        if (modalSelectedIdsInput) {
            modalSelectedIdsInput.value = mode === 'bulk' ? JSON.stringify(options.selectedIds || []) : '[]';
        }
        if (saveBtn) {
            saveBtn.innerHTML = mode === 'bulk'
                ? '<i class="fas fa-link me-1"></i>Attacher les matières sélectionnées'
                : '<i class="fas fa-save me-1"></i>Enregistrer les liaisons';
        }

        if (mode === 'single' && options.matiereId) {
            loadExistingLiaisons(options.matiereId);
        }

        const modalElement = document.getElementById('configureModal');
        if (modalElement) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            modal.show();
        }
    }
    window.openConfigureModal = openConfigureModal;

    function loadExistingLiaisons(matiereId) {
        fetch(`/esbtp/matieres/${matiereId}/liaisons`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                if (!data.success) {
                    return;
                }
                document.querySelectorAll('.filiere-checkbox').forEach((checkbox) => {
                    checkbox.checked = data.filieres?.includes(Number(checkbox.value));
                });
                document.querySelectorAll('.niveau-checkbox').forEach((checkbox) => {
                    checkbox.checked = data.niveaux?.includes(Number(checkbox.value));
                });
                updateCombinationsPreview();
            })
            .catch((error) => {
                debugError('Erreur chargement liaisons matière:', error);
            });
    }

    function loadAvailableMatieres(filiereId, niveauId) {
        const matieresListDiv = document.getElementById('matieres-list');
        const matieresContainer = document.getElementById('matieres-selection-container');

        if (!filiereId || !niveauId || !matieresListDiv || !matieresContainer) {
            return;
        }

        matieresContainer.style.display = 'block';
        matieresListDiv.innerHTML = `
            <div class="d-flex justify-content-center align-items-center py-4 text-muted">
                <div class="spinner-border text-primary me-2" role="status"></div>
                <span>Chargement des matières disponibles...</span>
            </div>
        `;

        fetch(`/esbtp/matieres/available-for-combination?filiere_id=${filiereId}&niveau_id=${niveauId}`)
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                if (!data.success || !Array.isArray(data.matieres)) {
                    throw new Error(data.message || 'Réponse invalide');
                }

                if (data.matieres.length === 0) {
                    matieresListDiv.innerHTML = `
                        <div class="d-flex align-items-center justify-content-center py-4 text-muted">
                            <i class="fas fa-info-circle me-2"></i>
                            <span>Aucune matière disponible pour cette combinaison</span>
                        </div>
                    `;
                    return;
                }

        matieresListDiv.innerHTML = data.matieres.map((matiere) => `
            <div class="form-check mb-3 p-2" style="border-radius: 6px; transition: all 0.2s ease;">
                <input class="form-check-input matiere-modal-checkbox" type="checkbox"
                       value="${matiere.id}" id="matiere-${matiere.id}"
                       name="selected_matieres[]">
                        <label class="form-check-label" for="matiere-${matiere.id}" style="cursor: pointer; width: 100%;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="font-semibold color-dark">${matiere.name}</span>
                                    ${matiere.code ? `<span class="badge secondary ms-2">${matiere.code}</span>` : ''}
                                </div>
                                <div class="text-muted small">
                                    ${matiere.coefficient ? `Coeff: ${matiere.coefficient}` : ''}
                                    ${matiere.total_heures ? `• ${matiere.total_heures}h` : ''}
                                </div>
                            </div>
                            ${matiere.description ? `<small class="text-muted d-block mt-1">${matiere.description}</small>` : ''}
                        </label>
                    </div>
                `).join('');
            })
            .catch((error) => {
                debugError('Erreur lors du chargement des matières:', error);
                matieresListDiv.innerHTML = `
                    <div class="d-flex align-items-center justify-content-center py-4 text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span>Erreur lors du chargement des matières</span>
                    </div>
                `;
            });
    }

    function updateCombinationsPreview() {
        const combinationsPreview = document.getElementById('combinations-preview');
        if (!combinationsPreview) {
            return;
        }

        const selectedFilieres = Array.from(document.querySelectorAll('.filiere-checkbox:checked')).map((checkbox) => {
            const fallbackLabel = checkbox.closest('label')?.querySelector('.font-semibold')?.textContent?.trim()
                || checkbox.closest('label')?.textContent?.trim()
                || '';
            return {
                id: Number(checkbox.value),
                label: checkbox.dataset.label?.trim() || fallbackLabel
            };
        });

        const selectedNiveaux = Array.from(document.querySelectorAll('.niveau-checkbox:checked')).map((checkbox) => {
            const fallbackLabel = checkbox.closest('label')?.querySelector('.font-semibold')?.textContent?.trim()
                || checkbox.closest('label')?.textContent?.trim()
                || '';
            return {
                id: Number(checkbox.value),
                label: checkbox.dataset.label?.trim() || fallbackLabel
            };
        });

        if (selectedFilieres.length === 0 || selectedNiveaux.length === 0) {
            combinationsPreview.innerHTML = `
                <div class="d-flex align-items-center" style="color: #0369a1;">
                    <i class="fas fa-info-circle me-2"></i>
                    <span>Sélectionnez au moins une filière et un niveau pour voir les combinaisons possibles.</span>
                </div>
            `;
            return;
        }

        let html = `
            <div class="d-flex align-items-center mb-3">
                <i class="fas fa-check-circle me-2" style="color: #059669;"></i>
                <strong style="color: #047857;">${selectedFilieres.length * selectedNiveaux.length} combinaison(s) sélectionnée(s)</strong>
            </div>
            <div class="d-flex flex-wrap gap-2">
        `;

        selectedFilieres.forEach((filiere) => {
            selectedNiveaux.forEach((niveau) => {
                html += `
                    <span class="badge primary">
                        <i class="fas fa-link me-1"></i>
                        ${filiere.label} ↔ ${niveau.label}
                    </span>
                `;
            });
        });

        html += '</div>';
        combinationsPreview.innerHTML = html;

        if (document.getElementById('modal-mode')?.value === 'bulk') {
            const filiereId = selectedFilieres.length === 1 ? selectedFilieres[0].id : null;
            const niveauId = selectedNiveaux.length === 1 ? selectedNiveaux[0].id : null;
            if (filiereId && niveauId) {
                loadAvailableMatieres(filiereId, niveauId);
            }
        }
    }

    document.querySelectorAll('.filiere-checkbox, .niveau-checkbox').forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            updateCombinationsPreview();
        });
    });

    const configureModalElement = document.getElementById('configureModal');
    if (configureModalElement) {
        configureModalElement.addEventListener('hidden.bs.modal', () => {
            document.querySelectorAll('.filiere-checkbox, .niveau-checkbox').forEach((checkbox) => {
                checkbox.checked = false;
            });
            updateCombinationsPreview();
            document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
        });
    }

    const saveLiaisonsBtn = document.getElementById('save-liaisons-btn');
    if (saveLiaisonsBtn) {
        saveLiaisonsBtn.addEventListener('click', () => {
            const mode = document.getElementById('modal-mode')?.value ?? 'single';
            const matiereId = document.getElementById('modal-matiere-id')?.value;
            const selectedFilieres = Array.from(document.querySelectorAll('.filiere-checkbox:checked')).map((checkbox) => Number(checkbox.value));
            const selectedNiveaux = Array.from(document.querySelectorAll('.niveau-checkbox:checked')).map((checkbox) => Number(checkbox.value));

            if (selectedFilieres.length === 0 || selectedNiveaux.length === 0) {
                if (!confirm('Aucune combinaison complète sélectionnée. Voulez-vous tout de même continuer ?')) {
                    return;
                }
            }

            const modalElement = document.getElementById('configureModal');
            const modalInstance = modalElement ? bootstrap.Modal.getInstance(modalElement) : null;

            saveLiaisonsBtn.disabled = true;
            const originalLabel = saveLiaisonsBtn.innerHTML;
            saveLiaisonsBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Enregistrement...';

            if (mode === 'bulk') {
                const selectedIds = JSON.parse(document.getElementById('modal-selected-ids')?.value || '[]');
                fetch('{{ route('esbtp.matieres.add-to-combination') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        matiere_ids: selectedIds,
                        filiere_ids: selectedFilieres,
                        niveau_ids: selectedNiveaux
                    })
                })
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }
                        return response.json();
                    })
                    .then((data) => {
                        if (!data.success) {
                            throw new Error(data.message || 'Erreur serveur');
                        }
                        selectedIds.forEach((id) => refreshMatiereRow(id));
                        clearSelection();
                        modalInstance?.hide();
                        document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
                        document.body.classList.remove('modal-open');
                        document.body.style.removeProperty('padding-right');
                        showToast('success', data.message || 'Liaisons mises à jour avec succès.');
                    })
                    .catch((error) => {
                        debugError('Erreur lors de l\'ajout des matières:', error);
                        showToast('error', 'Impossible d\'ajouter les matières sélectionnées.');
                    })
                    .finally(() => {
                        saveLiaisonsBtn.disabled = false;
                        saveLiaisonsBtn.innerHTML = originalLabel;
                    });
            } else if (matiereId) {
                fetch(`/esbtp/matieres/${matiereId}/update-liaisons`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        filieres: selectedFilieres,
                        niveaux: selectedNiveaux
                    })
                })
                    .then((response) => {
                        if (!response.ok) {
                            return response.json().then((data) => {
                                throw new Error(data.message || `HTTP ${response.status}`);
                            });
                        }
                        return response.json();
                    })
                    .then((data) => {
                        if (!data.success) {
                            throw new Error(data.message || 'Erreur serveur');
                        }
                        refreshMatiereRow(matiereId);
                        modalInstance?.hide();
                        document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
                        document.body.classList.remove('modal-open');
                        document.body.style.removeProperty('padding-right');
                        showToast('success', data.message || 'Liaisons mises à jour avec succès.');
                    })
                    .catch((error) => {
                        debugError('Erreur lors de la mise à jour des liaisons:', error);
                        showToast('error', error.message || 'Impossible de mettre à jour les liaisons.');
                    })
                    .finally(() => {
                        saveLiaisonsBtn.disabled = false;
                        saveLiaisonsBtn.innerHTML = originalLabel;
                    });
            }
        });
    }

    document.addEventListener('click', (event) => {
        const configureBtn = event.target.closest('.configure-matiere-btn');
        if (configureBtn) {
            event.preventDefault();
            const matiereId = Number(configureBtn.dataset.matiereId);
            const matiereName = configureBtn.dataset.matiereName || 'Matière';
            openConfigureModal({
                mode: 'single',
                matiereId,
                matiereName
            });
        }
    });

    updateSummary(JSON.parse(resultsContainer.dataset.summary || '{}'));
    syncHeaderSearchFromForm();
    initTableInteractions();

    window.addEventListener('popstate', () => {
        applyQueryToForm(window.location.href);
        syncHeaderSearchFromForm();
        submitFilterForm(false);
    });
})();
</script>
@endpush
